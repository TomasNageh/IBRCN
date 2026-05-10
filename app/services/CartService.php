<?php

/**
 * FILE: CartService.php
 * PURPOSE: Normalizes session/JS cart payloads and syncs them with `cart_items` via `CartRepository` helpers.
 * USED BY: `public/cart.php`, `public/cart-sync.php`, `public/cart-action.php`.
 * DESIGN PATTERN: None — session orchestration + persistence coordination service.
 */

require_once __DIR__ . '/../patterns/DB.php';
require_once __DIR__ . '/../models/CartRepository.php';

/**
 * CartService
 *
 * Normalizes cart payloads coming from JS and coordinates persistence
 * to the `cart_items` table via `CartRepository`.
 *
 * Session cart item shape used across the app:
 * - book_id, title/name, author, image, unitPrice, price, quantity
 */
class CartService
{
    public function __construct(private PDO $db)
    {
    }

    public function ensureSessionCartInitialized(): void
    {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }
    }

    /**
     * If cart is empty and the DB-backed cart is available, hydrate it into session.
     */
    public function hydrateSessionCartIfEmpty(int $userId, string $role): void
    {
        $this->ensureSessionCartInitialized();

        if ($role !== 'Reader') {
            return;
        }

        if (!CartRepository::hasQuantityColumn($this->db)) {
            return;
        }

        if (count($_SESSION['cart']) !== 0) {
            return;
        }

        $_SESSION['cart'] = (new CartRepository($this->db))->loadSessionCart($userId);
    }

    /**
     * Add a validated item to cart (merge quantity when same book exists).
     *
     * @param array<string,mixed> $item
     */
    public function addToSessionCart(array $item): array
    {
        $this->ensureSessionCartInitialized();

        $bookId = (int) ($item['book_id'] ?? 0);
        $quantity = max(1, (int) ($item['quantity'] ?? 1));

        $cart = $_SESSION['cart'];
        $found = false;
        foreach ($cart as &$existing) {
            if ((int) ($existing['book_id'] ?? 0) === $bookId) {
                $existing['quantity'] = (int) ($existing['quantity'] ?? 1) + $quantity;
                $found = true;
                break;
            }
        }
        unset($existing);

        if (!$found) {
            $cart[] = $item;
        }

        $_SESSION['cart'] = $cart;
        return $cart;
    }

    /**
     * Normalize a single incoming item (from JS payload) to session shape.
     *
     * @param array<string,mixed> $item
     * @return array<string,mixed>|null
     */
    public function normalizeIncomingItemForAdd(array $item): ?array
    {
        $bookId = (int) ($item['book_id'] ?? 0);
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $title = trim((string) ($item['name'] ?? $item['title'] ?? ''));
        $image = trim((string) ($item['image'] ?? $item['cover_image'] ?? ''));
        $unitPrice = (float) ($item['unitPrice'] ?? $item['price_value'] ?? 0);
        $displayPrice = (string) ($item['price'] ?? ('EGP ' . number_format($unitPrice, 2)));
        $author = (string) ($item['author'] ?? '');

        if ($bookId <= 0 || $title === '' || $unitPrice <= 0 || $image === '') {
            return null;
        }

        return array(
            'book_id' => $bookId,
            'title' => $title,
            'name' => $title,
            'author' => $author,
            'image' => $image,
            'unitPrice' => $unitPrice,
            'price' => $displayPrice,
            'quantity' => $quantity,
        );
    }

    /**
     * Normalize an incoming cart array (from localStorage sync payload).
     *
     * @param mixed $incomingCart
     * @return list<array<string,mixed>>
     */
    public function normalizeIncomingCart($incomingCart): array
    {
        if (!is_array($incomingCart)) {
            return array();
        }

        $normalized = array();
        foreach ($incomingCart as $item) {
            if (!is_array($item)) {
                continue;
            }

            $bookId = (int) ($item['book_id'] ?? $item['bookId'] ?? 0);
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $title = trim((string) ($item['title'] ?? $item['name'] ?? ''));
            $image = trim((string) ($item['image'] ?? $item['cover_image'] ?? ''));
            $unitPrice = (float) ($item['unit_price'] ?? $item['unitPrice'] ?? 0);
            $author = (string) ($item['author'] ?? '');

            if ($bookId <= 0 || $title === '' || $image === '') {
                continue;
            }

            $normalized[] = array(
                'book_id' => $bookId,
                'title' => $title,
                'name' => $title,
                'author' => $author,
                'image' => $image,
                'unitPrice' => $unitPrice,
                'price' => 'EGP ' . number_format($unitPrice, 2),
                'quantity' => $quantity,
            );
        }

        return $normalized;
    }

    /**
     * Persist the session cart to DB (best-effort).
     *
     * @param list<array<string,mixed>> $sessionCart
     */
    public function persistSessionCart(int $userId, array $sessionCart): void
    {
        try {
            (new CartRepository($this->db))->replaceFromSession($userId, $sessionCart);
        } catch (Throwable $e) {
            error_log('persistSessionCart: ' . $e->getMessage());
        }
    }
}

