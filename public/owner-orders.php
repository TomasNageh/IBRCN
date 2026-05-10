<?php
session_start();

/**
 * FILE: owner-orders.php
 * PURPOSE: Shows bookstore owner orders and allows marking orders as ready for pickup.
 * USED BY: Browser route `/public/owner-orders.php`, which includes `app/views/owner/orders.php` after `OwnerOrdersService` loads data.
 * DESIGN PATTERN: Observer (indirect) — order readiness triggers reader notifications via `NotificationService` observer wiring.
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/AuthMiddleware.php';
require_once __DIR__ . '/../app/services/OwnerOrdersService.php';

AuthMiddleware::requireRole('Owner');

$database = DB::getInstance();
$ownerOrders = new OwnerOrdersService($database);

$errorMessage = '';
$successMessage = '';

$postResult = $ownerOrders->handlePost($_POST, (int) $_SESSION['user_id']);
$errorMessage = $postResult['errorMessage'];
$successMessage = $postResult['successMessage'];

$viewData = $ownerOrders->loadViewData((int) $_SESSION['user_id']);
$orders = $viewData['orders'];
$orderItems = $viewData['orderItems'];
$inAppNotifications = $viewData['inAppNotifications'];

require_once __DIR__ . '/../app/views/owner/orders.php';