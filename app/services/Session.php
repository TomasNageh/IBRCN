<?php

/**
 * FILE: Session.php
 * PURPOSE: Framework-free session lifecycle helpers (`ensureStarted`, `destroy`) shared by middleware and logout endpoints.
 * USED BY: `AuthMiddleware`, `AuthController`, `public/logout.php`.
 * DESIGN PATTERN: None — thin procedural wrapper around PHP native sessions.
 */

/**
 * Session utilities used across endpoints/controllers/middleware.
 *
 * This is intentionally small and framework-free: the app uses plain PHP scripts
 * in `public/` as endpoints.
 */
final class Session
{
    public static function ensureStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['user'], $_SESSION['role']);
    }

    /**
     * Clear all session data and attempt to remove the session cookie.
     * Mirrors the existing behavior in `public/logout.php`.
     */
    public static function destroy(): void
    {
        self::ensureStarted();
        $_SESSION = array();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
