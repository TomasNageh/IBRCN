<?php

/**
 * FILE: AuthMiddleware.php
 * PURPOSE: Plain-PHP guards (`requireAuthenticated`, `requireGuest`, `requireRole`) enforcing session + role routing expectations.
 * USED BY: Every protected script under `public/` (cart, owner dashboards, admin tools, etc.).
 * DESIGN PATTERN: None — procedural middleware-style checks before controllers run.
 */

require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/RoleRedirector.php';

/**
 * AuthMiddleware
 *
 * Simple access guards for the plain-PHP endpoints in `public/`.
 * This keeps authorization checks consistent across the application.
 */
class AuthMiddleware
{
    public static function requireAuthenticated(): void
    {
        Session::ensureStarted();

        if (!Session::isAuthenticated()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function requireGuest(): void
    {
        Session::ensureStarted();

        if (Session::isAuthenticated()) {
            RoleRedirector::redirectByRole((string) $_SESSION['role']);
        }
    }

    public static function requireRole(array|string $allowedRoles): void
    {
        Session::ensureStarted();

        if (!Session::isAuthenticated()) {
            header('Location: login.php');
            exit;
        }

        $roles = is_array($allowedRoles) ? $allowedRoles : array($allowedRoles);
        if (!in_array((string) $_SESSION['role'], $roles, true)) {
            RoleRedirector::redirectByRole((string) $_SESSION['role']);
        }
    }
}