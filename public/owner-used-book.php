<?php
/**
 * FILE: owner-used-book.php
 * PURPOSE: Owner endpoint for adding a new used book listing to the store inventory.
 * USED BY: Browser route `/public/owner-used-book.php`, which includes `app/views/owner/used_book.php`.
 * DESIGN PATTERN: None (delegates persistence to `OwnerInventory` model).
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/AuthMiddleware.php';
require_once __DIR__ . '/../app/models/OwnerInventory.php';

AuthMiddleware::requireRole('Owner');

$database = DB::getInstance();
$ownerInventory = new OwnerInventory($database);

$errorMessage = '';
$successMessage = '';
$formData = array(
    'isbn' => (string) ($_POST['isbn'] ?? ''),
    'title' => (string) ($_POST['title'] ?? ''),
    'author' => (string) ($_POST['author'] ?? ''),
    'condition' => (string) ($_POST['condition'] ?? 'Good'),
    'price' => (string) ($_POST['price'] ?? ''),
    'quantity' => (string) ($_POST['quantity'] ?? '1'),
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_used_book'])) {
    $result = $ownerInventory->createUsedListing((int) $_SESSION['user_id'], $_POST);
    if ($result['ok']) {
        $successMessage = $result['message'];
        $formData = array(
            'isbn' => '',
            'title' => '',
            'author' => '',
            'condition' => 'Good',
            'price' => '',
            'quantity' => '1',
        );
    } else {
        $errorMessage = $result['message'];
    }
}

$ownerStore = $ownerInventory->getStoreByOwnerId((int) $_SESSION['user_id']);
require_once __DIR__ . '/../app/views/owner/used_book.php';
