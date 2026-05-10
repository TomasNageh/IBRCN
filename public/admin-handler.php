<?php

/**
 * FILE: admin-handler.php
 * PURPOSE: Admin endpoint variant that processes admin POST actions and renders the admin dashboard.
 * USED BY: Browser route `/public/admin-handler.php` (legacy alias for admin dashboard workflows).
 * DESIGN PATTERN: None (delegates to `AdminPanelService` and renders `app/views/admin/home.php`).
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/models/Admin.php';
require_once __DIR__ . '/../app/services/AuthMiddleware.php';
require_once __DIR__ . '/../app/services/AdminPanelService.php';

AuthMiddleware::requireRole('Admin');

$adminPanel = new AdminPanelService();
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $adminPanel->handlePost($_POST);
    $message = $result['message'];
    $messageType = $result['messageType'];
}

$viewData = $adminPanel->loadViewData();
$pendingStores = $viewData['pendingStores'];
$approvedStores = $viewData['approvedStores'];
$allUsers = $viewData['allUsers'];

require_once __DIR__ . '/../app/views/admin/home.php';
