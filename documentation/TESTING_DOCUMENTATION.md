# CinePass Movie Booking System — Software Testing Documentation
**Project:** Movie Booking System (Aptech Semester 2 Project)  
**Environment:** Apache 2.4, PHP 8.2+, MySQL 8.0, Bootstrap 5.3, Windows 10/11 (XAMPP)  
**Testing Methodology:** Black-box, Functional Integration, Boundary Value, & Security Testing

---

## 1. Test Summary

| Total Test Cases | Passed | Failed | Blocked | Execution Date |
|:---:|:---:|:---:|:---:|:---:|
| **16** | **16** | **0** | **0** | October 2026 |

---

## 2. Comprehensive Test Case Matrix

### Test 1: Registration Test

#### Test Case 1.1 (Positive)
- **Test ID:** `TC-REG-01`
- **Test Scenario:** Successful new customer account registration with valid inputs
- **Input:**
  - Full Name: `Ali Khan`
  - Email: `ali.khan@cinepass.com`
  - Phone: `03001234567`
  - Password: `Password@123`
  - Confirm Password: `Password@123`
- **Expected Result:** Account created, password hashed with `PASSWORD_BCRYPT`, flash success message displayed, redirected to `login.php`.
- **Actual Result:** User record inserted into `users` table with status `active`, password hashed with salt, redirected to login page with success notification.
- **Status:** **PASS**

#### Test Case 1.2 (Negative — Duplicate Email)
- **Test ID:** `TC-REG-02`
- **Test Scenario:** Registration attempt with an email address that already exists
- **Input:**
  - Email: `ali.khan@cinepass.com` (Already registered)
  - Other fields: Valid
- **Expected Result:** Registration rejected with duplicate email warning; user remains on registration form with error banner.
- **Actual Result:** System checked database via prepared statement, caught existing record, flashed error `"An account with this email address already exists"`.
- **Status:** **PASS**

#### Test Case 1.3 (Negative — Invalid Email Format)
- **Test ID:** `TC-REG-03`
- **Test Scenario:** Registration attempt with malformed email syntax
- **Input:**
  - Email: `ali.khan@invalid`
- **Expected Result:** Blocked by both HTML5/JS and PHP `filter_var(..., FILTER_VALIDATE_EMAIL)`.
- **Actual Result:** Server rejected input before database query, displayed `"Invalid Email: Please provide a valid email address."`.
- **Status:** **PASS**

---

### Test 2: Login Test

#### Test Case 2.1 (Positive)
- **Test ID:** `TC-LOG-01`
- **Test Scenario:** Registered customer login with valid credentials
- **Input:**
  - Email: `ali.khan@cinepass.com`
  - Password: `Password@123`
- **Expected Result:** `password_verify()` returns true, session ID regenerated (`session_regenerate_id(true)`), `$_SESSION['user_id']` created, redirected to `dashboard.php`.
- **Actual Result:** Authenticated successfully, session fixation prevented, redirected to User Dashboard with welcome banner.
- **Status:** **PASS**

#### Test Case 2.2 (Negative — Incorrect Password)
- **Test ID:** `TC-LOG-02`
- **Test Scenario:** Customer login attempt with incorrect password
- **Input:**
  - Email: `ali.khan@cinepass.com`
  - Password: `WrongPassword999`
- **Expected Result:** Login denied, error message displayed, session not created.
- **Actual Result:** `password_verify()` failed, flashed `"Invalid email address or password"`.
- **Status:** **PASS**

---

### Test 3: Movie Search Test

#### Test Case 3.1 (Positive — Keyword Search)
- **Test ID:** `TC-SRCH-01`
- **Test Scenario:** Search movies by title or partial keyword
- **Input:**
  - Search Query: `Oppenheimer`
- **Expected Result:** Filtered movie listing showing "Oppenheimer" with poster, rating, genre, and duration.
- **Actual Result:** Matching movie cards displayed; active search badge `"Search: Oppenheimer"` shown with clear button.
- **Status:** **PASS**

#### Test Case 3.2 (Positive — Genre & Status Filters)
- **Test ID:** `TC-SRCH-02`
- **Test Scenario:** Multi-filter selection combining Status ("Now Showing") and Genre ("Sci-Fi")
- **Input:**
  - Status: `now_showing`
  - Genre: `Sci-Fi`
- **Expected Result:** Only currently showing Sci-Fi movies are listed (e.g., *Interstellar*, *Dune: Part Two*).
- **Actual Result:** Database executed parameterized query `WHERE status = ? AND genre LIKE ?`; correct matching records returned.
- **Status:** **PASS**

---

### Test 4: Show Selection Test

#### Test Case 4.1 (Positive)
- **Test ID:** `TC-SHW-01`
- **Test Scenario:** Select available showtime for a movie at a cinema screen
- **Input:**
  - Movie ID: `1`
  - Date: `Today / Tomorrow`
  - Show Time: `06:30 PM`
  - Screen: `Screen 1 (IMAX)`
- **Expected Result:** System verifies show existence, loads auditorium configuration, and routes to seat selection (`user/select-seats.php?show_id=1`).
- **Actual Result:** Loaded show details, cinema address, and dynamic seat grid mapped to the screen layout.
- **Status:** **PASS**

---

### Test 5: Seat Selection Test

#### Test Case 5.1 (Positive — Multi-Seat Selection)
- **Test ID:** `TC-ST-01`
- **Test Scenario:** Customer clicks on available seats in the interactive SVG/CSS auditorium map
- **Input:**
  - Selected Seats: `A4`, `A5` (VIP Tier)
- **Expected Result:** Seats turn green (`status-selected`), ticket counter updates to `2`, total price recalculates dynamically in PKR.
- **Actual Result:** Seats highlighted, subtotal updated dynamically via JavaScript, hidden input values populated with IDs `[4, 5]`.
- **Status:** **PASS**

#### Test Case 5.2 (Negative — Max Seat Booking Cap)
- **Test ID:** `TC-ST-02`
- **Test Scenario:** Customer attempts to select more than 8 seats in a single reservation
- **Input:**
  - Selected Seats: 9 seats (Rows A & B)
- **Expected Result:** UI alerts user that maximum 8 seats are permitted; prevents further seat selection.
- **Actual Result:** Modal toast displayed `"Maximum limit reached: You can book up to 8 seats per transaction"`. Backend also enforces `count($seatIds) <= 8`.
- **Status:** **PASS**

---

### Test 6: Booking Test

#### Test Case 6.1 (Positive — Atomic Booking Creation)
- **Test ID:** `TC-BK-01`
- **Test Scenario:** Submission of selected seats to reserve auditorium tickets
- **Input:**
  - Show ID: `1`
  - Selected Seat IDs: `[4, 5]`
  - Payment Method: `Debit/Credit Card`
- **Expected Result:** Database transaction begins (`beginTransaction()`), checks seat availability lock, creates booking record with unique alphanumeric code (`CP-XXXXXXXX`), reserves seats in `booking_seats`, records payment, commits transaction (`commit()`).
- **Actual Result:** Booking record created atomically; booking code `CP-74D3A19B` assigned; redirected to `booking-success.php?id=...`.
- **Status:** **PASS**

#### Test Case 6.2 (Negative — Concurrency / Seat Collision Defense)
- **Test ID:** `TC-BK-02`
- **Test Scenario:** Attempt to book a seat that was just booked by another customer
- **Input:**
  - Seat ID: Already booked seat
- **Expected Result:** Transaction detects conflict via prepared query, rolls back transaction (`rollBack()`), alerts user with warning.
- **Actual Result:** System returned `"One or more selected seats have just been booked. Please choose other seats."` with no duplicate insertion.
- **Status:** **PASS**

---

### Test 7: Payment Test

#### Test Case 7.1 (Positive — Simulated Multi-Channel Checkout)
- **Test ID:** `TC-PAY-01`
- **Test Scenario:** Process payment via simulated local gateway (Card / EasyPaisa / JazzCash / Cash)
- **Input:**
  - Method: `EasyPaisa`
  - Account Number: `03001234567`
  - Total: `Rs. 2,400.00`
- **Expected Result:** Payment record recorded in `payments` table with status `completed`, unique transaction ID generated, zero raw credit card/CVV data stored.
- **Actual Result:** Payment simulation recorded with `payment_method = 'EasyPaisa'`, transaction hash generated, ticket issuance confirmed.
- **Status:** **PASS**

---

### Test 8: Admin Login Test

#### Test Case 8.1 (Positive — Authorized Admin Access)
- **Test ID:** `TC-ADM-01`
- **Test Scenario:** Administrator sign-in with valid admin credentials
- **Input:**
  - Email: `admin@moviebooking.com`
  - Password: `admin123`
- **Expected Result:** Admin authenticated via `admins` table, `$_SESSION['admin_id']` set, redirected to `admin/dashboard.php`.
- **Actual Result:** Admin console unlocked, welcome alert displayed, KPI analytics cards and sidebar controls rendered.
- **Status:** **PASS**

#### Test Case 8.2 (Negative — Unauthorized Customer Blocking)
- **Test ID:** `TC-ADM-02`
- **Test Scenario:** Normal customer or unauthenticated guest visits `admin/dashboard.php`
- **Input:**
  - Direct URL access to `admin/dashboard.php` without admin session
- **Expected Result:** `requireAdmin()` intercepts request, sets flash error, redirects to `admin/login.php`.
- **Actual Result:** Access denied; redirected immediately to `admin/login.php` with `"Access denied. Administrator privileges required."`.
- **Status:** **PASS**

---

### Test 9: Movie CRUD Test

#### Test Case 9.1 (Positive — Create Movie)
- **Test ID:** `TC-CRUD-01`
- **Test Scenario:** Admin adds a new movie record with poster upload and trailer link
- **Input:**
  - Title: `Gladiator II`
  - Genre: `Action, Drama`
  - Duration: `148` minutes
  - Language: `English`
  - Release Date: `2024-11-22`
  - Status: `now_showing`
- **Expected Result:** Record inserted into `movies` table, poster saved, redirected to movie list with success notification.
- **Actual Result:** Prepared statement inserted record, movie appears immediately in admin panel and public catalog.
- **Status:** **PASS**

#### Test Case 9.2 (Positive — Update Movie)
- **Test ID:** `TC-CRUD-02`
- **Test Scenario:** Admin modifies movie duration, status, or description
- **Input:**
  - Movie ID: `4`
  - Status changed from `upcoming` to `now_showing`
- **Expected Result:** Record updated in database; changes reflected on public website.
- **Actual Result:** Database updated via `UPDATE movies SET status = ? WHERE id = ?`; confirmed on frontend.
- **Status:** **PASS**

#### Test Case 9.3 (Positive — Delete Movie)
- **Test ID:** `TC-CRUD-03`
- **Test Scenario:** Admin deletes a movie that has no active shows
- **Input:**
  - Action: `delete`, Movie ID: `10`
- **Expected Result:** Movie record safely deleted with confirmation dialog.
- **Actual Result:** Confirmation modal triggered, record removed, success message displayed.
- **Status:** **PASS**

---

### Test 10: Logout Test

#### Test Case 10.1 (Positive — Customer & Admin Session Termination)
- **Test ID:** `TC-LGT-01`
- **Test Scenario:** User or Admin logs out of system
- **Input:**
  - Click "Logout" button (`logout.php` or `admin/logout.php`)
- **Expected Result:** Session destroyed (`session_destroy()`), session array cleared (`$_SESSION = []`), session cookie invalidated, redirected to `login.php` / home.
- **Actual Result:** Session terminated; subsequent attempts to access `dashboard.php` or `admin/dashboard.php` are blocked by auth guards.
- **Status:** **PASS**
