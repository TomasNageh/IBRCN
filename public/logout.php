<?php

/**
 * FILE: logout.php
 * PURPOSE: Ends the current session and redirects the user back to the login page.
 * USED BY: Logout links in reader/owner/admin views that send the browser to `/public/logout.php`.
 * DESIGN PATTERN: None — inline session teardown matching former `Session::destroy()` behavior.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION = array();

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();
header("Location: login.php");
exit;
