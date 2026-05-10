<?php

session_start();

/**
 * FILE: mailbox.php
 * PURPOSE: Shows the user mailbox inbox and message detail view (emails captured via the configured mail transport).
 * USED BY: Browser route `/public/mailbox.php`, which includes `app/views/mailbox/inbox.php` after `MailboxService` builds view data.
 * DESIGN PATTERN: None (delegates mailbox parsing to `MailboxService` and `UserMailbox`).
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/AuthMiddleware.php';
require_once __DIR__ . '/../app/services/MailboxService.php';

AuthMiddleware::requireAuthenticated();

$database = DB::getInstance();
$role = (string) ($_SESSION['role'] ?? '');
$mailboxService = new MailboxService($database);
$vm = $mailboxService->buildViewModel((int) $_SESSION['user_id'], $role, $_GET);

if ($vm['backHref'] === 'login.php') {
    header('Location: login.php');
    exit;
}

$userEmail = $vm['userEmail'];
$messages = $vm['messages'];
$backHref = $vm['backHref'];
$readerHref = $vm['readerHref'];
$detail = $vm['detail'];
$invalidSelection = $vm['invalidSelection'];

require_once __DIR__ . '/../app/views/mailbox/inbox.php';
