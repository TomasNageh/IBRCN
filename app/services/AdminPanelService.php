<?php

/**
 * FILE: AdminPanelService.php
 * PURPOSE: Executes admin POST actions (approve stores/users, etc.) and prepares admin home view-model fragments.
 * USED BY: `public/admin.php`, `public/admin-handler.php`.
 * DESIGN PATTERN: None — orchestrates `Admin` static model calls + `NotificationService` side effects.
 */

require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/NotificationService.php';

/**
 * AdminPanelService
 *
 * Encapsulates the admin panel POST actions and view-model loading.
 * Endpoints like `public/admin.php` should remain thin and consistent.
 */
class AdminPanelService
{
    /**
     * Handle a single POST action from the admin panel.
     *
     * @return array{message:string,messageType:string}
     */
    public function handlePost(array $post): array
    {
        $message = '';
        $messageType = '';

        if (!isset($post['action'])) {
            return array('message' => $message, 'messageType' => $messageType);
        }

        $action = (string) $post['action'];

        if ($action === 'approve_store') {
            $storeId = (int) ($post['store_id'] ?? 0);
            if ($storeId > 0 && Admin::approveStore($storeId)) {
                $message = "Store #{$storeId} approved successfully.";
                $messageType = 'success';
                $this->notifyStoreStatusChanged($storeId, 'Approved', 'Notification on store approval');
            } else {
                $message = 'Failed to approve store.';
                $messageType = 'error';
            }
        } elseif ($action === 'reject_store') {
            $storeId = (int) ($post['store_id'] ?? 0);
            if ($storeId > 0 && Admin::rejectStore($storeId)) {
                $message = "Store #{$storeId} rejected.";
                $messageType = 'success';
                $this->notifyStoreStatusChanged($storeId, 'Rejected', 'Notification on store rejection');
            } else {
                $message = 'Failed to reject store.';
                $messageType = 'error';
            }
        } elseif ($action === 'change_role') {
            $userId = (int) ($post['user_id'] ?? 0);
            $newRole = trim((string) ($post['new_role'] ?? ''));
            if ($userId > 0 && Admin::updateUserRole($userId, $newRole)) {
                $message = "User role updated to {$newRole}.";
                $messageType = 'success';
            } else {
                $message = 'Failed to update user role.';
                $messageType = 'error';
            }
        } elseif ($action === 'delete_user') {
            $userId = (int) ($post['user_id'] ?? 0);
            if ($userId > 0 && Admin::deleteUser($userId)) {
                $message = 'User deleted successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete user.';
                $messageType = 'error';
            }
        } elseif ($action === 'create_user') {
            $username = trim((string) ($post['username'] ?? ''));
            $email = trim((string) ($post['email'] ?? ''));
            $password = (string) ($post['password'] ?? '');
            $role = trim((string) ($post['role'] ?? 'Reader'));

            if ($username === '' || $email === '' || $password === '') {
                $message = 'Username, email, and password are required.';
                $messageType = 'error';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Invalid email format.';
                $messageType = 'error';
            } elseif (strlen($password) < 8) {
                $message = 'Password must be at least 8 characters long.';
                $messageType = 'error';
            } else {
                $newUserId = Admin::createUser($username, $email, $password, $role);
                if ($newUserId !== false) {
                    $message = "User '{$username}' created successfully.";
                    $messageType = 'success';
                    $this->notifyUserRegistered((int) $newUserId, $email, $username);
                } else {
                    $message = 'Failed to create user. Username or email may already exist.';
                    $messageType = 'error';
                }
            }
        } elseif ($action === 'update_user') {
            $userId = (int) ($post['user_id'] ?? 0);
            $username = trim((string) ($post['username'] ?? ''));
            $email = trim((string) ($post['email'] ?? ''));
            $role = trim((string) ($post['role'] ?? 'Reader'));

            if ($username === '' || $email === '') {
                $message = 'Username and email are required.';
                $messageType = 'error';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Invalid email format.';
                $messageType = 'error';
            } elseif ($userId > 0 && Admin::updateUser($userId, $username, $email, $role)) {
                $message = 'User updated successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to update user. Username or email may already exist.';
                $messageType = 'error';
            }
        }

        return array('message' => $message, 'messageType' => $messageType);
    }

    /**
     * Load the datasets required by the admin home view.
     *
     * @return array{pendingStores:mixed,approvedStores:mixed,allUsers:mixed}
     */
    public function loadViewData(): array
    {
        return array(
            'pendingStores' => Admin::getPendingStores(),
            'approvedStores' => Admin::getApprovedStores(),
            'allUsers' => Admin::getAllUsers(),
        );
    }

    private function notifyStoreStatusChanged(int $storeId, string $status, string $logPrefix): void
    {
        try {
            (new NotificationService())->onStoreStatusChanged($storeId, $status);
        } catch (Throwable $e) {
            error_log($logPrefix . ': ' . $e->getMessage());
        }
    }

    private function notifyUserRegistered(int $userId, string $email, string $username): void
    {
        try {
            (new NotificationService())->onUserRegistered($userId, $email, $username);
        } catch (Throwable $e) {
            error_log('Welcome notification (admin create user): ' . $e->getMessage());
        }
    }
}

