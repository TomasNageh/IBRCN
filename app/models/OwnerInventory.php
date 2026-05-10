<?php

/**
 * FILE: OwnerInventory.php
 * PURPOSE: Owner-facing inventory CRUD, used-book intake, and bulk quantity adjustments scoped via store ownership joins.
 * USED BY: Owner dashboard PHP endpoints (`owner-inventory*.php`, `owner-used-book.php`).
 * DESIGN PATTERN: None — transactional persistence around `stores`, `books`, and `store_inventory` tables.
 */

// Validates owner/store approval gates before mutating listings or merging duplicate ISBN inventory rows for a single store (model).
class OwnerInventory
{
    private PDO $databaseConnection;

    /**
     * Stores PDO supplied by controllers/services constructing owner inventory workflows per authenticated session.
     *
     * @param PDO $databaseConnection Shared singleton-backed connection reused across sequential inventory mutations safely.
     * @return void
     */
    public function __construct(PDO $databaseConnection)
    {
        $this->databaseConnection = $databaseConnection;
    }

    /**
     * Loads the first store row for this owner (lowest `store_id`) as the owner UI assumes one primary store.
     *
     * @param int $ownerId Authenticated owner (`stores.owner_id`).
     * @return array<string, mixed>|null Store row including `status`, or null when no store exists.
     */
    public function getStoreByOwnerId(int $ownerId): ?array
    {
        $statement = $this->databaseConnection->prepare(
            "SELECT store_id, name, status
            FROM stores
            WHERE owner_id = :owner_id
            ORDER BY store_id ASC
            LIMIT 1"
        );
        $statement->execute(array('owner_id' => $ownerId));
        $store = $statement->fetch();

        return $store ?: null;
    }

    /**
     * Creates or merges a used-book listing for an approved store (ISBN resolves/creates `books`, then upserts `store_inventory`).
     *
     * @param int                $ownerId Authenticated owner placing the listing.
     * @param array<string,mixed> $payload Form fields: isbn, title, author, condition, price, quantity (strings accepted like legacy forms).
     * @return array<string, mixed> `{ ok: bool, message: string }` consumed directly by owner views.
     */
    public function createUsedListing(int $ownerId, array $payload): array
    {
        $store = $this->getStoreByOwnerId($ownerId);
        if (!$store) {
            return array('ok' => false, 'message' => 'No bookstore profile found for this owner.');
        }
        if ($store['status'] !== 'Approved') {
            return array('ok' => false, 'message' => 'Store is not approved yet. Please wait for admin approval.');
        }

        $isbn = trim((string) ($payload['isbn'] ?? ''));
        $title = trim((string) ($payload['title'] ?? ''));
        $author = trim((string) ($payload['author'] ?? ''));
        $condition = (string) ($payload['condition'] ?? 'Good');
        $price = (string) ($payload['price'] ?? '');
        $quantity = (string) ($payload['quantity'] ?? '');

        if ($isbn === '' || $title === '' || $author === '' || $price === '' || $quantity === '') {
            return array('ok' => false, 'message' => 'Please fill in all required fields.');
        }

        $allowedConditions = array('Fine', 'Good', 'Fair');
        if (!in_array($condition, $allowedConditions, true)) {
            return array('ok' => false, 'message' => 'Invalid condition selected.');
        }

        $priceValue = (float) $price;
        $quantityValue = (int) $quantity;
        if ($priceValue <= 0) {
            return array('ok' => false, 'message' => 'Price must be greater than 0.');
        }
        if ($quantityValue <= 0) {
            return array('ok' => false, 'message' => 'Quantity must be greater than 0.');
        }

        try {
            $this->databaseConnection->beginTransaction();
            $bookId = $this->findOrCreateBook($isbn, $title, $author);
            $this->upsertInventory((int) $store['store_id'], $bookId, $condition, $priceValue, $quantityValue);
            $this->databaseConnection->commit();
        } catch (Throwable $exception) {
            if ($this->databaseConnection->inTransaction()) {
                $this->databaseConnection->rollBack();
            }

            return array('ok' => false, 'message' => 'Could not save listing. Please try again.');
        }

        return array('ok' => true, 'message' => 'Used book listing saved successfully.');
    }

    /**
     * Finds an existing book by ISBN or inserts a new catalog row before inventory attachment.
     *
     * @param string $isbn   Owner-entered ISBN text stored in `books.isbn`.
     * @param string $title  Title for newly inserted rows only.
     * @param string $author Author for newly inserted rows only.
     * @return int `books.book_id` used by inventory upsert logic.
     */
    private function findOrCreateBook(string $isbn, string $title, string $author): int
    {
        $findStatement = $this->databaseConnection->prepare('SELECT book_id FROM books WHERE isbn = :isbn LIMIT 1');
        $findStatement->execute(array('isbn' => $isbn));
        $book = $findStatement->fetch();
        if ($book) {
            return (int) $book['book_id'];
        }

        $insertStatement = $this->databaseConnection->prepare(
            "INSERT INTO books (isbn, title, author, status)
            VALUES (:isbn, :title, :author, 'Available')"
        );
        $insertStatement->execute(array(
            'isbn' => $isbn,
            'title' => $title,
            'author' => $author,
        ));

        return (int) $this->databaseConnection->lastInsertId();
    }

    /**
     * Inserts inventory or merges quantities/pricing into the existing UNIQUE(store_id, book_id) row.
     *
     * @param int    $storeId    Target store receiving stock.
     * @param int    $bookId     Catalog id from `findOrCreateBook`.
     * @param string $condition  Allowed condition label enforced earlier by `createUsedListing`.
     * @param float  $price      Unit price for insert/update paths.
     * @param int    $quantity   Quantity to add on merge, or baseline qty on insert.
     * @return void
     */
    private function upsertInventory(int $storeId, int $bookId, string $condition, float $price, int $quantity): void
    {
        // Schema: UNIQUE (store_id, book_id) — one inventory row per book per store; condition is on that row.
        $findStatement = $this->databaseConnection->prepare(
            "SELECT inventory_id
            FROM store_inventory
            WHERE store_id = :store_id AND book_id = :book_id
            LIMIT 1"
        );
        $findStatement->execute(array(
            'store_id' => $storeId,
            'book_id' => $bookId,
        ));
        $inventory = $findStatement->fetch();

        if ($inventory) {
            $updateStatement = $this->databaseConnection->prepare(
                "UPDATE store_inventory
                SET `condition` = :condition, price = :price, quantity = quantity + :add_qty
                WHERE inventory_id = :inventory_id"
            );
            $updateStatement->execute(array(
                'condition' => $condition,
                'price' => $price,
                'add_qty' => $quantity,
                'inventory_id' => (int) $inventory['inventory_id'],
            ));

            return;
        }

        $insertStatement = $this->databaseConnection->prepare(
            "INSERT INTO store_inventory (store_id, book_id, `condition`, price, quantity)
            VALUES (:store_id, :book_id, :condition, :price, :quantity)"
        );
        $insertStatement->execute(array(
            'store_id' => $storeId,
            'book_id' => $bookId,
            'condition' => $condition,
            'price' => $price,
            'quantity' => $quantity,
        ));
    }

    /**
     * Lists all inventory lines for an owner’s store(s), sorted by title, with formatted EGP price helper fields for templates.
     *
     * @param int $ownerId Owner user id (`stores.owner_id`).
     * @return list<array<string,mixed>> Rows including `price_egp_formatted` for legacy UI formatting.
     */
    public function listInventoryForOwner(int $ownerId): array
    {
        $statement = $this->databaseConnection->prepare(
            "SELECT i.inventory_id, i.`condition`, i.price, i.quantity, i.hold_quantity,
                    b.book_id, b.isbn, b.title, b.author, b.cover_image
            FROM stores s
            INNER JOIN store_inventory i ON i.store_id = s.store_id
            INNER JOIN books b ON b.book_id = i.book_id
            WHERE s.owner_id = :owner_id
            ORDER BY b.title ASC, i.inventory_id ASC"
        );
        $statement->execute(array('owner_id' => $ownerId));
        $rows = $statement->fetchAll();
        foreach ($rows as &$row) {
            $row['price_egp_formatted'] = 'EGP ' . number_format((float) $row['price'], 2);
        }
        unset($row);

        return $rows;
    }

    /**
     * Loads one inventory row for editing when it belongs to this owner’s store.
     *
     * @param int $ownerId     Owner user id.
     * @param int $inventoryId Inventory row id from owner UI.
     * @return array<string, mixed>|null Joined inventory+book row or null when not found / unauthorized by SQL joins.
     */
    public function getInventoryRowForOwner(int $ownerId, int $inventoryId): ?array
    {
        $statement = $this->databaseConnection->prepare(
            "SELECT i.inventory_id, i.store_id, i.book_id, i.`condition`, i.price, i.quantity, i.hold_quantity,
                    b.isbn, b.title, b.author, b.cover_image
            FROM stores s
            INNER JOIN store_inventory i ON i.store_id = s.store_id
            INNER JOIN books b ON b.book_id = i.book_id
            WHERE s.owner_id = :owner_id AND i.inventory_id = :inventory_id
            LIMIT 1"
        );
        $statement->execute(array('owner_id' => $ownerId, 'inventory_id' => $inventoryId));
        $row = $statement->fetch();

        return $row ?: null;
    }

    /**
     * Updates condition/price/qty for a listing after validating store approval and enumerated condition values.
     *
     * @param int                $ownerId     Owner performing the edit.
     * @param int                $inventoryId Target inventory row id.
     * @param array<string,mixed> $payload     Fields: condition, price, quantity (same semantics as legacy POST handling).
     * @return array<string, mixed> `{ ok, message }` tuple for owner UI flash messaging.
     */
    public function updateInventoryRow(int $ownerId, int $inventoryId, array $payload): array
    {
        $row = $this->getInventoryRowForOwner($ownerId, $inventoryId);
        if (!$row) {
            return array('ok' => false, 'message' => 'Listing not found.');
        }

        $store = $this->getStoreByOwnerId($ownerId);
        if (!$store || $store['status'] !== 'Approved') {
            return array('ok' => false, 'message' => 'Your store cannot edit listings yet.');
        }

        $condition = (string) ($payload['condition'] ?? 'Good');
        $allowedConditions = array('New', 'Fine', 'Good', 'Fair');
        if (!in_array($condition, $allowedConditions, true)) {
            return array('ok' => false, 'message' => 'Invalid condition.');
        }

        $priceValue = (float) ($payload['price'] ?? 0);
        $quantityValue = (int) ($payload['quantity'] ?? 0);
        if ($priceValue <= 0) {
            return array('ok' => false, 'message' => 'Price must be greater than 0.');
        }
        if ($quantityValue < 0) {
            return array('ok' => false, 'message' => 'Quantity cannot be negative.');
        }

        try {
            $statement = $this->databaseConnection->prepare(
                "UPDATE store_inventory i
                INNER JOIN stores s ON s.store_id = i.store_id
                SET i.`condition` = :condition, i.price = :price, i.quantity = :quantity
                WHERE s.owner_id = :owner_id AND i.inventory_id = :inventory_id"
            );
            $statement->execute(array(
                'condition' => $condition,
                'price' => $priceValue,
                'quantity' => $quantityValue,
                'owner_id' => $ownerId,
                'inventory_id' => $inventoryId,
            ));
        } catch (Throwable $exception) {
            return array('ok' => false, 'message' => 'Could not update listing.');
        }

        return array('ok' => true, 'message' => 'Listing updated.');
    }

    /**
     * Deletes a listing or zeroes quantity when historical `order_items` reference the inventory row (preserves referential reporting).
     *
     * @param int $ownerId     Owner requesting removal.
     * @param int $inventoryId Inventory row targeted by owner UI actions.
     * @return array<string, mixed> `{ ok, message }` explaining soft-delete vs hard-delete outcomes matching legacy strings.
     */
    public function removeInventoryRow(int $ownerId, int $inventoryId): array
    {
        $row = $this->getInventoryRowForOwner($ownerId, $inventoryId);
        if (!$row) {
            return array('ok' => false, 'message' => 'Listing not found.');
        }

        try {
            $countStatement = $this->databaseConnection->prepare(
                "SELECT COUNT(*) AS c FROM order_items WHERE inventory_id = :id"
            );
            $countStatement->execute(array('id' => $inventoryId));
            $orderItemCount = (int) ($countStatement->fetch()['c'] ?? 0);

            if ($orderItemCount > 0) {
                $statement = $this->databaseConnection->prepare(
                    "UPDATE store_inventory i
                    INNER JOIN stores s ON s.store_id = i.store_id
                    SET i.quantity = 0
                    WHERE s.owner_id = :owner_id AND i.inventory_id = :inventory_id"
                );
                $statement->execute(array('owner_id' => $ownerId, 'inventory_id' => $inventoryId));

                return array('ok' => true, 'message' => 'This title was on past orders — quantity set to 0.');
            }

            $deleteStatement = $this->databaseConnection->prepare(
                "DELETE i FROM store_inventory i
                INNER JOIN stores s ON s.store_id = i.store_id
                WHERE s.owner_id = :owner_id AND i.inventory_id = :inventory_id"
            );
            $deleteStatement->execute(array('owner_id' => $ownerId, 'inventory_id' => $inventoryId));
        } catch (Throwable $exception) {
            return array('ok' => false, 'message' => 'Could not remove listing.');
        }

        return array('ok' => true, 'message' => 'Listing removed.');
    }

    /**
     * Adds a fixed quantity to every inventory row owned by this owner (bulk “level up” helper).
     *
     * @param int $ownerId   Owner user id.
     * @param int $increment Positive integer applied to each inventory quantity column via joined UPDATE.
     * @return array<string, mixed> `{ ok, message }` tuple matching legacy responses.
     */
    public function levelUpAllInventory(int $ownerId, int $increment = 1): array
    {
        if ($increment <= 0) {
            return array('ok' => false, 'message' => 'Increment must be greater than 0.');
        }

        try {
            $statement = $this->databaseConnection->prepare(
                "UPDATE store_inventory i
                INNER JOIN stores s ON s.store_id = i.store_id
                SET i.quantity = i.quantity + :increment
                WHERE s.owner_id = :owner_id"
            );
            $statement->execute(array(
                'increment' => $increment,
                'owner_id' => $ownerId,
            ));

            if ($statement->rowCount() === 0) {
                return array('ok' => false, 'message' => 'No inventory items found to level up.');
            }
        } catch (Throwable $exception) {
            return array('ok' => false, 'message' => 'Could not level up inventory.');
        }

        return array('ok' => true, 'message' => 'All inventory books were leveled up by +' . $increment . '.');
    }
}
