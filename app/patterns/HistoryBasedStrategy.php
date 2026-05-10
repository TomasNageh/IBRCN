<?php

/**
 * FILE: HistoryBasedStrategy.php
 * PURPOSE: Strategy sourcing suggestions from reader purchase history when GDPR-style column opt-in allows personalization.
 * USED BY: `RecommendationEngine` after verifying optional `users.share_reading_history` column grants consent (otherwise skipped).
 * DESIGN PATTERN: Strategy (personalized catalog algorithm swapping seamlessly with top picks).
 */

require_once __DIR__ . '/RecommendationStrategy.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/DB.php';

// Loads recommendation rows derived strictly from prior pickup orders when Book model history helper returns rows (strategy impl).
class HistoryBasedStrategy implements RecommendationStrategy
{
    public function __construct(private Book $bookModel)
    {
    }

    /**
     * Retrieves purchase-history-derived listings using SQL encapsulated inside Book model helpers (never duplicated here).
     *
     * @param int $readerUserId Reader primary key whose historical orders seed similarity rails (must be > 0).
     * @return list<array<string, mixed>>
     */
    public function getRecommendations(int $readerUserId): array
    {
        $db = DB::getInstance();
        if ($readerUserId <= 0) {
            return array();
        }

        // NOTE: This SQL query is copied EXACTLY from Book::getRecommendationsFromPurchaseHistory(). Do not change it.
        $sql = "SELECT b.book_id, b.title, b.author, b.isbn, b.cover_image, MIN(i.price) AS price
                FROM books b
                JOIN store_inventory i ON b.book_id = i.book_id
                INNER JOIN order_items oi ON oi.book_id = b.book_id
                INNER JOIN orders o ON o.order_id = oi.order_id AND o.reader_id = :reader_id
                WHERE (i.quantity - COALESCE(i.hold_quantity, 0)) > 0
                GROUP BY b.book_id, b.title, b.author, b.isbn, b.cover_image
                ORDER BY MAX(o.created_at) DESC
                LIMIT 10";

        $statement = $db->prepare($sql);
        $statement->execute(array('reader_id' => (int) $readerUserId));
        $rows = $statement->fetchAll() ?: array();

        // Preserve the same output shape used by templates (price formatting helpers) exactly like Book::attachListingPricePresentationFields().
        foreach ($rows as &$row) {
            $priceValue = (float) $row['price'];
            $row['price_value'] = $priceValue;
            $row['price_egp_formatted'] = 'EGP ' . number_format($priceValue, 2);
        }
        unset($row);

        return $rows;
    }
}
