<?php
session_start();

/**
 * FILE: orders.php
 * PURPOSE: Shows the reader’s order history and allows marking orders as collected, then renders the orders page.
 * USED BY: Browser route `/public/orders.php`, which includes `app/views/reader/orders.php`.
 * DESIGN PATTERN: Observer (indirect) — uses `NotificationService` observer wiring to read in-app notifications and publish events elsewhere.
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/AuthMiddleware.php';
require_once __DIR__ . '/../app/models/Book.php';
require_once __DIR__ . '/../app/models/Order.php';
require_once __DIR__ . '/../app/services/NotificationService.php';
require_once __DIR__ . '/../app/services/ReaderClubDashboardService.php';

AuthMiddleware::requireAuthenticated();

$database = DB::getInstance();
$orderModel = new Order($database, new Book($database));
$notifier = new NotificationService(null, null, $database);
$inAppNotifications = $notifier->getInAppStore()->listFor((int) $_SESSION['user_id']);

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_collected'], $_POST['order_id'])) {
    $result = $orderModel->markCollected((int) $_SESSION['user_id'], (int) $_POST['order_id']);
    if ($result['ok']) {
        $successMessage = $result['message'];
    } else {
        $errorMessage = $result['message'];
    }
}

$orders = $orderModel->getReaderOrders((int) $_SESSION['user_id']);
$orderItems = $orderModel->getReaderOrderItems((int) $_SESSION['user_id']);

$myClubs = array();
if ((string) ($_SESSION['role'] ?? '') === 'Reader') {
    $myClubs = (new ReaderClubDashboardService($database))->getReaderClubs((int) $_SESSION['user_id']);
}

require_once __DIR__ . '/../app/views/reader/orders.php';