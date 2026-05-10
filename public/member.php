<?php

/**
 * FILE: member.php
 * PURPOSE: Public endpoint for the Reading Clubs page; delegates workflows to `ReadingClubController` and renders the clubs UI view.
 * USED BY: Browser route `/public/member.php` (GET/POST).
 * DESIGN PATTERN: None (uses DB Singleton via `DB::getInstance()`; SQL remains in models).
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/ReadingClub.php';
require_once __DIR__ . '/../app/controllers/ReadingClubController.php';

$db = DB::getInstance();
$readingClub = new ReadingClub($db);
$userModel = new User($db);

$controller = new ReadingClubController($db, $readingClub, $userModel);
$viewData = $controller->handleRequest();

extract($viewData, EXTR_SKIP);

require_once __DIR__ . '/../app/views/reader/member.php';
