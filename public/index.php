<?php

/**
 * FILE: index.php
 * PURPOSE: Public homepage endpoint; redirects authenticated Owners/Admins to their dashboards and renders the Reader storefront.
 * USED BY: Browser route `/public/index.php`, which instantiates `HomeController` and renders `app/views/reader/home.php`.
 * DESIGN PATTERN: None (uses DB Singleton via `DB::getInstance()` and uses Strategy internally via `RecommendationEngine` in models/services).
 */

session_start();

/**
 * Role-based landing redirects for authenticated non-reader accounts.
 * Reader accounts continue to the storefront rendered by HomeController.
 */
if (isset($_SESSION["user"], $_SESSION["role"])) {
    $redirectByRole = array(
        "Owner" => "owner.php",
        "Admin" => "admin.php",
    );

    $target = $redirectByRole[(string) $_SESSION["role"]] ?? null;
    if ($target !== null) {
        header("Location: " . $target);
        exit;
    }
}

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/models/Book.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/ReadingClub.php';
require_once __DIR__ . '/../app/services/RecommendationEngine.php';

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
