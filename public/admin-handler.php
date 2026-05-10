<?php

/**
 * FILE: admin-handler.php
 * PURPOSE: Admin endpoint variant that processes admin POST actions and renders the admin dashboard.
 * USED BY: Browser route `/public/admin-handler.php` (legacy alias for admin dashboard workflows).
 * DESIGN PATTERN: None (delegates to `AdminPanelService` and renders `app/views/admin/home.php`).
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/models/Admin.php';
require_once __DIR__ . '/../app/services/AuthMiddleware.php';
require_once __DIR__ . '/../app/services/NotificationService.php';

AuthMiddleware::requireRole('Admin');

$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = (string) $_POST['action'];

        if ($action === 'approve_store') {
            $storeId = (int) ($_POST['store_id'] ?? 0);
            if ($storeId > 0 && Admin::approveStore($storeId)) {
                $message = "Store #{$storeId} approved successfully.";
                $messageType = 'success';
                try {
                    (new NotificationService())->onStoreStatusChanged($storeId, 'Approved');
                } catch (Throwable $e) {
                    error_log('Notification on store approval: ' . $e->getMessage());
                }
            } else {
                $message = 'Failed to approve store.';
                $messageType = 'error';
            }
        } elseif ($action === 'reject_store') {
            $storeId = (int) ($_POST['store_id'] ?? 0);
            if ($storeId > 0 && Admin::rejectStore($storeId)) {
                $message = "Store #{$storeId} rejected.";
                $messageType = 'success';
                try {
                    (new NotificationService())->onStoreStatusChanged($storeId, 'Rejected');
                } catch (Throwable $e) {
                    error_log('Notification on store rejection: ' . $e->getMessage());
                }
            } else {
                $message = 'Failed to reject store.';
                $messageType = 'error';
            }
        } elseif ($action === 'change_role') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $newRole = trim((string) ($_POST['new_role'] ?? ''));
            if ($userId > 0 && Admin::updateUserRole($userId, $newRole)) {
                $message = "User role updated to {$newRole}.";
                $messageType = 'success';
            } else {
                $message = 'Failed to update user role.';
                $messageType = 'error';
            }
        } elseif ($action === 'delete_user') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            if ($userId > 0 && Admin::deleteUser($userId)) {
                $message = 'User deleted successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete user.';
                $messageType = 'error';
            }
        } elseif ($action === 'create_user') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $role = trim((string) ($_POST['role'] ?? 'Reader'));

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
                    try {
                        (new NotificationService())->onUserRegistered((int) $newUserId, $email, $username);
                    } catch (Throwable $e) {
                        error_log('Welcome notification (admin create user): ' . $e->getMessage());
                    }
                } else {
                    $message = 'Failed to create user. Username or email may already exist.';
                    $messageType = 'error';
                }
            }
        } elseif ($action === 'update_user') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $username = trim((string) ($_POST['username'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $role = trim((string) ($_POST['role'] ?? 'Reader'));

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
    }
}

$pendingStores = Admin::getPendingStores();
$approvedStores = Admin::getApprovedStores();
$allUsers = Admin::getAllUsers();

require_once __DIR__ . '/../app/views/admin/home.php';
