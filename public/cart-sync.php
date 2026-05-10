<?php
/**
 * FILE: cart-sync.php
 * PURPOSE: JSON endpoint that replaces the server-side session cart with the client cart and persists it in the database.
 * USED BY: Reader cart page JavaScript (fetch to `/public/cart-sync.php`) for keeping localStorage and session cart consistent.
 * DESIGN PATTERN: None (delegates cart normalization/persistence to `CartService`).
 */

header('Content-Type: application/json; charset=utf-8');

session_start();

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

$incomingCart = $payload['cart'] ?? array();
$pdo = DB::getInstance();
$cartService = new CartService($pdo);
$normalized = $cartService->normalizeIncomingCart($incomingCart);

$_SESSION['cart'] = $normalized;

$cartService->persistSessionCart((int) $_SESSION['user_id'], $normalized);

echo json_encode(array('ok' => true, 'count' => count($normalized)));