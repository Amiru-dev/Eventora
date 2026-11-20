# Eventora — Campus Event Booking System

A web application for a university club that lists campus events and lets students book seats.

**Version:** 1.0  
**Author:** A. U. Vidanapathirana (ITBSC/2025/EA/WD/003)  
**Institution:** Faculty of Computing and Information Technology, NIIBS

---

## Overview

Eventora allows visitors to browse and search upcoming campus events. Registered students can log in, book seats and manage their bookings. Organizers can submit and manage events (subject to admin approval). Administrators can manage the platform, users, events, promotions and view all activity.

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Front-end | HTML5, CSS3, Bootstrap 5 |
| Client Logic | AngularJS 1.x, Vanilla JavaScript |
| Server | PHP 8.x (Apache) |
| Database | MySQL (single shared database) |
| Payment | PayHere Sandbox |
| Email | PHPMailer + Gmail SMTP |
| QR Codes | phpqrcode library |
| Version Control | Git |

## Setup Instructions

### Prerequisites

- XAMPP (Apache + PHP 8.x + MySQL)
- Git
- Web browser (Chrome, Firefox or Edge)

### 1. Clone the Repository

```bash
git clone <repository-url> Eventora
```

### 2. Configure XAMPP

Place or symlink the project folder inside `C:\xampp\htdocs\` or configure a virtual host pointing to the project root.

### 3. Create the Database

1. Start Apache and MySQL from XAMPP Control Panel.
2. Open phpMyAdmin at `http://localhost/phpmyadmin`.
3. Create a new database named `eventora`.
4. Import `setup/schema.sql` to create all tables.
5. Import `setup/seed.sql` to insert the administrator account and sample data.

### 4. Configure Application

Copy and edit the configuration files in `config/`:

- **`config/database.php`** — Database host, name, username, password.
- **`config/app.php`** — Application constants, timezone, base URL.
- **`config/payhere.php`** — PayHere sandbox merchant ID and secret.
- **`config/mail.php`** — Gmail SMTP credentials for PHPMailer.

> **Important:** Never commit `config/payhere.php` or `config/mail.php` with real credentials. Use the `.example` templates provided.

### 5. Seed Accounts

After importing `seed.sql`, the following test accounts are available:

| Role | Email | Password |
|------|-------|----------|
| Administrator | admin@eventora.com | Admin@123 |
| Student | student@eventora.com | Student@123 |
| Organizer | organizer@eventora.com | Organizer@123 |

### 6. Scheduled Tasks (Cron Jobs)

For full functionality, set up these scheduled tasks:

```
# Expire abandoned payment holds (every minute)
* * * * * php /path/to/Eventora/cron/expire_holds.php

# Send event reminders (every 15 minutes)
*/15 * * * * php /path/to/Eventora/cron/send_reminders.php

# Expire waitlist offers (every 5 minutes)
*/5 * * * * php /path/to/Eventora/cron/expire_waitlist.php
```

On Windows/XAMPP, use Task Scheduler or call these scripts manually during testing.

### 7. Access the Application

Open `http://localhost/Eventora/` in your browser.

---

## Project Structure

```
Eventora/
├── index.php                # Landing page / public event list
├── config/                  # Database, app, payment, mail config
├── includes/                # Reusable PHP business logic
├── api/                     # JSON endpoints (AngularJS consumes)
├── pages/                   # Server-rendered pages by role
│   ├── auth/                # Login, register, logout
│   ├── student/             # Student dashboard, bookings, tickets
│   ├── organizer/           # Organizer event management, check-in
│   ├── admin/               # Admin dashboard, approvals, reports
│   └── payment/             # PayHere checkout, return, notify
├── assets/                  # CSS, JS, images, vendor libraries
│   ├── css/
│   ├── js/
│   ├── img/
│   └── vendor/              # Bootstrap, AngularJS, PHPMailer, phpqrcode
├── templates/               # Reusable HTML partials and email templates
├── cron/                    # Scheduled task scripts
└── setup/                   # Database schema and seed data
```

---

## Features

- **Event Discovery:** Browse, search, filter and sort events with AngularJS (no page reload).
- **Role-based Access:** Student, Organizer (pending/approved) and Administrator roles.
- **Booking System:** Seat management with capacity enforcement, bulk discounts and promo codes.
- **PayHere Sandbox Payments:** Secure test payments with server-side verification.
- **QR Tickets:** Cryptographically secure ticket tokens with QR code display and one-time check-in.
- **Waitlist & Wishlist:** FIFO waiting queue for sold-out events and personal favourites list.
- **Email Notifications:** Booking confirmations, event reminders, approval notifications.
- **Reviews & Ratings:** Post-attendance reviews with eligibility rules.
- **Dashboards & Reports:** Role-scoped analytics with charts and CSV export.
- **Audit Logging:** Traceable admin actions, payment changes and check-ins.

---

## Currency & Locale

- **Currency:** LKR (Sri Lankan Rupee), stored as `DECIMAL(10,2)`
- **Timezone:** Asia/Colombo
- **Calculations:** Rounded to 2 decimal places

## License

This project is developed for academic assessment purposes.
