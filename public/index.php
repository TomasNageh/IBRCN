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
require_once __DIR__ . '/../app/controllers/HomeController.php';

$database = DB::getInstance();
$controller = new HomeController(new Book($database));
$controller->index();
