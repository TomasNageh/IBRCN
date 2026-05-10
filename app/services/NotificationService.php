<?php

/**
 * FILE: NotificationService.php
 * PURPOSE: Bridges commerce lifecycle milestones to email transports plus in-app feeds; reader-visible segments publish through Observer wiring.
 * USED BY: Checkout endpoints, owner order readiness handlers, admin onboarding helpers invoking welcome/store notices.
 * DESIGN PATTERN: Observer (`EventPublisher` + `ReaderObserver`) routes reader-touching alerts without nested subscriber lists here.
 */

require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/InAppNotificationStore.php';
require_once __DIR__ . '/../patterns/DB.php';
require_once __DIR__ . '/../patterns/EventPublisher.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Admin.php';

// Coordinates transactional messaging while delegating reader broadcasts to Observer collaborators registered during construction.
class NotificationService
{
    private EmailService $email;

    private InAppNotificationStore $inApp;

    private PDO $db;

    private EventPublisher $eventPublisher;

    /**
     * Wires optional dependency overrides for tests while defaulting to shared singleton PDO + concrete stores/services.
     *
     * @param EmailService|null           $email Nullable transport stub allowing mocked sends during isolated PHPUnit-style runs.
     * @param InAppNotificationStore|null $inApp Nullable store stub capturing pushes without touching filesystem tables.
     * @param PDO|null                    $db    Nullable PDO override when callers already participate inside transactions.
     * @return void
     */
    public function __construct(?EmailService $email = null, ?InAppNotificationStore $inApp = null, ?PDO $db = null)
    {
        $this->db = $db ?? DB::getInstance();
        $this->email = $email ?? new EmailService();
        $this->inApp = $inApp ?? new InAppNotificationStore(null, $this->db);
        $this->eventPublisher = new EventPublisher();
        $this->eventPublisher->subscribe(new ReaderObserver($this->email, $this->inApp, $this->db));
    }

    /**
     * Returns the backing in-app notification persistence helper for mailbox dashboards needing raw queue inspection hooks.
     *
     * @return InAppNotificationStore Shared instance constructed alongside this notifier for consistent PDO references.
     */
    public function getInAppStore(): InAppNotificationStore
    {
        return $this->inApp;
    }

    /**
     * After checkout: publishes reader confirmations via Observer, then notifies owners per sub-order (SR-INV-01 / SR-INV-02).
     *
     * @param int                             $readerId Session-backed reader account credited with placed pickups.
     * @param array<int, array<string, mixed>> $orders   Aggregated order summaries grouped per bookstore checkout spawn.
     * @return void
     */
    public function onCheckoutSuccess(int $readerId, array $orders): void
    {
        if ($orders === array()) {
            return;
        }

        $userModel = new User($this->db);
        if (!$userModel->findById($readerId)) {
            return;
        }

        $this->eventPublisher->publish(
            'checkout_success',
            array(
                'reader_id' => $readerId,
                'orders' => $orders,
            )
        );

        $orderModel = new Order($this->db, new Book($this->db));

        foreach ($orders as $orderSummary) {
            $orderId = (int) ($orderSummary['order_id'] ?? 0);
            $storeId = (int) ($orderSummary['store_id'] ?? 0);
            $totalAmount = (float) ($orderSummary['total_amount'] ?? 0);

            $ownerContact = $orderModel->getStoreOwnerContact($storeId);
            if ($ownerContact) {
                $this->email->sendOwnerNewOrderNotice(
                    (string) $ownerContact['email'],
                    (string) $ownerContact['username'],
                    $orderId,
                    $totalAmount,
                    (string) $ownerContact['store_name']
                );
                $this->inApp->push(
                    (int) $ownerContact['user_id'],
                    'New customer order',
                    'Order #' . $orderId . ' — EGP ' . number_format($totalAmount, 2) . '.',
                    'order'
                );
            }
        }
    }

    /**
     * Sends owner-facing store lifecycle notices whenever administrative approval toggles persist into `stores.status` transitions.
     *
     * @param int    $storeId     Subject bookstore primary key referenced inside admin moderation tooling.
     * @param string $statusLabel Human-readable label mirrored in UI badges ("Approved", etc.).
     * @return void
     */
    public function onStoreStatusChanged(int $storeId, string $statusLabel): void
    {
        $row = Admin::getStoreWithOwner($storeId);
        if (!$row) {
            return;
        }

        $this->email->sendStoreStatusUpdate(
            (string) $row['email'],
            (string) $row['username'],
            (string) $row['name'],
            $statusLabel
        );

        $this->inApp->push(
            (int) $row['owner_id'],
            'Store application ' . $statusLabel,
            'Your store "' . (string) $row['name'] . '" is now ' . $statusLabel . '.',
            'store'
        );
    }

    /**
     * Alerts readers that pickup readiness occurred by publishing observer-friendly payloads reconstructed from order context queries.
     *
     * @param int $ownerId Authenticated owner credited with flipping readiness flags inside fulfillment dashboards.
     * @param int $orderId Customer order identifier tying notifications back to reader-visible history timelines.
     * @return void
     */
    public function onOrderMarkedReady(int $ownerId, int $orderId): void
    {
        $orderModel = new Order($this->db, new Book($this->db));
        $pickupContext = $orderModel->getPickupNotifyContext($ownerId, $orderId);
        if (!$pickupContext) {
            return;
        }

        $this->eventPublisher->publish(
            'order_ready_for_pickup',
            array(
                'context' => $pickupContext,
                'order_id' => $orderId,
            )
        );
    }

    /**
     * Publishes registration celebrations so ReaderObserver mirrors legacy welcome email/in-app pairings for freshly inserted accounts.
     *
     * @param int    $userId   Newly inserted surrogate key assigned inside transactional signup handlers.
     * @param string $email    Mailbox used for welcome templates + audit correlation hooks downstream.
     * @param string $username Friendly handle surfaced inside greeting copy blocks unchanged from legacy wording.
     * @return void
     */
    public function onUserRegistered(int $userId, string $email, string $username): void
    {
        $this->eventPublisher->publish(
            'user_registered',
            array(
                'user_id' => $userId,
                'email' => $email,
                'username' => $username,
            )
        );
    }
}
