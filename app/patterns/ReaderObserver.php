<?php

/**
 * OBSERVER PATTERN — ReaderObserver.php (observer role)
 *
 * WHAT IS OBSERVER?
 * Observers listen for announcements from `EventPublisher` and react without the publisher enumerating recipients.
 *
 * WHY DO WE USE IT HERE?
 * Reader-facing emails/in-app pushes stay centralized here so `NotificationService` only publishes facts once per lifecycle edge.
 *
 * FILE: ReaderObserver.php
 * PURPOSE: Implements reader alerts triggered after checkout confirmation slices, pickup-ready notices, and welcome messaging.
 * USED BY: `EventPublisher` subscriptions wired inside `NotificationService`.
 * DESIGN PATTERN: Observer (responds to publish/update notifications).
 */

require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/../services/InAppNotificationStore.php';
require_once __DIR__ . '/../models/User.php';

// Listens for reader-facing notification events and forwards them through EmailService + InAppNotificationStore unchanged.
class ReaderObserver
{
    public function __construct(
        private EmailService $emailService,
        private InAppNotificationStore $inAppNotificationStore,
        private PDO $databaseConnection
    ) {
    }

    /**
     * Handles domain announcements forwarded by `EventPublisher::publish()` when reader-touching outcomes occur.
     *
     * @param string               $eventType Symbolic channel mirroring NotificationService historical hooks.
     * @param array<string, mixed> $payload Structured details identical to pre-refactor inline notification blocks.
     * @return void
     */
    public function update(string $eventType, array $payload): void
    {
        if ($eventType === 'checkout_success') {
            $this->notifyReaderAfterCheckoutOrderLines($payload);
            return;
        }

        if ($eventType === 'order_ready_for_pickup') {
            $this->notifyReaderOrderReadyForPickup($payload);
            return;
        }

        if ($eventType === 'user_registered') {
            $this->notifyReaderWelcomeAfterRegistration($payload);
        }
    }

    /**
     * Sends per-order reader confirmations identical to legacy NotificationService::onCheckoutSuccess reader branch.
     *
     * @param array<string, mixed> $payload Expects keys reader_id, orders (list of order summaries).
     * @return void
     */
    private function notifyReaderAfterCheckoutOrderLines(array $payload): void
    {
        $readerId = (int) ($payload['reader_id'] ?? 0);
        $orders = isset($payload['orders']) && is_array($payload['orders']) ? $payload['orders'] : array();
        if ($readerId <= 0 || $orders === array()) {
            return;
        }

        $userModel = new User($this->databaseConnection);
        $reader = $userModel->findById($readerId);
        if (!$reader) {
            return;
        }

        foreach ($orders as $orderRow) {
            $orderId = (int) ($orderRow['order_id'] ?? 0);
            $storeName = (string) ($orderRow['store_name'] ?? 'Bookstore');
            $totalAmount = (float) ($orderRow['total_amount'] ?? 0);

            $this->emailService->sendOrderConfirmation(
                (string) $reader['email'],
                (string) $reader['username'],
                $orderId,
                $totalAmount,
                $storeName
            );

            $this->inAppNotificationStore->push(
                $readerId,
                'Order placed',
                'Order #' . $orderId . ' at ' . $storeName . ' for EGP ' . number_format($totalAmount, 2) . '.',
                'order'
            );
        }
    }

    /**
     * Mirrors legacy pickup-ready reader messaging paths without altering subjects/bodies.
     *
     * @param array<string, mixed> $payload Expects keys context (array), order_id (int).
     * @return void
     */
    private function notifyReaderOrderReadyForPickup(array $payload): void
    {
        $context = isset($payload['context']) && is_array($payload['context']) ? $payload['context'] : array();
        $orderId = (int) ($payload['order_id'] ?? 0);
        if ($context === array() || $orderId <= 0) {
            return;
        }

        $readerId = (int) ($context['reader_id'] ?? 0);

        $this->emailService->sendOrderReadyForPickup(
            (string) $context['reader_email'],
            (string) $context['reader_name'],
            $orderId,
            (string) $context['store_name']
        );

        $this->inAppNotificationStore->push(
            $readerId,
            'Order ready for pickup',
            'Order #' . $orderId . ' at ' . (string) $context['store_name'] . ' is ready.',
            'order'
        );
    }

    /**
     * Sends welcome messaging identical to legacy NotificationService::onUserRegistered reader touches.
     *
     * @param array<string, mixed> $payload Expects user_id, email, username strings/ints matching registration controller output.
     * @return void
     */
    private function notifyReaderWelcomeAfterRegistration(array $payload): void
    {
        $userId = (int) ($payload['user_id'] ?? 0);
        $email = (string) ($payload['email'] ?? '');
        $username = (string) ($payload['username'] ?? '');
        if ($userId <= 0 || $email === '' || $username === '') {
            return;
        }

        $this->emailService->sendWelcomeEmail($email, $username);
        $this->inAppNotificationStore->push(
            $userId,
            'Welcome to IBRCN',
            'Your account is ready. Browse stores and place pickup orders.',
            'account'
        );
    }
}
