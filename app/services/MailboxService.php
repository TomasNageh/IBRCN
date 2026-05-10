<?php

/**
 * FILE: MailboxService.php
 * PURPOSE: Builds the mailbox view-model (messages list + detail selection) for `public/mailbox.php`.
 * USED BY: `public/mailbox.php`.
 * DESIGN PATTERN: None — composes `User` lookups with `UserMailbox` file parsing helpers.
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/UserMailbox.php';

/**
 * MailboxService
 *
 * Builds the mailbox view model for `public/mailbox.php`.
 * Keeps the endpoint focused on HTTP concerns.
 */
class MailboxService
{
    public function __construct(private PDO $db, private ?UserMailbox $mailbox = null)
    {
        $this->mailbox = $mailbox ?? new UserMailbox();
    }

    /**
     * @return array{
     *   userEmail:string,
     *   messages:array,
     *   backHref:string,
     *   readerHref:string,
     *   detail:?array,
     *   invalidSelection:bool
     * }
     */
    public function buildViewModel(int $userId, string $role, array $query): array
    {
        $userRow = (new User($this->db))->findById($userId);
        if (!$userRow) {
            return array(
                'userEmail' => '',
                'messages' => array(),
                'backHref' => 'login.php',
                'readerHref' => 'reader.php',
                'detail' => null,
                'invalidSelection' => false,
            );
        }

        $userEmail = (string) $userRow['email'];
        $messages = $this->mailbox->listForEmail($userEmail);

        $backHref = 'reader.php';
        if ($role === 'Owner') {
            $backHref = 'owner.php';
        } elseif ($role === 'Admin') {
            $backHref = 'admin.php';
        }

        $detail = null;
        if (!empty($query['m'])) {
            $detail = $this->mailbox->getForRecipient((string) $query['m'], $userEmail);
        }

        $invalidSelection = !empty($query['m']) && $detail === null;

        return array(
            'userEmail' => $userEmail,
            'messages' => $messages,
            'backHref' => $backHref,
            'readerHref' => 'reader.php',
            'detail' => $detail,
            'invalidSelection' => $invalidSelection,
        );
    }
}

