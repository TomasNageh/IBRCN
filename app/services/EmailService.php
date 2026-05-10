<?php

/**
 * FILE: EmailService.php
 * PURPOSE: Sends templated emails through configurable transports (file/api/smtp/fake) and mirrors some sends into `notifications`.
 * USED BY: `NotificationService`, tests/scripts invoking mail directly.
 * DESIGN PATTERN: None — strategy-like transport switching implemented as internal methods (not the mandated Strategy pattern).
 */

/**
 * Email delivery with pluggable transports for local testing and sandbox APIs.
 *
 * Transports:
 * - file: writes messages under storage/mail (default for XAMPP local testing)
 * - api: Mailtrap Sending API (set mail.api_token or MAILTRAP_API_TOKEN)
 * - smtp: legacy SMTP with AUTH LOGIN
 * - fake: no network; records last message only
 */
class EmailService
{
    private array $config;
    private array $lastMessage = array();

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? require __DIR__ . '/../../config/config.php';
    }

    public function getLastMessage(): array
    {
        return $this->lastMessage;
    }

    private function rememberMessage(string $toEmail, string $toName, string $subject, string $htmlBody, string $altBody): void
    {
        $this->lastMessage = array(
            'to_email' => $toEmail,
            'to_name' => $toName,
            'subject' => $subject,
            'html_body' => $htmlBody,
            'alt_body' => $altBody,
        );
    }

    private function dispatch(string $toEmail, string $toName, string $subject, string $htmlBody, string $altBody): bool
    {
        $mail = $this->config['mail'] ?? array();

        if (empty($mail['enabled'])) {
            error_log('Email sending is disabled by configuration.');
            $this->recordEmailNotification($toEmail, $subject, $htmlBody);
            return true;
        }

        $this->rememberMessage($toEmail, $toName, $subject, $htmlBody, $altBody);

        if (!empty($mail['fake'])) {
            error_log('Email fake mode: message prepared without send — ' . $subject);
            $this->recordEmailNotification($toEmail, $subject, $htmlBody);
            return true;
        }

        $transport = (string) ($mail['transport'] ?? 'file');

        if ($transport === 'fake') {
            error_log('Email transport=fake: message prepared without send — ' . $subject);
            $this->recordEmailNotification($toEmail, $subject, $htmlBody);
            return true;
        }

        if ($transport === 'file') {
            $ok = $this->sendViaFile($mail, $toEmail, $toName, $subject, $htmlBody, $altBody);
            if ($ok) {
                $this->recordEmailNotification($toEmail, $subject, $htmlBody);
            }
            return $ok;
        }

        if ($transport === 'api') {
            $ok = $this->sendViaApi($mail, $toEmail, $toName, $subject, $htmlBody, $altBody);
            if ($ok) {
                $this->recordEmailNotification($toEmail, $subject, $htmlBody);
            }
            return $ok;
        }

        if ($transport === 'smtp') {
            $ok = $this->sendViaSmtp($mail, $toEmail, $toName, $subject, $htmlBody, $altBody);
            if ($ok) {
                $this->recordEmailNotification($toEmail, $subject, $htmlBody);
            }
            return $ok;
        }

        error_log('Unknown mail transport: ' . $transport);
        return false;
    }

    /**
     * Mirrors outbound email into the `notifications` table when the recipient email matches a user.
     */
    private function recordEmailNotification(string $toEmail, string $subject, string $htmlBody): void
    {
        try {
            require_once __DIR__ . '/../patterns/DB.php';
            require_once __DIR__ . '/../models/NotificationsRepository.php';
            $plain = $this->htmlToPlain($htmlBody);
            if (strlen($plain) > 400) {
                $plain = substr($plain, 0, 397) . '...';
            }
            (new NotificationsRepository(DB::getInstance()))->logEmailByAddress($toEmail, $subject, $plain);
        } catch (Throwable $e) {
            error_log('recordEmailNotification: ' . $e->getMessage());
        }
    }

    private function mailStorageDir(array $mail): string
    {
        $dir = (string) ($mail['file_dir'] ?? (dirname(__DIR__, 2) . '/storage/mail'));
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private function sendViaFile(array $mail, string $toEmail, string $toName, string $subject, string $htmlBody, string $altBody): bool
    {
        $dir = $this->mailStorageDir($mail);
        $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.txt';
        $path = $dir . DIRECTORY_SEPARATOR . $name;

        $block = array(
            'To' => $toName . ' <' . $toEmail . '>',
            'Subject' => $subject,
            'Date' => gmdate('c'),
            '---TEXT---' => $altBody,
            '---HTML---' => $htmlBody,
        );
        $written = @file_put_contents($path, print_r($block, true), LOCK_EX);
        if ($written === false) {
            error_log('Could not write mail file to ' . $path);
            return false;
        }

        if (!empty($mail['debug'])) {
            error_log('Email saved to file: ' . $path);
        }

        return true;
    }

    private function sendViaApi(array $mail, string $toEmail, string $toName, string $subject, string $htmlBody, string $altBody): bool
    {
        $apiUrl = (string) ($mail['api_url'] ?? 'https://send.api.mailtrap.io/api/send');
        $apiToken = (string) ($mail['api_token'] ?? '');
        if ($apiToken === '' && getenv('MAILTRAP_API_TOKEN')) {
            $apiToken = (string) getenv('MAILTRAP_API_TOKEN');
        }

        if ($apiToken === '') {
            error_log('Mail API token missing. Set mail.api_token or MAILTRAP_API_TOKEN.');
            return false;
        }

        $fromEmail = (string) ($mail['from_email'] ?? 'noreply@ibrcn.local');
        $fromName = (string) ($mail['from_name'] ?? 'IBRCN');
        $debug = !empty($mail['debug']);

        $payload = array(
            'from' => array('email' => $fromEmail, 'name' => $fromName),
            'to' => array(array('email' => $toEmail, 'name' => $toName)),
            'subject' => $subject,
            'text' => $altBody,
            'html' => $htmlBody,
        );

        $requestBody = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($requestBody === false) {
            error_log('Failed to encode mail API payload.');
            return false;
        }

        $headers = array(
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json',
            'Accept: application/json',
        );

        if (function_exists('curl_init')) {
            $curl = curl_init($apiUrl);
            curl_setopt_array($curl, array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $requestBody,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
            ));
            $responseBody = curl_exec($curl);
            $curlError = curl_error($curl);
            $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($responseBody === false) {
                error_log('Mail API request failed: ' . $curlError);
                return false;
            }
            if ($debug) {
                error_log('Mail API status: ' . $statusCode . ' body: ' . $responseBody);
            }

            return $statusCode >= 200 && $statusCode < 300;
        }

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $requestBody,
                'timeout' => 20,
                'ignore_errors' => true,
            ),
        ));
        $responseBody = @file_get_contents($apiUrl, false, $context);
        if ($responseBody === false) {
            error_log('Mail API request failed using file_get_contents().');
            return false;
        }

        $statusCode = 0;
        if (!empty($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches) === 1) {
            $statusCode = (int) $matches[1];
        }
        if ($debug) {
            error_log('Mail API status: ' . $statusCode . ' body: ' . $responseBody);
        }

        return $statusCode >= 200 && $statusCode < 300;
    }

    private function sendViaSmtp(array $mail, string $toEmail, string $toName, string $subject, string $htmlBody, string $altBody): bool
    {
        $host = (string) ($mail['smtp_host'] ?? 'sandbox.smtp.mailtrap.io');
        $port = (int) ($mail['smtp_port'] ?? 2525);
        $username = (string) ($mail['smtp_user'] ?? '');
        $password = (string) ($mail['smtp_pass'] ?? '');
        $secure = (string) ($mail['smtp_secure'] ?? '');
        $fromEmail = (string) ($mail['from_email'] ?? 'noreply@ibrcn.local');
        $fromName = (string) ($mail['from_name'] ?? 'IBRCN');
        $debug = !empty($mail['debug']);

        $errno = 0;
        $errstr = '';
        $socket = $this->openSmtpSocket($host, $port, $secure, $errno, $errstr);
        if (!$socket) {
            error_log("SMTP connect failed: {$errstr} ({$errno})");
            return false;
        }

        $result = $this->sendSmtpConversation(
            $socket,
            $host,
            $username,
            $password,
            $fromEmail,
            $fromName,
            $toEmail,
            $toName,
            $subject,
            $htmlBody,
            $altBody,
            $debug
        );
        fclose($socket);

        return $result;
    }

    private function openSmtpSocket(string $host, int $port, string $secure, ?int &$errno = null, ?string &$errstr = null)
    {
        $scheme = 'tcp';
        if ($secure === 'ssl') {
            $scheme = 'ssl';
        } elseif ($secure === 'tls') {
            $scheme = 'tls';
        }

        return @stream_socket_client("{$scheme}://{$host}:{$port}", $errno, $errstr, 15);
    }

    private function sendSmtpConversation(
        $socket,
        string $host,
        string $username,
        string $password,
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $altBody,
        bool $debug
    ): bool {
        stream_set_timeout($socket, 15);

        $readResponse = function () use ($socket): array {
            $lines = array();
            while (($line = fgets($socket, 515)) !== false) {
                $lines[] = trim($line);
                if (preg_match('/^\d{3}\s/', $line) === 1) {
                    break;
                }
            }

            return $lines;
        };

        $sendCommand = function (string $command) use ($socket, $debug, $readResponse): array {
            if ($debug) {
                error_log('SMTP >> ' . $command);
            }
            fwrite($socket, $command . "\r\n");
            $response = $readResponse();
            if ($debug) {
                error_log('SMTP << ' . implode(' | ', $response));
            }

            return $response;
        };

        $response = $readResponse();
        if (!$this->responseHasCode($response, array(220))) {
            error_log('SMTP greeting failed: ' . implode(' | ', $response));
            return false;
        }

        $response = $sendCommand('EHLO ' . gethostname());
        if (!$this->responseHasCode($response, array(250))) {
            error_log('SMTP EHLO failed: ' . implode(' | ', $response));
            return false;
        }

        $response = $sendCommand('AUTH LOGIN');
        if (!$this->responseHasCode($response, array(334))) {
            error_log('SMTP AUTH LOGIN failed: ' . implode(' | ', $response));
            return false;
        }

        $response = $sendCommand(base64_encode($username));
        if (!$this->responseHasCode($response, array(334))) {
            error_log('SMTP username rejected: ' . implode(' | ', $response));
            return false;
        }

        $response = $sendCommand(base64_encode($password));
        if (!$this->responseHasCode($response, array(235))) {
            error_log('SMTP password rejected: ' . implode(' | ', $response));
            return false;
        }

        $response = $sendCommand('MAIL FROM:<' . $fromEmail . '>');
        if (!$this->responseHasCode($response, array(250))) {
            error_log('SMTP MAIL FROM failed: ' . implode(' | ', $response));
            return false;
        }

        $response = $sendCommand('RCPT TO:<' . $toEmail . '>');
        if (!$this->responseHasCode($response, array(250, 251))) {
            error_log('SMTP RCPT TO failed: ' . implode(' | ', $response));
            return false;
        }

        $boundary = '=_IBRCN_' . bin2hex(random_bytes(8));
        $headers = array(
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'To: ' . $toName . ' <' . $toEmail . '>',
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        );

        $message = implode("\r\n", $headers) . "\r\n\r\n";
        $message .= '--' . $boundary . "\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $altBody . "\r\n\r\n";
        $message .= '--' . $boundary . "\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $htmlBody . "\r\n\r\n";
        $message .= '--' . $boundary . "--\r\n";

        $response = $sendCommand('DATA');
        if (!$this->responseHasCode($response, array(354))) {
            error_log('SMTP DATA failed: ' . implode(' | ', $response));
            return false;
        }

        fwrite($socket, str_replace("\r\n.", "\r\n..", $message) . "\r\n.\r\n");
        $response = $readResponse();
        if (!$this->responseHasCode($response, array(250))) {
            error_log('SMTP message send failed: ' . implode(' | ', $response));
            return false;
        }

        $sendCommand('QUIT');
        return true;
    }

    private function responseHasCode(array $response, array $allowedCodes): bool
    {
        if ($response === array()) {
            return false;
        }

        foreach ($allowedCodes as $code) {
            if (strpos($response[0], (string) $code) === 0) {
                return true;
            }
        }

        return false;
    }

    private function htmlToPlain(string $html): string
    {
        $plain = strip_tags(str_replace(array('<p>', '<br>', '<br/>', '<br />'), array("\n", "\n", "\n", "\n"), $html));

        return trim($plain);
    }

    /**
     * SR-INV-01: order confirmation to reader.
     */
    public function sendOrderConfirmation(string $toEmail, string $readerName, int $orderId, float $totalAmount, string $storeName): bool
    {
        $subject = "Order Confirmation - IBRCN (#{$orderId})";

        $body = '<h3>Thank you for your order, ' . htmlspecialchars($readerName) . '!</h3>';
        $body .= '<p>Your O2O order <strong>#' . (int) $orderId . '</strong> at <strong>' . htmlspecialchars($storeName) . '</strong> has been placed.</p>';
        $body .= '<p><strong>Total Amount:</strong> EGP ' . number_format($totalAmount, 2) . '</p>';
        $body .= '<p>We will notify you when your items are ready for local pickup.</p>';

        return $this->dispatch($toEmail, $readerName, $subject, $body, $this->htmlToPlain($body));
    }

    /**
     * Notify bookstore owner that a new order was placed (SR-INV-01).
     */
    public function sendOwnerNewOrderNotice(string $toEmail, string $ownerName, int $orderId, float $totalAmount, string $storeName): bool
    {
        $subject = "New IBRCN Order #{$orderId} — {$storeName}";

        $body = '<h3>New order at ' . htmlspecialchars($storeName) . '</h3>';
        $body .= '<p>Hello ' . htmlspecialchars($ownerName) . ',</p>';
        $body .= '<p>A reader placed order <strong>#' . (int) $orderId . '</strong> for a total of <strong>EGP ' . number_format($totalAmount, 2) . '</strong>.</p>';
        $body .= '<p>Please prepare the items and mark the order as ready when applicable.</p>';

        return $this->dispatch($toEmail, $ownerName, $subject, $body, $this->htmlToPlain($body));
    }

    /**
     * UC-38: store application decision.
     */
    public function sendStoreStatusUpdate(string $toEmail, string $ownerName, string $storeName, string $status): bool
    {
        $subject = 'IBRCN Store Application: ' . $status;

        $body = '<h3>Hello ' . htmlspecialchars($ownerName) . ',</h3>';
        $body .= '<p>The application for your bookstore <strong>' . htmlspecialchars($storeName) . '</strong> has been <strong>' . htmlspecialchars($status) . '</strong>.</p>';

        if ($status === 'Approved') {
            $body .= '<p>You can log in to the Owner Portal and manage your inventory.</p>';
        } else {
            $body .= '<p>Please contact support if you need more information.</p>';
        }

        return $this->dispatch($toEmail, $ownerName, $subject, $body, $this->htmlToPlain($body));
    }

    /**
     * UC-02: order ready for pickup.
     */
    public function sendOrderReadyForPickup(string $toEmail, string $readerName, int $orderId, string $storeName): bool
    {
        $subject = 'Your IBRCN order is ready for pickup';

        $body = '<h3>Hello ' . htmlspecialchars($readerName) . ',</h3>';
        $body .= '<p>Order <strong>#' . (int) $orderId . '</strong> is ready at <strong>' . htmlspecialchars($storeName) . '</strong>.</p>';
        $body .= '<p>Please bring your confirmation when you collect your books.</p>';

        return $this->dispatch($toEmail, $readerName, $subject, $body, $this->htmlToPlain($body));
    }

    public function sendWelcomeEmail(string $toEmail, string $displayName): bool
    {
        $subject = 'Welcome to IBRCN';

        $body = '<h3>Welcome, ' . htmlspecialchars($displayName) . '!</h3>';
        $body .= '<p>Your account is active. You can browse independent bookstores and place O2O pickup orders.</p>';

        return $this->dispatch($toEmail, $displayName, $subject, $body, $this->htmlToPlain($body));
    }
}
