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

AuthMiddleware::requireRole('Owner');

$database = DB::getInstance();
$ownerInventory = new OwnerInventory($database);
$ownerId = (int) $_SESSION['user_id'];

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_csv'])) {
    if (!isset($_FILES['csv_file'])) {
        $errorMessage = 'Upload failed. Please try again.';
    } elseif ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'Upload failed. Please try again.';
    } else {
        $allowedTypes = array('text/csv', 'text/plain', 'application/vnd.ms-excel');
        $actualType = mime_content_type($_FILES['csv_file']['tmp_name']);
        if (!in_array($actualType, $allowedTypes, true)) {
            $errorMessage = 'Only CSV files are accepted.';
        } else {
            $maxSizeBytes = 10 * 1024 * 1024; // 10 MB in bytes
            if ((int) $_FILES['csv_file']['size'] > $maxSizeBytes) {
                $errorMessage = 'File exceeds the 10 MB size limit.';
            } else {
                $uploadsDir = dirname(__DIR__) . '/uploads/inventory_imports';
                if (!is_dir($uploadsDir)) {
                    @mkdir($uploadsDir, 0775, true);
                }

                $safeName = 'inventory_import_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.csv';
                $destinationPath = $uploadsDir . DIRECTORY_SEPARATOR . $safeName;

                if (!move_uploaded_file($_FILES['csv_file']['tmp_name'], $destinationPath)) {
                    $errorMessage = 'Upload failed. Please try again.';
                } else {
                    $successMessage = 'CSV uploaded successfully.';
                }
            }
        }
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
