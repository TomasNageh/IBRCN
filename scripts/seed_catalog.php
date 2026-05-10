<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/patterns/DB.php';

$pdo = DB::getInstance();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$seedOwner = array(
    'username' => 'ibrcn_owner',
    'email' => 'owner@ibrcn.local',
    'password' => 'IBRCN123!',
    'role' => 'Owner',
);

$storeSeed = array(
    'name' => 'IBRCN Central',
    'address' => 'Cairo, Egypt',
    'region' => 'Cairo',
    'latitude' => 30.0444,
    'longitude' => 31.2357,
    'status' => 'Approved',
);

$catalog = array(
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

$pdo->beginTransaction();

$ownerId = firstOrCreateUser($pdo, $seedOwner);
$storeId = firstOrCreateStore($pdo, $storeSeed, $ownerId);

foreach ($catalog as $book) {
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
    } else {
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
        $bookId = (int) $pdo->lastInsertId();
    }

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
    } else {
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
}

$pdo->commit();

echo "Seed complete.\n";
echo "Owner username: {$seedOwner['username']}\n";
echo "Owner password: {$seedOwner['password']}\n";
echo "Store: {$storeSeed['name']}\n";