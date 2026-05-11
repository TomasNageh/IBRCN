<?php

/**
 * Builds IBRCN_Code_Mastery_Guide.pdf from the HTML companion file using Dompdf.
 *
 * Usage (from ibrcn): php scripts/build_codebook_pdf.php
 */

declare(strict_types=1);

$base = dirname(__DIR__);
$htmlPath = $base . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'IBRCN_Code_Mastery_Guide.html';
$outPath = $base . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'IBRCN_Code_Mastery_Guide.pdf';

if (!is_file($htmlPath)) {
    fwrite(STDERR, "Missing HTML source: {$htmlPath}\n");
    exit(1);
}

require_once $base . '/vendor/autoload.php';

$html = (string) file_get_contents($htmlPath);

$options = new Dompdf\Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf\Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dir = dirname($outPath);
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

file_put_contents($outPath, $dompdf->output());

echo "Wrote: {$outPath}\n";
