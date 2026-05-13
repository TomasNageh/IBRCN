# 📚 IBRCN — Independent Bookstore & Reader's Club Network

> CS251 Software Engineering 1 · Capital University · Spring 2026  
> Project 14 

---

## Overview

IBRCN is a web-based platform that connects independent local bookstores with readers through a unified digital network. Bookstores keep their unique local identity while participating in a shared online marketplace. Readers can discover books, place pickup orders, join book clubs, and attend author events.

---

## Features

### For Readers
- Register, log in, and manage your account
- Browse and search books across all registered bookstores (by title, author, genre, or ISBN)
- Add books to a cart and place **O2O (Online-to-Offline) pickup orders**
- Intelligent stock routing — find the nearest store with a book in stock
- Join or create public and private **Book Clubs**
- Track reading goals with **Reading Challenges**
- Receive personalised **book recommendations** based on reading history
- GDPR-compliant **privacy controls** and right-to-be-forgotten
- View order history and mark orders as collected

### For Bookstore Owners
- Apply to join through a multi-stage verification process
- Manage inventory: add, edit, and remove listings (new and used books)
- Bulk inventory import via CSV upload
- Process incoming O2O orders and mark them ready for pickup
- View a **financial dashboard** showing sales and earnings
- Feature **Staff Picks** on the store profile
- Download **PDF inventory reports**

### For Administrators
- Approve or reject bookstore registration applications
- Manage all user accounts and roles (RBAC)
- View a full **audit trail** of transactions and inventory changes
- Download **PDF reports** of all stores and owners

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP (native, OOP, MVC) |
| Database | MySQL via PDO with prepared statements |
| Frontend | HTML5 + Bootstrap (custom — no templates) |
| PDF Reports | Dompdf (via Composer) |
| Local Server | XAMPP / Apache |
| Diagrams | Visual Paradigm Community Edition |

---

## Architecture

The application follows a **Modular Monolith** with strict **MVC (Model-View-Controller)** separation.

```
public/          → HTTP entry points (route-like PHP scripts)
app/
  controllers/   → Request coordination (AuthController, HomeController, …)
  models/        → Data access (Book, Order, User, ReadingClub, …)
  services/      → Cross-cutting workflows (NotificationService, CartService, …)
  views/         → HTML templates (reader, owner, admin, auth, mailbox)
  patterns/      → Design pattern implementations
config/          → Database credentials and application config
storage/         → File-backed mail and in-app notification queues
vendor/          → Composer dependencies
```

### Design Patterns Implemented

| Pattern | Location | Purpose |
|---|---|---|
| **Singleton** | `app/patterns/DB.php` | Single shared PDO connection per request |
| **Observer** | `app/patterns/EventPublisher.php` + `ReaderObserver.php` | Decoupled checkout/order notifications |
| **Strategy** | `app/patterns/RecommendationStrategy.php` + strategies | Swap recommendation algorithms at runtime based on privacy settings |

---

## Getting Started

### Prerequisites
- XAMPP (PHP 8.0+, Apache, MySQL)
- Composer

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/your-username/ibrcn.git
cd ibrcn

# 2. Install PHP dependencies
php composer.phar install

# 3. Create the database
#    Import the schema into MySQL via phpMyAdmin or CLI:
mysql -u root ibrcn < config/legacy_bookengine_compat.sql

# 4. Configure database credentials
#    Edit config/db_config.php with your MySQL settings

# 5. Seed demo data (optional)
php scripts/seed_catalog.php
php scripts/seed_demo_owners.php

# 6. Start XAMPP and visit
http://localhost/ibrcn/public/
```

---

## Demo Accounts

After running the seed scripts:

| Role | Username | Password |
|---|---|---|
| Reader | *(register via UI)* | — |
| Owner | `ibrcn_owner` | `IBRCN123!` |
| Owner | `owner_alexandria` | `Owner123!` |
| Owner | `owner_giza` | `Owner123!` |
| Admin | *(create via registration with Admin role)* | — |

---

## Project Structure Highlights

```
public/index.php        → Reader storefront homepage
public/login.php        → Login
public/register.php     → Registration
public/cart.php         → Shopping cart & checkout
public/orders.php       → Reader order history
public/member.php       → Reading clubs
public/owner.php        → Owner portal
public/admin.php        → Admin dashboard
```

---

## Security

- Passwords hashed with `password_hash()` / `PASSWORD_BCRYPT`
- All DB queries use PDO prepared statements (SQL injection prevention)
- Output escaped with `htmlspecialchars()` (XSS prevention)
- Role-Based Access Control (RBAC) enforced via `AuthMiddleware`
- GDPR-compliant data export (JSON) and account deletion

---

## Team

| # | Student ID | Name |
|---|---|---|
| 1 | 20240231 | Tomas Nageh |
| 2 | 20240991 | Macarius Emad |
| 3 | 20240262 | Jolie Fayez |
| 4 | 20240188 | Barthina Reda |
| 5 | 20240205 | Beshoy Saleh |
| 6 | 20241040 | Mina Tharwat |

**Instructor:** Dr. Amr S. Ghoneim  
**Module:** CS251 Software Engineering 1 · Capital University · Spring 2026

---

## License

MIT License — see [LICENSE](LICENSE) for details.
