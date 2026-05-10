<?php

/**
 * FILE: db_config.php
 * PURPOSE: Holds MySQL connection settings used by the DB Singleton (`app/patterns/DB.php`) only.
 * USED BY: `config/constants.php` (merged into full app config), `app/patterns/DB.php`
 * DESIGN PATTERN: None — plain configuration values (singleton consumes these credentials).
 */

// Returns database hostname or IP address shown to PDO DSN builder (matches legacy IBRCN local defaults).
return array(
    'host' => '127.0.0.1',
    // TCP port MySQL listens on (standard default).
    'port' => '3306',
    // Logical schema/database name inside MySQL.
    'db' => 'ibrcn',
    // MySQL username with privileges on `db`.
    'user' => 'root',
    // MySQL password string for `user` (empty string matches legacy local XAMPP defaults).
    'pass' => '',
);
