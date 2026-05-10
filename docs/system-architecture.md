# IBRCN System Architecture

This diagram represents the current architecture implemented in the `ibrcn` application.

```mermaid
flowchart TB
    Client["Browser Clients<br/>Reader • Owner • Admin"]
    HTTP["HTTP Layer<br/>Apache/XAMPP + PHP endpoints in public/"]
    Guard["AuthMiddleware<br/>Guest/Auth/Role guards"]

    Client --> HTTP --> Guard

    subgraph App["Application Layer"]
        direction LR
        C1["Auth + Home Controllers<br/>AuthController, HomeController"]
        C2["Portal Endpoints<br/>reader.php, owner.php, admin.php,<br/>orders.php, mailbox.php, cart-action.php"]
    end

    subgraph Domain["Domain Models / Repositories"]
        direction LR
        M1["User + Access<br/>User, Admin, AuditLogRepository"]
        M2["Catalog + Cart<br/>Book, CartRepository, OwnerInventory"]
        M3["Orders + Clubs<br/>Order, ReadingClub, NotificationsRepository"]
    end

    subgraph Cross["Cross-Cutting Services"]
        direction LR
        S1["NotificationService<br/>Domain event orchestration"]
        S2["EmailService<br/>file/api/smtp/fake transports"]
        S3["InAppNotificationStore<br/>In-app inbox persistence"]
        S4["PdfReportService<br/>Admin/Owner report export"]
    end

    subgraph Data["Data / Infrastructure"]
        direction TB
        DBCore["Database::getInstance()<br/>Singleton PDO connection"]
        MySQL[("MySQL database<br/>ibrcn schema")]
        FileMail[("storage/mail")]
        FileInApp[("storage/in_app")]
        Vendor["Composer vendor<br/>dompdf/autoload.php"]
        MailProvider["Mail API / SMTP provider"]
    end

    Guard --> App
    App --> Domain
    Domain --> DBCore --> MySQL

    Domain --> S1
    S1 --> S2
    S1 --> S3
    App --> S4

    S2 --> FileMail
    S2 --> MailProvider
    S3 --> FileInApp
    S3 --> MySQL
    S4 --> Vendor
    S4 --> Domain
```

## Layer Notes

- Public endpoints in `public/` act as route handlers and compose dependencies.
- `AuthMiddleware` enforces guest/authenticated/role access before business operations.
- Controllers coordinate input/session flow and delegate data operations to models/repositories.
- Services handle cross-cutting workflows:
  - `NotificationService`: triggers email and in-app events.
  - `EmailService`: supports `file`, `api`, `smtp`, and `fake` transports.
  - `PdfReportService`: generates admin/owner PDF reports through Dompdf.
- `Database` provides a shared PDO connection to MySQL.

## Key Runtime Scenarios

1. Login/Register: `public/login.php` or `public/register.php` -> `AuthController` -> `User` model -> MySQL.
2. Cart/Checkout: `public/cart-action.php` + cart/order pages -> `CartRepository`/`Order` -> MySQL -> `NotificationService`.
3. Admin/Owner Reports: PDF endpoints -> `PdfReportService` -> Dompdf vendor libs -> streamed PDF response.
