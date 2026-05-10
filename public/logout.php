<?php

/**
 * FILE: logout.php
 * PURPOSE: Ends the current session and redirects the user back to the login page.
 * USED BY: Logout links in reader/owner/admin views that send the browser to `/public/logout.php`.
 * DESIGN PATTERN: None (uses `Session` helper to destroy session state).
 */

require_once __DIR__ . '/../app/services/Session.php';

Session::destroy();
header("Location: login.php");
exit;
