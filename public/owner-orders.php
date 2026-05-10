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
require_once __DIR__ . '/../app/models/Book.php';
require_once __DIR__ . '/../app/models/Order.php';
require_once __DIR__ . '/../app/services/NotificationService.php';

AuthMiddleware::requireRole('Owner');

$database = DB::getInstance();
$orderModel = new Order($database, new Book($database));
$notifier = new NotificationService(null, null, $database);

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_ready'], $_POST['order_id'])) {
    $orderId = (int) $_POST['order_id'];
    $result = $orderModel->markReady((int) $_SESSION['user_id'], $orderId);
    if ($result['ok']) {
        $successMessage = (string) $result['message'];
        try {
            $notifier->onOrderMarkedReady((int) $_SESSION['user_id'], $orderId);
        } catch (Throwable $e) {
            error_log('Notification on order ready: ' . $e->getMessage());
        }
    } else {
        $errorMessage = (string) $result['message'];
    }
}

$orders = $orderModel->getOwnerOrders((int) $_SESSION['user_id']);
$orderItems = $orderModel->getOwnerOrderItems((int) $_SESSION['user_id']);
$inAppNotifications = $notifier->listInApp((int) $_SESSION['user_id']);

require_once __DIR__ . '/../app/views/owner/orders.php';
