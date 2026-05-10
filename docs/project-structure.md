# Project Structure Guide

This guide explains what each top-level module in `ibrcn` is responsible for.
It is intended as a quick onboarding map for future clean-code refactors.

## Architectural Style

- Pattern: lightweight MVC + Service Layer.
- Goal: keep page output and behavior stable while improving readability and maintainability.
- Main flow:
  1. `public/*.php` receives HTTP request.
  2. Middleware and/or controller orchestrates the use case.
  3. Models and repositories fetch/store data.
  4. Services handle cross-cutting flows.
  5. Views render HTML.

## Folder Responsibilities

- `public/`
  - Application entry points (route-like PHP scripts).
  - Keeps HTTP concerns (session, redirects, request parsing, response rendering).
  - Example: `index.php` composes `HomeController` and renders reader home.
  - Most endpoints `require_once app/bootstrap.php` to standardize common includes.

- `app/Controllers/`
  - Coordinates request flow for a page/use case.
  - Should not include low-level SQL or heavy business logic.
  - Example: `HomeController` prepares featured books and reader clubs for the view.

- `app/Models/`
  - Data access and domain persistence operations.
  - Contains query-heavy classes such as `Book`, `User`, `ReadingClub`, `Order`.

- `app/Services/`
  - Reusable application workflows that do not belong to one model.
  - Example:
    - `ReaderClubDashboardService`: builds "My Reading Clubs" data for the home page.
    - `CartService`: normalizes/persists session carts used by cart endpoints.
    - `OwnerOrdersService`: owner order workflow for mark-ready + listing data.
    - `AdminPanelService`: admin workflow for store/user actions + view data.
    - `MailboxService`: mailbox view-model builder for file-based notifications.
    - `PdfDownloadService`: shared PDF response header/stream helper.
    - `NotificationService`: coordinates email + in-app notifications.
    - `PdfReportService`: creates admin/owner reports.

- `app/Middleware/`
  - Access control and request guards (`AuthMiddleware`).

- `app/Core/`
  - Shared infrastructure primitives.
  - `Database` exposes a singleton PDO connection.
  - `Session` holds common session start/destroy helpers.
  - `RoleRedirector` centralizes role landing redirects.

- `views/`
  - HTML templates.
  - Keep logic minimal and presentation-focused.

- `config/`
  - Environment and app-level configuration.

- `storage/`
  - File-backed runtime data (notifications/mail cache and generated files).

- `docs/`
  - Architecture and maintainability documentation.

## Clean-Code Rules For This Project

- Keep controllers thin; move orchestration logic to services.
- Keep views dumb; no direct database operations in templates.
- Use dependency injection where possible (constructor arguments over hidden globals).
- Add comments only where intent is not obvious.
- Prefer small methods with single responsibility.
- Preserve endpoint URLs and rendered UI output to avoid behavior regressions.
