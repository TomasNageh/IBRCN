<?php

require_once __DIR__ . '/../patterns/DB.php';

/**
 * FILE: Admin.php
 * PURPOSE: Static query helpers backing moderation dashboards (stores/users inventory) shared by admin endpoints and PDF exporters.
 * USED BY: `public/admin.php`, `AdminPanelService`, PDF reports requiring aggregated operational snapshots.
 * DESIGN PATTERN: None — relies on `DB` Singleton for lazy PDO reuse inside static accessors.
 */

// Provides admin-scope persistence accessors without instantiating controller-owned services (static model helpers).
class Admin
{
    private static $db = null;

    private static function getDb()
    {
        if (self::$db === null) {
            self::$db = DB::getInstance();
        }
        return self::$db;
    }

    // Bookstore approval/rejection
    public static function getPendingStores()
    {
        $db = self::getDb();
        $stmt = $db->prepare(
            'SELECT s.store_id, s.name, s.owner_id, s.region, s.status, u.username, u.email
            FROM stores s
            LEFT JOIN users u ON s.owner_id = u.user_id
            WHERE s.status = "Pending"
            ORDER BY s.store_id ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getApprovedStores()
    {
        $db = self::getDb();
        $stmt = $db->prepare(
            'SELECT s.store_id, s.name, s.owner_id, s.region, s.status, u.username
            FROM stores s
            LEFT JOIN users u ON s.owner_id = u.user_id
            WHERE s.status = "Approved"
            ORDER BY s.store_id ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * All bookstores with owner username/email for admin PDF / exports.
     *
     * @return list<array<string,mixed>>
     */
    public static function getAllStoresWithOwners(): array
    {
        $db = self::getDb();
        $stmt = $db->prepare(
            'SELECT s.store_id, s.name, s.owner_id, s.region, s.status, u.username, u.email
            FROM stores s
            LEFT JOIN users u ON s.owner_id = u.user_id
            ORDER BY s.status ASC, s.store_id ASC'
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function approveStore($storeId)
    {
        $db = self::getDb();
        $stmt = $db->prepare('UPDATE stores SET status = :status WHERE store_id = :store_id');
        return $stmt->execute([':status' => 'Approved', ':store_id' => (int)$storeId]);
    }

    public static function rejectStore($storeId)
    {
        $db = self::getDb();
        $stmt = $db->prepare('UPDATE stores SET status = :status WHERE store_id = :store_id');
        return $stmt->execute([':status' => 'Rejected', ':store_id' => (int)$storeId]);
    }

    /**
     * Store row joined with owner contact for notifications (UC-38).
     */
    public static function getStoreWithOwner(int $storeId): ?array
    {
        $db = self::getDb();
        $stmt = $db->prepare(
            'SELECT s.store_id, s.name, s.owner_id, s.status, u.username, u.email
            FROM stores s
            INNER JOIN users u ON u.user_id = s.owner_id
            WHERE s.store_id = :store_id
            LIMIT 1'
        );
        $stmt->execute([':store_id' => $storeId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // User management
    public static function getAllUsers()
    {
        $db = self::getDb();
        $stmt = $db->prepare(
            'SELECT user_id, username, email, role FROM users ORDER BY user_id ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function updateUserRole($userId, $newRole)
    {
        $db = self::getDb();
        $validRoles = ['Reader', 'Owner', 'Admin'];
        if (!in_array($newRole, $validRoles)) {
            return false;
        }
        $stmt = $db->prepare('UPDATE users SET role = :role WHERE user_id = :user_id');
        return $stmt->execute([':role' => $newRole, ':user_id' => (int)$userId]);
    }

    public static function deleteUser($userId)
    {
        $db = self::getDb();
        $stmt = $db->prepare('DELETE FROM users WHERE user_id = :user_id');
        return $stmt->execute([':user_id' => (int)$userId]);
    }

    public static function getUserById($userId)
    {
        $db = self::getDb();
        $stmt = $db->prepare(
            'SELECT user_id, username, email, role FROM users WHERE user_id = :user_id LIMIT 1'
        );
        $stmt->execute([':user_id' => (int)$userId]);
        return $stmt->fetch();
    }

    public static function checkUsernameExists($username)
    {
        $db = self::getDb();
        $stmt = $db->prepare('SELECT user_id FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        return $stmt->fetch() !== false;
    }

    public static function checkEmailExists($email)
    {
        $db = self::getDb();
        $stmt = $db->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() !== false;
    }

    /**
     * @return int|false New user_id on success
     */
    public static function createUser($username, $email, $password, $role = 'Reader')
    {
        $db = self::getDb();
        $validRoles = ['Reader', 'Owner', 'Admin'];
        
        if (!in_array($role, $validRoles)) {
            return false;
        }
        
        if (self::checkUsernameExists($username) || self::checkEmailExists($email)) {
            return false;
        }
        
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        try {
            $stmt = $db->prepare(
                'INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password, :role)'
            );
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':role' => $role
            ]);
            return (int) $db->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function updateUser($userId, $username, $email, $role = 'Reader')
    {
        $db = self::getDb();
        $validRoles = ['Reader', 'Owner', 'Admin'];
        
        if (!in_array($role, $validRoles)) {
            return false;
        }
        
        // Check if username/email are taken by other users
        $current = self::getUserById($userId);
        if (!$current) {
            return false;
        }
        
        if ($username !== $current['username'] && self::checkUsernameExists($username)) {
            return false;
        }
        
        if ($email !== $current['email'] && self::checkEmailExists($email)) {
            return false;
        }
        
        $stmt = $db->prepare(
            'UPDATE users SET username = :username, email = :email, role = :role WHERE user_id = :user_id'
        );
        return $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':role' => $role,
            ':user_id' => (int)$userId
        ]);
    }
}
