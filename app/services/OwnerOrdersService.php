<?php

/**
 * FILE: OwnerOrdersService.php
 * PURPOSE: Owner order listing helpers and “mark ready” POST handling with pickup notifications.
 * USED BY: `public/owner-orders.php`.
 * DESIGN PATTERN: None — wraps `Order` + `NotificationService` for a thin endpoint script.
 */

require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/NotificationService.php';

/**
 * OwnerOrdersService
 *
 * Owner-facing order management workflow (list orders/items + mark-ready action).
 */
class OwnerOrdersService
{
    private Order $orderModel;
    private NotificationService $notifier;

    public function __construct(private PDO $db)
    {
        $this->orderModel = new Order($db, new Book($db));
        $this->notifier = new NotificationService(null, null, $db);
    }

    /**
     * Handle the mark-ready action.
     *
     * @return array{errorMessage:string,successMessage:string}
     */
    public function handlePost(array $post, int $ownerId): array
    {
        $errorMessage = '';
        $successMessage = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['mark_ready'], $post['order_id'])) {
            $orderId = (int) $post['order_id'];
            $result = $this->orderModel->markReady($ownerId, $orderId);
            if ($result['ok']) {
                $successMessage = (string) $result['message'];
                try {
                    $this->notifier->onOrderMarkedReady($ownerId, $orderId);
                } catch (Throwable $e) {
                    error_log('Notification on order ready: ' . $e->getMessage());
                }
            } else {
                $errorMessage = (string) $result['message'];
            }
        }

        return array('errorMessage' => $errorMessage, 'successMessage' => $successMessage);
    }

    /**
     * @return array{orders:mixed,orderItems:mixed,inAppNotifications:mixed}
     */
    public function loadViewData(int $ownerId): array
    {
        return array(
            'orders' => $this->orderModel->getOwnerOrders($ownerId),
            'orderItems' => $this->orderModel->getOwnerOrderItems($ownerId),
            'inAppNotifications' => $this->notifier->getInAppStore()->listFor($ownerId),
        );
    }
}

