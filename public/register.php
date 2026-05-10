<?php

/**
 * FILE: register.php
 * PURPOSE: Public endpoint that shows the registration form and POST-handles account creation for new users.
 * USED BY: Browser route `/public/register.php`, which delegates rendering to `app/views/auth/register.php` via `AuthController`.
 * DESIGN PATTERN: None (uses DB Singleton indirectly through the `User` model; controller coordinates the flow).
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/AuthMiddleware.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

AuthMiddleware::requireGuest();

$database = DB::getInstance();
$controller = new AuthController(new User($database));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $controller->register();
    exit;
}

$controller->showRegister();
