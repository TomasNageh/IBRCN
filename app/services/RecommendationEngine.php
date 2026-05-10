<?php

/**
 * FILE: RecommendationEngine.php
 * PURPOSE: Chooses homepage Strategy (`HistoryBasedStrategy` vs `TopPicksStrategy`) based on optional privacy column semantics.
 * USED BY: `HomeController::index()` when assembling `$books` before rendering reader storefront hero rails.
 * DESIGN PATTERN: Strategy coordinator (selects algorithm without embedding branching SQL/HTML inside controllers).
 */

require_once __DIR__ . '/../patterns/RecommendationStrategy.php';
require_once __DIR__ . '/../patterns/TopPicksStrategy.php';
require_once __DIR__ . '/../patterns/HistoryBasedStrategy.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../models/User.php';

// Selects which RecommendationStrategy runs for homepage rails while preserving identical default outcomes when opt-in absent.
class RecommendationEngine
{
    public function __construct(private Book $bookModel)
    {
    }

    /**
     * Resolves catalog spotlight rows for storefront homepage respecting optional reader consent metadata when present.
     *
     * @param int|null $readerUserId Null when visitor anonymous; positive int when authenticated Reader session supplies user_id.
     * @return list<array<string, mixed>> Homepage book cards shaped like legacy featured listings (pricing helpers applied).
     */
    public function getHomePageRecommendations(?int $readerUserId): array
    {
        $readerId = ($readerUserId !== null && $readerUserId > 0) ? (int) $readerUserId : 0;

        if ($readerId > 0) {
            $userModel = new User($this->bookModel->getDatabaseConnection());
            if ($userModel->readerAllowsPersonalizedRecommendationsFromHistory($readerId)) {
                $historyStrategy = new HistoryBasedStrategy($this->bookModel);
                $historyRows = $historyStrategy->getRecommendations($readerId);
                if ($historyRows !== array()) {
                    return $historyRows;
                }
            }
        }

        $topPicksStrategy = new TopPicksStrategy($this->bookModel);

        return $topPicksStrategy->getRecommendations($readerId);
    }
}
