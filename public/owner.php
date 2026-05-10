<?php

/**
 * FILE: owner.php
 * PURPOSE: Owner portal landing endpoint that shows the owner home page.
 * USED BY: Browser route `/public/owner.php`, which includes `app/views/owner/home.php`.
 * DESIGN PATTERN: None (guards access using `AuthMiddleware`).
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/AuthMiddleware.php';
AuthMiddleware::requireRole('Owner');

require_once __DIR__ . '/../app/views/owner/home.php';
