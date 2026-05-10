<?php

/**
 * FILE: AuthMiddleware.php
 * PURPOSE: Plain-PHP guards (`requireAuthenticated`, `requireGuest`, `requireRole`) enforcing session + role routing expectations.
 * USED BY: Every protected script under `public/` (cart, owner dashboards, admin tools, etc.).
 * DESIGN PATTERN: None — procedural middleware-style checks before controllers run.
 */

/**
 * AuthMiddleware
 *
 * Simple access guards for the plain-PHP endpoints in `public/`.
 * This keeps authorization checks consistent across the application.
 */
class AuthMiddleware
{
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

    public static function requireAuthenticated(): void
    {
        self::ensureStarted();

        if (!self::isAuthenticated()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function requireGuest(): void
    {
        self::ensureStarted();

        if (self::isAuthenticated()) {
            self::redirectByRole((string) $_SESSION['role']);
        }
    }

    public static function requireRole(array|string $allowedRoles): void
    {
        self::ensureStarted();

        if (!self::isAuthenticated()) {
            header('Location: login.php');
            exit;
        }

        $roles = is_array($allowedRoles) ? $allowedRoles : array($allowedRoles);
        if (!in_array((string) $_SESSION['role'], $roles, true)) {
            self::redirectByRole((string) $_SESSION['role']);
        }
    }
}
