-- Compatibility layer for old IBRCN PHP pages.
-- Run this AFTER importing ibrcn_schema.sql into MySQL.

USE ibrcn;

CREATE TABLE IF NOT EXISTS signup (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS member (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_store_name VARCHAR(120) NOT NULL,
    book_store_owner_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    order_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    store_id INT UNSIGNED NOT NULL,
    status ENUM('Placed', 'Ready', 'Collected') NOT NULL DEFAULT 'Placed',
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ready_at DATETIME NULL,
    collected_at DATETIME NULL,
    INDEX idx_orders_user_id (user_id),
    INDEX idx_orders_store_id (store_id),
    INDEX idx_orders_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    inventory_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    title_snapshot VARCHAR(255) NOT NULL,
    author_snapshot VARCHAR(255) NOT NULL DEFAULT '',
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    INDEX idx_order_items_order_id (order_id),
    INDEX idx_order_items_inventory_id (inventory_id),
    INDEX idx_order_items_book_id (book_id)
) ENGINE=InnoDB;
