<?php

/**
 * SINGLETON PATTERN — DB.php
 *
 * WHAT IS SINGLETON?
 * A Singleton means "only one of this object can ever exist at a time."
 * Think of it like the principal's office — there's only one, and everyone
 * goes to the same one.
 *
 * WHY DO WE USE IT HERE?
 * Every time a controller needs the database, instead of opening a brand new
 * connection (which is slow and wastes memory), they all share the exact same
 * one connection created once per page load.
 *
 * FILE: DB.php
 * PURPOSE: Provide exactly one shared PDO connection per HTTP request using credentials from `config/db_config.php`.
 * USED BY: Every model/service resolving `$pdo = DB::getInstance();`.
 * DESIGN PATTERN: Singleton (exactly one live PDO handle).
 */

// Manages database connection. Only one connection exists at a time (Singleton pattern).
class DB
{
    // Holds the one PDO connection reused everywhere during this PHP request lifecycle.
    private static ?PDO $instance = null;

    // Blocks direct instantiation outside this class so callers cannot accidentally splinter connections.
    private function __construct()
    {
    }

    // Blocks cloning of the singleton holder so duplicates cannot be created via clone operations.
    private function __clone()
    {
    }

    /**
     * Retrieves (and lazily constructs if missing) the shared PDO database connection for this request.
     *
     * @return PDO Fully configured PDO using identical DSN/attributes as the legacy Database wrapper class.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/db_config.php';

            try {
                $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['db']};charset=utf8mb4";
                self::$instance = new PDO($dsn, $config['user'], $config['pass']);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $exception) {
                die('Database connection failed: ' . $exception->getMessage());
            }
        }

        return self::$instance;
    }
}
