<?php

/**
 * FILE: owner-inventory.php
 * PURPOSE: Owner inventory management endpoint for listing/editing/removing inventory and importing inventory CSV uploads.
 * USED BY: Browser route `/public/owner-inventory.php` and the Owner Portal navigation links.
 * DESIGN PATTERN: None (uses DB Singleton via `DB::getInstance()`; business rules live in `OwnerInventory` model).
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/AuthMiddleware.php';
require_once __DIR__ . '/../app/models/OwnerInventory.php';
require_once __DIR__ . '/../app/controllers/BookstoreController.php';

AuthMiddleware::requireRole('Owner');

$database = DB::getInstance();
$ownerInventory = new OwnerInventory($database);
$ownerId = (int) $_SESSION['user_id'];

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_csv'])) {
    $result = (new BookstoreController())->handleCSVUpload();
    if ($result['ok']) {
        $successMessage = $result['message'];
    } else {
        $errorMessage = $result['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_listing'])) {
    $invId = (int) ($_POST['inventory_id'] ?? 0);
    $result = $ownerInventory->removeInventoryRow($ownerId, $invId);
    if ($result['ok']) {
        $successMessage = $result['message'];
    } else {
        $errorMessage = $result['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['level_up_all'])) {
    $increment = max(1, (int) ($_POST['increment'] ?? 1));
    $result = $ownerInventory->levelUpAllInventory($ownerId, $increment);
    if ($result['ok']) {
        $successMessage = $result['message'];
    } else {
        $errorMessage = $result['message'];
    }
}

$listings = $ownerInventory->listInventoryForOwner($ownerId);
require_once __DIR__ . '/../app/views/owner/inventory_list.php';
