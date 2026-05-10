<?php
/**
 * Add more books to the catalog using existing images
 * Run with: php add_more_books.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/patterns/DB.php';

$db = DB::getInstance();

// Books to add - using existing images in rotation
$booksToAdd = array(
    array(
        'isbn' => '9780062316097',
        'title' => 'Educated',
        'author' => 'Tara Westover',
        'cover_image' => './img/book-1.png'
    ),
    array(
        'isbn' => '9780143039211',
        'title' => 'Mindset: The New Psychology of Success',
        'author' => 'Carol S. Dweck',
        'cover_image' => './img/book-2.png'
    ),
    array(
        'isbn' => '9781491927281',
        'title' => 'Dare to Lead: Brave Work to Change Your Life',
        'author' => 'Brené Brown',
        'cover_image' => './img/book-3.png'
    ),
    array(
        'isbn' => '9781492074505',
        'title' => 'The Midnight Library',
        'author' => 'Matt Haig',
        'cover_image' => './img/book-4.png'
    ),
    array(
        'isbn' => '9780451490032',
        'title' => 'The Power of Now',
        'author' => 'Eckhart Tolle',
        'cover_image' => './img/book-5.png'
    ),
    array(
        'isbn' => '9780374533557',
        'title' => 'Quiet: The Power of Introverts',
        'author' => 'Susan Cain',
        'cover_image' => './img/book-6.png'
    ),
    array(
        'isbn' => '9780544002685',
        'title' => 'A Brief History of Time',
        'author' => 'Stephen Hawking',
        'cover_image' => './img/book-7.png'
    ),
    array(
        'isbn' => '9780735619678',
        'title' => 'Educated',
        'author' => 'Tara Westover',
        'cover_image' => './img/book-8.png'
    ),
    array(
        'isbn' => '9780062407597',
        'title' => 'Becoming',
        'author' => 'Michelle Obama',
        'cover_image' => './img/book-9.png'
    ),
    array(
        'isbn' => '9780062269935',
        'title' => 'Unbroken: A World War II Story',
        'author' => 'Laura Hillenbrand',
        'cover_image' => './img/book-10.png'
    ),
);

$added = 0;
$skipped = 0;

foreach ($booksToAdd as $book) {
    // Check if ISBN already exists
    $checkStmt = $db->prepare("SELECT book_id FROM books WHERE isbn = :isbn LIMIT 1");
    $checkStmt->execute(array('isbn' => $book['isbn']));
    
    if ($checkStmt->fetch()) {
        echo "⊘ Skipped: {$book['title']} (ISBN {$book['isbn']}) - already exists\n";
        $skipped++;
        continue;
    }
    
    // Insert the book
    $stmt = $db->prepare(
        "INSERT INTO books (isbn, title, author, cover_image, status)
         VALUES (:isbn, :title, :author, :cover_image, 'Available')"
    );
    
    $result = $stmt->execute(array(
        'isbn' => $book['isbn'],
        'title' => $book['title'],
        'author' => $book['author'],
        'cover_image' => $book['cover_image'],
    ));
    
    if ($result) {
        echo "✓ Added: {$book['title']} by {$book['author']}\n";
        $added++;
    } else {
        echo "✗ Failed: {$book['title']}\n";
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "Summary: Added {$added} books, Skipped {$skipped}\n";
echo "Total books in catalog: " . $db->query("SELECT COUNT(*) FROM books")->fetchColumn() . "\n";
