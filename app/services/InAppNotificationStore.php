<?php

/**
 * FILE: InAppNotificationStore.php
 * PURPOSE: Persists lightweight in-app notifications as JSON files under `storage/in_app` and optionally logs rows to DB.
 * USED BY: `NotificationService` for reader/owner in-app alerts alongside email transports.
 * DESIGN PATTERN: None — hybrid filesystem + optional `NotificationsRepository` logging.
 */

/**
 * File-backed in-app feed plus optional rows in `notifications` (channel InApp).
 */
class InAppNotificationStore
{
    private string $baseDir;

    private ?PDO $db;

    public function __construct(?string $baseDir = null, ?PDO $db = null)
    {
        $this->baseDir = $baseDir ?? (dirname(__DIR__, 2) . '/storage/in_app');
        if (!is_dir($this->baseDir)) {
            @mkdir($this->baseDir, 0775, true);
        }
        $this->db = $db;
    }

    public function push(int $userId, string $title, string $body, string $category = 'info'): void
    {
        if ($this->db !== null) {
            try {
                require_once __DIR__ . '/../models/NotificationsRepository.php';
                $msg = trim($title . ': ' . $body);
                if (strlen($msg) > 65000) {
                    $msg = substr($msg, 0, 65000);
                }
                (new NotificationsRepository($this->db))->logInApp($userId, $msg);
            } catch (Throwable $e) {
                error_log('InApp DB notification: ' . $e->getMessage());
            }
        }

        $path = $this->pathForUser($userId);
        $list = array();
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            $list = is_array($decoded) ? $decoded : array();
        }

        array_unshift($list, array(
            'at' => gmdate('c'),
            'title' => $title,
            'body' => $body,
            'category' => $category,
        ));

        $list = array_slice($list, 0, 40);
        @file_put_contents($path, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    public function listFor(int $userId): array
    {
        $path = $this->pathForUser($userId);
        if (!is_file($path)) {
            return array();
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : array();
    }

    private function pathForUser(int $userId): string
    {
        return $this->baseDir . DIRECTORY_SEPARATOR . 'user_' . $userId . '.json';
    }
}
