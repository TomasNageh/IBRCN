<?php
session_start();

/**
 * FILE: book-stores.php
 * PURPOSE: Shows pickup locations (stores with stock) for a selected book.
 * USED BY: Reader storefront links “Pickup locations” which navigate to `/public/book-stores.php?book_id=...`.
 * DESIGN PATTERN: None (delegates catalog/inventory queries to `Book` model).
 */

if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] === 'Owner') {
    header('Location: owner.php');
    exit;
}
if ($_SESSION['role'] === 'Admin') {
    header('Location: admin.php');
    exit;
}

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/models/Book.php';

$database = DB::getInstance();
$bookModel = new Book($database);

$bookId = (int) ($_GET['book_id'] ?? 0);
$isbnRaw = isset($_GET['isbn']) ? trim((string) $_GET['isbn']) : '';

$readerLat = null;
$readerLng = null;
if (isset($_GET['lat'], $_GET['lng']) && $_GET['lat'] !== '' && $_GET['lng'] !== '') {
    $readerLat = (float) $_GET['lat'];
    $readerLng = (float) $_GET['lng'];
    if (abs($readerLat) > 90 || abs($readerLng) > 180) {
        $readerLat = null;
        $readerLng = null;
    }
}

$errorMessage = '';
$bookRow = null;

if ($bookId > 0) {
    $bookRow = $bookModel->getBookById($bookId);
} elseif ($isbnRaw !== '') {
    $bookRow = $bookModel->findBookByIsbn($isbnRaw);
}

if (!$bookRow) {
    $errorMessage = 'Book not found. Open this page from a book using its Pickup locations link or pass a valid book_id / ISBN.';
    $stores = array();
} else {
    $stores = $bookModel->findStoresWithStockForBook((int) $bookRow['book_id'], $readerLat, $readerLng);
}

require_once __DIR__ . '/../app/views/reader/book_stores.php';
