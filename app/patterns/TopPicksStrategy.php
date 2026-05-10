<?php

/**
 * FILE: TopPicksStrategy.php
 * PURPOSE: Strategy returning globally curated featured inventory identical to legacy `Book::getFeaturedBooks()` output.
 * USED BY: `RecommendationEngine` whenever privacy/opt-in rules disallow personalized purchase-history signals (default path).
 * DESIGN PATTERN: Strategy (neutral storefront spotlight catalog path).
 */

require_once __DIR__ . '/RecommendationStrategy.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/DB.php';

// Delegates to Book model featured listing query unchanged (platform-wide top picks strategy implementation).
class TopPicksStrategy implements RecommendationStrategy
{
    public function __construct(private Book $bookModel)
    {
    }

    /**
     * Loads featured inventory spotlight identical to pre-strategy homepage featured rails.
     *
     * @param int $readerUserId Unused but kept for uniform strategy interface symmetry across implementations.
     * @return list<array<string, mixed>>
     */
    public function getRecommendations(int $readerUserId): array
    {
        $db = DB::getInstance();

        // NOTE: This SQL query is copied EXACTLY from Book::getFeaturedBooks(). Do not change it.
        $sql = "SELECT b.book_id, b.title, b.author, b.isbn, b.cover_image, MIN(i.price) AS price
                FROM books b
                JOIN store_inventory i ON b.book_id = i.book_id
            WHERE (i.quantity - COALESCE(i.hold_quantity, 0)) > 0
                GROUP BY b.book_id, b.title, b.author, b.isbn, b.cover_image
                LIMIT 10";

        $statement = $db->query($sql);
        $rows = $statement ? ($statement->fetchAll() ?: array()) : array();

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
