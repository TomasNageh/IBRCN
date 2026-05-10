<?php

/**
 * FILE: UserMailbox.php
 * PURPOSE: Parses locally stored mail transport files (`storage/mail`) into inbox lists + preview snippets per recipient email.
 * USED BY: `MailboxService`.
 * DESIGN PATTERN: None — filesystem scanner + lightweight MIME-ish text parsing helpers.
 */

/**
 * Reads IBRCN “file mail” records from storage/mail and filters by recipient email.
 */
class UserMailbox
{
    private string $mailDir;

    public function __construct(?array $config = null)
    {
        $cfg = $config ?? require dirname(__DIR__, 2) . '/config/config.php';
        $mail = $cfg['mail'] ?? array();
        $this->mailDir = rtrim((string) ($mail['file_dir'] ?? dirname(__DIR__, 2) . '/storage/mail'), DIRECTORY_SEPARATOR);
    }

    /**
     * @return list<array{filename:string,subject:string,date:string,to:string,preview:string}>
     */
    public function listForEmail(string $userEmail): array
    {
        $want = $this->normalizeEmail($userEmail);
        if ($want === '') {
            return array();
        }

        $glob = glob($this->mailDir . DIRECTORY_SEPARATOR . '*.txt') ?: array();
        $out = array();

        foreach ($glob as $path) {
            $base = basename($path);
            $parsed = $this->parseMailFile($path);
            if ($parsed === null) {
                continue;
            }
            $recv = $this->normalizeEmail($parsed['to_email'] ?? '');
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
    public function getForRecipient(string $filename, string $userEmail): ?array
    {
        if (!preg_match('/^[a-zA-Z0-9._-]+\.txt$/', $filename)) {
            return null;
        }

        $path = $this->mailDir . DIRECTORY_SEPARATOR . $filename;
        $realDir = realpath($this->mailDir);
        $realFile = realpath($path);
        if ($realDir === false || $realFile === false || !is_file($path)) {
            return null;
        }
        if (strncmp($realFile, $realDir, strlen($realDir)) !== 0) {
            return null;
        }

        $parsed = $this->parseMailFile($path);
        if ($parsed === null) {
            return null;
        }

        $want = $this->normalizeEmail($userEmail);
        $recv = $this->normalizeEmail($parsed['to_email'] ?? '');
        if ($recv === '' || $recv !== $want) {
            return null;
        }

        return $parsed;
    }

    private function normalizeEmail(string $e): string
    {
        return strtolower(trim($e));
    }

    /**
     * @return ?array{subject:string,date:string,to_raw:string,to_email:string,text_body:string,html_body:string}
     */
    private function parseMailFile(string $path): ?array
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
}
