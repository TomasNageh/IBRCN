<?php

/**
 * FILE: reader.php
 * PURPOSE: Reader portal endpoint that renders the storefront home page for authenticated reader accounts.
 * USED BY: Browser route `/public/reader.php`, which uses `HomeController` and includes `app/views/reader/home.php`.
 * DESIGN PATTERN: Strategy (indirect) — homepage recommendations are selected by `RecommendationEngine`.
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/AuthMiddleware.php';
require_once __DIR__ . '/../app/models/Book.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/ReadingClub.php';
require_once __DIR__ . '/../app/services/RecommendationEngine.php';

AuthMiddleware::requireRole('Reader');

$database = DB::getInstance();

$readerUserId = null;
if (isset($_SESSION['user_id'], $_SESSION['role']) && (string) $_SESSION['role'] === 'Reader') {
    $readerUserId = (int) $_SESSION['user_id'];
}

$books = (new RecommendationEngine(new Book($database)))->getHomePageRecommendations($readerUserId);

$myClubs = array();

if ($readerUserId !== null) {
    $readerClub = new ReadingClub($database);
    $readerClub->ensureSchema();
    $user = (new User($database))->findById($readerUserId);
    if ($user) {
        $myClubs = $readerClub->findClubsForUser((int) $user['user_id'], (string) $user['email']);
        $readsByClub = $readerClub->getMemberReadsForClubs(array_column($myClubs, 'club_id'));
        foreach ($myClubs as &$clubRow) {
            $clubRow['member_reads'] = $readsByClub[(int) $clubRow['club_id']] ?? array();
        }
        unset($clubRow);
    }
}

require_once __DIR__ . '/../app/views/reader/home.php';
