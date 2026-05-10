<?php
/**
 * FILE: cart-action.php
 * PURPOSE: JSON endpoint that adds an item to the authenticated reader’s cart and persists it server-side.
 * USED BY: Frontend cart “Add to Cart” actions in reader UI (AJAX/fetch to `/public/cart-action.php`).
 * DESIGN PATTERN: None (delegates cart rules to `CartService`).
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/AuthMiddleware.php';
require_once __DIR__ . '/../app/services/CartService.php';

AuthMiddleware::requireAuthenticated();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('ok' => false, 'message' => 'Invalid request.'));
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$action = (string) ($payload['action'] ?? 'add');
$item = is_array($payload['item'] ?? null) ? $payload['item'] : $payload;

$pdo = DB::getInstance();
$cartService = new CartService($pdo);
$cartService->ensureSessionCartInitialized();
$cart = $_SESSION['cart'];

if ($action === 'add') {
    $normalizedItem = $cartService->normalizeIncomingItemForAdd(is_array($item) ? $item : array());
    if ($normalizedItem === null) {
        echo json_encode(array('ok' => false, 'message' => 'Invalid cart item.'));
        exit;
    }

    $cart = $cartService->addToSessionCart($normalizedItem);
    $cartService->persistSessionCart((int) $_SESSION['user_id'], $cart);

    echo json_encode(array('ok' => true, 'count' => count($cart)));
    exit;
}

echo json_encode(array('ok' => false, 'message' => 'Unsupported action.'));