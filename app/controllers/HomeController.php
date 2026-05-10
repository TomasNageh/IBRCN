<?php

/**
 * FILE: HomeController.php
 * PURPOSE: Prepares homepage featured rails plus optional signed-in Reader club summaries prior to rendering storefront landing templates.
 * USED BY: `public/index.php`, `public/reader.php` after middleware confirms Reader-oriented routing expectations when applicable.
 * DESIGN PATTERN: Strategy selection delegated to `RecommendationEngine` (history vs top picks) without embedding SQL branches here.
 */

require_once __DIR__ . '/../patterns/DB.php';
require_once __DIR__ . '/../services/ReaderClubDashboardService.php';
require_once __DIR__ . '/../services/RecommendationEngine.php';
require_once __DIR__ . '/../models/Book.php';

// Aggregates Strategy-driven catalog highlights with reader club widgets consumed exclusively by `views/reader/home.php` output.
class HomeController
{
    private Book $bookModel;

    private ReaderClubDashboardService $readerClubDashboard;

    private RecommendationEngine $recommendationEngine;

    /**
     * Stores injected Book model plus lazily composed dashboard helper mirroring legacy constructor defaults when arguments omitted.
     *
     * @param Book                             $bookModel            Primary catalog dependency powering Strategy queries.
     * @param ReaderClubDashboardService|null    $readerClubDashboard Optional override enabling tests to stub club summaries.
     * @param RecommendationEngine|null        $recommendationEngine  Optional swap hook for deterministic homepage assertions.
     * @return void
     */
    public function __construct(
        Book $bookModel,
        ?ReaderClubDashboardService $readerClubDashboard = null,
        ?RecommendationEngine $recommendationEngine = null
    ) {
        $this->bookModel = $bookModel;
        $databaseConnection = DB::getInstance();
        $this->readerClubDashboard = $readerClubDashboard ?? new ReaderClubDashboardService($databaseConnection);
        $this->recommendationEngine = $recommendationEngine ?? new RecommendationEngine($this->bookModel);
    }

    /**
     * Builds `$books`/`$myClubs` view variables then renders reader homepage markup without embedding HTML in this controller layer.
     *
     * @return void
     */
    public function index(): void
    {
        $readerUserId = null;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (isset($_SESSION['user_id'], $_SESSION['role']) && (string) $_SESSION['role'] === 'Reader') {
            $readerUserId = (int) $_SESSION['user_id'];
        }

        $books = $this->recommendationEngine->getHomePageRecommendations($readerUserId);

        $myClubs = array();

        if ($readerUserId !== null) {
            $myClubs = $this->readerClubDashboard->getReaderClubs($readerUserId);
        }

        require_once __DIR__ . '/../views/reader/home.php';
    }
}
