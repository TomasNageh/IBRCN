<?php

/**
 * FILE: NotificationsRepository.php
 * PURPOSE: Persists rows into `notifications` for mailbox/history views (Email and InApp channels).
 * USED BY: `InAppNotificationStore`, `EmailService::recordEmailNotification()`.
 * DESIGN PATTERN: None — thin PDO repository with guarded inserts.
 */

// Writes notification audit rows tied to recipient users without altering message wording supplied by callers (repository).
class NotificationsRepository
{
    /**
     * Binds PDO provided by caller (typically `DB::getInstance()` shared handle).
     *
     * @param PDO $databaseConnection Active connection used for prepared INSERT statements only.
     * @return void
     */
    public function __construct(private PDO $databaseConnection)
    {
    }

    /**
     * Records an in-app notification line when recipient id and message body are non-empty.
     *
     * @param int    $recipientId Target `users.user_id` receiving the visible notification row.
     * @param string $message     Human-readable body stored for inbox rendering (already finalized upstream).
     * @return void
     */
    public function logInApp(int $recipientId, string $message): void
    {
        if ($recipientId <= 0 || $message === '') {
            return;
        }
        $this->insert($recipientId, $message, 'InApp');
    }

    /**
     * Logs an email notification if the recipient address matches a user row (case-insensitive email equality).
     *
     * @param string $toEmail Lower/upper variant mailbox string looked up against `users.email`.
     * @param string $subject Primary subject line concatenated into stored message when detail absent.
     * @param string $detail  Optional plain snippet appended after em dash for auditing context.
     * @return void
     */
    public function logEmailByAddress(string $toEmail, string $subject, string $detail = ''): void
    {
        $email = trim($toEmail);
        if ($email === '') {
            return;
        }
        $statement = $this->databaseConnection->prepare('SELECT user_id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $statement->execute(array('email' => $email));
        $row = $statement->fetch();
        if (!$row) {
            return;
        }
        $message = $subject;
        if ($detail !== '') {
            $message .= ' — ' . $detail;
        }
        if (strlen($message) > 65000) {
            $message = substr($message, 0, 65000);
        }
        $this->insert((int) $row['user_id'], $message, 'Email');
    }

    /**
     * Logs outbound email metadata when recipient primary key is already known (skips address lookup SQL).
     *
     * @param int    $recipientId Known `users.user_id` backing mailbox dashboards/history panes.
     * @param string $subject     Subject line feeding stored notification summary column semantics.
     * @param string $detail      Optional supplemental plain text merged after subject identically to address-based logging.
     * @return void
     */
    public function logEmailForUser(int $recipientId, string $subject, string $detail = ''): void
    {
        if ($recipientId <= 0) {
            return;
        }
        $message = $subject;
        if ($detail !== '') {
            $message .= ' — ' . $detail;
        }
        if (strlen($message) > 65000) {
            $message = substr($message, 0, 65000);
        }
        $this->insert($recipientId, $message, 'Email');
    }

    /**
     * Shared INSERT helper enforcing allowed channel enumeration before touching database constraints.
     *
     * @param int    $recipientId Recipient user id targeted by notification subsystem ACL checks upstream.
     * @param string $message     Final serialized human-readable payload capped for TEXT-like safety margins.
     * @param string $channel     Literal discriminator restricted to Email/InApp matching legacy schema expectations.
     * @return void
     */
    private function insert(int $recipientId, string $message, string $channel): void
    {
        if (!in_array($channel, array('Email', 'InApp'), true)) {
            return;
        }
        try {
            $statement = $this->databaseConnection->prepare(
                'INSERT INTO notifications (recipient_id, message, channel) VALUES (:rid, :msg, :ch)'
            );
            $statement->execute(array(
                'rid' => $recipientId,
                'msg' => $message,
                'ch' => $channel,
            ));
        } catch (Throwable $exception) {
            error_log('notifications insert: ' . $exception->getMessage());
        }
    }
}
