# IBRCN refactoring summary

This document records the structural refactor applied under `ibrcn/` to align with the requested MVC layout, configuration split, documentation headers (incrementally applied across new/changed core files), and the three mandated design patterns (Singleton, Observer, Strategy).

## Folder moves (logical destinations)

| Former location | New location |
|-----------------|--------------|
| `ibrcn/views/**` | `ibrcn/app/views/**` |
| `ibrcn/app/Controllers/**` | `ibrcn/app/controllers/**` |
| `ibrcn/app/Models/**` | `ibrcn/app/models/**` |
| `ibrcn/app/Services/**` | `ibrcn/app/services/**` |
| `ibrcn/app/Core/Session.php` | `ibrcn/app/services/Session.php` |
| `ibrcn/app/Core/RoleRedirector.php` | `ibrcn/app/services/RoleRedirector.php` |
| `ibrcn/app/Middleware/AuthMiddleware.php` | `ibrcn/app/services/AuthMiddleware.php` |
| `ibrcn/app/Core/Database.php` (removed) | `ibrcn/app/patterns/DB.php` (Singleton PDO wrapper) |

Public scripts under `ibrcn/public/` remain the Apache-visible endpoints; their `require_once` paths now target `app/controllers`, `app/models`, `app/services`, and `app/views` consistently.

New folders:

- `ibrcn/app/patterns/` — **only** `DB.php`, `EventPublisher.php`, `ReaderObserver.php`, `RecommendationStrategy.php`, `HistoryBasedStrategy.php`, `TopPicksStrategy.php`.
- `ibrcn/uploads/` — placeholder for user uploads (`.gitkeep` only).

## Configuration split

| File | Role |
|------|------|
| `config/db_config.php` | Returns the MySQL credential array consumed by `DB::getInstance()` (DSN inputs unchanged). |
| `config/constants.php` | Defines `DB_*` constants and returns the full merged config (includes `mail` array identical to prior behavior). |
| `config/config.php` | Back-compat shim: `return require __DIR__ . '/constants.php';` |

## Renamed symbols / classes

| Old | New |
|-----|-----|
| `Database::getInstance()->getConnection()` | `DB::getInstance()` (returns `PDO` directly) |
| `class Database` | `class DB` (`app/patterns/DB.php`) |

No legacy SQL strings inside existing featured-books listing were altered; the featured query text stays in `Book::getFeaturedBooks()` as before.

## New functions / methods (additive)

| Location | Name | Purpose |
|----------|------|---------|
| `app/models/Book.php` | `getDatabaseConnection()` | Exposes injected PDO to `RecommendationEngine` without opening new connections. |
| `app/models/Book.php` | `attachListingPricePresentationFields()` | Shared price formatting for listing rows (behavior-preserving refactor). |
| `app/models/Book.php` | `getRecommendationsFromPurchaseHistory()` | Purchase-history-based recommendations (used **only** when optional column exists and allows personalization — otherwise homepage stays on top picks). |
| `app/models/User.php` | `readerAllowsPersonalizedRecommendationsFromHistory()` | Reads optional `users.share_reading_history` when present; otherwise returns false so behavior matches legacy homepage defaults. |
| `app/models/User.php` | `usersTableHasShareReadingHistoryColumn()` | Memoized `SHOW COLUMNS` guard so missing-column installs stay identical to production behavior. |

No controller or public URL filenames were renamed (`login.php`, `cart.php`, etc. unchanged).

## Design patterns — where applied

1. **Singleton — `app/patterns/DB.php`**  
   Single PDO per request; credentials/DSN/error-mode attributes mirror the removed `Database` wrapper.

2. **Observer — `app/patterns/EventPublisher.php` + `app/patterns/ReaderObserver.php`**  
   `NotificationService` publishes `checkout_success`, `order_ready_for_pickup`, and `user_registered`.  
   `ReaderObserver::update()` performs the same reader email + in-app pushes as the previous inline methods (including ordering: checkout still validates reader exists before publishing). Owner/store branches remain in `NotificationService` where they already lived.

3. **Strategy — `RecommendationStrategy` + `TopPicksStrategy` + `HistoryBasedStrategy` + `app/services/RecommendationEngine.php`**  
   Homepage listings flow through `RecommendationEngine`, which prefers history strategy only when the optional DB column grants consent and returns non-empty rows; otherwise it uses the original featured-books path via `TopPicksStrategy`.

## Files moved to `_archive`

None. No unused dead code blocks were relocated during this pass.

## Documentation coverage note

File-level docblocks and functional comments were added comprehensively for new pattern/services pieces (`patterns/*`, `RecommendationEngine`, revamped `NotificationService`, expanded `Book`/`User` sections, split config files, `bootstrap.php`, `HomeController`).  
Older endpoints/models/services retain their prior inline documentation where present; fully rewriting every legacy method comment across the entire codebase would be a second pass without behavioral benefit.

## Verification

Syntax checks were run with `c:\xampp\php\php.exe -l` on representative entry points (`bootstrap.php`, `ReaderObserver.php`, `RecommendationEngine.php`, `public/db.php`).

## Follow-up documentation pass (continuation)

Additional **FILE / PURPOSE / USED BY / DESIGN PATTERN** headers and expanded PHPDoc were applied across remaining core models (`NotificationsRepository`, `AuditLogRepository`, `CartRepository`, `Order`, `OwnerInventory`, expanded `ReadingClub`), `AuthController`, and services that previously only had informal comments (`CartService`, `InAppNotificationStore`, `MailboxService`, `AdminPanelService`, `ReaderClubDashboardService`, `OwnerOrdersService`, `UserMailbox`, `PdfDownloadService`, `PdfReportService`, plus an explicit header on `EmailService` clarifying it is not the curriculum Strategy pattern).
