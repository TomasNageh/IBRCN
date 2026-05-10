<?php

/**
 * FILE: BookstoreController.php
 * PURPOSE: Handles bookstore-owner inventory workflows that require request validation (including CSV inventory import upload).
 * USED BY: `public/owner-inventory.php` which delegates CSV upload handling when the owner submits the import form.
 * DESIGN PATTERN: None — controller coordinates request validation and calls models/services for persistence.
 */

require_once __DIR__ . '/../patterns/DB.php';

// Validates and processes bookstore inventory CSV uploads for owners (controller layer).
class BookstoreController
{
    /**
     * Handles a CSV file upload with strict validation (type + size) before moving it into uploads storage.
     *
     * NOTE: This is additive functionality used only when the owner submits the CSV import form.
     *
     * @return array{ok:bool,message:string} Result status and user-facing message.
     */
    public function handleCSVUpload(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('ok' => false, 'message' => 'Invalid request.');
        }

        if (!isset($_FILES['csv_file'])) {
            return array('ok' => false, 'message' => 'Upload failed. Please try again.');
        }

        // --- FILE UPLOAD VALIDATION ---

        // Check the file was uploaded without errors
        if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            return array('ok' => false, 'message' => 'Upload failed. Please try again.');
        }

        // Only allow CSV files (inventory bulk import)
        $allowedTypes = array('text/csv', 'text/plain', 'application/vnd.ms-excel');
        $actualType = mime_content_type($_FILES['csv_file']['tmp_name']);
        if (!in_array($actualType, $allowedTypes, true)) {
            return array('ok' => false, 'message' => 'Only CSV files are accepted.');
        }

        // Reject files larger than 10 MB
        $maxSizeBytes = 10 * 1024 * 1024; // 10 MB in bytes
        if ((int) $_FILES['csv_file']['size'] > $maxSizeBytes) {
            return array('ok' => false, 'message' => 'File exceeds the 10 MB size limit.');
        }

        $uploadsDir = dirname(__DIR__, 2) . '/uploads/inventory_imports';
        if (!is_dir($uploadsDir)) {
            @mkdir($uploadsDir, 0775, true);
        }

        $safeName = 'inventory_import_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.csv';
        $destinationPath = $uploadsDir . DIRECTORY_SEPARATOR . $safeName;

        // If we reach here the file is safe to move
        if (!move_uploaded_file($_FILES['csv_file']['tmp_name'], $destinationPath)) {
            return array('ok' => false, 'message' => 'Upload failed. Please try again.');
        }

        // This project spec requires the upload feature + validation; parsing/importing is intentionally not performed here.
        return array('ok' => true, 'message' => 'CSV uploaded successfully.');
    }
}

