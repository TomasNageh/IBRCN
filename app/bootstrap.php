<?php

/**
 * FILE: bootstrap.php
 * PURPOSE: Minimal shared bootstrap for every `public/*.php` endpoint: loads config/constants, DB Singleton, session helpers, redirects.
 * USED BY: Entire site front controller scripts plus CLI utilities requiring identical PDO/session baseline initialization flows.
 * DESIGN PATTERN: None — orchestrates Singleton (`DB`) availability without owning business workflows itself.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/patterns/DB.php';
require_once __DIR__ . '/services/Session.php';
require_once __DIR__ . '/services/RoleRedirector.php';
