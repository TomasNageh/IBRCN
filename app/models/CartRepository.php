<?php

/**
 * FILE: CartRepository.php
 * PURPOSE: Syncs session-shaped cart payloads with `cart_items` rows for authenticated readers (requires `quantity` column).
 * USED BY: `CartService`, `AuthController::hydrateCartForReader`, checkout persistence helpers.
 * DESIGN PATTERN: None — coordinates INSERT/DELETE with inventory resolution via `Book` model lookups.
 */

require_once __DIR__ . '/Book.php';

// Bridges PHP session cart arrays and durable `cart_items` storage including cheapest-in-stock inventory resolution logic (repository).
class CartRepository
{
    /**
     * Stores PDO handle injected by services/controllers operating inside reader cart workflows.
     *
     * @param PDO $databaseConnection Shared connection for transactional cart synchronization routines.
     * @return void
     */
    public function __construct(private PDO $databaseConnection)
    {
    }

    /**
     * Detects whether legacy schema migrations added `cart_items.quantity` so callers skip incompatible SQL safely.
     *
     * @param PDO $databaseConnection Connection used for SHOW COLUMNS introspection (same singleton typically).
     * @return bool True when quantity column exists enabling multi-qty rows per inventory line.
     */
    public static function hasQuantityColumn(PDO $databaseConnection): bool
    {
        try {
            $rows = $databaseConnection->query("SHOW COLUMNS FROM cart_items LIKE 'quantity'")->fetchAll();

            return count($rows) > 0;
        } catch (Throwable $exception) {
            return false;
        }
    }

    /**
     * Picks one inventory row for checkout alignment (cheapest listed price among approved stores with stock).
     *
     * @param int $bookId Catalog identifier referenced through session cart line items.
     * @return int|null Inventory primary key when stock exists; null when unavailable across approved stores.
     */
    public function resolveInventoryIdForBook(int $bookId): ?int
    {
        if ($bookId <= 0) {
            return null;
        }
        $bookModel = new Book($this->databaseConnection);
        $stores = $bookModel->findStoresWithStockForBook($bookId);
        if ($stores === array()) {
            return null;
        }
        $firstStoreRow = $stores[0];

        return isset($firstStoreRow['inventory_id']) ? (int) $firstStoreRow['inventory_id'] : null;
    }

    /**
     * Replace DB cart from session structure used by cart.php / JS (aggregates quantities per book before insert).
     *
     * @param int                      $readerId    Authenticated reader key owning durable cart rows.
     * @param list<array<string,mixed>> $sessionCart Session cart item list carrying book_id + quantity keys minimally.
     * @return void
     */
    public function replaceFromSession(int $readerId, array $sessionCart): void
    {
        if ($readerId <= 0 || !self::hasQuantityColumn($this->databaseConnection)) {
            return;
        }

        $deleteStatement = $this->databaseConnection->prepare('DELETE FROM cart_items WHERE reader_id = :rid');
        $deleteStatement->execute(array('rid' => $readerId));

        $insertStatement = $this->databaseConnection->prepare(
            'INSERT INTO cart_items (reader_id, inventory_id, quantity) VALUES (:rid, :iid, :qty)'
        );

        $quantitiesByBook = array();
        foreach ($sessionCart as $item) {
            $bookId = (int) ($item['book_id'] ?? 0);
            if ($bookId <= 0) {
                continue;
            }
            $quantitiesByBook[$bookId] = ($quantitiesByBook[$bookId] ?? 0) + max(1, (int) ($item['quantity'] ?? 1));
        }

        foreach ($quantitiesByBook as $bookId => $quantity) {
            $inventoryId = $this->resolveInventoryIdForBook($bookId);
            if ($inventoryId === null) {
                continue;
            }
            try {
                $insertStatement->execute(array('rid' => $readerId, 'iid' => $inventoryId, 'qty' => $quantity));
            } catch (Throwable $exception) {
                error_log('cart_items insert: ' . $exception->getMessage());
            }
        }
    }

    /**
     * Build session cart array from DB (for display / checkout) preserving legacy keys (`unitPrice`, `price` string, etc.).
     *
     * @param int $readerId Reader user id whose persisted rows should hydrate `$_SESSION['cart']` clones.
     * @return list<array<string,mixed>> Deterministic structure consumed by cart.php templating layer unchanged historically.
     */
    public function loadSessionCart(int $readerId): array
    {
        if ($readerId <= 0 || !self::hasQuantityColumn($this->databaseConnection)) {
            return array();
        }

        $statement = $this->databaseConnection->prepare(
            'SELECT c.inventory_id, c.quantity,
                    b.book_id, b.title, b.author, b.cover_image,
                    i.price
             FROM cart_items c
             INNER JOIN store_inventory i ON i.inventory_id = c.inventory_id
             INNER JOIN books b ON b.book_id = i.book_id
             WHERE c.reader_id = :rid'
        );
        $statement->execute(array('rid' => $readerId));
        $rows = $statement->fetchAll();

        $sessionLines = array();
        foreach ($rows as $row) {
            $unitPrice = (float) $row['price'];
            $sessionLines[] = array(
                'book_id' => (int) $row['book_id'],
                'title' => (string) $row['title'],
                'name' => (string) $row['title'],
                'author' => (string) ($row['author'] ?? ''),
                'image' => (string) ($row['cover_image'] ?? ''),
                'unitPrice' => $unitPrice,
                'price' => 'EGP ' . number_format($unitPrice, 2),
                'quantity' => max(1, (int) $row['quantity']),
                'inventory_id' => (int) $row['inventory_id'],
            );
        }

        return $sessionLines;
    }

    /**
     * Deletes every durable cart row for reader (post-checkout or explicit empty-cart flows).
     *
     * @param int $readerId Reader user id whose rows should be cleared when quantity schema supported.
     * @return void
     */
    public function clear(int $readerId): void
    {
        if ($readerId <= 0 || !self::hasQuantityColumn($this->databaseConnection)) {
            return;
        }
        $statement = $this->databaseConnection->prepare('DELETE FROM cart_items WHERE reader_id = :rid');
        $statement->execute(array('rid' => $readerId));
    }
}
