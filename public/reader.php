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
require_once __DIR__ . '/../app/controllers/HomeController.php';

AuthMiddleware::requireRole('Reader');

$database = DB::getInstance();
$controller = new HomeController(new Book($database));
$controller->index();
