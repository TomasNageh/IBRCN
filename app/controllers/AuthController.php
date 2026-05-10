<?php

/**
 * FILE: AuthController.php
 * PURPOSE: Handles login, logout-adjacent session hydration (cart), registration, and audit logging hooks for auth events.
 * USED BY: `public/login.php`, `public/register.php`.
 * DESIGN PATTERN: None — coordinates `User` model + `Session`/`RoleRedirector` helpers + optional notifications on register.
 */

// Orchestrates credential verification and session population for IBRCN roles without embedding HTML (controller layer).
class AuthController
{
    private User $userModel;

    private static function ensureStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    private static function isAuthenticated(): bool
    {
        return isset($_SESSION['user'], $_SESSION['role']);
    }

    private static function redirectByRole(string $role): void
    {
        if ($role === 'Owner') {
            header('Location: owner.php');
            exit;
        }

        if ($role === 'Admin') {
            header('Location: admin.php');
            exit;
        }

        header('Location: reader.php');
        exit;
    }

    /**
     * @param User $userModel Injected user persistence gateway constructed by the calling endpoint with shared PDO.
     * @return void
     */
    public function __construct(User $userModel)
    {
        $this->userModel = $userModel;
    }

    /**
     * Renders the login view for guests; redirects authenticated users to their role landing page.
     *
     * @param string $error Optional error message shown when a prior attempt failed (initial load passes empty string).
     * @return void
     */
    public function showLogin(string $error = ''): void
    {
        self::ensureStarted();
        if (self::isAuthenticated()) {
            self::redirectByRole((string) $_SESSION['role']);
        }

        $errorMessage = $error;
        $formData = array(
            'username' => (string) ($_POST['username'] ?? ''),
        );
        require __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Validates posted credentials, regenerates session id on success, hydrates session keys, audits login, redirects by role.
     *
     * @return void
     */
    public function login(): void
    {
        self::ensureStarted();
        if (self::isAuthenticated()) {
            self::redirectByRole((string) $_SESSION['role']);
        }

        if (!isset($_POST['username']) || !isset($_POST['password'])) {
            $this->showLogin('Please provide both username and password.');
            return;
        }

        $username = trim((string) $_POST['username']);
        $password = (string) $_POST['password'];
        $formData = array('username' => $username);

        $user = $this->userModel->findByUsername($username);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errorMessage = 'Invalid username or password.';
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = $user['username'];
        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $this->auditLoginSuccess((int) $user['user_id'], (string) $user['role']);
        $this->hydrateCartForReader((int) $user['user_id'], (string) $user['role']);
        self::redirectByRole((string) $user['role']);
    }

    /**
     * Renders registration form for guests; redirects authenticated users to role landing page.
     *
     * @param string $error Optional validation error message for repost UX.
     * @return void
     */
    public function showRegister(string $error = ''): void
    {
        self::ensureStarted();
        if (self::isAuthenticated()) {
            self::redirectByRole((string) $_SESSION['role']);
        }

        $errorMessage = $error;
        $formData = array(
            'username' => (string) ($_POST['username'] ?? ''),
            'email' => (string) ($_POST['email'] ?? ''),
            'role' => (string) ($_POST['role'] ?? 'Reader'),
        );
        require __DIR__ . '/../views/auth/register.php';
    }

    /**
     * Validates registration POST fields, inserts user, triggers welcome notifications, hydrates session + reader cart, redirects.
     *
     * @return void
     */
    public function register(): void
    {
        self::ensureStarted();
        if (self::isAuthenticated()) {
            self::redirectByRole((string) $_SESSION['role']);
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $password2 = (string) ($_POST['password2'] ?? '');
        $role = (string) ($_POST['role'] ?? 'Reader');

        $formData = array('username' => $username, 'email' => $email, 'role' => $role);
        $errorMessage = '';
        $allowedRoles = array('Reader', 'Owner', 'Admin');

        if (!in_array($role, $allowedRoles, true)) {
            $this->showRegister('Invalid role selected.');
            return;
        }

        if ($password !== $password2) {
            $this->showRegister('Passwords do not match.');
            return;
        }

        if (strlen($password) < 8) {
            $this->showRegister('Password must be at least 8 characters long.');
            return;
        }

        $userId = $this->userModel->registerUser($username, $email, $password, $role);
        if ($userId === false) {
            $this->showRegister('Registration failed. Username or email may already exist.');
            return;
        }

        try {
            require_once __DIR__ . '/../services/NotificationService.php';
            (new NotificationService())->onUserRegistered((int) $userId, $email, $username);
        } catch (Throwable $exception) {
            error_log('Welcome notification: ' . $exception->getMessage());
        }

        session_regenerate_id(true);
        $_SESSION['user'] = $username;
        $_SESSION['user_id'] = $userId;
        $_SESSION['role'] = $role;
        $this->auditRegisterSuccess((int) $userId, $role);
        $this->hydrateCartForReader((int) $userId, $role);
        self::redirectByRole((string) $role);
    }

    /**
     * Writes an audit_log row for successful login (failures are intentionally not logged here).
     *
     * @param int    $userId Primary key of user who authenticated successfully.
     * @param string $role   Role string stored in session (`Reader`, `Owner`, `Admin`).
     * @return void
     */
    private function auditLoginSuccess(int $userId, string $role): void
    {
        try {
            require_once __DIR__ . '/../patterns/DB.php';
            require_once __DIR__ . '/../models/AuditLogRepository.php';
            (new AuditLogRepository(DB::getInstance()))->write(
                $userId,
                $role,
                'login_success',
                $userId,
                'users'
            );
        } catch (Throwable $exception) {
            error_log('audit login: ' . $exception->getMessage());
        }
    }

    /**
     * Writes an audit_log row after successful registration completes.
     *
     * @param int    $userId New user id.
     * @param string $role   Selected role at signup time.
     * @return void
     */
    private function auditRegisterSuccess(int $userId, string $role): void
    {
        try {
            require_once __DIR__ . '/../patterns/DB.php';
            require_once __DIR__ . '/../models/AuditLogRepository.php';
            (new AuditLogRepository(DB::getInstance()))->write(
                $userId,
                $role,
                'register_success',
                $userId,
                'users'
            );
        } catch (Throwable $exception) {
            error_log('audit register: ' . $exception->getMessage());
        }
    }

    /**
     * Loads persisted DB cart into `$_SESSION['cart']` for Reader accounts; clears cart session key for other roles.
     *
     * @param int    $userId freshly authenticated user id.
     * @param string $role   Role string used to decide whether cart hydration applies.
     * @return void
     */
    private function hydrateCartForReader(int $userId, string $role): void
    {
        if ($role !== 'Reader') {
            unset($_SESSION['cart']);
            return;
        }
        try {
            require_once __DIR__ . '/../patterns/DB.php';
            require_once __DIR__ . '/../models/CartRepository.php';
            $pdo = DB::getInstance();
            $cart = (new CartRepository($pdo))->loadSessionCart($userId);
            $_SESSION['cart'] = $cart;
        } catch (Throwable $exception) {
            $_SESSION['cart'] = array();
            error_log('hydrateCartForReader: ' . $exception->getMessage());
        }
    }
}
