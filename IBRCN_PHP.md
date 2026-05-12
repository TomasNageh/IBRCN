# IBRCN PHP Project — Complete Learning Guide
### From Zero to Full-Stack: Lessons 1–5

---

## Table of Contents

1. [Lesson 1 — The Big Picture: How Does a Website Work?](#lesson-1)
2. [Lesson 2 — PHP Syntax: Reading the Actual Code](#lesson-2)
3. [Lesson 3 — The Database Layer: MySQL, SQL, and PDO](#lesson-3)
4. [Lesson 4 — Authentication, Sessions & Security](#lesson-4)
5. [Lesson 5 — The Shopping Cart & Checkout System](#lesson-5)

---

<a name="lesson-1"></a>
## Lesson 1 — The Big Picture: How Does a Website Work?

Before touching a single line of code, you need to understand what happens when someone opens a website.

---

### 1.1 The Client-Server Model

Imagine a restaurant:

- **You** (the customer) sit at a table and ask for food → this is the **client** (your browser)
- **The kitchen** prepares the food → this is the **server**
- **The waiter** carries requests back and forth → this is **HTTP** (HyperText Transfer Protocol)

When you type `google.com` in your browser:

```
Your Browser  ──── HTTP Request ────►  Google's Server
              ◄─── HTTP Response ────  Google's Server
```

The server sends back HTML, CSS, and JavaScript — your browser reads them and draws the page you see.

---

### 1.2 Static vs Dynamic Websites

| Type | Description |
|------|-------------|
| **Static** | The server just sends a file that was already written. Every user sees the exact same thing. Like a PDF. |
| **Dynamic** | The server runs code first, builds the page based on who's asking, then sends it. Facebook shows *your* feed, not someone else's. IBRCN is dynamic. |

---

### 1.3 Where PHP Fits In

```
Browser                     Server (XAMPP/Apache)
  │                               │
  │── GET /index.php ────────────►│
  │                               │  PHP runs here
  │                               │  → talks to MySQL
  │                               │  → builds HTML
  │◄── HTML page ─────────────────│
```

- **PHP** is a language that runs on the **server**, not in the browser. It can read from a database, check passwords, and build different HTML depending on who is logged in.
- **MySQL** is the database — a place to store and retrieve data (users, books, orders) in organized tables.

---

### 1.4 What is XAMPP?

XAMPP turns your own computer into a server so you can test the website locally without needing the internet.

| Letter | What it is | What it does |
|--------|-----------|--------------|
| **A** | Apache | The web server — listens for browser requests |
| **M** | MySQL/MariaDB | The database |
| **P** | PHP | The language that runs your code |

When XAMPP is running and you visit `http://localhost/ibrcn/public/index.php`, Apache intercepts that, runs the PHP file, and sends back HTML to your browser.

---

### 1.5 The Folder Structure of IBRCN -> MVC Model Controler View

```
ibrcn/
│
├── public/          ← The FRONT DOOR. Only these files talk to the browser.
│   ├── index.php       (homepage)
│   ├── login.php       (login page)
│   ├── cart.php        (shopping cart)
│   └── ...
│
├── app/             ← The BACK ROOMS. The actual brain of the app.
│   ├── models/         (talks to the database)
│   ├── views/          (HTML templates)
│   ├── controllers/    (coordinates everything)
│   ├── services/       (helper workflows)
│   └── patterns/       (design patterns: DB, Observer, Strategy)
│
├── config/          ← Settings (database password, mail config)
├── storage/         ← Files saved by the app (emails, notifications)
└── vendor/          ← External libraries installed via Composer
```

> **Golden rule:** A browser can only reach files in `public/`. Everything else is hidden behind the scenes.

---

### 1.6 The MVC Pattern — The Most Important Concept

Your entire project follows a pattern called **MVC: Model, View, Controller**. Think of it as dividing responsibilities:

```
Browser Request
      │
      ▼
┌─────────────┐
│ CONTROLLER  │  ← The manager. Receives the request, decides what to do.
└──────┬──────┘
       │ asks for data          │ sends data to
       ▼                        ▼
┌─────────────┐          ┌─────────────┐
│    MODEL    │          │    VIEW     │
│             │          │             │
│ Talks to    │          │ HTML tem-   │
│ the database│          │ plate. Just │
│ Pure data.  │          │ displays.   │
└─────────────┘          └─────────────┘
```

**A concrete example — the homepage:**

1. Browser requests `public/index.php`
2. `index.php` creates a `HomeController`
3. `HomeController` asks the `Book` model: "give me featured books"
4. `Book` model runs SQL against MySQL, returns data
5. `HomeController` passes that data to `app/views/reader/home.php`
6. The view renders HTML with the books embedded
7. Apache sends that HTML back to the browser

```php
// public/index.php (simplified)
require_once '../app/bootstrap.php';
require_once '../app/models/Book.php';
require_once '../app/controllers/HomeController.php';

$database = DB::getInstance();
$controller = new HomeController(new Book($database));
$controller->index();
```

```php
// app/controllers/HomeController.php (simplified)
class HomeController
{
    public function __construct(private Book $bookModel) {}

    public function index(): void
    {
        $books = $this->bookModel->getFeaturedBooks();
        require '../app/views/reader/home.php';
    }
}
```

```php
// app/models/Book.php (simplified)
class Book
{
    public function __construct(private PDO $db) {}

    public function getFeaturedBooks(): array
    {
        $sql = "SELECT b.title, b.author, MIN(i.price) AS price
                FROM books b
                JOIN store_inventory i ON b.book_id = i.book_id
                WHERE i.quantity > 0
                GROUP BY b.book_id
                LIMIT 10";

        $statement = $this->db->query($sql);
        return $statement->fetchAll();
    }
}
```

---

### 1.7 What is PDO?

PDO (PHP Data Objects) is PHP's way of talking to MySQL **safely** using prepared statements:

```php
// DANGEROUS — never do this:
$sql = "SELECT * FROM users WHERE username = '$username'";

// SAFE with PDO prepared statements:
$stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
$stmt->execute(['username' => $username]);
$user = $stmt->fetch();
```

PDO separates the SQL structure from the data. MySQL receives them separately and can never be tricked.

---

### 1.8 The Database — Tables and Relationships

```
users               books               store_inventory
─────────────       ──────────────      ───────────────────
user_id  (PK)       book_id   (PK)      inventory_id (PK)
username            isbn                store_id  ──► stores.store_id
email               title               book_id   ──► books.book_id
password_hash       author              price
role                cover_image         quantity
                    status              hold_quantity

orders              order_items         stores
──────────          ───────────         ──────────────────
order_id  (PK)      order_item_id (PK)  store_id   (PK)
reader_id ─► users  order_id ──► orders owner_id ──► users
store_id  ─► stores inventory_id ──► si name
status              price               address
total_amount                            status
```

The arrows (→) are **foreign keys** — they link rows in one table to rows in another. This is called a **relational database**.

---

### Lesson 1 Summary

| Concept | What it means |
|---------|--------------|
| Client-Server | Browser asks, server answers |
| HTTP | The language browsers and servers use to communicate |
| PHP | Code that runs on the server, builds pages dynamically |
| MySQL | Database that stores all the app's data |
| XAMPP | Local server bundle for development |
| MVC | Model (data), View (HTML), Controller (coordinator) |
| PDO | PHP's safe way to talk to MySQL |
| Foreign Keys | Links between database tables |

---

<a name="lesson-2"></a>
## Lesson 2 — PHP Syntax: Reading the Actual Code

Now we'll learn PHP syntax using real code from your project.

---

### 2.1 How PHP Files Work

Every PHP file starts with `<?php`. That tag tells Apache "start running PHP code from here".

```php
<?php
// This is a comment — PHP ignores it
echo "Hello World";  // echo = print something to the browser
```

View files mix PHP and HTML:

```php
<!-- app/views/reader/home.php -->
<h3>
<?php echo htmlspecialchars($book['title']); ?>
</h3>
```

---

### 2.2 Variables

Variables in PHP always start with `$`:

```php   loosly typed 
<?php
$name      = "Tomas";    // string (text)
$age       = 17;         // integer (whole number)
$price     = 120.50;     // float (decimal number)
$isLoggedIn = true;      // boolean (true or false)
$nothing   = null;       // null (no value)
```

From the project's `config/db_config.php`:

```php
return array(
    'host' => '127.0.0.1',
    'port' => '3306',
    'db'   => 'ibrcn',
    'user' => 'root',
    'pass' => '',
);
```

---

### 2.3 Arrays — The Most Used Thing in PHP

**Indexed array** — numbered from 0:

```php
$genres = ['Fiction', 'Science', 'History'];
echo $genres[0];  // Fiction
echo $genres[2];  // History
```

**Associative array** — named keys (like a dictionary):

```php
$book = [
    'title'  => 'The Midnight Library',
    'author' => 'Matt Haig',
    'price'  => 120.00,
];
echo $book['title'];  // The Midnight Library
```

From `scripts/seed_catalog.php` — an **array of arrays**:

```php
$catalog = array(
    array(
        'isbn'        => '9780000000010',
        'title'       => 'The Midnight Library',
        'author'      => 'Matt Haig',
        'cover_image' => './img/book-1.png',
        'price'       => 120.00,
        'quantity'    => 8
    ),
    array(
        'isbn'        => '9780000000027',
        'title'       => 'Atomic Habits',
        'author'      => 'James Clear',
        'price'       => 150.00,
        'quantity'    => 6
    ),
);
```

---

### 2.4 If / Else — Making Decisions

```php
$role = 'Reader';

if ($role === 'Admin') {
    echo "Go to admin dashboard";
} elseif ($role === 'Owner') {
    echo "Go to owner dashboard";
} else {
    echo "Go to reader homepage";
}
```

> Always use `===` (triple equals) — checks both **value AND type**.

From `public/index.php` — redirecting logged-in users:

```php
if (isset($_SESSION["user"], $_SESSION["role"])) {

    $redirectByRole = array(
        "Owner" => "owner.php",
        "Admin" => "admin.php",
    );

    $target = $redirectByRole[(string) $_SESSION["role"]] ?? null;

    if ($target !== null) {
        header("Location: " . $target);
        exit;
    }
}
```

**Key concepts:**
- `isset($x)` — checks if variable exists and is not null
- `$_SESSION` — a special PHP array that remembers data between pages
- `??` (null coalescing) — returns a default if the value doesn't exist

---

### 2.5 Loops — Doing Things Repeatedly

**`foreach`** — the most common loop:

```php
$books = ['Atomic Habits', 'Sapiens', 'Ikigai'];
foreach ($books as $book) {
    echo $book . "<br>";
}
```

With a key:

```php
$book = ['title' => 'Sapiens', 'author' => 'Harari', 'price' => 175];
foreach ($book as $key => $value) {
    echo "$key: $value <br>";
}
```

From `app/views/reader/home.php`:

```php
<?php foreach ($books as $book): ?>
    <div class="swiper-slide box">
        <h3><?php echo htmlspecialchars($book['title']); ?></h3>
        <div class="price"><?php echo htmlspecialchars($book['price_egp_formatted']); ?></div>
    </div>
<?php endforeach; ?>
```

> `htmlspecialchars()` converts dangerous characters to safe HTML. **Always use this when displaying user data.**

---

### 2.6 Functions

```php
function addNumbers(int $a, int $b): int
{
    return $a + $b;
}

$result = addNumbers(3, 5);
echo $result;  // 8
```

From `app/models/Book.php` — a real function:

```php
private function attachListingPricePresentationFields(array $listingRows): array
{
    foreach ($listingRows as &$listingRow) {
        $priceValue = (float) $listingRow['price'];
        $listingRow['price_value'] = $priceValue;
        $listingRow['price_egp_formatted'] = 'EGP ' . number_format($priceValue, 2);
    }
    unset($listingRow);  // important: break the reference after &$ loop

    return $listingRows;
}
```

**Key concepts:**
- `(float)` — a **cast** that forces a value to a specific type
- `&$listingRow` — the `&` means "reference" — changes affect the original array

---

### 2.7 Classes and Objects — The Heart of This Project

A **class** is a blueprint. An **object** is a thing built from that blueprint.

```php
class Book
{
    public string $title;
    public string $author;
    public float $price;

    public function __construct(string $title, string $author, float $price)
    {
        $this->title  = $title;
        $this->author = $author;
        $this->price  = $price;
    }

    public function getFormattedPrice(): string
    {
        return 'EGP '.number_format($this->price, 2);
    }
}

$book = new Book('Atomic Habits', 'James Clear', 150.00);
echo $book->title;                 // Atomic Habits
echo $book->getFormattedPrice();   // EGP 150.00
```

- `$this` — inside a class, `$this` refers to the current object.
- Visibility: `public` (anywhere), `private` (this class only), `protected` (this class + children)

---

### 2.8 Constructor Property Promotion

Your project uses a modern PHP shortcut:

```php
// Old way (verbose):
class HomeController
{
    private Book $bookModel;
    public function __construct(Book $bookModel)
    {
        $this->bookModel = $bookModel;
    }
}

// Modern way (what your project uses):
class HomeController
{
    public function __construct(private Book $bookModel)
    {
        // PHP automatically creates $this->bookModel = $bookModel
    }
}
```

---

### 2.9 Require and Include — Connecting Files

```php
require_once '../app/bootstrap.php';      // load this file (crash if missing)
require_once '../app/models/Book.php';
require_once '../app/controllers/HomeController.php';
```

- `require_once` — loads the file exactly **once**. If the file is missing, PHP stops with a fatal error.
- `__DIR__` — a PHP constant meaning "the folder this file is in".

---

### 2.10 The $_POST and $_GET Superglobals

**`$_GET`** — data from the URL:

```
http://localhost/ibrcn/public/book-stores.php?book_id=5
```

```php
$bookId = (int) ($_GET['book_id'] ?? 0);  // → 5
```

**`$_POST`** — data from a form submission (not visible in URL):

```php
$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
```

From `app/controllers/AuthController.php` — the real login handler:

```php
public function login(): void
{
    if (!isset($_POST['username']) || !isset($_POST['password'])) {
        $this->showLogin('Please provide both username and password.');
        return;
    }

    $username = trim((string) $_POST['username']);
    $password = (string) $_POST['password'];

    $user = $this->userModel->findByUsername($username);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $errorMessage = 'Invalid username or password.';
        require '../app/views/auth/login.php';
        return;
    }

    session_regenerate_id(true);
    $_SESSION['user']    = $user['username'];
    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['role']    = $user['role'];

    RoleRedirector::redirectByRole((string) $user['role']);
}
```

> `password_verify($password, $hash)` — compares a plain password against a stored bcrypt hash safely.

---

### 2.11 Tracing One Full Request

```
Step 1: Browser sends POST /login.php with username="tomas" and password="mypassword"

Step 2: public/login.php runs
        ├── loads bootstrap.php
        ├── loads User.php, AuthController.php
        └── calls $controller->login()

Step 3: AuthController::login() runs
        ├── reads $_POST['username'] → "tomas"
        ├── reads $_POST['password'] → "mypassword"
        └── calls $this->userModel->findByUsername("tomas")

Step 4: User::findByUsername() runs SQL:
        SELECT user_id, username, password_hash, role
        FROM users WHERE username = 'tomas' LIMIT 1
        → returns array: ['user_id'=>1, 'role'=>'Reader', ...]

Step 5: Back in AuthController::login()
        ├── password_verify("mypassword", "$2y$10$...hash...") → true
        ├── $_SESSION['user'] = 'tomas'
        ├── $_SESSION['role'] = 'Reader'
        └── header("Location: reader.php") → redirect

Step 6: Browser navigates to reader.php
```

---

### Lesson 2 Summary

| Concept | What it means |
|---------|--------------|
| `<?php` | Starts PHP code |
| Variables `$x` | Store values |
| Arrays `[]` | Store multiple values |
| `if/else` | Make decisions |
| `foreach` | Loop through arrays |
| Functions | Reusable code blocks |
| Classes & Objects | Blueprints and instances |
| `$this` | Refers to current object |
| `require_once` | Load another PHP file |
| `$_POST / $_GET` | Receive form/URL data |
| `$_SESSION` | Remember data between pages |
| `password_verify()` | Safely check passwords |

---

<a name="lesson-3"></a>
## Lesson 3 — The Database Layer: MySQL, SQL, and PDO

This is where data lives. Every book, user, order, and club in IBRCN is stored in MySQL.

---

### 3.1 What is a Database?

Think of MySQL as a collection of spreadsheets that are linked together. Each spreadsheet is a **table**. Each row is one **record**. Each column is one piece of information.

```
Table: users
┌─────────┬──────────┬───────────────────┬────────────────────────────┬─────────┐
│ user_id │ username │ email             │ password_hash              │ role    │
├─────────┼──────────┼───────────────────┼────────────────────────────┼─────────┤
│ 1       │ tomas    │ tomas@mail.com    │ $2y$10$abc123...           │ Reader  │
│ 2       │ mina     │ mina@store.com    │ $2y$10$xyz789...           │ Owner   │
│ 3       │ admin1   │ admin@ibrcn.com   │ $2y$10$def456...           │ Admin   │
└─────────┴──────────┴───────────────────┴────────────────────────────┴─────────┘
```

- **Primary Key (PK)** — the unique identifier for each row. Auto-incremented by MySQL.
- **Foreign Key (FK)** — a column that points to the primary key of another table.

---

### 3.2 The Core Tables of IBRCN

```
USERS ──────────────────────────────────────────────────────
  user_id | username | email | password_hash | role
       │
       │ one user can own many stores
       ▼
STORES ─────────────────────────────────────────────────────
  store_id | owner_id(FK) | name | address | status
       │
       │ one store has many inventory rows
       ▼
STORE_INVENTORY ─────────────────────────────────────────────
  inventory_id | store_id(FK) | book_id(FK) | price | qty
       │                              │
       │                              ▼
ORDERS ─────────────────         BOOKS ──────────────────────
  order_id                         book_id | isbn | title
  reader_id(FK)                    author  | cover_image
  store_id(FK)
  status                 ──────────────────────────────────
  total_amount               ORDER_ITEMS
       │                     order_item_id | order_id(FK)
       │ one order has       inventory_id(FK) | price
       └──────── many items
```

---

### 3.3 SQL — The Language of Databases

SQL (Structured Query Language) is how you talk to MySQL. The four core operations — **CRUD**:

| Operation | SQL Keyword | What it does |
|-----------|------------|--------------|
| **C**reate | `INSERT` | Add a new row |
| **R**ead | `SELECT` | Get rows |
| **U**pdate | `UPDATE` | Change a row |
| **D**elete | `DELETE` | Remove a row |

**SELECT — Reading Data:**

```sql
SELECT * FROM users;
SELECT username, email, role FROM users;
SELECT * FROM users WHERE role = 'Reader';
SELECT * FROM books ORDER BY title ASC LIMIT 10;
```

From `app/models/User.php`:

```php
public function findByUsername(string $username): ?array
{
    $stmt = $this->db->prepare(
        "SELECT user_id, username, email, password_hash, role
         FROM users
         WHERE username = :username
         LIMIT 1"
    );
    $stmt->execute(array('username' => $username));
    $user = $stmt->fetch();

    return $user ?: null;
}
```

**INSERT — Adding Data:**

```sql
INSERT INTO users (username, email, password_hash, role)
VALUES ('fatima', 'fatima@mail.com', '$2y$10$hash...', 'Reader');
```

From `app/models/User.php`:

```php
public function registerUser(string $username, string $email,
                             string $password, string $role): bool|int
{
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, email, password_hash, role)
             VALUES (:username, :email, :password, :role)"
        );
        $stmt->execute(array(
            'username' => $username,
            'email'    => $email,
            'password' => $hashedPassword,
            'role'     => $role,
        ));

        return (int) $this->db->lastInsertId();

    } catch (PDOException $e) {
        return false;
    }
}
```

> `lastInsertId()` — after an INSERT, MySQL tells you what ID it gave the new row.

**UPDATE — Changing Data:**

```sql
UPDATE orders SET status = 'Ready' WHERE order_id = 42;
```

From `app/models/Order.php`:

```php
public function markReady(int $ownerId, int $orderId): array
{
    $statement = $this->databaseConnection->prepare(
        "UPDATE orders o
         INNER JOIN stores s ON s.store_id = o.store_id
         SET o.status = 'Ready'
         WHERE o.order_id = :order_id
         AND s.owner_id = :owner_id
         AND o.status = 'Placed'"
    );
    $statement->execute(array('order_id' => $orderId, 'owner_id' => $ownerId));

    if ($statement->rowCount() === 0) {
        return array('ok' => false, 'message' => 'Order could not be marked ready.');
    }

    return array('ok' => true, 'message' => 'Order marked as ready.');
}
```

**DELETE — Removing Data:**

```php
// app/models/CartRepository.php
public function clear(int $readerId): void
{
    $statement = $this->databaseConnection->prepare(
        'DELETE FROM cart_items WHERE reader_id = :rid'
    );
    $statement->execute(array('rid' => $readerId));
}
```

---

### 3.4 JOIN — Combining Tables

**INNER JOIN** — only returns rows with a match in BOTH tables:

```sql
SELECT o.order_id, o.status, o.total_amount, s.name AS store_name
FROM orders o
INNER JOIN stores s ON s.store_id = o.store_id
WHERE o.reader_id = 1;
```

**LEFT JOIN** — returns ALL rows from the left table, even with no match on the right:

```sql
SELECT rc.name, cr.book_title
FROM reading_clubs rc
LEFT JOIN club_member_current_read cr
       ON cr.club_id = rc.club_id AND cr.user_id = 1;
```

From `app/models/Book.php` — finding all stores with a specific book in stock:

```php
public function findStoresWithStockForBook(int $bookId): array
{
    $stmt = $this->databaseConnection->prepare(
        "SELECT s.store_id, s.name, s.address, s.region,
                i.inventory_id, i.condition, i.price, i.quantity
         FROM store_inventory i
         INNER JOIN stores s ON s.store_id = i.store_id
         WHERE i.book_id = :book_id
           AND (i.quantity - COALESCE(i.hold_quantity, 0)) > 0
           AND s.status = 'Approved'"
    );
    $stmt->execute(array('book_id' => $bookId));
    return $stmt->fetchAll();
}
```

> `COALESCE(value, fallback)` — returns the first non-null value. If `hold_quantity` is NULL, use 0 instead.

---

### 3.5 The Singleton Pattern — One Database Connection

Your project uses a **Singleton** design pattern so the database connection is created **exactly once** and reused everywhere.

```php
// app/patterns/DB.php

class DB
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {

            $config = require __DIR__ . '/../../config/db_config.php';

            $dsn = "mysql:host={$config['host']}"
                 . ";port={$config['port']}"
                 . ";dbname={$config['db']}"
                 . ";charset=utf8mb4";

            self::$instance = new PDO($dsn, $config['user'], $config['pass']);

            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }

        return self::$instance;
    }
}
```

Usage — same connection returned every time:

```php
$database = DB::getInstance();   // first call → creates connection
$database = DB::getInstance();   // second call → returns SAME connection

$bookModel = new Book($database);
$userModel = new User($database);
// Both models share the exact same PDO connection
```

---

### 3.6 PDO Prepared Statements — Step by Step

Every database interaction follows the same three steps:

```php
// Step 1: PREPARE — send the SQL structure to MySQL (with placeholders)
$stmt = $db->prepare("SELECT * FROM books WHERE isbn = :isbn LIMIT 1");

// Step 2: EXECUTE — send the actual values separately
$stmt->execute(array('isbn' => '9780000000010'));

// Step 3: FETCH — get the results back
$book = $stmt->fetch();        // one row
$books = $stmt->fetchAll();    // all rows
```

SQL injection is impossible because MySQL receives the structure and data in two separate steps.

---

### 3.7 Transactions — All or Nothing

When multiple SQL statements must ALL succeed or ALL fail together:

```php
// app/models/Order.php

public function checkoutCart(int $userId, array $cartItems): array
{
    try {
        $this->databaseConnection->beginTransaction();   // START

        foreach ($groupedByStore as $storeGroup) {
            $orderId = $this->createOrderHeader($userId, $storeGroup['store_id'], $storeGroup['total']);
            $this->reserveInventory($line['inventory_id'], $line['quantity']);
            $this->createOrderItem($orderId, $line);
        }

        $this->databaseConnection->commit();             // SAVE permanently

        return array('ok' => true, 'orders' => $createdOrders);

    } catch (Throwable $exception) {
        $this->databaseConnection->rollBack();           // UNDO everything

        return array('ok' => false, 'message' => 'Could not complete checkout.');
    }
}
```

**Why transactions matter:** Without one, if step 3 fails, steps 1 and 2 remain in the database — creating a phantom order with no items and reduced stock for nothing.

---

### 3.8 A Complete Model Walk-Through — getFeaturedBooks()

```php
public function getFeaturedBooks(): array
{
    $sql = "SELECT b.book_id,
                   b.title,
                   b.author,
                   b.isbn,
                   b.cover_image,
                   MIN(i.price) AS price   -- cheapest price across all stores
            FROM books b
            JOIN store_inventory i ON b.book_id = i.book_id
            WHERE (i.quantity - COALESCE(i.hold_quantity, 0)) > 0
            GROUP BY b.book_id, b.title, b.author, b.isbn, b.cover_image
            LIMIT 10";

    $statement = $this->db->query($sql);
    $rows = $statement->fetchAll();

    return $this->attachListingPricePresentationFields($rows);
}
```

- `MIN(i.price)` — if a book is in 3 stores at 120, 135, 150 → picks 120 (cheapest)
- `GROUP BY` — collapses multiple inventory rows into one row per book
- `AS price` — gives the result column a readable name

---

### 3.9 The Audit Log

Every important action is recorded. From `app/models/AuditLogRepository.php`:

```php
public function write(
    ?int    $actorId,
    string  $actorRole,
    string  $actionType,
    ?int    $affectedRecordId = null,
    ?string $affectedTable    = null
): void {
    $statement = $this->databaseConnection->prepare(
        'INSERT INTO audit_log
         (actor_id, actor_role, action_type, affected_record_id, affected_table)
         VALUES (:aid, :role, :act, :rec_id, :tbl)'
    );
    $statement->execute(array(
        'aid'    => $actorId,
        'role'   => $actorRole,
        'act'    => $actionType,
        'rec_id' => $affectedRecordId,
        'tbl'    => $affectedTable,
    ));
}
```

Called after login, registration, and checkout:

```php
(new AuditLogRepository(DB::getInstance()))->write(
    $user['user_id'], $user['role'], 'login_success', $user['user_id'], 'users'
);
```

---

### Lesson 3 Summary

| Concept | What it means |
|---------|--------------|
| Tables & Rows | Spreadsheet-like data storage in MySQL |
| Primary Key | Unique ID for each row |
| Foreign Key | Column linking to another table's primary key |
| SELECT | Read rows from a table |
| INSERT | Add a new row |
| UPDATE | Change an existing row |
| DELETE | Remove a row |
| JOIN | Combine data from multiple tables |
| Prepared Statements | Safe SQL with placeholders |
| Transactions | All-or-nothing group of SQL operations |
| Singleton Pattern | One shared database connection |
| Audit Log | Recording every important action |

---

<a name="lesson-4"></a>
## Lesson 4 — Authentication, Sessions & Security

This is one of the most critical parts of any web application.

---

### 4.1 The Problem Authentication Solves

HTTP is **stateless** — every request is completely independent. The server has no memory:

```
Request 1: "Hi, I'm Tomas, show me my orders"
Server: "OK here are your orders"

Request 2: "Show me my orders"
Server: "Who are you? I don't know you"
```

Sessions solve this.

---

### 4.2 How Sessions Work

A session is like a wristband at an event:

1. You show your ID at the door (login with username/password)
2. Staff check your ID is real (verify against database)
3. They give you a wristband with a unique number (session ID)
4. Every time you enter a room, you show your wristband
5. Staff look up your wristband number to know who you are

```
Browser                          Server
  │── POST /login.php ──────────►│
  │   username=tomas              │  PHP verifies password
  │   password=mypassword         │  Creates session data
  │                               │
  │◄── Set-Cookie: PHPSESSID=abc  │  Sends session ID as cookie
  │                               │
  │── GET /reader.php ───────────►│
  │   Cookie: PHPSESSID=abc       │  PHP looks up session abc
  │◄── Reader homepage ───────────│  Serves personalized page
```

The session ID is stored as a cookie in the browser. The actual data stays on the server.

---

### 4.3 The Session Helper — Session.php

```php
// app/services/Session.php

final class Session
{
    public static function ensureStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['user'], $_SESSION['role']);
    }

    public static function destroy(): void
    {
        self::ensureStarted();

        $_SESSION = array();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
    }
}
```

`static` methods are called on the class itself, not an object:

```php
Session::ensureStarted();
Session::isAuthenticated();
Session::destroy();
```

---

### 4.4 Password Security — Hashing

**Never store passwords as plain text.** Hashing is a one-way transformation:

```
"mypassword123"  →  password_hash()  →  "$2y$10$N9qo8uLOickgx2ZMRZoMye..."
```

You cannot go backwards. Even developers can't see your password.

```php
// Storing a password (registration):
$hashedPassword = password_hash("mypassword123", PASSWORD_BCRYPT);

// Checking a password (login):
$isCorrect = password_verify("mypassword123", "$2y$10$N9qo8uLOickgx2ZMRZoMye...");
// Returns true if they match
```

BCRYPT adds a random **salt** — two users with the same password get different hashes:

```
User A: "password123" → "$2y$10$ABCsalt...hash1..."
User B: "password123" → "$2y$10$XYZsalt...hash2..."
```

---

### 4.5 The Complete Login Flow

```php
// app/controllers/AuthController.php

public function login(): void
{
    Session::ensureStarted();

    if (Session::isAuthenticated()) {
        RoleRedirector::redirectByRole((string) $_SESSION['role']);
    }

    // ── VALIDATION ──────────────────────────────────────────

    if (!isset($_POST['username']) || !isset($_POST['password'])) {
        $this->showLogin('Please provide both username and password.');
        return;
    }

    $username = trim((string) $_POST['username']);
    $password = (string) $_POST['password'];

    // ── DATABASE LOOKUP ─────────────────────────────────────

    $user = $this->userModel->findByUsername($username);

    // ── PASSWORD CHECK ──────────────────────────────────────

    if (!$user || !password_verify($password, $user['password_hash'])) {
        // Vague message — don't reveal if username or password was wrong
        $errorMessage = 'Invalid username or password.';
        require __DIR__ . '/../views/auth/login.php';
        return;
    }

    // ── SESSION CREATION ────────────────────────────────────

    session_regenerate_id(true);   // Security: get a new session ID

    $_SESSION['user']    = $user['username'];
    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['role']    = $user['role'];

    // ── AUDIT LOG + REDIRECT ─────────────────────────────────

    $this->auditLoginSuccess((int) $user['user_id'], (string) $user['role']);
    $this->hydrateCartForReader((int) $user['user_id'], (string) $user['role']);

    RoleRedirector::redirectByRole((string) $user['role']);
}
```

---

### 4.6 Role-Based Redirection

```php
// app/services/RoleRedirector.php

final class RoleRedirector
{
    public static function redirectByRole(string $role): void
    {
        if ($role === 'Owner') { header('Location: owner.php'); exit; }
        if ($role === 'Admin') { header('Location: admin.php'); exit; }

        // Default: Reader
        header('Location: reader.php');
        exit;
    }
}
```

> `header('Location: ...')` — sends an HTTP redirect. `exit` — stops PHP immediately after. **Critical — never omit `exit` after a redirect.**

---

### 4.7 Protecting Pages — AuthMiddleware

The bouncer of your application. Every protected page calls `AuthMiddleware` at the very top.

```php
// app/services/AuthMiddleware.php

class AuthMiddleware
{
    // Any logged-in user
    public static function requireAuthenticated(): void
    {
        Session::ensureStarted();
        if (!Session::isAuthenticated()) {
            header('Location: login.php');
            exit;
        }
    }

    // Must NOT be logged in (login/register pages)
    public static function requireGuest(): void
    {
        Session::ensureStarted();
        if (Session::isAuthenticated()) {
            RoleRedirector::redirectByRole((string) $_SESSION['role']);
        }
    }

    // Must have a specific role
    public static function requireRole(array|string $allowedRoles): void
    {
        Session::ensureStarted();

        if (!Session::isAuthenticated()) {
            header('Location: login.php');
            exit;
        }

        $roles = is_array($allowedRoles) ? $allowedRoles : array($allowedRoles);

        if (!in_array((string) $_SESSION['role'], $roles, true)) {
            RoleRedirector::redirectByRole((string) $_SESSION['role']);
        }
    }
}
```

**How every protected page uses it:**

```php
// public/admin.php
AuthMiddleware::requireRole('Admin');
// → Not logged in: redirect to login.php
// → Wrong role: redirect to their dashboard
// → Admin: continue running this file

// public/owner-inventory.php
AuthMiddleware::requireRole('Owner');

// public/cart.php
AuthMiddleware::requireAuthenticated();

// public/login.php
AuthMiddleware::requireGuest();
```

---

### 4.8 The Registration Flow

```php
public function register(): void
{
    Session::ensureStarted();

    $username  = trim((string) ($_POST['username'] ?? ''));
    $email     = trim((string) ($_POST['email'] ?? ''));
    $password  = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password2'] ?? '');
    $role      = (string) ($_POST['role'] ?? 'Reader');

    // ── VALIDATION ──────────────────────────────────────────

    $allowedRoles = array('Reader', 'Owner', 'Admin');
    if (!in_array($role, $allowedRoles, true)) {
        $this->showRegister('Invalid role selected.');
        return;
    }

    if ($password !== $password2) {
        $this->showRegister('Passwords do not match.');
        return;
    }

    if (strlen($password) < 8) {
        $this->showRegister('Password must be at least 8 characters long.');
        return;
    }

    // ── DATABASE INSERT ─────────────────────────────────────

    $userId = $this->userModel->registerUser($username, $email, $password, $role);

    if ($userId === false) {
        $this->showRegister('Registration failed. Username or email may already exist.');
        return;
    }

    // ── SESSION + REDIRECT ───────────────────────────────────

    session_regenerate_id(true);
    $_SESSION['user']    = $username;
    $_SESSION['user_id'] = $userId;
    $_SESSION['role']    = $role;

    $this->auditRegisterSuccess((int) $userId, $role);
    RoleRedirector::redirectByRole((string) $role);
}
```

---

### 4.9 The Login View — HTML Form

```php
// app/views/auth/login.php (simplified)

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($errorMessage); ?>
    </div>
<?php endif; ?>

<form action="login.php" method="post">

    <input type="text" name="username"
           value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>"
           required />

    <input type="password" name="password" required />

    <button type="submit" name="login">Sign In</button>

</form>
```

- `method="post"` — form data goes in the HTTP body (not the URL). **Always use POST for passwords.**
- `name="username"` → becomes `$_POST['username']`
- `required` — browser won't submit if the field is empty

---

### 4.10 Session Fixation — Why We Regenerate the Session ID

```php
session_regenerate_id(true);
```

**Without regeneration:**

```
1. Attacker visits login.php → gets session ID: "evil123"
2. Attacker tricks victim into using that session ID
3. Victim logs in → account is now linked to "evil123"
4. Attacker uses "evil123" → logged in as victim!
```

**With `session_regenerate_id(true)`:**

```
1. Victim logs in with session ID "old456"
2. PHP creates NEW session ID "new789"
3. "old456" is destroyed
4. Attacker's "old456" is now useless
```

---

### 4.11 The Logout Flow

```php
// public/logout.php
require_once __DIR__ . '/../app/services/Session.php';

Session::destroy();
header("Location: login.php");
exit;
```

`Session::destroy()` does three things:
1. Wipes `$_SESSION` array
2. Deletes the session cookie from the browser
3. Destroys the session file on the server

---

### 4.12 Protecting Against XSS

**XSS (Cross-Site Scripting)** — an attacker injects malicious JavaScript into your page.

```
Attacker's username: <script>alert('hacked')</script>

Without protection → browser RUNS the script!
With htmlspecialchars() → browser displays it as text (safe)
```

```php
// Always escape output:
<div class="account-name">
    <?php echo htmlspecialchars($_SESSION["user"]); ?>
</div>
```

The function converts: `&` → `&amp;`, `<` → `&lt;`, `>` → `&gt;`, `"` → `&quot;`

---

### 4.13 Complete Authentication Trace

```
═══ FIRST VISIT (not logged in) ════════════════════════════

1. Tomas visits http://localhost/ibrcn/public/index.php
   session_start() → no session exists yet
   HomeController::index() runs → shows homepage

═══ LOGIN ══════════════════════════════════════════════════

2. Tomas visits login.php
   AuthMiddleware::requireGuest() → not authenticated → continue

3. Tomas submits username="tomas", password="mypassword"

4. AuthController::login() runs:
   - findByUsername("tomas") → gets user from DB
   - password_verify("mypassword", hash) → true ✓
   - session_regenerate_id(true) → new session ID
   - $_SESSION['user'] = 'tomas'
   - $_SESSION['role'] = 'Reader'
   - Audit log written
   - header("Location: reader.php")

═══ PROTECTED PAGE ═════════════════════════════════════════

5. Browser follows redirect → GET reader.php
   AuthMiddleware::requireRole('Reader')
   → isAuthenticated() → true ✓
   → in_array('Reader', ['Reader']) → true ✓
   → Page renders with Tomas's data

═══ LOGOUT ═════════════════════════════════════════════════

6. Tomas clicks "Logout" → logout.php runs
   Session::destroy() → everything cleared
   Redirect → login.php
```

---

### 4.14 Security Summary Table

| Attack | How IBRCN Defends |
|--------|------------------|
| Password theft | `password_hash()` with bcrypt — one-way |
| SQL Injection | PDO prepared statements |
| XSS | `htmlspecialchars()` on all output |
| Session Fixation | `session_regenerate_id(true)` after login |
| Unauthorized access | `AuthMiddleware` on every protected page |
| Brute force | Vague error messages |
| Cross-role access | `requireRole()` before any code runs |

---

### Lesson 4 Summary

| Concept | What it means |
|---------|--------------|
| Stateless HTTP | Each request is independent |
| Sessions | Server-side storage linked to browser via cookie |
| `$_SESSION` | PHP's array for storing session data |
| `session_start()` | Must be called before using `$_SESSION` |
| `password_hash()` | One-way encryption of passwords |
| `password_verify()` | Check typed password against stored hash |
| `session_regenerate_id()` | New session ID after login |
| `AuthMiddleware` | Checks auth before every protected page |
| `htmlspecialchars()` | Prevent XSS by escaping output |
| `header('Location:')` | Redirect the browser |
| `exit` | Stop PHP execution immediately |

---

<a name="lesson-5"></a>
## Lesson 5 — The Shopping Cart & Checkout System

This is one of the most complex parts of IBRCN. Let's trace how a book goes from "Add to Cart" to a completed order.

---

### 5.1 The Cart — Two Places At Once

IBRCN stores the cart in **two places simultaneously**:

| Location | Technology | Purpose |
|----------|-----------|---------|
| Browser | `localStorage` (JavaScript) | Fast UI updates |
| Server | `$_SESSION['cart']` (PHP) | Used during checkout |
| Database | `cart_items` table (MySQL) | Persists across logins |

---

### 5.2 The Cart Item Shape

Every item in the cart is an array with this structure:

```php
$cartItem = [
    'book_id'   => 2,
    'title'     => 'Atomic Habits',
    'name'      => 'Atomic Habits',    // same as title, legacy field
    'author'    => 'James Clear',
    'image'     => './img/book-2.png',
    'unitPrice' => 150.00,
    'price'     => 'EGP 150.00',       // formatted display string
    'quantity'  => 1,
];
```

---

### 5.3 Adding to Cart — The JavaScript Side

When a reader clicks "Add To Cart", JavaScript handles it first:

```javascript
// From public/js/script.js (simplified)
var addCart = tgt.closest(".reader-add-cart");
if (addCart) {
    var bookId     = addCart.dataset.bookId;
    var name       = addCart.dataset.bookTitle;
    var priceValue = Number(addCart.dataset.bookPriceValue);
    var author     = addCart.dataset.bookAuthor;

    addOrMergeCartItem({
        bookId: bookId, name: name,
        unitPrice: priceValue, author: author,
    }).then(function () {
        window.location.href = 'cart.php';
    });
}
```

The HTML button (from `app/views/reader/home.php`):

```html
<a href="#" class="btn reader-add-cart"
   data-book-id="<?php echo $safeId; ?>"
   data-book-title="<?php echo $safeTitle; ?>"
   data-book-price-value="<?php echo $safePriceValue; ?>"
   data-book-author="<?php echo $safeAuthor; ?>">
    Add To Cart
</a>
```

> `data-*` attributes — HTML5 way to embed extra data in elements. JavaScript reads them via `element.dataset.bookId`.

---

### 5.4 Syncing Cart to Server — cart-sync.php

After updating `localStorage`, JavaScript immediately POSTs to the server:

```javascript
function syncCartToServer(cart) {
    return fetch('cart-sync.php', {
        method: 'POST',
        credentials: 'same-origin',  // send cookies (session)
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart: cart })
    });
}
```

> `fetch()` — modern JavaScript for HTTP requests without reloading the page. This is called **AJAX**.

The server receives it in `public/cart-sync.php`:

```php
header('Content-Type: application/json; charset=utf-8');
session_start();

AuthMiddleware::requireAuthenticated();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('ok' => false, 'message' => 'Invalid request.'));
    exit;
}

$raw = file_get_contents('php://input');     // read JSON body
$payload = json_decode($raw ?: '', true);   // convert to PHP array

$incomingCart = $payload['cart'] ?? array();

$cartService = new CartService(DB::getInstance());
$normalized = $cartService->normalizeIncomingCart($incomingCart);

$_SESSION['cart'] = $normalized;
$cartService->persistSessionCart((int) $_SESSION['user_id'], $normalized);

echo json_encode(array('ok' => true, 'count' => count($normalized)));
```

> `file_get_contents('php://input')` — reads the raw request body.  
> `json_decode($raw, true)` — converts JSON string to PHP array.  
> `json_encode($array)` — converts PHP array to JSON string.

---

### 5.5 CartService — Normalizing Data

```php
// app/services/CartService.php

public function normalizeIncomingCart($incomingCart): array
{
    $normalized = array();

    foreach ($incomingCart as $item) {
        // Handle both 'book_id' and 'bookId' (JavaScript uses camelCase)
        $bookId    = (int) ($item['book_id'] ?? $item['bookId'] ?? 0);
        $quantity  = max(1, (int) ($item['quantity'] ?? 1));
        $title     = trim((string) ($item['title'] ?? $item['name'] ?? ''));
        $unitPrice = (float) ($item['unit_price'] ?? $item['unitPrice'] ?? 0);

        // Skip invalid items
        if ($bookId <= 0 || $title === '') continue;

        $normalized[] = array(
            'book_id'   => $bookId,
            'title'     => $title,
            'name'      => $title,
            'unitPrice' => $unitPrice,
            'price'     => 'EGP ' . number_format($unitPrice, 2),
            'quantity'  => $quantity,
        );
    }

    return $normalized;
}
```

> `max(1, $qty)` — ensures quantity is never less than 1.

---

### 5.6 Persisting the Cart to MySQL

```php
// app/models/CartRepository.php

public function replaceFromSession(int $readerId, array $sessionCart): void
{
    // Step 1: Delete all existing cart rows for this reader
    $deleteStatement = $this->databaseConnection->prepare(
        'DELETE FROM cart_items WHERE reader_id = :rid'
    );
    $deleteStatement->execute(array('rid' => $readerId));

    // Step 2: Insert fresh rows from the session cart
    $insertStatement = $this->databaseConnection->prepare(
        'INSERT INTO cart_items (reader_id, inventory_id, quantity)
         VALUES (:rid, :iid, :qty)'
    );

    foreach ($quantitiesByBook as $bookId => $quantity) {
        $inventoryId = $this->resolveInventoryIdForBook($bookId);
        if ($inventoryId === null) continue;

        $insertStatement->execute(array(
            'rid' => $readerId,
            'iid' => $inventoryId,
            'qty' => $quantity,
        ));
    }
}
```

> **Why delete then re-insert?** It's simpler than trying to update existing rows. This "replace" strategy ensures the DB cart always matches the session cart exactly.

---

### 5.7 The Checkout Flow — Order::checkoutCart()

When the reader clicks "Proceed to Checkout":

```php
// app/models/Order.php

public function checkoutCart(int $userId, array $cartItems): array
{
    $normalizedItems = $this->normalizeCartItems($cartItems);

    if ($normalizedItems === array()) {
        return array('ok' => false, 'message' => 'Your cart is empty.');
    }

    // Step 1: Group items by store (one order per store)
    $groupedByStore = array();

    foreach ($normalizedItems as $item) {
        $book = $this->bookModel->getBookById($item['book_id']);
        if (!$book) {
            return array('ok' => false, 'message' => 'Book not found.');
        }

        $resolved = $this->resolveInventoryForItem($item['book_id'], $item['quantity']);

        if ($resolved === null) {
            return array('ok' => false, 'message' => 'Insufficient stock.');
        }

        $storeId = (int) $resolved['store_id'];

        if (!isset($groupedByStore[$storeId])) {
            $groupedByStore[$storeId] = array(
                'store_id'   => $storeId,
                'store_name' => (string) $resolved['name'],
                'items'      => array(),
                'total'      => 0.0,
            );
        }

        $groupedByStore[$storeId]['items'][] = array(
            'book_id'      => (int) $book['book_id'],
            'inventory_id' => (int) $resolved['inventory_id'],
            'title'        => (string) $book['title'],
            'quantity'     => $item['quantity'],
            'unit_price'   => (float) $resolved['price'],
        );

        $groupedByStore[$storeId]['total'] += (float) $resolved['price'] * $item['quantity'];
    }

    // Step 2: Create orders in a transaction
    try {
        $this->databaseConnection->beginTransaction();
        $createdOrders = array();

        foreach ($groupedByStore as $storeGroup) {

            // INSERT into orders table
            $orderId = $this->createOrderHeader(
                $userId, (int) $storeGroup['store_id'], (float) $storeGroup['total']
            );

            foreach ($storeGroup['items'] as $line) {
                // Reserve the stock (add hold)
                $this->reserveInventory((int) $line['inventory_id'], (int) $line['quantity']);

                // INSERT into order_items table
                $this->createOrderItem($orderId, $line);
            }

            $createdOrders[] = array(
                'order_id'     => $orderId,
                'store_id'     => (int) $storeGroup['store_id'],
                'store_name'   => $storeGroup['store_name'],
                'total_amount' => (float) $storeGroup['total'],
            );
        }

        $this->databaseConnection->commit();

        return array('ok' => true, 'message' => 'Checkout complete.', 'orders' => $createdOrders);

    } catch (Throwable $exception) {
        if ($this->databaseConnection->inTransaction()) {
            $this->databaseConnection->rollBack();
        }
        return array('ok' => false, 'message' => 'Could not complete checkout.');
    }
}
```

---

### 5.8 Inventory Reservation — Preventing Overselling

When checkout happens, stock isn't immediately reduced. Instead, a **hold** is placed:

```
store_inventory row:
  quantity      = 10   ← total physical stock
  hold_quantity =  3   ← 3 units reserved for pending orders

Available to buy = 10 - 3 = 7
```

```php
private function reserveInventory(int $inventoryId, int $quantity): void
{
    $statement = $this->databaseConnection->prepare(
        "UPDATE store_inventory
         SET hold_quantity = hold_quantity + :qty
         WHERE inventory_id = :inventory_id
         AND quantity - hold_quantity >= :qty"
        // The AND clause ensures we only update if there's enough stock
    );
    $statement->execute(array('qty' => $quantity, 'inventory_id' => $inventoryId));

    if ($statement->rowCount() === 0) {
        throw new RuntimeException('Could not reserve inventory.');
        // This triggers the transaction rollback!
    }
}
```

When the reader **collects** the order, both quantity AND hold are reduced:

```php
// UPDATE store_inventory
// SET quantity      = quantity      - :qty,
//     hold_quantity = hold_quantity - :qty
// WHERE inventory_id = :inventory_id
```

---

### 5.9 Creating Order Records

```php
private function createOrderHeader(int $userId, int $storeId, float $totalAmount): int
{
    $statement = $this->databaseConnection->prepare(
        "INSERT INTO orders
         (reader_id, store_id, status, total_amount, created_at)
         VALUES (:reader_id, :store_id, 'Placed', :total_amount, NOW())"
    );
    $statement->execute(array(
        'reader_id'    => $userId,
        'store_id'     => $storeId,
        'total_amount' => $totalAmount,
    ));

    return (int) $this->databaseConnection->lastInsertId();
}
```

> `NOW()` — MySQL function that inserts the current timestamp automatically.

---

### 5.10 Notifications After Checkout

```php
// app/services/NotificationService.php

public function onCheckoutSuccess(int $readerId, array $orders): void
{
    foreach ($orders as $orderSummary) {
        $ownerContact = $orderModel->getStoreOwnerContact($storeId);

        if ($ownerContact) {
            // Email the store owner
            $this->email->sendOwnerNewOrderNotice(
                $ownerContact['email'],
                $ownerContact['username'],
                $orderId, $totalAmount,
                $ownerContact['store_name']
            );

            // In-app notification for the owner
            $this->inApp->push(
                (int) $ownerContact['user_id'],
                'New customer order',
                'Order #' . $orderId . ' — EGP ' . number_format($totalAmount, 2),
                'order'
            );
        }
    }
}
```

---

### 5.11 The Complete Cart-to-Collected Journey

```
READER              SERVER                       DATABASE
  │                    │                              │
  │ Clicks "Add"       │                              │
  │──────────────────►│                              │
  │                    │ JS → localStorage            │
  │                    │ POST cart-sync.php ──────────►
  │                    │                              │ cart_items updated
  │                    │◄────────────────────────────  │
  │                    │                              │
  │ Clicks "Checkout"  │                              │
  │──────────────────►│                              │
  │                    │ checkoutCart() runs          │
  │                    │ BEGIN TRANSACTION ───────────►
  │                    │                              │ INSERT orders
  │                    │                              │ UPDATE hold_quantity
  │                    │                              │ INSERT order_items
  │                    │ COMMIT ──────────────────────►
  │                    │                              │
  │                    │ Clear cart ─────────────────►│
  │                    │                              │ DELETE cart_items
  │                    │ Send emails + notifications  │
  │◄──────────────────│                              │
  │ "Checkout complete"│                              │
  │                    │                              │
OWNER                  │                              │
  │ Clicks "Mark Ready"│                              │
  │──────────────────►│                              │
  │                    │ markReady() ────────────────►│
  │                    │                              │ UPDATE status='Ready'
  │                    │ Notify reader ───────────────►
  │                    │                              │
READER                 │                              │
  │ Collects book      │                              │
  │ Clicks "Collected" │                              │
  │──────────────────►│                              │
  │                    │ markCollected() ────────────►│
  │                    │                              │ UPDATE status='Collected'
  │                    │                              │ quantity -= N
  │                    │                              │ hold_quantity -= N
```

---

### Lesson 5 Summary

| Concept | What it means |
|---------|--------------|
| `localStorage` | Browser-side storage (JavaScript) |
| AJAX / `fetch()` | HTTP requests without page reload |
| Cart sync | Cart lives in `localStorage`, `$_SESSION`, AND MySQL |
| Inventory hold | Reserve stock without reducing it immediately |
| Transaction | All-or-nothing group of SQL operations |
| Order grouping | One order per store (multi-store cart supported) |
| `lastInsertId()` | Get the auto-generated ID after INSERT |
| `NOW()` | MySQL function for current timestamp |
| Observer pattern | Notifications fired after checkout without tight coupling |

---

## Overall Summary Table

| Lesson | Key Topics Covered |
|--------|--------------------|
| **1** | Client-Server, HTTP, PHP, MySQL, XAMPP, MVC, PDO, Foreign Keys |
| **2** | Variables, Arrays, Loops, Functions, Classes, $_POST/$_GET, Sessions |
| **3** | SQL CRUD, JOINs, Prepared Statements, Transactions, Singleton, Audit Log |
| **4** | Sessions, Password Hashing, Login/Logout, AuthMiddleware, XSS Defense |
| **5** | Cart (localStorage + Session + DB), AJAX, Checkout, Inventory Holds, Notifications |

---

*End of IBRCN PHP Learning Guide — Lessons 1 to 5*
# IBRCN Project — Lessons 6 & 7
### CS251 Software Engineering 1 — Capital University, Spring 2026

---

# Lesson 6: Design Patterns — Singleton, Observer & Strategy

## 6.1 What is a Design Pattern?

A design pattern is a **proven solution to a recurring problem** in software. Think of it like architectural blueprints — you don't invent how to build a door from scratch every time. You use a known design.

Your project implements exactly three patterns:

```
┌─────────────────────────────────────────────────────────────┐
│                    IBRCN Design Patterns                    │
├─────────────────┬───────────────────┬───────────────────────┤
│   SINGLETON     │     OBSERVER      │      STRATEGY         │
│                 │                   │                       │
│ DB.php          │ EventPublisher.php│ RecommendationEngine  │
│                 │ ReaderObserver.php│ TopPicksStrategy      │
│ One database    │                   │ HistoryBasedStrategy  │
│ connection only │ Notify without    │                       │
│                 │ tight coupling    │ Swap algorithms       │
│                 │                   │ at runtime            │
└─────────────────┴───────────────────┴───────────────────────┘
```

---

## 6.2 Pattern 1: Singleton — One Instance Only

### The Problem It Solves

Without Singleton, every model that needs the database would create its own connection:

```php
// WITHOUT Singleton — wasteful:
class Book {
    public function __construct() {
        $this->db = new PDO("mysql:host=...", "root", "");
        // Opens connection #1
    }
}

class User {
    public function __construct() {
        $this->db = new PDO("mysql:host=...", "root", "");
        // Opens connection #2
    }
}

class Order {
    public function __construct() {
        $this->db = new PDO("mysql:host=...", "root", "");
        // Opens connection #3
    }
}

// One page request opens 3 separate MySQL connections!
// MySQL has connection limits — this wastes resources
```

### The Solution

```php
// WITH Singleton — one connection shared by everyone:
$db = DB::getInstance();   // creates connection
$db = DB::getInstance();   // returns SAME connection
$db = DB::getInstance();   // returns SAME connection

$book  = new Book($db);    // all three models
$user  = new User($db);    // share the exact
$order = new Order($db);   // same PDO object
```

### The Full Implementation — DB.php

```php
// app/patterns/DB.php

class DB
{
    // ── KEY PART 1: Static property ─────────────────────────
    // 'static' means it belongs to the CLASS not any instance
    // Starts as null — no connection exists yet
    private static ?PDO $instance = null;

    // ── KEY PART 2: Private constructor ─────────────────────
    // Nobody outside can do: $db = new DB()
    // Forces everyone to use getInstance()
    private function __construct() {}

    // ── KEY PART 3: Private clone ────────────────────────────
    // Nobody can do: $db2 = clone $db
    private function __clone() {}

    // ── KEY PART 4: The one public method ───────────────────
    public static function getInstance(): PDO
    {
        // Only enter this block ONCE — first call only
        if (self::$instance === null) {

            // Load credentials from config file
            $config = require __DIR__ . '/../../config/db_config.php';

            try {
                // Build connection string (DSN)
                $dsn = "mysql:host={$config['host']}"
                     . ";port={$config['port']}"
                     . ";dbname={$config['db']}"
                     . ";charset=utf8mb4";

                // Create the ONE AND ONLY PDO connection
                self::$instance = new PDO(
                    $dsn,
                    $config['user'],
                    $config['pass']
                );

                // Throw exceptions on SQL errors
                self::$instance->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );

                // Return results as ['column' => 'value'] arrays
                self::$instance->setAttribute(
                    PDO::ATTR_DEFAULT_FETCH_MODE,
                    PDO::FETCH_ASSOC
                );

            } catch (PDOException $e) {
                die('Database connection failed: ' . $e->getMessage());
            }
        }

        // Every call — first or hundredth — returns this same object
        return self::$instance;
    }
}
```

### Visualizing the Singleton

```
First call:                    Subsequent calls:

DB::getInstance()              DB::getInstance()
        │                              │
        ▼                              ▼
$instance === null?            $instance === null?
        │ YES                          │ NO
        ▼                              ▼
Create new PDO()               Skip creation
Store in $instance
        │                              │
        └──────────────────────────────┘
                      │
                      ▼
              Return $instance
              (always the SAME object)
```

### Why `static` Matters

```php
// Regular property — belongs to each OBJECT:
class Counter {
    public int $count = 0;  // each object has its own $count
}
$a = new Counter();
$b = new Counter();
$a->count++;    // $a->count = 1
                // $b->count still = 0  ← separate!

// Static property — belongs to the CLASS itself:
class Counter {
    public static int $count = 0;  // ONE shared $count
}
Counter::$count++;  // = 1
Counter::$count++;  // = 2
// All code in the entire app shares this ONE value
```

This is exactly how `DB::$instance` works — one shared connection for the whole application.

---

## 6.3 Pattern 2: Observer — React Without Knowing Who's Listening

### The Problem It Solves

After checkout, several things need to happen:
- Send confirmation email to reader
- Send notification email to store owner
- Create in-app notification for reader
- Create in-app notification for owner
- Log to audit trail

**Without Observer** — everything crammed into one place:

```php
// BAD — NotificationService knows about everything:
public function onCheckoutSuccess($readerId, $orders) {
    $this->emailService->sendToReader(...);
    $this->emailService->sendToOwner(...);
    $this->inAppStore->pushToReader(...);
    $this->inAppStore->pushToOwner(...);
    $this->auditLog->write(...);
    $this->smsService->sendToReader(...);   // add SMS?   → edit this class
    $this->pushNotification->send(...);    // add push?  → edit again
    // This class grows forever and becomes a nightmare
}
```

**With Observer** — the publisher just announces the event. Observers react independently:

```php
// GOOD — NotificationService just announces:
$this->eventPublisher->publish('checkout_success', [
    'reader_id' => $readerId,
    'orders'    => $orders,
]);
// Done. Publisher doesn't know or care who reacts.
// Add new reactions? Just add a new observer.
```

### The YouTube Analogy

```
YouTube Channel (EventPublisher)
        │
        │ uploads video (publish event)
        │
        ├──► Subscriber A gets notified (ReaderObserver)
        ├──► Subscriber B gets notified (could be LogObserver)
        └──► Subscriber C gets notified (could be SMSObserver)

Channel doesn't know how many subscribers exist.
Channel doesn't know what subscribers DO with the notification.
Subscribers don't know about each other.
```

### Part 1 — EventPublisher (The Subject)

```php
// app/patterns/EventPublisher.php

class EventPublisher
{
    // List of all registered observers
    private array $readerObservers = array();

    // SUBSCRIBE — add an observer to the list
    public function subscribe(ReaderObserver $observer): void
    {
        $this->readerObservers[] = $observer;
    }

    // UNSUBSCRIBE — remove an observer
    public function unsubscribe(ReaderObserver $observer): void
    {
        $this->readerObservers = array_filter(
            $this->readerObservers,
            // Keep everyone EXCEPT the one being removed
            fn($existing) => $existing !== $observer
        );
        // Re-index so keys are 0,1,2... not 0,2,4...
        $this->readerObservers = array_values($this->readerObservers);
    }

    // PUBLISH — tell ALL observers something happened
    public function publish(string $eventType, array $payload): void
    {
        foreach ($this->readerObservers as $observer) {
            // Each observer decides what to do with this event
            $observer->update($eventType, $payload);
        }
    }
}
```

> **Arrow function** `fn($x) => expression` is a short way to write a function.
> Same as: `function($existing) { return $existing !== $observer; }`

### Part 2 — ReaderObserver (The Listener)

```php
// app/patterns/ReaderObserver.php

class ReaderObserver
{
    public function __construct(
        private EmailService           $emailService,
        private InAppNotificationStore $inAppNotificationStore,
        private PDO                    $databaseConnection
    ) {}

    // Called by EventPublisher whenever an event fires
    public function update(string $eventType, array $payload): void
    {
        // Route to the right handler based on event type
        if ($eventType === 'checkout_success') {
            $this->notifyReaderAfterCheckout($payload);
            return;
        }

        if ($eventType === 'order_ready_for_pickup') {
            $this->notifyReaderOrderReady($payload);
            return;
        }

        if ($eventType === 'user_registered') {
            $this->notifyReaderWelcome($payload);
        }
    }

    // Handler for checkout_success
    private function notifyReaderAfterCheckout(array $payload): void
    {
        $readerId = (int) ($payload['reader_id'] ?? 0);
        $orders   = $payload['orders'] ?? array();

        if ($readerId <= 0 || $orders === array()) return;

        $userModel = new User($this->databaseConnection);
        $reader    = $userModel->findById($readerId);
        if (!$reader) return;

        foreach ($orders as $orderRow) {
            $orderId   = (int)    ($orderRow['order_id']     ?? 0);
            $storeName = (string) ($orderRow['store_name']   ?? 'Bookstore');
            $total     = (float)  ($orderRow['total_amount'] ?? 0);

            // 1. Send confirmation email
            $this->emailService->sendOrderConfirmation(
                (string) $reader['email'],
                (string) $reader['username'],
                $orderId, $total, $storeName
            );

            // 2. Create in-app notification
            $this->inAppNotificationStore->push(
                $readerId,
                'Order placed',
                'Order #' . $orderId . ' at ' . $storeName
                    . ' for EGP ' . number_format($total, 2) . '.',
                'order'
            );
        }
    }

    // Handler for order_ready_for_pickup
    private function notifyReaderOrderReady(array $payload): void
    {
        $context = $payload['context'] ?? array();
        $orderId = (int) ($payload['order_id'] ?? 0);

        if ($context === array() || $orderId <= 0) return;

        $readerId = (int) ($context['reader_id'] ?? 0);

        $this->emailService->sendOrderReadyForPickup(
            (string) $context['reader_email'],
            (string) $context['reader_name'],
            $orderId,
            (string) $context['store_name']
        );

        $this->inAppNotificationStore->push(
            $readerId,
            'Order ready for pickup',
            'Order #' . $orderId . ' at '
                . (string) $context['store_name'] . ' is ready.',
            'order'
        );
    }

    // Handler for user_registered
    private function notifyReaderWelcome(array $payload): void
    {
        $userId   = (int)    ($payload['user_id']  ?? 0);
        $email    = (string) ($payload['email']    ?? '');
        $username = (string) ($payload['username'] ?? '');

        if ($userId <= 0 || $email === '') return;

        $this->emailService->sendWelcomeEmail($email, $username);

        $this->inAppNotificationStore->push(
            $userId,
            'Welcome to IBRCN',
            'Your account is ready. Browse stores and place pickup orders.',
            'account'
        );
    }
}
```

### Part 3 — NotificationService Wires Everything Together

```php
// app/services/NotificationService.php

class NotificationService
{
    private EmailService           $email;
    private InAppNotificationStore $inApp;
    private PDO                    $db;
    private EventPublisher         $eventPublisher;

    public function __construct(
        ?EmailService           $email = null,
        ?InAppNotificationStore $inApp = null,
        ?PDO                    $db    = null
    ) {
        $this->db           = $db    ?? DB::getInstance();
        $this->email        = $email ?? new EmailService();
        $this->inApp        = $inApp ?? new InAppNotificationStore(null, $this->db);
        $this->eventPublisher = new EventPublisher();

        // Subscribe the observer — the wiring step
        $this->eventPublisher->subscribe(
            new ReaderObserver($this->email, $this->inApp, $this->db)
        );
    }

    public function onCheckoutSuccess(int $readerId, array $orders): void
    {
        if ($orders === array()) return;

        $userModel = new User($this->db);
        if (!$userModel->findById($readerId)) return;

        // PUBLISH — ReaderObserver handles reader emails automatically
        $this->eventPublisher->publish('checkout_success', array(
            'reader_id' => $readerId,
            'orders'    => $orders,
        ));

        // Handle owner notifications directly (not through observer)
        $orderModel = new Order($this->db, new Book($this->db));

        foreach ($orders as $orderSummary) {
            $orderId   = (int)   ($orderSummary['order_id']     ?? 0);
            $storeId   = (int)   ($orderSummary['store_id']     ?? 0);
            $total     = (float) ($orderSummary['total_amount'] ?? 0);

            $ownerContact = $orderModel->getStoreOwnerContact($storeId);

            if ($ownerContact) {
                $this->email->sendOwnerNewOrderNotice(
                    (string) $ownerContact['email'],
                    (string) $ownerContact['username'],
                    $orderId, $total,
                    (string) $ownerContact['store_name']
                );

                $this->inApp->push(
                    (int) $ownerContact['user_id'],
                    'New customer order',
                    'Order #' . $orderId
                        . ' — EGP ' . number_format($total, 2) . '.',
                    'order'
                );
            }
        }
    }

    public function onOrderMarkedReady(int $ownerId, int $orderId): void
    {
        $orderModel    = new Order($this->db, new Book($this->db));
        $pickupContext = $orderModel->getPickupNotifyContext($ownerId, $orderId);

        if (!$pickupContext) return;

        $this->eventPublisher->publish('order_ready_for_pickup', array(
            'context'  => $pickupContext,
            'order_id' => $orderId,
        ));
    }

    public function onUserRegistered(int $userId, string $email, string $username): void
    {
        $this->eventPublisher->publish('user_registered', array(
            'user_id'  => $userId,
            'email'    => $email,
            'username' => $username,
        ));
    }
}
```

### Observer Flow Visualized

```
public/cart.php
    │ checkout succeeds
    ▼
NotificationService::onCheckoutSuccess(1, $orders)
    │ publish('checkout_success', payload)
    ▼
EventPublisher::publish()
    │ loops through $readerObservers
    ▼
ReaderObserver::update('checkout_success', payload)
    ├──► sendOrderConfirmation()  → email to reader
    └──► inApp->push()           → notification to reader

Back in NotificationService (directly):
    ├──► sendOwnerNewOrderNotice() → email to owner
    └──► inApp->push()            → notification to owner
```

---

## 6.4 Pattern 3: Strategy — Swap Algorithms at Runtime

### The Problem It Solves

The recommendation engine needs to behave differently based on privacy settings:

```
Privacy toggle ON  → show books based on purchase history
Privacy toggle OFF → show generic top picks for everyone
```

**Without Strategy** — ugly if/else inside the controller:

```php
// BAD — controller decides algorithm logic:
public function index(): void
{
    if ($readerUserId && $userAllowsHistory) {
        // Run history-based SQL here
        $sql = "SELECT ... FROM orders JOIN books ...";
        $books = $db->query($sql)->fetchAll();
    } else {
        // Run top picks SQL here
        $sql = "SELECT ... FROM books JOIN store_inventory ...";
        $books = $db->query($sql)->fetchAll();
    }
    // Controller is doing too much — it knows raw SQL
}
```

**With Strategy** — controller just asks the engine:

```php
// GOOD — controller only coordinates:
public function index(): void
{
    $books = $this->recommendationEngine->getHomePageRecommendations($userId);
    // Controller has NO idea which algorithm ran
    // That decision is completely hidden inside RecommendationEngine
}
```

### Part 1 — The Interface (The Contract)

An **interface** defines what methods a class MUST have, without implementing them:

```php
// app/patterns/RecommendationStrategy.php

interface RecommendationStrategy
{
    // Any class implementing this interface MUST have this method
    // with exactly this signature
    public function getRecommendations(int $readerUserId): array;
}
```

> Think of an interface as a job description: "whoever fills this role must be
> able to do X" — without specifying HOW they do it.

### Part 2 — Strategy A: TopPicksStrategy

```php
// app/patterns/TopPicksStrategy.php

// 'implements RecommendationStrategy' means:
// "I promise to have getRecommendations()"
class TopPicksStrategy implements RecommendationStrategy
{
    public function __construct(private Book $bookModel) {}

    public function getRecommendations(int $readerUserId): array
    {
        $db = DB::getInstance();

        // Show books that are in stock — no personalization
        $sql = "SELECT b.book_id, b.title, b.author,
                       b.isbn, b.cover_image,
                       MIN(i.price) AS price
                FROM books b
                JOIN store_inventory i ON b.book_id = i.book_id
                WHERE (i.quantity - COALESCE(i.hold_quantity, 0)) > 0
                GROUP BY b.book_id, b.title, b.author, b.isbn, b.cover_image
                LIMIT 10";

        $statement = $db->query($sql);
        $rows = $statement ? ($statement->fetchAll() ?: array()) : array();

        foreach ($rows as &$row) {
            $priceValue                 = (float) $row['price'];
            $row['price_value']         = $priceValue;
            $row['price_egp_formatted'] = 'EGP ' . number_format($priceValue, 2);
        }
        unset($row);

        return $rows;
    }
}
```

### Part 3 — Strategy B: HistoryBasedStrategy

```php
// app/patterns/HistoryBasedStrategy.php

class HistoryBasedStrategy implements RecommendationStrategy
{
    public function __construct(private Book $bookModel) {}

    public function getRecommendations(int $readerUserId): array
    {
        if ($readerUserId <= 0) return array();

        $db = DB::getInstance();

        // Show books the reader has actually ordered before,
        // sorted by most recently ordered
        $sql = "SELECT b.book_id, b.title, b.author,
                       b.isbn, b.cover_image,
                       MIN(i.price) AS price
                FROM books b
                JOIN store_inventory i ON b.book_id = i.book_id
                INNER JOIN order_items oi ON oi.book_id = b.book_id
                INNER JOIN orders o
                        ON o.order_id = oi.order_id
                       AND o.reader_id = :reader_id
                WHERE (i.quantity - COALESCE(i.hold_quantity, 0)) > 0
                GROUP BY b.book_id, b.title, b.author, b.isbn, b.cover_image
                ORDER BY MAX(o.created_at) DESC
                LIMIT 10";

        $statement = $db->prepare($sql);
        $statement->execute(array('reader_id' => (int) $readerUserId));
        $rows = $statement->fetchAll() ?: array();

        foreach ($rows as &$row) {
            $priceValue                 = (float) $row['price'];
            $row['price_value']         = $priceValue;
            $row['price_egp_formatted'] = 'EGP ' . number_format($priceValue, 2);
        }
        unset($row);

        return $rows;
    }
}
```

### Part 4 — RecommendationEngine (The Context)

The class that **chooses** which strategy to use:

```php
// app/services/RecommendationEngine.php

class RecommendationEngine
{
    public function __construct(private Book $bookModel) {}

    public function getHomePageRecommendations(?int $readerUserId): array
    {
        $readerId = ($readerUserId !== null && $readerUserId > 0)
                    ? (int) $readerUserId
                    : 0;

        // ── STRATEGY SELECTION ────────────────────────────────

        if ($readerId > 0) {
            $userModel = new User($this->bookModel->getDatabaseConnection());

            // Check if reader opted into personalized recommendations
            if ($userModel->readerAllowsPersonalizedRecommendationsFromHistory($readerId)) {

                // Try history-based strategy first
                $historyStrategy = new HistoryBasedStrategy($this->bookModel);
                $historyRows     = $historyStrategy->getRecommendations($readerId);

                // Only use it if it actually returned results
                if ($historyRows !== array()) {
                    return $historyRows;   // ← Strategy A used
                }
            }
        }

        // Fall back to top picks (default for everyone)
        $topPicksStrategy = new TopPicksStrategy($this->bookModel);
        return $topPicksStrategy->getRecommendations($readerId);  // ← Strategy B used
    }
}
```

### Strategy Flow Visualized

```
HomeController::index()
        │ calls
        ▼
RecommendationEngine::getHomePageRecommendations($userId)
        │
        ├─ Is user logged in?
        │      │ NO ─────────────────────────────────────────────┐
        │      │ YES                                             │
        │      ▼                                                 │
        │  Does user allow history recommendations?             │
        │      │ NO ─────────────────────────────────────────────┤
        │      │ YES                                             │
        │      ▼                                                 │
        │  HistoryBasedStrategy::getRecommendations()           │
        │      │ Returns results?                                │
        │      ├── YES → return history books                    │
        │      └── NO ───────────────────────────────────────────┤
        │                                                        │
        │                                                        ▼
        │                               TopPicksStrategy::getRecommendations()
        │                                                        │
        └────────────────────────────────────────────────────────┘
                                                                 │
                                                                 ▼
                                                       return featured books
```

---

## 6.5 Comparing All Three Patterns

| Pattern | Problem Solved | IBRCN Usage |
|---------|---------------|-------------|
| **SINGLETON** | "Only one of this should ever exist" | One PDO database connection per request |
| **OBSERVER** | "Notify many without tight coupling" | After checkout: email reader, notify owner, in-app alerts — all without checkout code knowing about emails |
| **STRATEGY** | "Swap algorithms without changing calling code" | Recommendations: history vs top picks, selected based on privacy toggle |

---

## 6.6 Real-World Analogies

**Singleton — The Principal's Office:**
There is only ONE principal's office in the school. Everyone goes to the same one. You don't build a new office every time someone needs to visit.

**Observer — School Announcement System:**
The principal makes an announcement over the speakers. Every classroom (observer) hears it and reacts in their own way. The principal doesn't call each teacher individually — she just broadcasts once.

**Strategy — GPS Navigation:**
You want to go from A to B. You can choose "Fastest route", "Avoid tolls", or "Scenic route". The destination is the same. Only the algorithm changes. You can switch strategy without rebuilding the car.

---

## 6.7 How the Patterns Work Together

```
Reader visits homepage
        │
        ▼
public/index.php
        │
        ▼
DB::getInstance()              ← SINGLETON: one PDO connection
        │
        ▼
HomeController::index()
        │
        ▼
RecommendationEngine           ← STRATEGY: picks algorithm
        │
        ├── HistoryBasedStrategy  (privacy = on + has history)
        └── TopPicksStrategy      (everyone else)
        │
        ▼
Reader places order → checkout
        │
        ▼
NotificationService            ← OBSERVER: fires events
        │
        ▼
EventPublisher::publish()
        │
        ▼
ReaderObserver::update()
        │
        ├── sendOrderConfirmation() → email
        └── inApp->push()          → notification
```

All three patterns run in the lifetime of a single page visit.

---

## Lesson 6 Summary

| Concept | What it means |
|---------|--------------|
| Design Pattern | Proven solution to a recurring software problem |
| Singleton | One instance of a class shared by everyone |
| `static` property | Belongs to the class, not any object |
| Private constructor | Prevents `new DB()` from outside |
| Observer | Publisher announces events, observers react independently |
| `subscribe()` | Register an observer to receive events |
| `publish()` | Fire an event to all registered observers |
| Strategy | Define a family of algorithms, make them interchangeable |
| Interface | Contract — defines what methods a class MUST have |
| `implements` | A class promising to fulfill an interface contract |
| Context class | The class that chooses and runs a strategy |

---
---

# Lesson 7: Reading Clubs & Social Features

## 7.1 What Are Reading Clubs in IBRCN?

Reading clubs are the **social heart** of the platform. They let readers:
- Create or join a club with other readers
- Share what book each member is currently reading
- Post discussion threads visible only to club members
- Track who has read how far (milestone system)

The feature spans four database tables, one model, one controller, and one service.

---

## 7.2 The Database Tables

Four tables power the reading clubs feature:

```
reading_clubs
─────────────────────────────────────────────────────────
club_id     (PK, auto-increment)
name        VARCHAR(255) NOT NULL
description TEXT
creator_email    VARCHAR(255)     ← legacy: creator identified by email
creator_user_id  INT UNSIGNED     ← modern: creator identified by user_id
created_at  DATETIME DEFAULT CURRENT_TIMESTAMP

club_members
─────────────────────────────────────────────────────────
member_id  (PK)
club_id    (FK → reading_clubs.club_id)
user_id    INT UNSIGNED NULL   ← NULL for legacy email-only rows
email      VARCHAR(255)        ← always stored for lookup fallback
joined_at  DATETIME

club_member_current_read
─────────────────────────────────────────────────────────
read_id     (PK)
club_id     (FK → reading_clubs.club_id)
user_id     INT UNSIGNED NOT NULL
book_title  VARCHAR(500)       ← what this member is reading RIGHT NOW
book_author VARCHAR(255)
updated_at  DATETIME           ← auto-updates on every change
UNIQUE KEY (club_id, user_id)  ← one entry per member per club

club_discussion_threads
─────────────────────────────────────────────────────────
thread_id   (PK)
club_id     (FK → reading_clubs.club_id)
user_id     INT UNSIGNED NOT NULL   ← who wrote this thread
title       VARCHAR(500)
body        TEXT
created_at  DATETIME
updated_at  DATETIME
```

> **UNIQUE KEY (club_id, user_id)** on `club_member_current_read` means one
> member can only have ONE current-read entry per club. If they save a new book,
> it replaces the old one (using `ON DUPLICATE KEY UPDATE`).

---

## 7.3 The Schema Bootstrap — ensureSchema()

Your project dynamically creates tables if they don't exist yet. This runs every time `member.php` is loaded:

```php
// app/models/ReadingClub.php

public function ensureSchema(): void
{
    // Create reading_clubs table if it doesn't exist
    $this->db->exec(
        "CREATE TABLE IF NOT EXISTS reading_clubs (
            club_id         INT AUTO_INCREMENT PRIMARY KEY,
            name            VARCHAR(255) NOT NULL,
            description     TEXT DEFAULT NULL,
            creator_email   VARCHAR(255) DEFAULT NULL,
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    // Create club_members table if it doesn't exist
    $this->db->exec(
        "CREATE TABLE IF NOT EXISTS club_members (
            member_id  INT AUTO_INCREMENT PRIMARY KEY,
            club_id    INT NOT NULL,
            email      VARCHAR(255) NOT NULL,
            joined_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (club_id) REFERENCES reading_clubs(club_id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    // Add creator_user_id column if it was added later (safe migration)
    $this->addColumnUnlessExists(
        'reading_clubs',
        'creator_user_id',
        'INT UNSIGNED NULL DEFAULT NULL AFTER creator_email'
    );

    // Add user_id column to club_members if it was added later
    $this->addColumnUnlessExists(
        'club_members',
        'user_id',
        'INT UNSIGNED NULL DEFAULT NULL AFTER club_id'
    );

    // Create current-read tracking table
    $this->db->exec(
        "CREATE TABLE IF NOT EXISTS club_member_current_read (
            read_id     INT AUTO_INCREMENT PRIMARY KEY,
            club_id     INT NOT NULL,
            user_id     INT UNSIGNED NOT NULL,
            book_title  VARCHAR(500) NOT NULL,
            book_author VARCHAR(255) DEFAULT NULL,
            updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_club_member_read (club_id, user_id),
            FOREIGN KEY (club_id) REFERENCES reading_clubs(club_id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    // Create discussion threads table
    $this->db->exec(
        "CREATE TABLE IF NOT EXISTS club_discussion_threads (
            thread_id  INT AUTO_INCREMENT PRIMARY KEY,
            club_id    INT NOT NULL,
            user_id    INT UNSIGNED NOT NULL,
            title      VARCHAR(500) NOT NULL,
            body       TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                       ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_disc_club_time (club_id, created_at),
            FOREIGN KEY (club_id) REFERENCES reading_clubs(club_id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}
```

The helper `addColumnUnlessExists()` safely adds a column only if it's missing:

```php
private function addColumnUnlessExists(
    string $table,
    string $column,
    string $definition
): void {
    // Ask MySQL: does this column exist?
    $stmt = $this->db->prepare(
        'SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME   = :t
           AND COLUMN_NAME  = :c'
    );
    $stmt->execute(array('t' => $table, 'c' => $column));

    // If count is 0 — column doesn't exist — add it
    if ((int) $stmt->fetchColumn() === 0) {
        $this->db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
    // If count is 1 — column already exists — do nothing
}
```

> **`information_schema.COLUMNS`** is a special MySQL system table that
> describes the structure of all your other tables. It's like a directory of
> your database.

---

## 7.4 Creating a Club

When a reader submits the "Create Club" form:

```php
// app/models/ReadingClub.php

public function createClub(
    string  $name,
    ?string $description,
    ?string $creatorEmail,
    ?int    $creatorUserId
): int {
    $stmt = $this->db->prepare(
        'INSERT INTO reading_clubs
         (name, description, creator_email, creator_user_id)
         VALUES (:name, :desc, :cemail, :cuid)'
    );
    $stmt->execute(array(
        ':name'   => $name,
        ':desc'   => $description,
        ':cemail' => $creatorEmail,
        ':cuid'   => $creatorUserId,
    ));

    // Return the new club's ID so we can add the creator as a member
    return (int) $this->db->lastInsertId();
}
```

After creating the club, the creator is immediately added as a member:

```php
// app/controllers/ReadingClubController.php (inside handlePost)

if ($action === 'create_club') {

    $name  = trim((string) ($_POST['club_name']        ?? ''));
    $desc  = trim((string) ($_POST['club_description'] ?? ''));

    if ($name === '') {
        return 'Club name is required.';
    }

    // 1. Create the club row
    $clubId = $this->readingClubModel->createClub(
        $name,
        $desc !== '' ? $desc : null,
        (string) $sessionUserRow['email'],
        $sessionUserId
    );

    // 2. Add creator as first member
    $this->readingClubModel->addMember(
        $clubId,
        $sessionUserId,
        (string) $sessionUserRow['email']
    );

    // 3. Invite other readers if checkboxes were ticked
    if (!empty($_POST['invite_user_ids']) && is_array($_POST['invite_user_ids'])) {
        foreach ($_POST['invite_user_ids'] as $rawId) {
            $otherUserId = (int) $rawId;
            if ($otherUserId <= 0 || $otherUserId === $sessionUserId) continue;

            $invitedUser = $this->userModel->findById($otherUserId);
            if ($invitedUser && $invitedUser['role'] === 'Reader') {
                $this->readingClubModel->addMember(
                    $clubId,
                    $otherUserId,
                    (string) $invitedUser['email']
                );
            }
        }
    }

    return 'Club created successfully.';
}
```

---

## 7.5 Adding a Member

```php
// app/models/ReadingClub.php

public function addMember(int $clubId, int $userId, string $email): bool
{
    // First check: is this user already a member?
    $chk = $this->db->prepare(
        'SELECT 1 FROM club_members
         WHERE club_id = :club_id AND user_id = :uid
         LIMIT 1'
    );
    $chk->execute(array('club_id' => $clubId, 'uid' => $userId));

    if ($chk->fetch()) {
        return false;  // Already a member — do nothing
    }

    // Not yet a member — insert the row
    $ins = $this->db->prepare(
        'INSERT INTO club_members (club_id, user_id, email)
         VALUES (:club_id, :uid, :email)'
    );
    $ins->execute(array(
        'club_id' => $clubId,
        'uid'     => $userId,
        'email'   => $email,
    ));

    return true;
}
```

> **`SELECT 1`** — a common optimization. Instead of `SELECT *` (which loads
> all columns), `SELECT 1` just returns the number 1 if a row exists. We only
> care whether a row exists, not its data.

---

## 7.6 Finding Clubs a User Belongs To

```php
// app/models/ReadingClub.php

public function findClubsForUser(int $userId, ?string $email): array
{
    $em = ($email !== null && $email !== '') ? $email : '';

    $sql = 'SELECT DISTINCT rc.club_id,
                   rc.name,
                   rc.description,
                   rc.creator_email,
                   rc.created_at,
                   cr.book_title  AS my_book_title,   -- reader current read
                   cr.book_author AS my_book_author
            FROM reading_clubs rc
            INNER JOIN club_members cm ON cm.club_id = rc.club_id
            LEFT JOIN club_member_current_read cr
                   ON cr.club_id = rc.club_id
                  AND cr.user_id = :rid
            WHERE cm.user_id = :uid
               OR (
                   TRIM(LOWER(cm.email)) = TRIM(LOWER(:em_a))
                   AND LENGTH(TRIM(:em_b)) > 0
               )
            ORDER BY rc.created_at DESC';

    $stmt = $this->db->prepare($sql);
    $stmt->execute(array(
        'uid'  => $userId,
        'rid'  => $userId,
        'em_a' => $em,
        'em_b' => $em,
    ));

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();
}
```

Breaking down the SQL:

```sql
SELECT DISTINCT rc.*
-- DISTINCT: avoid duplicate club rows if user appears twice in club_members

FROM reading_clubs rc
INNER JOIN club_members cm ON cm.club_id = rc.club_id
-- Join clubs to their membership rows

LEFT JOIN club_member_current_read cr
       ON cr.club_id = rc.club_id AND cr.user_id = :rid
-- Also grab THIS reader's current-read for each club
-- LEFT JOIN: include the club even if no current-read exists yet

WHERE cm.user_id = :uid
   OR (TRIM(LOWER(cm.email)) = TRIM(LOWER(:em_a)) AND ...)
-- Match by user_id OR by email (for legacy rows where user_id is NULL)
```

---

## 7.7 Getting All Members' Current Reads

```php
// app/models/ReadingClub.php

public function getMemberReadsForClubs(array $clubIds): array
{
    if ($clubIds === array()) return array();

    // Build: ?,?,? placeholders for each club ID
    $placeholders = implode(',', array_fill(0, count($clubIds), '?'));

    $sql = "SELECT cm.club_id,
                   cm.user_id AS member_user_id,
                   COALESCE(u.username, cm.email) AS display_name,
                   cr.book_title,
                   cr.book_author
            FROM club_members cm
            LEFT JOIN users u ON u.user_id = cm.user_id
            LEFT JOIN club_member_current_read cr
                   ON cr.club_id = cm.club_id
                  AND cm.user_id IS NOT NULL
                  AND cr.user_id = cm.user_id
            WHERE cm.club_id IN ($placeholders)
            ORDER BY cm.club_id, display_name";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($clubIds);  // Pass array directly — matches ? placeholders
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();

    // Group results by club_id for easy lookup in the view
    $out = array();
    foreach ($rows as $r) {
        $cid = (int) $r['club_id'];
        if (!isset($out[$cid])) {
            $out[$cid] = array();
        }
        $out[$cid][] = array(
            'member_user_id' => $r['member_user_id'] !== null
                                 ? (int) $r['member_user_id'] : null,
            'display_name'   => (string) $r['display_name'],
            'book_title'     => isset($r['book_title']) && $r['book_title'] !== ''
                                 ? (string) $r['book_title'] : null,
            'book_author'    => isset($r['book_author']) && $r['book_author'] !== ''
                                 ? (string) $r['book_author'] : null,
        );
    }

    return $out;
    // Returns: [club_id => [['display_name'=>'...', 'book_title'=>'...'], ...]]
}
```

**`array_fill(0, count($clubIds), '?')`** — creates an array of `?` marks.
If `$clubIds = [1, 3, 5]`, this creates `['?', '?', '?']`.

**`implode(',', ...)`** — joins array with commas → `"?,?,?"`.

**`COALESCE(u.username, cm.email)`** — show username if user exists, otherwise show email.

---

## 7.8 Saving a Member's Current Read

```php
// app/models/ReadingClub.php

public function saveMyCurrentRead(
    int     $clubId,
    int     $userId,
    string  $bookTitle,
    ?string $bookAuthor
): bool {
    $title  = trim($bookTitle);
    $author = $bookAuthor !== null ? trim($bookAuthor) : '';

    // Empty title = reader wants to CLEAR their current read
    if ($title === '') {
        $this->db->prepare(
            'DELETE FROM club_member_current_read
             WHERE club_id = :c AND user_id = :u'
        )->execute(array('c' => $clubId, 'u' => $userId));
        return true;
    }

    // INSERT if no row exists, UPDATE if it does (upsert)
    $stmt = $this->db->prepare(
        'INSERT INTO club_member_current_read
         (club_id, user_id, book_title, book_author)
         VALUES (:c, :u, :t, :a)
         ON DUPLICATE KEY UPDATE
             book_title  = VALUES(book_title),
             book_author = VALUES(book_author),
             updated_at  = CURRENT_TIMESTAMP'
    );

    return $stmt->execute(array(
        'c' => $clubId,
        'u' => $userId,
        't' => $title,
        'a' => $author !== '' ? $author : null,
    ));
}
```

> **`ON DUPLICATE KEY UPDATE`** — MySQL's upsert syntax. If a row with the
> same `(club_id, user_id)` UNIQUE KEY already exists, UPDATE it instead of
> failing with a duplicate error. This saves one extra SELECT query.

---

## 7.9 Discussion Threads — CRUD

### Creating a Thread

```php
public function addDiscussionThread(
    int    $clubId,
    int    $userId,
    string $title,
    string $body
): ?int {
    $title = trim($title);
    $body  = trim($body);

    // Both fields required
    if ($title === '' || $body === '') {
        return null;
    }

    $stmt = $this->db->prepare(
        'INSERT INTO club_discussion_threads
         (club_id, user_id, title, body)
         VALUES (:c, :u, :t, :b)'
    );

    if (!$stmt->execute(array('c' => $clubId, 'u' => $userId, 't' => $title, 'b' => $body))) {
        return null;
    }

    return (int) $this->db->lastInsertId();
}
```

### Updating a Thread (Author Only)

```php
public function updateDiscussionThread(
    int    $threadId,
    int    $userId,    // must be the original author
    string $title,
    string $body
): bool {
    $title = trim($title);
    $body  = trim($body);

    if ($title === '' || $body === '') return false;

    $stmt = $this->db->prepare(
        'UPDATE club_discussion_threads
         SET title      = :t,
             body       = :b,
             updated_at = CURRENT_TIMESTAMP
         WHERE thread_id = :id
           AND user_id   = :u'
        // AND user_id = :u  ← security: only author can edit their own thread
    );
    $stmt->execute(array('t' => $title, 'b' => $body, 'id' => $threadId, 'u' => $userId));

    return $stmt->rowCount() > 0;
    // rowCount() = 0 means no row matched (wrong id OR wrong author)
}
```

### Deleting a Thread (Author Only)

```php
public function deleteDiscussionThread(int $threadId, int $userId): bool
{
    $stmt = $this->db->prepare(
        'DELETE FROM club_discussion_threads
         WHERE thread_id = :id
           AND user_id   = :u'
        // AND user_id = :u  ← same security: only author can delete
    );
    $stmt->execute(array('id' => $threadId, 'u' => $userId));

    return $stmt->rowCount() > 0;
}
```

### Getting All Threads for a Club

```php
public function getDiscussionThreadsForClubs(array $clubIds): array
{
    if ($clubIds === array()) return array();

    $placeholders = implode(',', array_fill(0, count($clubIds), '?'));

    $sql = "SELECT t.thread_id,
                   t.club_id,
                   t.user_id,
                   t.title,
                   t.body,
                   t.created_at,
                   t.updated_at,
                   COALESCE(u.username, CONCAT('User #', t.user_id)) AS author_name
            FROM club_discussion_threads t
            INNER JOIN users u ON u.user_id = t.user_id
            WHERE t.club_id IN ($placeholders)
            ORDER BY t.club_id, t.created_at DESC";
    //                                      ^^^^ newest threads first

    $stmt = $this->db->prepare($sql);
    $stmt->execute($clubIds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();

    // Group by club_id — same pattern as getMemberReadsForClubs
    $out = array();
    foreach ($rows as $r) {
        $cid = (int) $r['club_id'];
        if (!isset($out[$cid])) $out[$cid] = array();
        $out[$cid][] = array(
            'thread_id'   => (int)    $r['thread_id'],
            'club_id'     => (int)    $r['club_id'],
            'user_id'     => (int)    $r['user_id'],
            'title'       => (string) $r['title'],
            'body'        => (string) $r['body'],
            'created_at'  => (string) $r['created_at'],
            'updated_at'  => (string) $r['updated_at'],
            'author_name' => (string) $r['author_name'],
        );
    }

    return $out;
}
```

---

## 7.10 Leaving a Club

```php
// app/models/ReadingClub.php

public function leaveClub(int $clubId, int $userId, ?string $email): bool
{
    // 1. Remove their current-read entry first (foreign key safety)
    $this->db->prepare(
        'DELETE FROM club_member_current_read
         WHERE club_id = :cid AND user_id = :uid'
    )->execute(array('cid' => $clubId, 'uid' => $userId));

    // 2. Remove the membership row
    // Handle both user_id match AND legacy email-only rows
    $em = ($email !== null && $email !== '') ? $email : '';

    $stmt = $this->db->prepare(
        'DELETE FROM club_members
         WHERE club_id = :cid
           AND (
               user_id = :uid
               OR (
                   user_id IS NULL
                   AND LENGTH(TRIM(:em_a)) > 0
                   AND TRIM(LOWER(email)) = TRIM(LOWER(:em_b))
               )
           )'
    );
    $stmt->execute(array(
        'cid'  => $clubId,
        'uid'  => $userId,
        'em_a' => $em,
        'em_b' => $em,
    ));

    return $stmt->rowCount() > 0;
}
```

---

## 7.11 The Controller — ReadingClubController

The controller coordinates all club actions. It follows the same pattern as everything else in MVC:

```php
// app/controllers/ReadingClubController.php (simplified)

class ReadingClubController
{
    public function __construct(
        private PDO         $db,
        private ReadingClub $readingClubModel,
        private User        $userModel
    ) {}

    public function handleRequest(): array
    {
        Session::ensureStarted();

        // Ensure tables exist
        $this->readingClubModel->ensureSchema();

        // Get the logged-in user's details
        $sessionUserId  = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
        $sessionUserRow = $sessionUserId ? $this->userModel->findById($sessionUserId) : null;
        $isReader       = $sessionUserRow && $sessionUserRow['role'] === 'Reader';

        // Get clubs this user belongs to
        $myClubs = $sessionUserRow
            ? $this->readingClubModel->findClubsForUser(
                $sessionUserId,
                (string) $sessionUserRow['email']
              )
            : array();

        $myClubIds = array_map(fn($row) => (int) $row['club_id'], $myClubs);

        // Handle form submissions
        $message = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $message = $this->handlePost($isReader, $sessionUserRow, $sessionUserId, $myClubs, $myClubIds);
        }

        // Load all clubs for the "Available Clubs" section
        $clubs = $this->readingClubModel->listAllClubsNewestFirst();

        // Load current-reads and discussions for my clubs
        $memberReadsByClub = array();
        $threadsByClub     = array();

        if ($sessionUserRow && !empty($myClubs)) {
            $clubIds = array_column($myClubs, 'club_id');

            $memberReadsByClub = $this->readingClubModel
                ->getMemberReadsForClubs($clubIds);

            $threadsByClub = $this->readingClubModel
                ->getDiscussionThreadsForClubs($clubIds);
        }

        // Return all variables the view needs
        return array(
            'sessionUserId'    => $sessionUserId,
            'sessionUserRow'   => $sessionUserRow,
            'isReader'         => $isReader,
            'myClubs'          => $myClubs,
            'myClubIds'        => $myClubIds,
            'message'          => $message,
            'clubs'            => $clubs,
            'memberReadsByClub' => $memberReadsByClub,
            'threadsByClub'    => $threadsByClub,
        );
    }
}
```

**`array_map(fn($row) => (int) $row['club_id'], $myClubs)`**
This applies a function to every element of `$myClubs` and returns a new array:
```php
// If $myClubs = [['club_id'=>'1', ...], ['club_id'=>'3', ...]]
// Result:         [1, 3]
```

**`array_column($myClubs, 'club_id')`**
Extracts all values of the `club_id` key from every sub-array:
```php
// If $myClubs = [['club_id'=>1, 'name'=>'A'], ['club_id'=>3, 'name'=>'B']]
// Result:         [1, 3]
```

---

## 7.12 The View — Rendering Club Data

The view `app/views/reader/member.php` displays everything. Here are the key sections:

### Showing Current Reads

```php
<?php foreach ($myClubs as $mc): ?>
    <div class="my-club-card">
        <h3><?php echo htmlspecialchars($mc['name']); ?></h3>

        <?php
        // Get the pre-grouped member reads for this club
        $clubReads = $memberReadsByClub[(int) $mc['club_id']] ?? array();
        ?>

        <?php if (!empty($clubReads)): ?>
            <div class="member-reads-list">
                <h4>What members are reading</h4>
                <ul>
                    <?php foreach ($clubReads as $mr): ?>
                        <li>
                            <strong>
                                <?php echo htmlspecialchars($mr['display_name']); ?>
                            </strong>

                            <?php if ($mr['book_title']): ?>
                                — <em><?php echo htmlspecialchars($mr['book_title']); ?></em>
                            <?php else: ?>
                                <span>— no book listed yet</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
```

### Saving a Current Read (Form)

```php
<form method="post" action="member.php">
    <!-- Tell the controller what action to take -->
    <input type="hidden" name="action"  value="save_current_read">
    <!-- Pass which club this is for -->
    <input type="hidden" name="club_id" value="<?php echo (int) $mc['club_id']; ?>">

    <label>Book title (what you are reading)</label>
    <input type="text" name="book_title"
           value="<?php echo htmlspecialchars((string) ($mc['my_book_title'] ?? '')); ?>"
           placeholder="e.g. The Midnight Library">

    <label>Author (optional)</label>
    <input type="text" name="book_author"
           value="<?php echo htmlspecialchars((string) ($mc['my_book_author'] ?? '')); ?>"
           placeholder="e.g. Matt Haig">

    <button type="submit" class="btn">Save current read</button>

    <p>Clear the title and save to remove your current read for this club.</p>
</form>
```

### Showing Discussion Threads

```php
<?php
$cid          = (int) $mc['club_id'];
$clubThreads  = $threadsByClub[$cid] ?? array();
?>

<div class="discussions-panel" id="discussions-c<?php echo $cid; ?>">
    <h4>Club discussions</h4>

    <?php foreach ($clubThreads as $th): ?>
        <?php
        $tid      = (int) $th['thread_id'];
        $isAuthor = (int) $th['user_id'] === $sessionUserId;
        ?>

        <div class="disc-thread">
            <h5><?php echo htmlspecialchars($th['title']); ?></h5>

            <div class="disc-meta">
                <?php echo htmlspecialchars($th['author_name']); ?>
                · <?php echo htmlspecialchars($th['created_at']); ?>
            </div>

            <div class="disc-body">
                <?php echo nl2br(htmlspecialchars($th['body'])); ?>
            </div>

            <?php if ($isAuthor): ?>
                <!-- Only author sees Edit and Delete buttons -->
                <div class="disc-actions">
                    <a href="member.php?edit_thread=<?php echo $tid; ?>">Edit</a>

                    <form method="post" action="member.php"
                          onsubmit="return confirm('Delete this discussion?');">
                        <input type="hidden" name="action"    value="discussion_delete">
                        <input type="hidden" name="thread_id" value="<?php echo $tid; ?>">
                        <button type="submit">Delete</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

    <?php endforeach; ?>

    <!-- New thread form -->
    <form method="post" action="member.php">
        <input type="hidden" name="action"  value="discussion_create">
        <input type="hidden" name="club_id" value="<?php echo $cid; ?>">

        <input type="text" name="disc_title" required placeholder="Topic title">
        <textarea name="disc_body" rows="4" required
                  placeholder="What do you want to discuss?"></textarea>

        <button type="submit" class="btn">Post discussion</button>
    </form>
</div>
```

> **`nl2br()`** — converts newlines (`\n`) to HTML `<br>` tags so
> multi-line messages display correctly in the browser.

---

## 7.13 Full Request Trace — Creating a Club

Let's trace every step when Tomas creates a club called "Midnight Readers":

```
═══ FORM SUBMISSION ════════════════════════════════════════

1. Tomas fills in the form:
   club_name        = "Midnight Readers"
   club_description = "We read together every Friday"
   invite_user_ids  = [3, 7]   ← two friends ticked

2. Browser sends POST to member.php

═══ CONTROLLER ═════════════════════════════════════════════

3. public/member.php runs:
   $controller = new ReadingClubController($db, $readingClub, $userModel)
   $viewData   = $controller->handleRequest()

4. handleRequest() calls handlePost()
   $action = "create_club"
   $name   = "Midnight Readers"
   $desc   = "We read together every Friday"

═══ MODEL — CREATE CLUB ════════════════════════════════════

5. ReadingClub::createClub() runs:
   SQL: INSERT INTO reading_clubs
        (name, description, creator_email, creator_user_id)
        VALUES ('Midnight Readers', 'We read...', 'tomas@mail.com', 1)
   
   Returns: club_id = 5  (auto-generated)

═══ MODEL — ADD MEMBERS ════════════════════════════════════

6. ReadingClub::addMember(5, 1, 'tomas@mail.com') runs:
   Check: SELECT 1 FROM club_members WHERE club_id=5 AND user_id=1
   → Not found → insert
   SQL: INSERT INTO club_members (club_id, user_id, email)
        VALUES (5, 1, 'tomas@mail.com')

7. Loop: invite_user_ids = [3, 7]

   User 3 found, role=Reader:
   ReadingClub::addMember(5, 3, 'mina@mail.com')
   SQL: INSERT INTO club_members (club_id, user_id, email)
        VALUES (5, 3, 'mina@mail.com')

   User 7 found, role=Reader:
   ReadingClub::addMember(5, 7, 'sara@mail.com')
   SQL: INSERT INTO club_members (club_id, user_id, email)
        VALUES (5, 7, 'sara@mail.com')

═══ REFRESH DATA ════════════════════════════════════════════

8. myClubs refreshed:
   ReadingClub::findClubsForUser(1, 'tomas@mail.com')
   → Returns [{'club_id':5, 'name':'Midnight Readers', ...}]

9. memberReadsByClub loaded:
   ReadingClub::getMemberReadsForClubs([5])
   → Returns {5: [
       {display_name:'tomas', book_title: null},
       {display_name:'mina',  book_title: null},
       {display_name:'sara',  book_title: null},
     ]}

═══ VIEW ════════════════════════════════════════════════════

10. member.php view renders:
    "Midnight Readers" card shows in "Your reading clubs" section
    All 3 members listed with "no book listed yet"
    Empty discussion thread panel shown
    $message = "Club created successfully." displayed
```

---

## 7.14 Data Flow Summary

```
Browser form submit (POST)
        │
        ▼
public/member.php
        │ creates
        ▼
ReadingClubController::handleRequest()
        │
        ├── handlePost()
        │       │
        │       ├── ReadingClub::createClub()      → INSERT reading_clubs
        │       ├── ReadingClub::addMember()        → INSERT club_members (×N)
        │       ├── ReadingClub::saveMyCurrentRead()→ UPSERT current_read
        │       ├── ReadingClub::addDiscussionThread→ INSERT threads
        │       ├── ReadingClub::updateDiscussion.. → UPDATE threads
        │       ├── ReadingClub::deleteDiscussion.. → DELETE threads
        │       └── ReadingClub::leaveClub()        → DELETE membership
        │
        ├── listAllClubsNewestFirst()    → SELECT all clubs
        ├── findClubsForUser()           → SELECT user's clubs + current reads
        ├── getMemberReadsForClubs()     → SELECT all members' reads grouped
        └── getDiscussionThreadsForClubs()→ SELECT threads grouped
                │
                ▼
        View: app/views/reader/member.php
                │
                ▼
        HTML sent to browser
```

---

## Lesson 7 Summary

| Concept | What it means |
|---------|--------------|
| `CREATE TABLE IF NOT EXISTS` | Create table only if it doesn't already exist |
| `ON UPDATE CURRENT_TIMESTAMP` | Auto-update a column whenever the row changes |
| `ON DUPLICATE KEY UPDATE` | Insert if new, update if key already exists (upsert) |
| `ON DELETE CASCADE` | When parent row deleted, delete child rows automatically |
| `UNIQUE KEY (a, b)` | Combination of two columns must be unique |
| `information_schema.COLUMNS` | MySQL system table describing database structure |
| `SELECT 1` | Efficient existence check — returns 1 if row found |
| `COALESCE(a, b)` | Return `a` if not null, otherwise return `b` |
| `CONCAT()` | Join strings in MySQL: `CONCAT('User #', id)` |
| `nl2br()` | Convert newlines to HTML `<br>` tags |
| `array_column()` | Extract one column from an array of arrays |
| `array_map()` | Apply a function to every element of an array |
| `array_fill()` | Create array of repeated values (used for SQL placeholders) |
| Discussion CRUD | Create/Read/Update/Delete — only author can edit/delete own threads |

---

## Overall Progress — All Lessons So Far

| Lesson | Key Topics |
|--------|-----------|
| **1** | Client-Server, HTTP, PHP, MySQL, XAMPP, MVC, PDO, Foreign Keys |
| **2** | Variables, Arrays, Loops, Functions, Classes, $_POST/$_GET, Sessions |
| **3** | SQL CRUD, JOINs, Prepared Statements, Transactions, Singleton, Audit Log |
| **4** | Sessions, Password Hashing, Login/Logout, AuthMiddleware, XSS Defense |
| **5** | Cart (localStorage + Session + DB), AJAX, Checkout, Inventory Holds |
| **6** | Singleton Pattern, Observer Pattern, Strategy Pattern |
| **7** | Reading Clubs, Schema Bootstrap, UPSERT, Discussion Threads, Controller Flow |