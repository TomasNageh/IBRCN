<?php

/**
 * FILE: RoleRedirector.php
 * PURPOSE: Sends authenticated users to role-default dashboards (`owner.php`, `admin.php`, `reader.php`) after auth transitions.
 * USED BY: `AuthMiddleware`, `AuthController` login/register success handlers.
 * DESIGN PATTERN: None — simple HTTP redirect helper.
 */

/**
 * Centralized role-based redirects.
 *
 * Many endpoints in `public/` rely on consistent role landing pages:
 * - Admin  -> `admin.php`
 * - Owner  -> `owner.php`
 * - Reader -> `reader.php`
 */
final class RoleRedirector
{
    public static function redirectByRole(string $role): void
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
}
