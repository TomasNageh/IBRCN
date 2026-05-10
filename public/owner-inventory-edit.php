<?php
/**
 * FILE: owner-inventory-edit.php
 * PURPOSE: Owner endpoint for editing an existing inventory listing (condition, price, quantity).
 * USED BY: Browser route `/public/owner-inventory-edit.php`, which includes `app/views/owner/inventory_edit.php`.
 * DESIGN PATTERN: None (delegates persistence to `OwnerInventory` model).
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/AuthMiddleware.php';
require_once __DIR__ . '/../app/models/OwnerInventory.php';

AuthMiddleware::requireRole('Owner');

$database = DB::getInstance();
$ownerInventory = new OwnerInventory($database);
$ownerId = (int) $_SESSION['user_id'];

$inventoryId = (int) ($_GET['inventory_id'] ?? $_POST['inventory_id'] ?? 0);
$errorMessage = '';
$successMessage = '';

$row = $inventoryId > 0 ? $ownerInventory->getInventoryRowForOwner($ownerId, $inventoryId) : null;

if (!$row) {
    header('Location: owner-inventory.php');
    exit;
}

$formData = array(
    'condition' => (string) ($row['condition']),
    'price' => (string) $row['price'],
    'quantity' => (string) $row['quantity'],
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_listing'])) {
    $formData = array(
        'condition' => (string) ($_POST['condition'] ?? $row['condition']),
        'price' => (string) ($_POST['price'] ?? ''),
        'quantity' => (string) ($_POST['quantity'] ?? ''),
    );
    $result = $ownerInventory->updateInventoryRow($ownerId, $inventoryId, $_POST);
    if ($result['ok']) {
        $successMessage = $result['message'];
        $row = $ownerInventory->getInventoryRowForOwner($ownerId, $inventoryId);
        if ($row) {
            $formData = array(
                'condition' => (string) $row['condition'],
                'price' => (string) $row['price'],
                'quantity' => (string) $row['quantity'],
            );
        }
    } else {
        $errorMessage = $result['message'];
    }
}

require_once __DIR__ . '/../app/views/owner/inventory_edit.php';
