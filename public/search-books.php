<?php
/**
 * FILE: search-books.php
 * PURPOSE: JSON endpoint that returns book search results for the reader search bar (title/author/ISBN).
 * USED BY: Reader storefront search UI (AJAX/fetch to `/public/search-books.php`).
 * DESIGN PATTERN: None (delegates SQL to `Book` model).
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/models/Book.php';

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

if (strlen($q) < 1) {
    echo json_encode(array('books' => array()));
    exit;
}

$pdo = DB::getInstance();
$books = (new Book($pdo))->searchListedBooks($q);

echo json_encode(array('books' => $books));
