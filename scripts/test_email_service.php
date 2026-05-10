<?php

require_once __DIR__ . '/../app/services/EmailService.php';

if ($argc < 2) {
    echo "Usage: php scripts/test_email_service.php recipient@example.com [Recipient Name] [--fake]\n";
    exit(1);
}

$recipientEmail = trim((string) $argv[1]);
$recipientName = trim((string) ($argv[2] ?? 'Test User'));
$fakeMode = in_array('--fake', $argv, true);

if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email address.\n";
    exit(1);
}

if ($fakeMode) {
    $config = require __DIR__ . '/../config/config.php';
    $config['mail']['fake'] = true;
    $config['mail']['enabled'] = true;
}

$emailService = new EmailService($fakeMode ? $config : null);
$sent = $emailService->sendOrderConfirmation($recipientEmail, $recipientName, 1001, 249.99, 'Demo Bookstore');

if ($sent) {
    if ($fakeMode) {
        $message = $emailService->getLastMessage();
        echo "Fake email prepared successfully.\n";
        echo "To: {$message['to_name']} <{$message['to_email']}>\n";
        echo "Subject: {$message['subject']}\n";
        echo "Preview saved in memory for this run.\n";
    }
    echo "Test email queued/sent successfully to {$recipientEmail}.\n";
    exit(0);
}

echo "Test email failed. Check mail.transport in config (file/api/smtp) and the PHP error log.\n";
exit(1);