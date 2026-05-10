<?php

/**
 * STRATEGY PATTERN — RecommendationStrategy.php
 *
 * WHAT IS STRATEGY?
 * Like choosing a route on Google Maps. The destination is the same
 * (a list of recommended books), but you can pick different "strategies"
 * to get there: fastest route (history-based) or scenic route (top picks).
 * Swapping strategies doesn't change anything else in the app.
 *
 * WHY DO WE USE IT HERE?
 * GDPR-style toggles may disable personalized purchase-history signals; when disabled we fall back to neutral top picks
 * without scattering conditional SQL throughout controllers.
 *
 * FILE: RecommendationStrategy.php
 * PURPOSE: Interface describing interchangeable homepage recommendation algorithms consumed by `RecommendationEngine`.
 * USED BY: `TopPicksStrategy`, `HistoryBasedStrategy`, orchestrated from `app/services/RecommendationEngine.php`.
 * DESIGN PATTERN: Strategy (swap algorithms without altering controller structure).
 */

// Declares one interchangeable recommendation algorithm operating against shared Book model state (strategy contract).
interface RecommendationStrategy
{
    /**
     * Loads homepage-facing catalog rows shaped like legacy `Book::getFeaturedBooks()` output arrays.
     *
     * @param int $readerUserId Authenticated reader primary key or `0` when unauthenticated visitors browse storefront widgets.
     * @return list<array<string, mixed>> Rows carrying pricing presentation keys identical to legacy featured listings.
     */
    public function getRecommendations(int $readerUserId): array;
}
