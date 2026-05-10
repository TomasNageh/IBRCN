<?php

/**
 * FILE: PdfReportService.php
 * PURPOSE: Renders HTML templates into PDF bytes via Dompdf for admin/owner printable reports.
 * USED BY: Admin/owner PDF endpoints alongside `PdfDownloadService`.
 * DESIGN PATTERN: None — static render helpers loading Composer autoload once per request.
 */

/**
 * Builds printable PDF reports using Dompdf (install via Composer in ibrcn).
 */
class PdfReportService
{
    private static function ensureDompdf(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException(
                'PDF support is not installed. From the ibrcn folder, run: php composer.phar install'
            );
        }
        require_once $autoload;
        $loaded = true;
    }

    private static function h(?string $s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param list<array<string,mixed>> $stores Rows from Admin::getAllStoresWithOwners()
     */
    public static function renderAdminBookstoresReport(array $stores): string
    {
        self::ensureDompdf();

        $generated = date('Y-m-d H:i');
        $rowsHtml = '';
        foreach ($stores as $row) {
            $rowsHtml .= '<tr>'
                . '<td>' . self::h($row['store_id'] ?? '') . '</td>'
                . '<td>' . self::h($row['name'] ?? '') . '</td>'
                . '<td>' . self::h($row['username'] ?? '—') . '</td>'
                . '<td>' . self::h($row['email'] ?? '—') . '</td>'
                . '<td>' . self::h($row['region'] ?? '—') . '</td>'
                . '<td>' . self::h($row['status'] ?? '') . '</td>'
                . '</tr>';
        }

        if ($rowsHtml === '') {
            $rowsHtml = '<tr><td colspan="6" style="text-align:center;padding:12px;">No bookstore records.</td></tr>';
        }

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">'
            . '<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
h1 { font-size: 18px; margin: 0 0 6px 0; }
.meta { font-size: 10px; color: #555; margin-bottom: 14px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
th { background: #f0f0f0; font-weight: bold; }
</style></head><body>'
            . '<h1>Bookstores &amp; owners</h1>'
            . '<div class="meta">IBRCN admin report · Generated ' . self::h($generated) . '</div>'
            . '<table><thead><tr>'
            . '<th>Store ID</th><th>Store name</th><th>Owner username</th><th>Owner email</th><th>Region</th><th>Status</th>'
            . '</tr></thead><tbody>' . $rowsHtml . '</tbody></table>'
            . '</body></html>';

        return self::htmlToPdf($html, 'landscape');
    }

    /**
     * @param array<string,mixed>|null $store From OwnerInventory::getStoreByOwnerId()
     * @param list<array<string,mixed>> $items From OwnerInventory::listInventoryForOwner()
     */
    public static function renderOwnerInventoryReport(?array $store, array $items, string $ownerUsername): string
    {
        self::ensureDompdf();

        $generated = date('Y-m-d H:i');
        $storeName = $store['name'] ?? 'Your store';
        $storeStatus = $store['status'] ?? '—';

        $totalQty = 0;
        $rowsHtml = '';
        foreach ($items as $row) {
            $qty = (int) ($row['quantity'] ?? 0);
            $hold = (int) ($row['hold_quantity'] ?? 0);
            $totalQty += $qty;
            $price = number_format((float) ($row['price'] ?? 0), 2);
            $rowsHtml .= '<tr>'
                . '<td>' . self::h($row['isbn'] ?? '') . '</td>'
                . '<td>' . self::h($row['title'] ?? '') . '</td>'
                . '<td>' . self::h($row['author'] ?? '') . '</td>'
                . '<td>' . self::h($row['condition'] ?? '') . '</td>'
                . '<td style="text-align:right;">EGP ' . self::h($price) . '</td>'
                . '<td style="text-align:right;">' . self::h((string) $qty) . '</td>'
                . '<td style="text-align:right;">' . self::h((string) $hold) . '</td>'
                . '</tr>';
        }

        if ($rowsHtml === '') {
            $rowsHtml = '<tr><td colspan="7" style="text-align:center;padding:12px;">No inventory listings.</td></tr>';
        }

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">'
            . '<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
h1 { font-size: 17px; margin: 0 0 6px 0; }
.meta { font-size: 10px; color: #555; margin-bottom: 12px; }
.summary { margin: 10px 0; font-size: 11px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #ccc; padding: 5px 7px; text-align: left; vertical-align: top; }
th { background: #f0f0f0; font-weight: bold; }
</style></head><body>'
            . '<h1>Inventory report</h1>'
            . '<div class="meta">Generated ' . self::h($generated) . '</div>'
            . '<div class="summary">'
            . '<strong>Owner:</strong> ' . self::h($ownerUsername) . '<br/>'
            . '<strong>Store:</strong> ' . self::h($storeName) . ' &nbsp;|&nbsp; <strong>Store status:</strong> ' . self::h($storeStatus)
            . '</div>'
            . '<div class="summary"><strong>Total units in stock (sum of quantities):</strong> ' . (string) $totalQty . '</div>'
            . '<table><thead><tr>'
            . '<th>ISBN</th><th>Title</th><th>Author</th><th>Condition</th><th>Price</th><th>Qty</th><th>On hold</th>'
            . '</tr></thead><tbody>' . $rowsHtml . '</tbody></table>'
            . '</body></html>';

        return self::htmlToPdf($html, 'landscape');
    }

    private static function htmlToPdf(string $html, string $orientation): string
    {
        $options = new Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();

        return (string) $dompdf->output();
    }
}
