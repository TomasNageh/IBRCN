<?php

/**
 * FILE: PdfDownloadService.php
 * PURPOSE: Streams PDF bytes or plain-text errors with consistent HTTP headers for admin/owner report endpoints.
 * USED BY: `public/admin-report-pdf.php`, `public/owner-report-pdf.php`.
 * DESIGN PATTERN: None — static response helper utilities.
 */

/**
 * PdfDownloadService
 *
 * Shared HTTP response helper for streaming generated PDFs to the browser.
 * Keeps endpoints small and consistent.
 */
class PdfDownloadService
{
    public static function sendPdf(string $pdfBytes, string $filename): void
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $pdfBytes;
    }

    public static function sendTextError(Throwable $e, int $statusCode = 500): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/plain; charset=utf-8');
        echo $e->getMessage();
    }
}

