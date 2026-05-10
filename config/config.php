<?php

/**
 * FILE: config.php
 * PURPOSE: Backward-compatible configuration loader kept so scripts requiring `config/config.php` continue working unchanged.
 * USED BY: `app/bootstrap.php`, CLI scripts under `scripts/` expecting `$config = require ...`.
 * DESIGN PATTERN: None — delegates to `constants.php`.
 */

return require __DIR__ . '/constants.php';
