<?php

session_start();

/**
 * FILE: mailbox.php
 * PURPOSE: Shows the user mailbox inbox and message detail view (emails captured via the configured mail transport).
 * USED BY: Browser route `/public/mailbox.php`, which includes `app/views/mailbox/inbox.php` after `MailboxService` builds view data.
 * DESIGN PATTERN: None (delegates mailbox parsing to `MailboxService` and `UserMailbox`).
 */

function ibrcn_mailbox_normalize_email(string $e): string
{
    return strtolower(trim($e));
}

function ibrcn_mailbox_get_mail_dir(): string
{
    $cfg = require dirname(__DIR__) . '/config/config.php';
    $mail = $cfg['mail'] ?? array();
    return rtrim((string) ($mail['file_dir'] ?? dirname(__DIR__) . '/storage/mail'), DIRECTORY_SEPARATOR);
}

/**
 * @return ?array{subject:string,date:string,to_raw:string,to_email:string,text_body:string,html_body:string}
 */
function ibrcn_mailbox_parse_mail_file(string $path): ?array
{
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return null;
    }

    if (!preg_match('/\[To\]\s*=>\s*(.+)/', $raw, $tm)) {
        return null;
    }
    $toRaw = trim($tm[1]);
    $toEmail = '';
    if (preg_match('/<([^>]+)>/', $toRaw, $em)) {
        $toEmail = trim($em[1]);
    } elseif (preg_match('/([\w.+-]+@[\w.-]+\.[a-z]{2,})/i', $toRaw, $em)) {
        $toEmail = trim($em[1]);
    }

    $subject = '';
    if (preg_match('/\[Subject\]\s*=>\s*(.+)/', $raw, $sm)) {
        $subject = trim($sm[1]);
    }

    $date = '';
    if (preg_match('/\[Date\]\s*=>\s*(.+)/', $raw, $dm)) {
        $date = trim($dm[1]);
    }

    $textBody = '';
    if (preg_match('/\[---TEXT---\]\s*=>\s*(.*?)\s*\[---HTML---\]/s', $raw, $tx)) {
        $textBody = trim($tx[1]);
    }

    $htmlBody = '';
    if (preg_match('/\[---HTML---\]\s*=>\s*(.*)/s', $raw, $hx)) {
        $htmlBody = trim($hx[1]);
        $htmlBody = preg_replace('/\)\s*$/', '', $htmlBody);
        $htmlBody = trim($htmlBody);
    }

    return array(
        'subject' => $subject,
        'date' => $date,
        'to_raw' => $toRaw,
        'to_email' => $toEmail,
        'text_body' => $textBody,
        'html_body' => $htmlBody,
    );
}

/**
 * @return list<array{filename:string,subject:string,date:string,to:string,preview:string}>
 */
function ibrcn_mailbox_list_for_email(string $userEmail): array
{
    $mailDir = ibrcn_mailbox_get_mail_dir();
    $want = ibrcn_mailbox_normalize_email($userEmail);
    if ($want === '') {
        return array();
    }

    $glob = glob($mailDir . DIRECTORY_SEPARATOR . '*.txt') ?: array();
    $out = array();

    foreach ($glob as $path) {
        $base = basename($path);
        $parsed = ibrcn_mailbox_parse_mail_file($path);
        if ($parsed === null) {
            continue;
        }
        $recv = ibrcn_mailbox_normalize_email($parsed['to_email'] ?? '');
        if ($recv !== $want) {
            continue;
        }

        $preview = $parsed['text_body'] ?? '';
        if ($preview === '') {
            $preview = isset($parsed['html_body']) ? strip_tags((string) $parsed['html_body']) : '';
        }
        $preview = preg_replace('/\s+/', ' ', $preview);
        $preview = trim((string) $preview);
        if (strlen($preview) > 140) {
            $preview = substr($preview, 0, 137) . '…';
        }

        $out[] = array(
            'filename' => $base,
            'subject' => $parsed['subject'] ?? '(No subject)',
            'date' => $parsed['date'] ?? '',
            'to' => $parsed['to_raw'] ?? '',
            'preview' => $preview,
        );
    }

    usort(
        $out,
        static function ($a, $b) {
            return strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
        }
    );

    return $out;
}

/**
 * @return ?array{subject:string,date:string,to_raw:string,to_email:string,text_body:string,html_body:string}
 */
function ibrcn_mailbox_get_for_recipient(string $filename, string $userEmail): ?array
{
    $mailDir = ibrcn_mailbox_get_mail_dir();
    if (!preg_match('/^[a-zA-Z0-9._-]+\.txt$/', $filename)) {
        return null;
    }

    $path = $mailDir . DIRECTORY_SEPARATOR . $filename;
    $realDir = realpath($mailDir);
    $realFile = realpath($path);
    if ($realDir === false || $realFile === false || !is_file($path)) {
        return null;
    }
    if (strncmp($realFile, $realDir, strlen($realDir)) !== 0) {
        return null;
    }

    $parsed = ibrcn_mailbox_parse_mail_file($path);
    if ($parsed === null) {
        return null;
    }

    $want = ibrcn_mailbox_normalize_email($userEmail);
    $recv = ibrcn_mailbox_normalize_email($parsed['to_email'] ?? '');
    if ($recv === '' || $recv !== $want) {
        return null;
    }

    return $parsed;
}

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/AuthMiddleware.php';
require_once __DIR__ . '/../app/models/User.php';

AuthMiddleware::requireAuthenticated();

$database = DB::getInstance();
$role = (string) ($_SESSION['role'] ?? '');
$userRow = (new User($database))->findById((int) $_SESSION['user_id']);
if (!$userRow) {
    $userEmail = '';
    $messages = array();
    $backHref = 'login.php';
    $readerHref = 'reader.php';
    $detail = null;
    $invalidSelection = false;
} else {
    $userEmail = (string) $userRow['email'];
    $messages = ibrcn_mailbox_list_for_email($userEmail);

    $backHref = 'reader.php';
    if ($role === 'Owner') {
        $backHref = 'owner.php';
    } elseif ($role === 'Admin') {
        $backHref = 'admin.php';
    }

    $detail = null;
    if (!empty($_GET['m'])) {
        $detail = ibrcn_mailbox_get_for_recipient((string) $_GET['m'], $userEmail);
    }

    $invalidSelection = !empty($_GET['m']) && $detail === null;
    $readerHref = 'reader.php';
}

if ($backHref === 'login.php') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../app/views/mailbox/inbox.php';
