<?php

/**
 * Seeds additional bookstore Owner accounts, approved stores, and inventory.
 * Cover paths match pages under public/ (e.g. ./img/book-1.png).
 *
 * Run from CLI: php scripts/seed_demo_owners.php
 * Safe to run multiple times (idempotent upserts by username / store name / ISBN).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/patterns/DB.php';

$pdo = DB::getInstance();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/** Shared catalog: same core set as seed_catalog.php (global books table). */
$coreCatalog = array(
    array('isbn' => '9780000000010', 'title' => 'The Midnight Library', 'author' => 'Matt Haig', 'cover_image' => './img/book-1.png', 'price' => 120.00, 'condition' => 'Good', 'quantity' => 8),
    array('isbn' => '9780000000027', 'title' => 'Atomic Habits', 'author' => 'James Clear', 'cover_image' => './img/book-2.png', 'price' => 150.00, 'condition' => 'Fine', 'quantity' => 6),
    array('isbn' => '9780000000034', 'title' => 'It Ends with Us', 'author' => 'Colleen Hoover', 'cover_image' => './img/book-3.png', 'price' => 135.00, 'condition' => 'Good', 'quantity' => 7),
    array('isbn' => '9780000000041', 'title' => 'The Alchemist', 'author' => 'Paulo Coelho', 'cover_image' => './img/book-4.png', 'price' => 110.00, 'condition' => 'Fine', 'quantity' => 10),
    array('isbn' => '9780000000058', 'title' => 'Rich Dad Poor Dad', 'author' => 'Robert T. Kiyosaki', 'cover_image' => './img/book-5.png', 'price' => 145.00, 'condition' => 'Good', 'quantity' => 5),
    array('isbn' => '9780000000065', 'title' => 'Ikigai', 'author' => 'Hector Garcia', 'cover_image' => './img/book-6.png', 'price' => 125.00, 'condition' => 'Fine', 'quantity' => 9),
    array('isbn' => '9780000000072', 'title' => 'Thinking, Fast and Slow', 'author' => 'Daniel Kahneman', 'cover_image' => './img/book-7.png', 'price' => 160.00, 'condition' => 'Good', 'quantity' => 4),
    array('isbn' => '9780000000089', 'title' => 'Deep Work', 'author' => 'Cal Newport', 'cover_image' => './img/book-8.png', 'price' => 140.00, 'condition' => 'Fine', 'quantity' => 6),
    array('isbn' => '9780000000096', 'title' => 'The Psychology of Money', 'author' => 'Morgan Housel', 'cover_image' => './img/book-9.png', 'price' => 155.00, 'condition' => 'Good', 'quantity' => 8),
    array('isbn' => '9780000000102', 'title' => 'Sapiens', 'author' => 'Yuval Noah Harari', 'cover_image' => './img/book-10.png', 'price' => 175.00, 'condition' => 'Fine', 'quantity' => 5),
);

/** Extra rows using additional images under public/img (unique ISBNs). */
$extraCatalog = array(
    array('isbn' => '9783000001001', 'title' => 'Desert Light Reader', 'author' => 'Amira Hassan', 'cover_image' => './img/book3.png', 'price' => 99.00, 'condition' => 'Good', 'quantity' => 5),
    array('isbn' => '9783000001002', 'title' => 'Harbor Stories', 'author' => 'Omar Saeed', 'cover_image' => './img/book5.png', 'price' => 88.50, 'condition' => 'Fine', 'quantity' => 4),
    array('isbn' => '9783000001003', 'title' => 'Garden Notes', 'author' => 'Layla Nour', 'cover_image' => './img/book6.png', 'price' => 72.00, 'condition' => 'Good', 'quantity' => 6),
    array('isbn' => '9783000001004', 'title' => 'Evening Essays', 'author' => 'Karim Farid', 'cover_image' => './img/book7.png', 'price' => 95.00, 'condition' => 'Fair', 'quantity' => 3),
    array('isbn' => '9783000001005', 'title' => 'Coastal Letters', 'author' => 'Yasmine Adel', 'cover_image' => './img/bookx.jpeg', 'price' => 110.00, 'condition' => 'Fine', 'quantity' => 4),
);

/**
 * @param list<string> $isbns
 * @param array<string,array<string,mixed>> $byIsbn
 * @return list<array<string,mixed>>
 */
function pickBooks(array $isbns, array $byIsbn): array
{
    $out = array();
    foreach ($isbns as $isbn) {
        if (isset($byIsbn[$isbn])) {
            $out[] = $byIsbn[$isbn];
        }
    }

    return $out;
}

function firstOrCreateUser(PDO $pdo, array $seedOwner): int
{
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = :username OR email = :email LIMIT 1');
    $stmt->execute(array('username' => $seedOwner['username'], 'email' => $seedOwner['email']));
    $row = $stmt->fetch();
    if ($row) {
        return (int) $row['user_id'];
    }

    $insert = $pdo->prepare(
        'INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)'
    );
    $insert->execute(array(
        'username' => $seedOwner['username'],
        'email' => $seedOwner['email'],
        'password_hash' => password_hash($seedOwner['password'], PASSWORD_BCRYPT),
        'role' => $seedOwner['role'],
    ));

    return (int) $pdo->lastInsertId();
}

function firstOrCreateStore(PDO $pdo, array $storeSeed, int $ownerId): int
{
    $stmt = $pdo->prepare('SELECT store_id FROM stores WHERE name = :name LIMIT 1');
    $stmt->execute(array('name' => $storeSeed['name']));
    $row = $stmt->fetch();
    if ($row) {
        return (int) $row['store_id'];
    }

    $insert = $pdo->prepare(
        'INSERT INTO stores (owner_id, name, address, region, latitude, longitude, status)
         VALUES (:owner_id, :name, :address, :region, :latitude, :longitude, :status)'
    );
    $insert->execute(array(
        'owner_id' => $ownerId,
        'name' => $storeSeed['name'],
        'address' => $storeSeed['address'],
        'region' => $storeSeed['region'],
        'latitude' => $storeSeed['latitude'],
        'longitude' => $storeSeed['longitude'],
        'status' => $storeSeed['status'],
    ));

    return (int) $pdo->lastInsertId();
}

function upsertBook(PDO $pdo, array $book): int
{
    $bookStmt = $pdo->prepare(
        'SELECT book_id FROM books WHERE isbn = :isbn LIMIT 1'
    );
    $bookStmt->execute(array('isbn' => $book['isbn']));
    $existingBook = $bookStmt->fetch();

    if ($existingBook) {
        $bookId = (int) $existingBook['book_id'];
        $updateBook = $pdo->prepare(
            'UPDATE books SET title = :title, author = :author, cover_image = :cover_image, status = :status WHERE book_id = :book_id'
        );
        $updateBook->execute(array(
            'title' => $book['title'],
            'author' => $book['author'],
            'cover_image' => $book['cover_image'],
            'status' => 'Available',
            'book_id' => $bookId,
        ));

        return $bookId;
    }

    $insertBook = $pdo->prepare(
        'INSERT INTO books (isbn, title, author, cover_image, status)
         VALUES (:isbn, :title, :author, :cover_image, :status)'
    );
    $insertBook->execute(array(
        'isbn' => $book['isbn'],
        'title' => $book['title'],
        'author' => $book['author'],
        'cover_image' => $book['cover_image'],
        'status' => 'Available',
    ));

    return (int) $pdo->lastInsertId();
}

function upsertInventory(PDO $pdo, int $storeId, int $bookId, array $book): void
{
    $inventoryStmt = $pdo->prepare(
        'SELECT inventory_id FROM store_inventory WHERE store_id = :store_id AND book_id = :book_id LIMIT 1'
    );
    $inventoryStmt->execute(array('store_id' => $storeId, 'book_id' => $bookId));
    $existingInventory = $inventoryStmt->fetch();

    if ($existingInventory) {
        $updateInventory = $pdo->prepare(
            'UPDATE store_inventory
             SET `condition` = :condition, price = :price, quantity = :quantity
             WHERE inventory_id = :inventory_id'
        );
        $updateInventory->execute(array(
            'condition' => $book['condition'],
            'price' => $book['price'],
            'quantity' => $book['quantity'],
            'inventory_id' => (int) $existingInventory['inventory_id'],
        ));

        return;
    }

    $insertInventory = $pdo->prepare(
        'INSERT INTO store_inventory (store_id, book_id, `condition`, price, quantity)
         VALUES (:store_id, :book_id, :condition, :price, :quantity)'
    );
    $insertInventory->execute(array(
        'store_id' => $storeId,
        'book_id' => $bookId,
        'condition' => $book['condition'],
        'price' => $book['price'],
        'quantity' => $book['quantity'],
    ));
}

$allCatalog = array_merge($coreCatalog, $extraCatalog);
$byIsbn = array();
foreach ($allCatalog as $b) {
    $byIsbn[$b['isbn']] = $b;
}

$demoPassword = 'Owner123!';

$shops = array(
    array(
        'owner' => array(
            'username' => 'owner_alexandria',
            'email' => 'alexandria.owner@ibrcn.demo',
            'password' => $demoPassword,
            'role' => 'Owner',
        ),
        'store' => array(
            'name' => 'Alexandria Corner Books',
            'address' => '26 Saad Zaghloul St, Alexandria',
            'region' => 'Alexandria',
            'latitude' => 31.2001,
            'longitude' => 29.9187,
            'status' => 'Approved',
        ),
        'isbns' => array('9780000000010', '9780000000027', '9780000000034', '9780000000041', '9780000000058'),
    ),
    array(
        'owner' => array(
            'username' => 'owner_mansoura',
            'email' => 'mansoura.owner@ibrcn.demo',
            'password' => $demoPassword,
            'role' => 'Owner',
        ),
        'store' => array(
            'name' => 'Mansoura Readers Loft',
            'address' => 'El Gomhouria St, Mansoura',
            'region' => 'Dakahlia',
            'latitude' => 31.0364,
            'longitude' => 31.3807,
            'status' => 'Approved',
        ),
        'isbns' => array('9780000000034', '9780000000041', '9780000000058', '9780000000065', '9780000000072'),
    ),
    array(
        'owner' => array(
            'username' => 'owner_hurghada',
            'email' => 'hurghada.owner@ibrcn.demo',
            'password' => $demoPassword,
            'role' => 'Owner',
        ),
        'store' => array(
            'name' => 'Red Sea Book Nook',
            'address' => 'Sheraton Rd, Hurghada',
            'region' => 'Red Sea',
            'latitude' => 27.2579,
            'longitude' => 33.8116,
            'status' => 'Approved',
        ),
        'isbns' => array(
            '9780000000089',
            '9780000000096',
            '9780000000102',
            '9783000001001',
            '9783000001002',
            '9783000001003',
            '9783000001004',
            '9783000001005',
        ),
    ),
    array(
        'owner' => array(
            'username' => 'owner_giza',
            'email' => 'giza.owner@ibrcn.demo',
            'password' => $demoPassword,
            'role' => 'Owner',
        ),
        'store' => array(
            'name' => 'Giza Plateau Books',
            'address' => 'Haram St, Giza',
            'region' => 'Giza',
            'latitude' => 30.0131,
            'longitude' => 31.2089,
            'status' => 'Approved',
        ),
        'isbns' => array('9780000000010', '9780000000065', '9780000000089', '9780000000102', '9783000001001'),
    ),
);

$pdo->beginTransaction();

try {
    foreach ($shops as $shop) {
        $ownerId = firstOrCreateUser($pdo, $shop['owner']);
        $storeId = firstOrCreateStore($pdo, $shop['store'], $ownerId);

        foreach (pickBooks($shop['isbns'], $byIsbn) as $book) {
            $bookId = upsertBook($pdo, $book);
            upsertInventory($pdo, $storeId, $bookId, $book);
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "Demo owners seed complete.\n\n";
echo "All demo accounts use password: {$demoPassword}\n\n";
foreach ($shops as $shop) {
    echo "- {$shop['owner']['username']} / {$shop['owner']['email']} → {$shop['store']['name']} ({$shop['store']['region']})\n";
}
echo "\nCovers use files under public/img (e.g. ./img/book-1.png … ./img/book-10.png, book3.png, book5.png, book6.png, book7.png, bookx.jpeg).\n";
