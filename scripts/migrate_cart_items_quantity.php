<?php
/**
 * One-time: add quantity to cart_items so a single row can represent qty > 1.
 * Run: php scripts/migrate_cart_items_quantity.php
 */
require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/app/patterns/DB.php';

$pdo = DB::getInstance();
$check = $pdo->query("SHOW COLUMNS FROM cart_items LIKE 'quantity'")->fetch();
if ($check) {
    echo "Column cart_items.quantity already exists.\n";
    exit(0);
}
$pdo->exec('ALTER TABLE cart_items ADD COLUMN quantity INT UNSIGNED NOT NULL DEFAULT 1 AFTER inventory_id');
echo "Added cart_items.quantity\n";
