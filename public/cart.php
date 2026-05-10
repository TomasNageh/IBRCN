<?php

/**
 * FILE: cart.php
 * PURPOSE: Public endpoint for the Reader shopping cart page; handles cart quantity updates and checkout POST, then renders cart UI.
 * USED BY: Browser route `/public/cart.php` (GET/POST) and AJAX sync calls (`/public/cart-sync.php`) for cart persistence.
 * DESIGN PATTERN: Observer (indirect) — after successful checkout, `NotificationService` publishes events via `EventPublisher`.
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/AuthMiddleware.php';
require_once __DIR__ . '/../app/models/Book.php';
require_once __DIR__ . '/../app/models/Order.php';
require_once __DIR__ . '/../app/models/CartRepository.php';
require_once __DIR__ . '/../app/models/AuditLogRepository.php';
require_once __DIR__ . '/../app/services/NotificationService.php';
require_once __DIR__ . '/../app/services/CartService.php';

AuthMiddleware::requireAuthenticated();

$database = DB::getInstance();
$bookModel = new Book($database);
$orderModel = new Order($database, $bookModel);

$cartService = new CartService($database);
$cartService->ensureSessionCartInitialized();
$cartService->hydrateSessionCartIfEmpty((int) $_SESSION['user_id'], (string) ($_SESSION['role'] ?? ''));

$serverCartItems = array();
foreach ($_SESSION['cart'] as $item) {
	$bookId = (int) ($item['book_id'] ?? 0);
	$book = $bookId > 0 ? $bookModel->getBookById($bookId) : null;
	$serverCartItems[] = array(
		'book_id' => $bookId,
		'title' => (string) ($item['title'] ?? ($book['title'] ?? 'Untitled')),
		'author' => (string) ($item['author'] ?? ($book['author'] ?? '')),
		'image' => (string) ($item['image'] ?? ($book['cover_image'] ?? '')),
		'unit_price' => (float) ($item['unitPrice'] ?? 0),
		'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
	);
}

$errorMessage = '';
$successMessage = '';
$checkoutSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_action'], $_POST['book_id'])) {
	$bookId = (int) $_POST['book_id'];
	$action = (string) $_POST['cart_action'];

	foreach ($_SESSION['cart'] as $index => $item) {
		if ((int) ($item['book_id'] ?? 0) !== $bookId) {
			continue;
		}

		if ($action === 'increase') {
			$_SESSION['cart'][$index]['quantity'] = (int) ($item['quantity'] ?? 1) + 1;
		} elseif ($action === 'decrease') {
			$currentQuantity = (int) ($item['quantity'] ?? 1);
			if ($currentQuantity > 1) {
				$_SESSION['cart'][$index]['quantity'] = $currentQuantity - 1;
			}
		} elseif ($action === 'remove') {
			unset($_SESSION['cart'][$index]);
		}

		$_SESSION['cart'] = array_values($_SESSION['cart']);
		break;
	}
	$cartService->persistSessionCart((int) $_SESSION['user_id'], $_SESSION['cart']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_cart'])) {
	$result = $orderModel->checkoutCart((int) $_SESSION['user_id'], $_SESSION['cart']);
	if ($result['ok']) {
		$_SESSION['cart'] = array();
		try {
			(new CartRepository($database))->clear((int) $_SESSION['user_id']);
		} catch (Throwable $e) {
			error_log('cart clear after checkout: ' . $e->getMessage());
		}
		$readerId = (int) $_SESSION['user_id'];
		$actorRole = (string) ($_SESSION['role'] ?? 'Reader');
		try {
			$audit = new AuditLogRepository($database);
			foreach ($result['orders'] ?? array() as $ord) {
				$oid = (int) ($ord['order_id'] ?? 0);
				if ($oid <= 0) {
					continue;
				}
				$audit->write($readerId, $actorRole, 'order_checkout', $oid, 'orders');
			}
		} catch (Throwable $e) {
			error_log('audit checkout: ' . $e->getMessage());
		}
		$successMessage = $result['message'];
		$checkoutSuccess = true;
		try {
			$notifier = new NotificationService(null, null, $database);
			$notifier->onCheckoutSuccess($readerId, $result['orders'] ?? array());
		} catch (Throwable $e) {
			error_log('Notification after checkout: ' . $e->getMessage());
		}
	} else {
		$errorMessage = $result['message'];
	}
}

$cartItems = $serverCartItems;
$totalPrice = 0.0;
foreach ($cartItems as $item) {
	$totalPrice += (float) $item['unit_price'] * (int) $item['quantity'];
}

if ($checkoutSuccess) {
	$cartItems = array();
	$totalPrice = 0.0;
}

require_once __DIR__ . '/../app/views/reader/cart.php';
