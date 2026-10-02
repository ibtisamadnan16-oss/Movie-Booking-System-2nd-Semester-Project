<?php
 
$pageTitle = "Secure Payment - CinePass";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$user = currentUser();
$userId = (int)$_SESSION['user_id'];

$dbStatus = checkDBStatus();
if (!$dbStatus['status']) {
    setFlash('system_error', 'Database offline. Please ensure MySQL is running in XAMPP.', 'danger');
    redirect('movies.php');
}

$db = getDB();

$bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : (isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0);
$showId = isset($_POST['show_id']) ? (int)$_POST['show_id'] : (isset($_GET['show_id']) ? (int)$_GET['show_id'] : 0);
$seatIdsRaw = trim($_POST['selected_seat_ids'] ?? ($_GET['seat_ids'] ?? ''));
$seatNumbersRaw = trim($_POST['selected_seat_numbers'] ?? ($_GET['seat_numbers'] ?? ''));
$preselectedMethod = trim($_POST['payment_method'] ?? ($_GET['payment_method'] ?? 'Debit/Credit Card'));

$booking = null;
$show = null;
$selectedSeats = [];
$grandTotal = 0;

if ($bookingId > 0) {
 
    $stmt = $db->prepare("
        SELECT b.*, s.show_date, s.start_time, s.end_time, s.ticket_price,
               m.title as movie_title, m.poster_image, m.duration_minutes, m.genre, m.rating,
               c.name as cinema_name, c.city as cinema_city, c.location as cinema_location,
               scr.screen_name, scr.screen_type,
               p.payment_status, p.transaction_id
        FROM bookings b
        JOIN shows s ON b.show_id = s.id
        JOIN movies m ON s.movie_id = m.id
        JOIN cinemas c ON s.cinema_id = c.id
        JOIN screens scr ON s.screen_id = scr.id
        LEFT JOIN payments p ON b.id = p.booking_id
        WHERE b.id = ? AND b.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        setFlash('booking_error', 'Booking record not found or access denied.', 'danger');
        redirect('user/bookings.php');
    }

    if (!empty($booking['payment_status']) && $booking['payment_status'] === 'completed') {
        setFlash('booking_success', 'This booking has already been paid and confirmed.', 'info');
        redirect('user/booking-success.php?booking_id=' . $bookingId);
    }

    $showId = (int)$booking['show_id'];
    $grandTotal = (float)$booking['total_amount'];

$seatsStmt = $db->prepare("
        SELECT s.id, s.seat_number, s.seat_type 
        FROM booking_seats bs 
        JOIN seats s ON bs.seat_id = s.id 
        WHERE bs.booking_id = ? 
        ORDER BY s.seat_row ASC, s.seat_column ASC
    ");
    $seatsStmt->execute([$bookingId]);
    $selectedSeats = $seatsStmt->fetchAll();
    $seatIds = array_column($selectedSeats, 'id');
    $seatIdsRaw = implode(',', $seatIds);
    $seatNumbersRaw = implode(', ', array_column($selectedSeats, 'seat_number'));

} else {
 
    if ($showId <= 0 || empty($seatIdsRaw)) {
 
        if (!empty($_SESSION['pending_booking'])) {
            $showId = (int)($_SESSION['pending_booking']['show_id'] ?? 0);
            $seatIdsRaw = trim($_SESSION['pending_booking']['seat_ids'] ?? '');
            $seatNumbersRaw = trim($_SESSION['pending_booking']['seat_numbers'] ?? '');
        }
    }

    if ($showId <= 0 || empty($seatIdsRaw)) {
        setFlash('booking_error', 'Please select a movie showtime and seats before making a payment.', 'warning');
        redirect('movies.php');
    }

    $seatIds = array_filter(array_map('intval', explode(',', $seatIdsRaw)));

$showStmt = $db->prepare("
        SELECT s.*, m.title as movie_title, m.poster_image, m.duration_minutes, m.genre, m.rating,
               c.name as cinema_name, c.city as cinema_city, c.location as cinema_location,
               scr.screen_name, scr.screen_type
        FROM shows s
        JOIN movies m ON s.movie_id = m.id
        JOIN cinemas c ON s.cinema_id = c.id
        JOIN screens scr ON s.screen_id = scr.id
        WHERE s.id = ? AND s.status != 'cancelled'
        LIMIT 1
    ");
    $showStmt->execute([$showId]);
    $show = $showStmt->fetch();

    if (!$show) {
        setFlash('booking_error', 'The requested showtime is no longer active.', 'danger');
        redirect('movies.php');
    }

$inPlaceholders = implode(',', array_fill(0, count($seatIds), '?'));
    $seatsStmt = $db->prepare("
        SELECT id, seat_number, seat_type, price_multiplier 
        FROM seats 
        WHERE id IN ($inPlaceholders) AND screen_id = ?
        ORDER BY seat_row ASC, seat_column ASC
    ");
    $seatsStmt->execute(array_merge($seatIds, [$show['screen_id']]));
    $selectedSeats = $seatsStmt->fetchAll();

    $basePrice = (float)$show['ticket_price'];
    $subtotal = 0;
    foreach ($selectedSeats as $st) {
        $multiplier = (float)($st['price_multiplier'] ?? 1.0);
        $subtotal += round($basePrice * $multiplier);
    }
    $tax = round($subtotal * 0.05);
    $grandTotal = $subtotal + $tax;
}

$seatCount = count($selectedSeats);
$seatNumbers = !empty($seatNumbersRaw) ? $seatNumbersRaw : implode(', ', array_column($selectedSeats, 'seat_number'));

$flashError = getFlash('payment_error');

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-4 my-lg-5 flex-grow-1">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-secondary text-decoration-none"><i class="fa-solid fa-house me-1"></i>Home</a></li>
            <li class="breadcrumb-item"><a href="<?= url('movies.php') ?>" class="text-secondary text-decoration-none">Movies</a></li>
            <li class="breadcrumb-item"><a href="<?= url('booking-summary.php?show_id=' . $showId . '&seat_ids=' . urlencode($seatIdsRaw)) ?>" class="text-secondary text-decoration-none">Summary</a></li>
            <li class="breadcrumb-item active text-danger fw-bold" aria-current="page">Simulated Payment</li>
        </ol>
    </nav>

    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($flashError['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <span class="badge bg-danger text-uppercase px-3 py-2 mb-2">Phase 10: Payment Gateway</span>
            <h2 class="text-white fw-bold mb-1">
                <i class="fa-solid fa-shield-halved text-success me-2"></i> Secure Payment Checkout
            </h2>
            <p class="text-secondary small mb-0">Select your preferred simulated payment method to finalize ticket reservation</p>
        </div>
        <div>
            <span class="badge bg-success-subtle text-success border border-success px-3 py-2">
                <i class="fa-solid fa-lock me-1"></i> 256-Bit SSL Demo Gateway
            </span>
        </div>
    </div>

    <div class="alert alert-dark border-secondary d-flex align-items-center gap-3 p-3 mb-4 shadow-sm">
        <i class="fa-solid fa-circle-info text-info fs-3 flex-shrink-0"></i>
        <div class="small">
            <strong class="text-white">Academic Project Simulation Note:</strong>
            <span class="text-secondary">
                This payment gateway operates in safe simulation mode for Aptech evaluation. 
                <strong class="text-warning">Real card numbers are NEVER saved in the database</strong>. 
                You can use the simulated options below or click <em>"Fill Test Data"</em> to complete your reservation.
            </span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="cine-card p-4">
                <h5 class="text-white fw-bold mb-4">
                    <i class="fa-solid fa-wallet text-danger me-2"></i> Select Payment Method
                </h5>

                <ul class="nav nav-pills nav-fill gap-2 mb-4 p-1 rounded bg-dark border border-secondary" id="paymentTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $preselectedMethod === 'Debit/Credit Card' ? 'active bg-danger text-white' : 'text-light' ?> py-2 fw-semibold small" 
                                id="card-tab" data-bs-toggle="pill" data-bs-target="#tab-card" type="button" role="tab">
                            <i class="fa-regular fa-credit-card me-1"></i> Card
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $preselectedMethod === 'EasyPaisa' ? 'active bg-danger text-white' : 'text-light' ?> py-2 fw-semibold small" 
                                id="easypaisa-tab" data-bs-toggle="pill" data-bs-target="#tab-easypaisa" type="button" role="tab">
                            <i class="fa-solid fa-mobile-screen-button text-success me-1"></i> EasyPaisa
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $preselectedMethod === 'JazzCash' ? 'active bg-danger text-white' : 'text-light' ?> py-2 fw-semibold small" 
                                id="jazzcash-tab" data-bs-toggle="pill" data-bs-target="#tab-jazzcash" type="button" role="tab">
                            <i class="fa-solid fa-wallet text-danger me-1"></i> JazzCash
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $preselectedMethod === 'Cash' ? 'active bg-danger text-white' : 'text-light' ?> py-2 fw-semibold small" 
                                id="cash-tab" data-bs-toggle="pill" data-bs-target="#tab-cash" type="button" role="tab">
                            <i class="fa-solid fa-money-bill-wave text-warning me-1"></i> Cash Counter
                        </button>
                    </li>
                </ul>

                <form id="paymentProcessingForm" method="POST" action="<?= url('actions/payment_action.php') ?>">
                    <input type="hidden" name="action" value="process_payment">
                    <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
                    <input type="hidden" name="show_id" value="<?= $showId ?>">
                    <input type="hidden" name="selected_seat_ids" value="<?= htmlspecialchars($seatIdsRaw) ?>">
                    <input type="hidden" name="selected_seat_numbers" value="<?= htmlspecialchars($seatNumbers) ?>">
                    <input type="hidden" name="total_amount" value="<?= $grandTotal ?>">
                    <input type="hidden" name="payment_method" id="selectedPaymentMethod" value="<?= htmlspecialchars($preselectedMethod) ?>">

                    <div class="tab-content" id="paymentTabsContent">
                        
                        <div class="tab-pane fade <?= $preselectedMethod === 'Debit/Credit Card' ? 'show active' : '' ?>" id="tab-card" role="tabpanel">
                            <div class="p-4 rounded-4 mb-4 shadow-lg text-white position-relative overflow-hidden" 
                                 style="background: linear-gradient(135deg, #1f2338 0%, #0d0f1a 100%); border: 1px solid rgba(255,255,255,0.12); max-width: 420px; margin: 0 auto;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="small text-uppercase tracking-wider opacity-75">Debit / Credit Card</div>
                                    <span class="fs-4 text-warning" id="cardBrandIcon"><i class="fa-brands fa-cc-visa"></i></span>
                                </div>
                                <div class="my-3 font-monospace fs-5 text-center letter-spacing-2" id="cardPreviewNumber" style="letter-spacing: 3px;">
                                    •••• •••• •••• 4242
                                </div>
                                <div class="d-flex justify-content-between align-items-end mt-4 small">
                                    <div>
                                        <div class="text-secondary" style="font-size: 0.65rem;">CARDHOLDER</div>
                                        <div class="fw-bold text-uppercase" id="cardPreviewName"><?= htmlspecialchars($user['name']) ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-secondary" style="font-size: 0.65rem;">EXPIRES</div>
                                        <div class="fw-bold font-monospace" id="cardPreviewExpiry">12/28</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label text-light small fw-bold mb-0">Card Payment Details</label>
                                <button type="button" class="btn btn-outline-warning btn-sm py-0 px-2" style="font-size: 0.75rem;" onclick="fillTestCard()">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Fill Test Card
                                </button>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label text-secondary small mb-1">Name on Card</label>
                                    <input type="text" id="cardNameInput" class="form-control cine-form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-secondary small mb-1">16-Digit Card Number</label>
                                    <div class="input-group">
                                        <input type="text" id="cardNumberInput" class="form-control cine-form-control font-monospace" placeholder="4242 4242 4242 4242" maxlength="19" required>
                                        <span class="input-group-text bg-dark border-secondary text-secondary">
                                            <i class="fa-solid fa-credit-card"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.72rem;">* Any dummy 16-digit card accepted. Details are never saved in DB.</small>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-secondary small mb-1">Expiry Date</label>
                                    <input type="text" id="cardExpiryInput" class="form-control cine-form-control font-monospace text-center" placeholder="MM/YY" maxlength="5" value="12/28" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-secondary small mb-1">CVV / CVC</label>
                                    <input type="password" id="cardCvvInput" class="form-control cine-form-control font-monospace text-center" placeholder="123" maxlength="3" value="888" required>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade <?= $preselectedMethod === 'EasyPaisa' ? 'show active' : '' ?>" id="tab-easypaisa" role="tabpanel">
                            <div class="p-3 mb-4 rounded bg-dark border border-success d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                        <i class="fa-solid fa-mobile-screen-button fs-5"></i>
                                    </div>
                                    <div>
                                        <h6 class="text-white fw-bold mb-0">EasyPaisa Mobile Account</h6>
                                        <small class="text-secondary">Direct simulated debit authorization</small>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="fillTestEasyPaisa()">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Fill Test Data
                                </button>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label text-secondary small mb-1">EasyPaisa Mobile Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-dark border-secondary text-secondary">+92</span>
                                        <input type="text" id="epNumberInput" class="form-control cine-form-control font-monospace" placeholder="3001234567" maxlength="10" value="3019876543">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-secondary small mb-1">Account Holder Full Name</label>
                                    <input type="text" id="epNameInput" class="form-control cine-form-control" value="<?= htmlspecialchars($user['name']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-secondary small mb-1">Simulated 5-Digit USSD / OTP Approval PIN</label>
                                    <input type="password" class="form-control cine-form-control font-monospace text-center" placeholder="•••••" maxlength="5" value="12345">
                                    <small class="text-muted" style="font-size: 0.72rem;">* Push notification will be simulated upon submission.</small>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade <?= $preselectedMethod === 'JazzCash' ? 'show active' : '' ?>" id="tab-jazzcash" role="tabpanel">
                            <div class="p-3 mb-4 rounded bg-dark border border-danger d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                        <i class="fa-solid fa-wallet fs-5"></i>
                                    </div>
                                    <div>
                                        <h6 class="text-white fw-bold mb-0">JazzCash Mobile Wallet</h6>
                                        <small class="text-secondary">Instant MPIN verified checkout</small>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="fillTestJazzCash()">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Fill Test Data
                                </button>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label text-secondary small mb-1">JazzCash Registered Mobile Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-dark border-secondary text-secondary">+92</span>
                                        <input type="text" id="jcNumberInput" class="form-control cine-form-control font-monospace" placeholder="3001234567" maxlength="10" value="3005551234">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-secondary small mb-1">CNIC Last 6 Digits</label>
                                    <input type="text" class="form-control cine-form-control font-monospace text-center" placeholder="123456" maxlength="6" value="789012">
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-secondary small mb-1">Simulated 4-Digit MPIN</label>
                                    <input type="password" class="form-control cine-form-control font-monospace text-center" placeholder="••••" maxlength="4" value="4321">
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade <?= $preselectedMethod === 'Cash' ? 'show active' : '' ?>" id="tab-cash" role="tabpanel">
                            <div class="p-4 rounded bg-dark border border-warning text-center">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-25 text-warning mb-3" style="width: 60px; height: 60px;">
                                    <i class="fa-solid fa-money-bill-wave fs-3"></i>
                                </div>
                                <h5 class="text-white fw-bold mb-2">Pay Cash at Cinema Box Office</h5>
                                <p class="text-secondary small mb-3" style="max-width: 480px; margin: 0 auto;">
                                    Your seats will be officially reserved in the system. You will receive an admission voucher with your Booking Reference. 
                                    Please arrive at the theatre counter at least <strong class="text-warning">20 minutes before showtime</strong> to pay cash and collect physical ticket stubs.
                                </p>
                                <div class="badge bg-warning text-dark px-3 py-2 fw-bold">
                                    <i class="fa-solid fa-circle-check me-1"></i> No Online Charges &bull; Cash on Collection
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top border-secondary">
                        <button type="submit" id="btnSubmitPayment" class="btn btn-cine-primary w-100 py-3 fw-bold fs-6 shadow-lg">
                            <i class="fa-solid fa-lock me-2"></i> Pay Rs. <?= number_format($grandTotal) ?> & Generate Ticket &rarr;
                        </button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <small class="text-secondary" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-shield-halved text-success me-1"></i> Encrypted Transaction. Double-booking prevention lock verified.
                    </small>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="cine-card p-4 sticky-top" style="top: 90px;">
                <h5 class="text-white fw-bold mb-3 border-bottom border-secondary pb-3">
                    <i class="fa-solid fa-receipt text-danger me-2"></i> Reservation Details
                </h5>

                <?php 
                $movieTitle = $booking['movie_title'] ?? ($show['movie_title'] ?? 'Movie');
                $posterImg  = $booking['poster_image'] ?? ($show['poster_image'] ?? '');
                $cinemaName = $booking['cinema_name'] ?? ($show['cinema_name'] ?? '');
                $screenName = $booking['screen_name'] ?? ($show['screen_name'] ?? '');
                $screenType = $booking['screen_type'] ?? ($show['screen_type'] ?? '');
                $showDate   = $booking['show_date'] ?? ($show['show_date'] ?? date('Y-m-d'));
                $startTime  = $booking['start_time'] ?? ($show['start_time'] ?? '12:00:00');
                ?>

                <div class="d-flex align-items-center gap-3 p-3 rounded bg-dark border border-secondary mb-3">
                    <?php if (!empty($posterImg)): ?>
                        <img src="<?= url('assets/images/' . $posterImg) ?>" 
                             alt="<?= htmlspecialchars($movieTitle) ?>" 
                             class="rounded shadow" 
                             style="width: 60px; height: 85px; object-fit: cover;"
                             onerror="this.style.display='none'">
                    <?php endif; ?>
                    <div>
                        <h6 class="text-white fw-bold mb-1"><?= htmlspecialchars($movieTitle) ?></h6>
                        <small class="text-secondary d-block"><i class="fa-solid fa-video text-danger me-1"></i> <?= htmlspecialchars($cinemaName) ?></small>
                        <small class="text-secondary d-block"><i class="fa-solid fa-tv text-danger me-1"></i> <?= htmlspecialchars($screenName) ?> (<?= htmlspecialchars($screenType) ?>)</small>
                    </div>
                </div>

                <div class="d-flex flex-column gap-2 small mb-3">
                    <div class="d-flex justify-content-between p-2 rounded bg-dark border border-secondary">
                        <span class="text-secondary">Date & Time:</span>
                        <strong class="text-light"><?= date('D, d M', strtotime($showDate)) ?> &bull; <?= date('h:i A', strtotime($startTime)) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between p-2 rounded bg-dark border border-secondary">
                        <span class="text-secondary">Seats (<?= $seatCount ?>):</span>
                        <strong class="text-success"><?= htmlspecialchars($seatNumbers) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between p-2 rounded bg-dark border border-secondary">
                        <span class="text-secondary">Ticket Holder:</span>
                        <strong class="text-light"><?= htmlspecialchars($user['name']) ?></strong>
                    </div>
                </div>

                <hr class="border-secondary my-3">

                <div class="d-flex justify-content-between align-items-center p-3 rounded bg-dark border border-danger mb-3">
                    <div>
                        <span class="text-secondary small text-uppercase fw-bold d-block">Amount Payable</span>
                        <span class="text-success fw-bold fs-3">Rs. <?= number_format($grandTotal) ?></span>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1 small">
                        All Inclusive
                    </span>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?= url('booking-summary.php?show_id=' . $showId . '&seat_ids=' . urlencode($seatIdsRaw)) ?>" class="btn btn-outline-secondary w-100 btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> Edit Summary
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentLoadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary text-white text-center p-4">
            <div class="spinner-border text-danger mx-auto mb-3" style="width: 3.5rem; height: 3.5rem;" role="status">
                <span class="visually-hidden">Processing...</span>
            </div>
            <h5 class="fw-bold mb-1">Authorizing Payment...</h5>
            <p class="text-secondary small mb-0">Simulating gateway transaction & issuing verified cinema tickets...</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectedPaymentMethodInput = document.getElementById('selectedPaymentMethod');
    const paymentTabs = document.querySelectorAll('#paymentTabs button[data-bs-toggle="pill"]');
    const paymentProcessingForm = document.getElementById('paymentProcessingForm');
    const btnSubmitPayment = document.getElementById('btnSubmitPayment');

    // Update active tab & hidden payment_method input
    paymentTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (e) {
            paymentTabs.forEach(t => t.classList.remove('bg-danger', 'text-white'));
            this.classList.add('bg-danger', 'text-white');

            const targetId = this.getAttribute('data-bs-target');
            let method = 'Debit/Credit Card';
            if (targetId === '#tab-easypaisa') method = 'EasyPaisa';
            if (targetId === '#tab-jazzcash') method = 'JazzCash';
            if (targetId === '#tab-cash') method = 'Cash';

            selectedPaymentMethodInput.value = method;

            if (method === 'Cash') {
                btnSubmitPayment.innerHTML = '<i class="fa-solid fa-check me-2"></i> Confirm Cash Reservation & Generate Ticket &rarr;';
            } else {
                btnSubmitPayment.innerHTML = '<i class="fa-solid fa-lock me-2"></i> Pay Rs. <?= number_format($grandTotal) ?> & Generate Ticket &rarr;';
            }
        });
    });

    // Real-time Card preview synchronization
    const cardNumberInput = document.getElementById('cardNumberInput');
    const cardNameInput = document.getElementById('cardNameInput');
    const cardExpiryInput = document.getElementById('cardExpiryInput');
    const cardPreviewNumber = document.getElementById('cardPreviewNumber');
    const cardPreviewName = document.getElementById('cardPreviewName');
    const cardPreviewExpiry = document.getElementById('cardPreviewExpiry');
    const cardBrandIcon = document.getElementById('cardBrandIcon');

    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function (e) {
            let val = this.value.replace(/\D/g, '').substring(0, 16);
            let formatted = val.match(/.{1,4}/g)?.join(' ') || '';
            this.value = formatted;
            cardPreviewNumber.textContent = formatted || '•••• •••• •••• 4242';

            if (val.startsWith('4')) {
                cardBrandIcon.innerHTML = '<i class="fa-brands fa-cc-visa text-primary"></i>';
            } else if (val.startsWith('5')) {
                cardBrandIcon.innerHTML = '<i class="fa-brands fa-cc-mastercard text-warning"></i>';
            } else {
                cardBrandIcon.innerHTML = '<i class="fa-solid fa-credit-card text-light"></i>';
            }
        });
    }

    if (cardNameInput) {
        cardNameInput.addEventListener('input', function () {
            cardPreviewName.textContent = this.value.toUpperCase() || '<?= strtoupper(htmlspecialchars($user['name'])) ?>';
        });
    }

    if (cardExpiryInput) {
        cardExpiryInput.addEventListener('input', function () {
            let val = this.value.replace(/\D/g, '').substring(0, 4);
            if (val.length >= 3) {
                this.value = val.substring(0, 2) + '/' + val.substring(2, 4);
            } else {
                this.value = val;
            }
            cardPreviewExpiry.textContent = this.value || '12/28';
        });
    }

    // Form submit simulated animation
    paymentProcessingForm.addEventListener('submit', function (e) {
        const modal = new bootstrap.Modal(document.getElementById('paymentLoadingModal'));
        modal.show();
    });
});

// Helper Autofill functions for quick academic evaluation
function fillTestCard() {
    const num = document.getElementById('cardNumberInput');
    const name = document.getElementById('cardNameInput');
    const exp = document.getElementById('cardExpiryInput');
    const cvv = document.getElementById('cardCvvInput');
    if (num) { num.value = '4242 4242 4242 4242'; num.dispatchEvent(new Event('input')); }
    if (name) { name.value = '<?= htmlspecialchars($user['name']) ?>'; name.dispatchEvent(new Event('input')); }
    if (exp) { exp.value = '12/28'; exp.dispatchEvent(new Event('input')); }
    if (cvv) cvv.value = '888';
}

function fillTestEasyPaisa() {
    const epNum = document.getElementById('epNumberInput');
    const epName = document.getElementById('epNameInput');
    if (epNum) epNum.value = '3019876543';
    if (epName) epName.value = '<?= htmlspecialchars($user['name']) ?>';
}

function fillTestJazzCash() {
    const jcNum = document.getElementById('jcNumberInput');
    if (jcNum) jcNum.value = '3005551234';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
