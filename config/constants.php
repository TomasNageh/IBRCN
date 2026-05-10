<?php

/**
 * FILE: constants.php
 * PURPOSE: Loads DB credentials, defines global DB_* constants for legacy scripts, and merges mail/app configuration.
 * USED BY: `config/config.php` (backward-compatible entry point), `app/bootstrap.php` (via config wrapper when needed)
 * DESIGN PATTERN: None — centralized constants derived from configuration arrays.
 */

// Pull canonical PDO credential bundle consumed by `DB::getInstance()` internals (never echoed).
$dbCredentials = require __DIR__ . '/db_config.php';

// Preserve legacy defines relied upon by older pages/scripts expecting constants rather than arrays.
if (!defined('DB_HOST')) {
    define('DB_HOST', $dbCredentials['host']);
}
if (!defined('DB_PORT')) {
    define('DB_PORT', $dbCredentials['port']);
}
if (!defined('DB_NAME')) {
    define('DB_NAME', $dbCredentials['db']);
}
if (!defined('DB_USER')) {
    define('DB_USER', $dbCredentials['user']);
}
if (!defined('DB_PASS')) {
    define('DB_PASS', $dbCredentials['pass']);
}

// Full merged configuration returned for callers using `$config = require 'config/config.php';`.
return array(
    'host' => $dbCredentials['host'],
    'port' => $dbCredentials['port'],
    'db' => $dbCredentials['db'],
    'user' => $dbCredentials['user'],
    'pass' => $dbCredentials['pass'],
    // Mail transport/settings unchanged from prior IBRCN configuration shape (EmailService consumes these keys).
    'mail' => array(
        'enabled' => true,
        'fake' => false,
        // Local testing: "file" writes under storage/mail; use "api" + MAILTRAP_API_TOKEN for Mailtrap; "smtp" for sandbox SMTP.
        'transport' => 'file',
        'file_dir' => __DIR__ . '/../storage/mail',
        'api_url' => 'https://send.api.mailtrap.io/api/send',
        'api_token' => getenv('MAILTRAP_API_TOKEN') ? (string) getenv('MAILTRAP_API_TOKEN') : '',
        'from_email' => 'noreply@ibrcn.local',
        'from_name' => 'IBRCN',
        'debug' => true,
        // Optional SMTP (used when transport => smtp), e.g. Mailtrap SMTP sandbox.
        'smtp_host' => getenv('MAIL_SMTP_HOST') ? (string) getenv('MAIL_SMTP_HOST') : '',
        'smtp_port' => getenv('MAIL_SMTP_PORT') ? (int) getenv('MAIL_SMTP_PORT') : 2525,
        'smtp_user' => getenv('MAIL_SMTP_USER') ? (string) getenv('MAIL_SMTP_USER') : '',
        'smtp_pass' => getenv('MAIL_SMTP_PASS') ? (string) getenv('MAIL_SMTP_PASS') : '',
        'smtp_secure' => getenv('MAIL_SMTP_SECURE') ? (string) getenv('MAIL_SMTP_SECURE') : '',
    ),
);
