<?php

/**
 * FILE: Order.php
 * PURPOSE: Checkout transactions, reader/owner order listings, pickup-ready transitions, and inventory reservation helpers.
 * USED BY: `public/cart.php`, `public/orders.php`, owner dashboards, `NotificationService`, `OwnerOrdersService`.
 * DESIGN PATTERN: None — coordinates prepared statements with injected `Book` model for stock discovery.
 */

require_once __DIR__ . '/Book.php';

// Encapsulates SQL for orders/order_items plus inventory hold bookkeeping tied to multi-store checkout grouping (model).
class Order
{
    private PDO $databaseConnection;

    private Book $bookModel;

    /**
     * Captures PDO plus catalog helper used to locate feasible inventory rows during checkout normalization.
     *
     * @param PDO  $databaseConnection Shared PDO from `DB::getInstance()` for transactional order placement.
     * @param Book $bookModel          Catalog/inventory gateway reused when resolving cheapest stocked SKU rows.
     * @return void
     */
    public function __construct(PDO $databaseConnection, Book $bookModel)
    {
        $this->databaseConnection = $databaseConnection;
        $this->bookModel = $bookModel;
    }

    /**
     * Groups mixed cart lines per store, reserves inventory holds, inserts headers/items, returns structured summaries for notifications.
     *
     * @param int                   $userId     Authenticated reader placing pickup orders across one or more stores.
     * @param array<int, mixed>     $cartItems  Session cart compatible rows carrying book_id + quantity keys minimally.
     * @return array<string, mixed> Shape includes ok/message plus orders array on success mirroring legacy consumers.
     */
    public function checkoutCart(int $userId, array $cartItems): array
    {
        $normalizedItems = $this->normalizeCartItems($cartItems);
        if ($normalizedItems === array()) {
            return array('ok' => false, 'message' => 'Your cart is empty.');
        }

        $groupedByStore = array();

        foreach ($normalizedItems as $item) {
            $book = $this->bookModel->getBookById($item['book_id']);
            if (!$book) {
                return array('ok' => false, 'message' => 'One of the selected books could not be found.');
            }

            $resolved = $this->resolveInventoryForItem($item['book_id'], $item['quantity']);
            if ($resolved === null) {
                return array('ok' => false, 'message' => 'Some items are no longer available in sufficient quantity.');
            }

            $storeId = (int) $resolved['store_id'];
            if (!isset($groupedByStore[$storeId])) {
                $groupedByStore[$storeId] = array(
                    'store_id' => $storeId,
                    'store_name' => (string) $resolved['name'],
                    'items' => array(),
                    'total' => 0.0,
                );
            }

            $groupedByStore[$storeId]['items'][] = array(
                'book_id' => (int) $book['book_id'],
                'inventory_id' => (int) $resolved['inventory_id'],
                'title' => (string) $book['title'],
                'author' => (string) ($book['author'] ?? ''),
                'quantity' => $item['quantity'],
                'unit_price' => (float) $resolved['price'],
            );
            $groupedByStore[$storeId]['total'] += ((float) $resolved['price']) * $item['quantity'];
        }

        try {
            $this->databaseConnection->beginTransaction();
            $createdOrders = array();

            foreach ($groupedByStore as $storeGroup) {
                $orderId = $this->createOrderHeader($userId, (int) $storeGroup['store_id'], (float) $storeGroup['total']);

                foreach ($storeGroup['items'] as $line) {
                    $this->reserveInventory((int) $line['inventory_id'], (int) $line['quantity']);
                    $this->createOrderItem($orderId, $line);
                }

                $createdOrders[] = array(
                    'order_id' => $orderId,
                    'store_id' => (int) $storeGroup['store_id'],
                    'store_name' => $storeGroup['store_name'],
                    'total_amount' => (float) $storeGroup['total'],
                );
            }

            $this->databaseConnection->commit();

            return array(
                'ok' => true,
                'message' => 'Checkout complete. Your order has been placed.',
                'orders' => $createdOrders,
            );
        } catch (Throwable $exception) {
            if ($this->databaseConnection->inTransaction()) {
                $this->databaseConnection->rollBack();
            }

            return array('ok' => false, 'message' => 'Could not complete checkout. Please try again.');
        }
    }

    /**
     * Lists reader-facing order summaries joined with store metadata for timeline views.
     *
     * @param int $userId Reader account key filtering `orders.reader_id`.
     * @return list<array<string, mixed>> Aggregated rows grouped per order id with counts for UI badges.
     */
    public function getReaderOrders(int $userId): array
    {
        $statement = $this->databaseConnection->prepare(
            "SELECT o.order_id, o.status, o.total_amount, o.created_at,
                    s.name AS store_name, s.region,
                    COUNT(oi.order_item_id) AS item_count
            FROM orders o
            INNER JOIN stores s ON s.store_id = o.store_id
            INNER JOIN order_items oi ON oi.order_id = o.order_id
            WHERE o.reader_id = :reader_id
            GROUP BY o.order_id, o.status, o.total_amount, o.created_at, s.name, s.region
            ORDER BY o.created_at DESC, o.order_id DESC"
        );
        $statement->execute(array('reader_id' => $userId));

        return $statement->fetchAll();
    }

    /**
     * Flattened line-item listing across reader orders for tables needing per-title quantities/prices.
     *
     * @param int $userId Reader key reused identical to `getReaderOrders` filtering semantics.
     * @return list<array<string, mixed>> Rows ordered newest-first preserving deterministic item ordering keys historically relied upon.
     */
    public function getReaderOrderItems(int $userId): array
    {
        $statement = $this->databaseConnection->prepare(
            "SELECT o.order_id, oi.quantity, oi.price,
                    s.name AS store_name, o.status
            FROM orders o
            INNER JOIN order_items oi ON oi.order_id = o.order_id
            INNER JOIN stores s ON s.store_id = o.store_id
            WHERE o.reader_id = :reader_id
            ORDER BY o.created_at DESC, o.order_id DESC, oi.order_item_id ASC"
        );
        $statement->execute(array('reader_id' => $userId));

        return $statement->fetchAll();
    }

    /**
     * Finalizes pickup by decrementing inventory quantities/holds when prior status was Ready and ownership checks pass.
     *
     * @param int $userId  Reader confirming collection at storefront counter flows.
     * @param int $orderId Order identifier validated against reader ownership constraints.
     * @return array<string, mixed> Status payload consumed by AJAX/flash messaging unchanged historically (`ok`/`message`).
     */
    public function markCollected(int $userId, int $orderId): array
    {
        try {
            $this->databaseConnection->beginTransaction();
            $order = $this->loadOrderForReader($userId, $orderId);
            if (!$order) {
                if ($this->databaseConnection->inTransaction()) {
                    $this->databaseConnection->rollBack();
                }

                return array('ok' => false, 'message' => 'Order not found.');
            }

            if ($order['status'] !== 'Ready') {
                if ($this->databaseConnection->inTransaction()) {
                    $this->databaseConnection->rollBack();
                }

                return array('ok' => false, 'message' => 'Only ready orders can be collected.');
            }

            $items = $this->loadOrderItems($orderId);
            foreach ($items as $item) {
                $statement = $this->databaseConnection->prepare(
                    "UPDATE store_inventory
                    SET quantity = quantity - :qty,
                        hold_quantity = hold_quantity - :qty
                    WHERE inventory_id = :inventory_id
                    AND quantity >= :qty
                    AND hold_quantity >= :qty"
                );
                $statement->execute(array(
                    'qty' => (int) $item['quantity'],
                    'inventory_id' => (int) $item['inventory_id'],
                ));

                if ($statement->rowCount() === 0) {
                    throw new RuntimeException('Inventory no longer available for collection.');
                }
            }

            $update = $this->databaseConnection->prepare(
                "UPDATE orders SET status = 'Collected' WHERE order_id = :order_id"
            );
            $update->execute(array('order_id' => $orderId));
            $this->databaseConnection->commit();

            return array('ok' => true, 'message' => 'Order marked as collected.');
        } catch (Throwable $exception) {
            if ($this->databaseConnection->inTransaction()) {
                $this->databaseConnection->rollBack();
            }

            return array('ok' => false, 'message' => 'Could not mark order as collected.');
        }
    }

    /**
     * Owner dashboard listing across stores they operate with reader names embedded for fulfillment UX tables.
     *
     * @param int $ownerId Store owner foreign key constraining joined `stores.owner_id` rows.
     * @return list<array<string, mixed>> Aggregated orders grouped like reader variant but scoped to owning storefront portfolio.
     */
    public function getOwnerOrders(int $ownerId): array
    {
        $statement = $this->databaseConnection->prepare(
            "SELECT o.order_id, o.status, o.total_amount, o.created_at,
                    s.name AS store_name, s.region,
                    u.username AS reader_name,
                    COUNT(oi.order_item_id) AS item_count
            FROM orders o
            INNER JOIN stores s ON s.store_id = o.store_id
            INNER JOIN users u ON u.user_id = o.reader_id
            INNER JOIN order_items oi ON oi.order_id = o.order_id
            WHERE s.owner_id = :owner_id
            GROUP BY o.order_id, o.status, o.total_amount, o.created_at, s.name, s.region, u.username
            ORDER BY o.created_at DESC, o.order_id DESC"
        );
        $statement->execute(array('owner_id' => $ownerId));

        return $statement->fetchAll();
    }

    /**
     * Flattened order lines for owner-facing UI mirroring reader counterpart but constrained via owner id joins.
     *
     * @param int $ownerId Owner user key reused across fulfillment dashboards for ACL enforcement at SQL layer.
     * @return list<array<string, mixed>> Deterministic ordering identical to reader variant aside from ownership WHERE clause.
     */
    public function getOwnerOrderItems(int $ownerId): array
    {
        $statement = $this->databaseConnection->prepare(
            "SELECT o.order_id, oi.quantity, oi.price,
                    s.name AS store_name, o.status
            FROM orders o
            INNER JOIN stores s ON s.store_id = o.store_id
            INNER JOIN order_items oi ON oi.order_id = o.order_id
            WHERE s.owner_id = :owner_id
            ORDER BY o.created_at DESC, o.order_id DESC, oi.order_item_id ASC"
        );
        $statement->execute(array('owner_id' => $ownerId));

        return $statement->fetchAll();
    }

    /**
     * Retrieves owner contact info + human-readable store names for outbound notifications keyed by store id alone.
     *
     * @param int $storeId Store dimension referenced during checkout notification fan-out loops per order slice.
     * @return array<string, mixed>|null Associative row or null when store missing (guards upstream loops quietly).
     */
    public function getStoreOwnerContact(int $storeId): ?array
    {
        $statement = $this->databaseConnection->prepare(
            "SELECT s.store_id, s.name AS store_name, u.user_id, u.username, u.email
            FROM stores s
            INNER JOIN users u ON u.user_id = s.owner_id
            WHERE s.store_id = :store_id
            LIMIT 1"
        );
        $statement->execute(array('store_id' => $storeId));
        $row = $statement->fetch();

        return $row ?: null;
    }

    /**
     * Reader + store context for pickup-ready email (UC-02) reused by NotificationService after readiness transitions.
     *
     * @param int $ownerId Authenticated owner performing readiness mutation for ACL enforcement.
     * @param int $orderId Target order being transitioned into Ready state downstream.
     * @return array<string, mixed>|null Context row packaged for templated emails/in-app observers when ownership validated.
     */
    public function getPickupNotifyContext(int $ownerId, int $orderId): ?array
    {
        $statement = $this->databaseConnection->prepare(
            "SELECT o.order_id, o.reader_id, u.email AS reader_email, u.username AS reader_name, s.name AS store_name
            FROM orders o
            INNER JOIN stores s ON s.store_id = o.store_id
            INNER JOIN users u ON u.user_id = o.reader_id
            WHERE o.order_id = :order_id AND s.owner_id = :owner_id
            LIMIT 1"
        );
        $statement->execute(array('order_id' => $orderId, 'owner_id' => $ownerId));
        $row = $statement->fetch();

        return $row ?: null;
    }

    /**
     * Moves eligible orders from Placed → Ready when owner id matches store ownership and status precondition satisfied.
     *
     * @param int $ownerId Subject owner performing readiness acknowledgement inside fulfillment UI flows.
     * @param int $orderId Order targeted for readiness transition guarded against cross-owner tampering via joins.
     * @return array<string, mixed> Response tuple `{ok,message}` consumed unchanged by AJAX endpoints historically.
     */
    public function markReady(int $ownerId, int $orderId): array
    {
        $statement = $this->databaseConnection->prepare(
            "UPDATE orders o
            INNER JOIN stores s ON s.store_id = o.store_id
                        SET o.status = 'Ready'
            WHERE o.order_id = :order_id
            AND s.owner_id = :owner_id
            AND o.status = 'Placed'"
        );
        $statement->execute(array('order_id' => $orderId, 'owner_id' => $ownerId));

        if ($statement->rowCount() === 0) {
            return array('ok' => false, 'message' => 'Order could not be marked ready.');
        }

        return array('ok' => true, 'message' => 'Order marked as ready.');
    }

    /**
     * Collapses duplicate book ids inside incoming cart arrays summing quantities safely for transactional grouping logic.
     *
     * @param array<int, mixed> $cartItems Raw session cart lines potentially containing duplicates or invalid quantities.
     * @return list<array<string, int>> Normalized list of associative rows each containing book_id + positive quantity totals.
     */
    private function normalizeCartItems(array $cartItems): array
    {
        $normalized = array();

        foreach ($cartItems as $item) {
            $bookId = (int) ($item['book_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 1);
            if ($bookId <= 0 || $quantity <= 0) {
                continue;
            }

            if (!isset($normalized[$bookId])) {
                $normalized[$bookId] = array('book_id' => $bookId, 'quantity' => 0);
            }

            $normalized[$bookId]['quantity'] += $quantity;
        }

        return array_values($normalized);
    }

    /**
     * Chooses first inventory row returned by Book stock discovery satisfying requested quantity availability thresholds.
     *
     * @param int $bookId    Catalog item needing fulfillment resolution prior to transaction commits.
     * @param int $quantity  Aggregated quantity demanded for this title within checkout grouping batching logic.
     * @return array<string, mixed>|null Store/inventory row shaped like `findStoresWithStockForBook` entries or null if insufficient stock.
     */
    private function resolveInventoryForItem(int $bookId, int $quantity): ?array
    {
        $stores = $this->bookModel->findStoresWithStockForBook($bookId);
        foreach ($stores as $store) {
            if ((int) $store['available_to_buy'] >= $quantity) {
                return $store;
            }
        }

        return null;
    }

    /**
     * Inserts order header snapshot carrying monetary totals + initial status values identical to legacy checkout semantics.
     *
     * @param int   $userId      Reader placing combined orders tied to `orders.reader_id` foreign key definitions.
     * @param int   $storeId     Fulfillment location grouping subsequent line items during transactional inserts.
     * @param float $totalAmount Monetary decimal aggregated pre-insert for notification template parity across channels.
     * @return int New `orders.order_id` surrogate key generated via auto-increment within successful transactions only.
     */
    private function createOrderHeader(int $userId, int $storeId, float $totalAmount): int
    {
        $statement = $this->databaseConnection->prepare(
            "INSERT INTO orders (reader_id, store_id, status, total_amount, platform_fee, net_revenue, tax_amount, created_at)
            VALUES (:reader_id, :store_id, 'Placed', :total_amount, 0.00, :total_amount, 0.00, NOW())"
        );
        $statement->execute(array(
            'reader_id' => $userId,
            'store_id' => $storeId,
            'total_amount' => $totalAmount,
        ));

        return (int) $this->databaseConnection->lastInsertId();
    }

    /**
     * Increments `hold_quantity` when sufficient net quantity remains to satisfy transactional reservation guarantees.
     *
     * @param int $inventoryId Target inventory surrogate referencing row-level locking assumptions inside checkout transactions.
     * @param int $quantity    Units to reserve simultaneously prior to `order_items` insertion steps completing successfully.
     * @return void
     */
    private function reserveInventory(int $inventoryId, int $quantity): void
    {
        $statement = $this->databaseConnection->prepare(
            "UPDATE store_inventory
            SET hold_quantity = hold_quantity + :qty
            WHERE inventory_id = :inventory_id
            AND quantity - hold_quantity >= :qty"
        );
        $statement->execute(array('qty' => $quantity, 'inventory_id' => $inventoryId));

        if ($statement->rowCount() === 0) {
            throw new RuntimeException('Could not reserve inventory.');
        }
    }

    /**
     * Persists `order_items` row tying header to inventory line + frozen unit price captured during checkout instant.
     *
     * @param int               $orderId Parent order header identifier produced moments earlier inside same transaction scope.
     * @param array<string,mixed> $line   Associative shape containing inventory_id/unit_price/title metadata historically logged elsewhere.
     * @return void
     */
    private function createOrderItem(int $orderId, array $line): void
    {
        $statement = $this->databaseConnection->prepare(
            "INSERT INTO order_items (order_id, inventory_id, price)
            VALUES (:order_id, :inventory_id, :price)"
        );
        $statement->execute(array(
            'order_id' => $orderId,
            'inventory_id' => (int) $line['inventory_id'],
            'price' => (float) $line['unit_price'],
        ));
    }

    /**
     * Loads minimal order tuple verifying reader ownership prior to collection mutations affecting inventory tables.
     *
     * @param int $userId  Reader candidate requesting destructive inventory adjustments via pickup confirmation flows.
     * @param int $orderId Order targeted for verification joins tying reader/store/inventory relationships tightly.
     * @return array<string, mixed>|null Row subset containing status when match located; otherwise null triggering UX errors.
     */
    private function loadOrderForReader(int $userId, int $orderId): ?array
    {
        $statement = $this->databaseConnection->prepare(
            "SELECT order_id, reader_id, status, store_id FROM orders WHERE order_id = :order_id AND reader_id = :reader_id LIMIT 1"
        );
        $statement->execute(array('order_id' => $orderId, 'reader_id' => $userId));
        $row = $statement->fetch();

        return $row ?: null;
    }

    /**
     * Retrieves inventory ids + quantities recorded on order for iterative decrement loops during collection handshake steps.
     *
     * @param int $orderId Parent order whose line items must align with inventory reductions atomically inside transactions.
     * @return list<array<string, mixed>> Rows enumerating inventory_id/quantity pairs duplicated from persisted checkout captures only.
     */
    private function loadOrderItems(int $orderId): array
    {
        $statement = $this->databaseConnection->prepare(
            "SELECT inventory_id, quantity FROM order_items WHERE order_id = :order_id"
        );
        $statement->execute(array('order_id' => $orderId));

        return $statement->fetchAll();
    }
}
