<?php

/**
 * FILE: admin.php
 * PURPOSE: Admin dashboard endpoint for approving stores and managing users, then rendering the admin home page.
 * USED BY: Browser route `/public/admin.php`, which includes `app/views/admin/home.php` after loading data via `AdminPanelService`.
 * DESIGN PATTERN: None (admin workflows are handled by services/models; views render results).
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
