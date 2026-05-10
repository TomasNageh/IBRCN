<?php

/**
 * FILE: admin-report-pdf.php
 * PURPOSE: Generates and downloads the Admin PDF report listing bookstores and their owners.
 * USED BY: Admin dashboard “Download PDF — all stores & owners” button linking to `/public/admin-report-pdf.php`.
 * DESIGN PATTERN: None (delegates PDF rendering to `PdfReportService`).
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/models/Admin.php';
require_once __DIR__ . '/../app/services/AuthMiddleware.php';
require_once __DIR__ . '/../app/services/PdfReportService.php';
require_once __DIR__ . '/../app/services/PdfDownloadService.php';

AuthMiddleware::requireRole('Admin');

try {
    $stores = Admin::getAllStoresWithOwners();
    $pdf = PdfReportService::renderAdminBookstoresReport($stores);
} catch (Throwable $e) {
    PdfDownloadService::sendTextError($e, 500);
    exit;
}

$filename = 'ibrcn-bookstores-owners-' . date('Y-m-d') . '.pdf';
PdfDownloadService::sendPdf($pdf, $filename);
