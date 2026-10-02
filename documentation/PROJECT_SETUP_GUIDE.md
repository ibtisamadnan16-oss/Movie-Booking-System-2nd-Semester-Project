# CinePass - Movie Booking System (Aptech 2nd Semester Project)

## Phase 1 — Project Setup & Architecture Complete

### Overview
**CinePass** is a full-featured web-based Movie Ticket Booking & Theatre Reservation System developed in **PHP 8**, **MySQL**, **Bootstrap 5**, and **JavaScript** for Aptech Computer Education (Shahr-e-Faisal Centre), 2nd Semester Project.

---

### Phase 1 Directory Structure

```text
Movie Booking System 2nd Semester Project/
│
├── config/                  # Configuration & Environment
│   ├── config.php           # App constants, BASE_URL, session initialization, errors
│   └── db.php               # PDO MySQL database connection engine & error handler
│
├── includes/                # Reusable Layout & Helper Components
│   ├── header.php           # HTML <head>, Bootstrap 5, FontAwesome 6, Google Fonts
│   ├── navbar.php           # Responsive cinema navbar with authentication logic
│   ├── footer.php           # Cinema footer, social links, scripts
│   └── functions.php        # Helper functions (sanitize, url, redirect, auth checks)
│
├── assets/                  # Public Static Assets
│   ├── css/
│   │   └── style.css        # Custom cinema dark-mode theme & animations
│   ├── js/
│   │   └── main.js          # Main JS interactions, Bootstrap helpers
│   └── images/              # Movie posters, banners & cinema hall photos
│
├── user/                    # Customer Portal
│   ├── index.php            # User portal router
│   ├── login.php            # Customer sign in form
│   ├── register.php         # Customer account registration form
│   ├── dashboard.php        # Customer profile & account overview
│   └── bookings.php         # Customer ticket reservation history
│
├── admin/                   # Administrator Management Panel
│   ├── index.php            # Admin login & access control
│   ├── dashboard.php        # Admin statistics & system overview
│   ├── movies.php           # Movie listing & management
│   ├── bookings.php         # Customer reservations monitoring
│   └── includes/
│       ├── header.php       # Admin navigation bar
│       ├── sidebar.php      # Admin dashboard sidebar
│       └── footer.php       # Admin footer
│
├── actions/                 # Backend Processing Handlers
│   ├── auth_action.php      # User & Admin Login, Registration, Logout
│   └── booking_action.php   # Booking transaction handlers (Phase 4)
│
├── database/                # Database Architecture
│   └── schema.sql           # Complete MySQL database schema & demo seed data
│
├── documentation/           # Project Guides & Documentation
│   └── PROJECT_SETUP_GUIDE.md # XAMPP Setup & Aptech project guidelines
│
└── index.php                # Main Landing Page & Live Phase 1 Diagnostic Dashboard
```

---

### How to Run the Project in XAMPP

#### 1. Start XAMPP Services
1. Open **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

#### 2. Access the Application
The project is linked to XAMPP `htdocs`:
- Open your browser and navigate to:
  **`http://localhost/movie-booking/`**

#### 3. Import MySQL Database (1-Step Setup)
1. Open **phpMyAdmin** in your browser:
   **`http://localhost/phpmyadmin/`**
2. Click on the **Import** tab at the top.
3. Click **Choose File** and select:
   `d:\Aptech Shahr-e-faisal\2nd Semester\Movie Booking System 2nd Semester Project\database\schema.sql`
4. Click **Go** (or **Import**) at the bottom.
5. The database `movie_booking_db` will be created with tables and sample seed records.

---

### Default Login Credentials

| Role | Email | Password | Access URL |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@moviebooking.com` | `admin123` | `http://localhost/movie-booking/admin/` |
| **Customer / User** | `user@example.com` | `user123` | `http://localhost/movie-booking/user/login.php` |

---

### Development Phases Roadmap
- **[x] Phase 1: Project Setup & Foldering** *(Completed)*
  - XAMPP integration & symlink junction
  - 8 core folder structures
  - `config/config.php` & `config/db.php` PDO connection
  - Global templates (`header`, `navbar`, `footer`, `functions`)
  - Initial `schema.sql` with tables & seed records
  - Homepage (`index.php`) with live environment diagnostic badges
- **[ ] Phase 2: Authentication & User Accounts**
- **[ ] Phase 3: Movie Catalog & Theatre Management**
- **[ ] Phase 4: Interactive Seat Selection & Booking Engine**
- **[ ] Phase 5: Admin Panel & Reporting**
