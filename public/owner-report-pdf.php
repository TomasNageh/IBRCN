<?php

/**
 * FILE: owner-report-pdf.php
 * PURPOSE: Generates and downloads the Owner inventory PDF report.
 * USED BY: Owner inventory page “Download inventory PDF” button linking to `/public/owner-report-pdf.php`.
 * DESIGN PATTERN: None (delegates PDF rendering to `PdfReportService`).
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/AuthMiddleware.php';
require_once __DIR__ . '/../app/models/OwnerInventory.php';
require_once __DIR__ . '/../app/services/PdfReportService.php';
require_once __DIR__ . '/../app/services/PdfDownloadService.php';

AuthMiddleware::requireRole('Owner');

$ownerId = (int) ($_SESSION['user_id'] ?? 0);
$ownerUsername = (string) ($_SESSION['user'] ?? 'Owner');

$database = DB::getInstance();
$ownerInventory = new OwnerInventory($database);

try {
    $store = $ownerInventory->getStoreByOwnerId($ownerId);
    $items = $ownerInventory->listInventoryForOwner($ownerId);
    $pdf = PdfReportService::renderOwnerInventoryReport($store, $items, $ownerUsername);
} catch (Throwable $e) {
    PdfDownloadService::sendTextError($e, 500);
    exit;
}

$safeSlug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) ($store['name'] ?? 'store'));
$safeSlug = trim($safeSlug, '-') ?: 'store';
$filename = 'ibrcn-inventory-' . $safeSlug . '-' . date('Y-m-d') . '.pdf';
PdfDownloadService::sendPdf($pdf, $filename);
