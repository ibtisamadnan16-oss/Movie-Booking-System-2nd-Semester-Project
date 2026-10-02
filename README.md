# CinePass - Movie Ticket Booking & Cinema Management System

CinePass is a full-featured, enterprise-grade web application developed in PHP 8, MySQL, Bootstrap 5, and JavaScript. The system delivers a seamless end-to-end cinema reservation workflow for customers alongside a powerful, comprehensive management dashboard for theatre administrators.

Designed as an Aptech Computer Education 2nd Semester Project, CinePass adheres to modern web standards, secure database architecture, and clean user experience principles.

---

## Table of Contents

- [Project Overview](#project-overview)
- [Key Features](#key-features)
  - [Customer Portal](#customer-portal)
  - [Administrator Console](#administrator-console)
- [Technology Stack](#technology-stack)
- [System Requirements](#system-requirements)
- [Step-by-Step Installation Guide](#step-by-step-installation-guide)
- [Default Login Credentials](#default-login-credentials)
- [Project Directory Structure](#project-directory-structure)
- [Database Architecture & Entities](#database-architecture--entities)
- [Security & Validation Standards](#security--validation-standards)
- [Author & Contact Information](#author--contact-information)

---

## Project Overview

CinePass simulates a multi-cinema ticketing network, allowing moviegoers to explore current releases, watch trailers, select showtimes, pick reserved auditorium seats in real time, and receive an authentic digital admission e-ticket complete with a scannable QR verification code.

Simultaneously, the administration panel provides real-time control over film schedules, venue configuration, seat capacities, customer booking records, and financial box office reporting.

---

## Key Features

### Customer Portal

1. **Movie Catalog & Live Search:**
   - Multi-criteria filtering by genre, release status (Now Showing vs. Upcoming), language, and screening date.
   - Real-time keyword search across movie titles, synopses, and cast details.
   - High-definition movie details page with embedded YouTube trailers, runtimes, age ratings, and genre tags.

2. **Interactive Seat Selection Engine:**
   - Visual auditorium seat layout mapping rows and seat tiers (Standard, Premium, VIP).
   - Real-time seat occupancy detection preventing double-booking of seats.
   - Dynamic pricing calculation based on seat tier selection.

3. **Complete 6-Step Checkout Workflow:**
   - Select Movie -> Select Cinema -> Select Showtime -> Select Seats -> Review Summary -> Confirm & Payment.
   - Simulation of payment processing with reference codes and transaction receipts.

4. **Authentic Dual-Notch E-Ticket:**
   - Digital cinema admission pass with custom printable CSS styling.
   - Dynamically generated QR verification code for auditorium check-in.
   - Complete booking summary including seat numbers, screen auditorium, showtime, and total paid.

5. **Customer Dashboard & Profile:**
   - Personal account overview displaying upcoming reservations vs. past movie history.
   - Ticket reprint and instant PDF-style browser printing.
   - Self-service booking cancellation with immediate seat release.
   - Profile management with secure password updating.

---

### Administrator Console

1. **Live Operations Dashboard:**
   - Real-time KPI statistics: Total Movies, Registered Users, Total Bookings, Confirmed Revenue, and Today's Shows.
   - Today's Movie Schedule overview with active occupancy counters.
   - Recent booking transactions log with instant status inspection.

2. **Movie Catalog Management (CRUD):**
   - Add new movies with poster images, trailer links, synopsis, genres, duration, and status.
   - Update existing listings and archive finished theatrical runs.

3. **Cinema & Screen Venue Management:**
   - Multi-branch cinema directory across cities.
   - Auditorium/Screen builder configuring screen types (2D, 3D, IMAX, 4DX) and total seat capacities.

4. **Showtimes & Screening Scheduler:**
   - Schedule showtimes by linking movies, cinemas, screens, screening dates, start times, end times, and ticket base prices.
   - Prevention of overlapping show schedules on the same screen.

5. **Reservation & Customer Transaction Auditing:**
   - Monitor all reservations across cinemas with filter by status (Confirmed, Cancelled, Pending).
   - Administrative cancellation and seat release functionality.

6. **Business Analytics & Financial Reporting:**
   - Gross box office revenue tracking.
   - Movie performance analysis (tickets sold and revenue share per title).
   - Cinema venue revenue breakdown and cancellation rate metrics.
   - Dedicated print and export ready report layouts.

---

## Technology Stack

| Layer | Technology | Description |
| :--- | :--- | :--- |
| **Backend** | PHP 8.2+ | Native OOP & procedural architecture with PDO |
| **Database** | MySQL 5.7+ / MariaDB 10.4+ | Relational schema with foreign keys and indexes |
| **Frontend UI** | Bootstrap 5.3.3 | Mobile-responsive grid, modals, and flexbox utilities |
| **Custom Styling** | CSS3 | Dark cinema theme, custom responsive rules, print styles |
| **Icons & Fonts** | FontAwesome 6 & Poppins | Vector typography and cinema iconography |
| **Clientside Logic**| JavaScript (ES6) | Real-time seat selection, validation, and dynamic UI |
| **Server Engine** | Apache HTTP Server | Standard local server deployment via XAMPP |

---

## System Requirements

- **Operating System:** Windows 10/11, macOS, or Linux
- **Local Server:** XAMPP, WampServer, or LAMP stack with Apache
- **PHP Version:** PHP 8.0 or higher (PHP 8.2 recommended, `pdo_mysql` extension enabled)
- **Database Server:** MySQL 5.7+ or MariaDB 10.4+
- **Browser:** Google Chrome, Mozilla Firefox, Microsoft Edge, or Safari

---

## Step-by-Step Installation Guide

### Step 1: Place the Project in XAMPP Web Root

Ensure the project folder is located inside your XAMPP `htdocs` directory:

```text
C:\xampp\htdocs\movie-booking\
```

*(Alternatively, create a directory junction / symlink pointing to your repository folder).*

### Step 2: Start XAMPP Services

1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

### Step 3: Import the Database

1. Open your web browser and navigate to phpMyAdmin:
   ```text
   http://localhost/phpmyadmin/
   ```
2. Create a new database named:
   ```text
   movie_booking_db
   ```
3. Click on the **Import** tab at the top.
4. Click **Choose File** and select the schema file from the project:
   ```text
   database/schema.sql
   ```
5. Click **Import** (or **Go**) at the bottom of the page.
6. The database will be created with all tables, constraints, and demo seed data.

### Step 4: Verify Configuration

Open `config/config.php` and verify your local database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'movie_booking_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', 'http://localhost/movie-booking/');
```

### Step 5: Launch the Application

Open your browser and access the site:

- **Customer Website:** `http://localhost/movie-booking/`
- **Admin Management Portal:** `http://localhost/movie-booking/admin/login.php`

---

## Default Login Credentials

| Role | Email Address | Password | Access Portal |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@moviebooking.com` | `admin123` | `/admin/login.php` |
| **Registered Customer** | `user@example.com` | `user123` | `/login.php` |

*(You can also register a new customer account anytime from the registration page).*

---

## Project Directory Structure

```text
Movie Booking System 2nd Semester Project/
│
├── actions/                     # Form processing and backend controller actions
│   ├── auth_action.php          # Login, Registration, Password update, Logout
│   ├── booking_action.php       # Reservation creation, Seat locking, Cancellation
│   ├── contact_action.php       # Customer inquiry submission handler
│   └── payment_action.php       # Payment confirmation and receipt generation
│
├── admin/                       # Administration management console
│   ├── cinemas/                 # Cinema venues CRUD (Add, Edit, Delete, View)
│   ├── movies/                  # Movie listings CRUD (Add, Edit, Delete, View)
│   ├── screens/                 # Auditorium configuration & capacity
│   ├── shows/                   # Showtimes and screening scheduler
│   ├── users/                   # Member and administrator account manager
│   ├── bookings.php             # Master booking monitor and action handlers
│   ├── dashboard.php            # Admin operational KPI dashboard
│   ├── login.php                # Dedicated admin authentication portal
│   ├── logout.php               # Admin session destruction handler
│   ├── reports.php              # Financial box office business analytics
│   └── includes/                # Admin header, footer, and sidebar navigation
│
├── assets/                      # Static web assets
│   ├── css/
│   │   ├── admin.css            # Professional dark admin console stylesheet
│   │   ├── responsive.css       # Cross-device mobile and tablet breakpoints
│   │   └── style.css            # Global cinema dark theme, typography, cards
│   ├── js/
│   │   └── main.js              # Clientside validation and UI helpers
│   └── images/                  # Movie posters, banners, and theatre assets
│
├── config/                      # System configuration & environment
│   ├── config.php               # Base URL, database constants, session config
│   └── db.php                   # PDO database connector and error handling
│
├── database/                    # SQL definitions & seed scripts
│   ├── movie_booking.sql        # Full database export
│   └── schema.sql               # Clean structure and initial seed dataset
│
├── documentation/               # Detailed project specifications and reports
│   ├── DATABASE_DESIGN_ER_DIAGRAM.md
│   ├── ER_DIAGRAM_AND_ALGORITHMS.md
│   ├── FINAL_PROJECT_DOCUMENTATION.md
│   ├── GUI_STANDARDS_AND_INTERFACE_DESIGN.md
│   ├── PROJECT_SETUP_GUIDE.md
│   └── TESTING_DOCUMENTATION.md
│
├── includes/                    # Reusable customer portal layout components
│   ├── footer.php               # Global footer with links and social profiles
│   ├── functions.php            # Core utility library (auth, sanitization, dates)
│   ├── header.php               # HTML head, stylesheets, and meta viewport
│   └── navbar.php               # Navigation header with live auth state
│
├── about.php                    # About CinePass cinema experience
├── booking.php                  # Interactive movie and showtime selection
├── booking-success.php          # Admission ticket voucher with scannable QR
├── booking-summary.php          # Pre-checkout reservation review
├── contact.php                  # Customer support and contact form
├── dashboard.php                # Customer member dashboard
├── index.php                    # Home landing page with featured movies
├── login.php                    # Customer login form
├── logout.php                   # Customer logout handler
├── movie-details.php            # Detailed movie page with trailer and shows
├── movies.php                   # Searchable and filterable movies catalog
├── my-bookings.php              # Customer ticket history and cancellation
├── payment.php                  # Checkout payment simulation
├── profile.php                  # User account profile editor
├── register.php                 # Customer registration form
├── seat-selection.php           # Real-time visual seat grid selector
└── README.md                    # Project documentation and setup guide
```

---

## Database Architecture & Entities

The relational database `movie_booking_db` consists of 9 normalized tables:

1. **`users`**: Customer and administrator accounts with hashed passwords and role flags.
2. **`movies`**: Titles, descriptions, genres, duration, ratings, poster paths, trailer URLs, and theatrical status.
3. **`cinemas`**: Theatre branches, geographic cities, and addresses.
4. **`screens`**: Auditoriums within cinemas, screen technology types (IMAX, 3D), and capacities.
5. **`shows`**: Scheduled screenings linking a movie, screen, date, start time, end time, and ticket price.
6. **`seats`**: Individual physical seats mapped to rows, numbers, and tiers (Standard, Premium, VIP).
7. **`bookings`**: Transaction header records tracking customer ID, show ID, seat counts, total paid, and status.
8. **`booking_seats`**: Associative junction table linking individual reserved seats to a booking.
9. **`payments`**: Transaction records containing payment method, gateway reference code, amount, and timestamp.

---

## Security & Validation Standards

- **SQL Injection Prevention:** 100% of database queries use PDO prepared statements with bound parameters.
- **Cross-Site Scripting (XSS) Defense:** All user-supplied inputs rendered in HTML are sanitized using `htmlspecialchars()`.
- **Password Security:** Passwords are encrypted using PHP's native `password_hash()` algorithm (Bcrypt).
- **Session Protection:** Cookie flags enforce `httponly=true`, `use_only_cookies=1`, and `samesite=Lax` to mitigate session hijacking.
- **Double-Booking Prevention:** Seat reservations utilize transactional validation ensuring simultaneous attempts to lock the same seat fail safely.
- **Dual-Layer Validation:** Bootstrap 5 validation provides immediate clientside feedback, backed by strict server-side validation.

---

## Author & Contact Information

- **Developer:** Ibtisam Adnan
- **Email:** [ibtisamadnan06@gmail.com](mailto:ibtisamadnan06@gmail.com)
- **GitHub:** [https://github.com/ibtisamadnan16-oss](https://github.com/ibtisamadnan16-oss)
- **Institution:** Aptech Computer Education (Shahr-e-Faisal Centre)
- **Academic Course:** 2nd Semester Project — Web Development & Database Architecture
