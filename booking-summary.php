<?php
 
$pageTitle = "Booking Summary - CinePass";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$dbStatus = checkDBStatus();
if (!$dbStatus['status']) {
    setFlash('system_error', 'Database offline. Please ensure MySQL is running in XAMPP.', 'danger');
    redirect('movies.php');
}

$db = getDB();

$showId = isset($_POST['show_id']) ? (int)$_POST['show_id'] : (isset($_GET['show_id']) ? (int)$_GET['show_id'] : 0);
$seatIdsRaw = trim($_POST['selected_seat_ids'] ?? ($_GET['seat_ids'] ?? ''));
$seatNumbersRaw = trim($_POST['selected_seat_numbers'] ?? ($_GET['seat_numbers'] ?? ''));

if (($showId <= 0 || empty($seatIdsRaw)) && !empty($_SESSION['pending_booking'])) {
    $showId = (int)($_SESSION['pending_booking']['show_id'] ?? 0);
    $seatIdsRaw = trim($_SESSION['pending_booking']['seat_ids'] ?? '');
    $seatNumbersRaw = trim($_SESSION['pending_booking']['seat_numbers'] ?? '');
}

if ($showId <= 0 || empty($seatIdsRaw)) {
    setFlash('booking_error', 'Please select your showtime and seats first.', 'warning');
    redirect('movies.php');
}

if (!isLoggedIn()) {
    $_SESSION['pending_booking'] = [
        'show_id'      => $showId,
        'seat_ids'     => $seatIdsRaw,
        'seat_numbers' => $seatNumbersRaw
    ];
    $_SESSION['redirect_url'] = 'booking-summary.php?show_id=' . $showId . '&seat_ids=' . urlencode($seatIdsRaw) . '&seat_numbers=' . urlencode($seatNumbersRaw);
    setFlash('auth_error', 'Please sign in or create an account to review your booking summary and confirm your tickets.', 'warning');
    redirect('login.php');
}

$user = currentUser();
$userId = (int)$_SESSION['user_id'];
$seatIds = array_filter(array_map('intval', explode(',', $seatIdsRaw)));

if (empty($seatIds)) {
    setFlash('booking_error', 'Please select at least 1 seat to proceed.', 'warning');
    redirect('user/select-seats.php?show_id=' . $showId);
}

$showStmt = $db->prepare("
    SELECT 
        s.*,
        m.title AS movie_title,
        m.genre,
        m.duration_minutes,
        m.rating,
        m.poster_image,
        m.language,
        c.name AS cinema_name,
        c.city AS cinema_city,
        c.location AS cinema_location,
        scr.screen_name,
        scr.screen_type,
        scr.total_seats
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
    setFlash('booking_error', 'The requested showtime is unavailable.', 'danger');
    redirect('movies.php');
}

$inPlaceholders = implode(',', array_fill(0, count($seatIds), '?'));
$seatsStmt = $db->prepare("
    SELECT id, seat_number, seat_row, seat_column, seat_type, price_multiplier, status
    FROM seats
    WHERE id IN ($inPlaceholders) AND screen_id = ?
    ORDER BY seat_row ASC, seat_column ASC
");
$seatsStmt->execute(array_merge($seatIds, [$show['screen_id']]));
$selectedSeats = $seatsStmt->fetchAll();

if (count($selectedSeats) !== count($seatIds)) {
    setFlash('booking_error', 'Invalid seats selected. Please choose your seats again.', 'danger');
    redirect('user/select-seats.php?show_id=' . $showId);
}

$conflictStmt = $db->prepare("
    SELECT bs.seat_id, s.seat_number 
    FROM booking_seats bs 
    JOIN seats s ON bs.seat_id = s.id 
    JOIN bookings b ON bs.booking_id = b.id 
    WHERE bs.show_id = ? AND bs.seat_id IN ($inPlaceholders) AND b.booking_status != 'cancelled'
");
$conflictStmt->execute(array_merge([$showId], $seatIds));
$conflicts = $conflictStmt->fetchAll();

if (!empty($conflicts)) {
    $takenNumbers = array_column($conflicts, 'seat_number');
    setFlash('booking_error', 'Seat(s) ' . implode(', ', $takenNumbers) . ' have just been reserved by someone else. Please choose alternative seats.', 'danger');
    redirect('user/select-seats.php?show_id=' . $showId);
}

$basePrice = (float)$show['ticket_price'];
$subtotal = 0;
$itemizedSeats = [];

foreach ($selectedSeats as $s) {
    $mult = (float)($s['price_multiplier'] ?? 1.0);
    $price = round($basePrice * $mult);
    $subtotal += $price;
    $itemizedSeats[] = [
        'id'         => $s['id'],
        'number'     => $s['seat_number'],
        'tier'       => $s['seat_type'],
        'multiplier' => $mult,
        'price'      => $price
    ];
}

$seatCount = count($selectedSeats);
$serviceTax = round($subtotal * 0.05); 
$grandTotal = $subtotal + $serviceTax;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container my-4 my-lg-5 flex-grow-1">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-secondary text-decoration-none"><i class="fa-solid fa-house me-1"></i>Home</a></li>
            <li class="breadcrumb-item"><a href="<?= url('movies.php') ?>" class="text-secondary text-decoration-none">Movies</a></li>
            <li class="breadcrumb-item"><a href="<?= url('movie-details.php?id=' . $show['movie_id']) ?>" class="text-secondary text-decoration-none"><?= htmlspecialchars($show['movie_title']) ?></a></li>
            <li class="breadcrumb-item"><a href="<?= url('user/select-seats.php?show_id=' . $show['id']) ?>" class="text-secondary text-decoration-none">Seats</a></li>
            <li class="breadcrumb-item active text-danger fw-bold" aria-current="page">Booking Summary</li>
        </ol>
    </nav>

    <div class="cine-card p-3 p-md-4 mb-4">
        <div class="d-none d-md-flex justify-content-between align-items-center position-relative">
            <div class="position-absolute top-50 start-0 end-0 translate-middle-y bg-secondary bg-opacity-25" style="height: 3px; z-index: 1;"></div>
            <div class="position-absolute top-50 start-0 translate-middle-y bg-danger" style="width: 80%; height: 3px; z-index: 1;"></div>

            <div class="position-relative text-center" style="z-index: 2;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger text-white fw-bold shadow" style="width: 38px; height: 38px;">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div class="small fw-bold text-light mt-1">1. Select Movie</div>
                <div class="text-secondary small" style="font-size: 0.72rem;"><?= htmlspecialchars(substr($show['movie_title'], 0, 15)) ?>...</div>
            </div>

            <div class="position-relative text-center" style="z-index: 2;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger text-white fw-bold shadow" style="width: 38px; height: 38px;">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div class="small fw-bold text-light mt-1">2. Select Cinema</div>
                <div class="text-secondary small" style="font-size: 0.72rem;"><?= htmlspecialchars(substr($show['cinema_name'], 0, 15)) ?></div>
            </div>

            <div class="position-relative text-center" style="z-index: 2;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger text-white fw-bold shadow" style="width: 38px; height: 38px;">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div class="small fw-bold text-light mt-1">3. Select Showtime</div>
                <div class="text-secondary small" style="font-size: 0.72rem;"><?= date('h:i A', strtotime($show['start_time'])) ?></div>
            </div>

            <div class="position-relative text-center" style="z-index: 2;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger text-white fw-bold shadow" style="width: 38px; height: 38px;">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div class="small fw-bold text-light mt-1">4. Select Seats</div>
                <div class="text-secondary small" style="font-size: 0.72rem;"><?= $seatCount ?> Seats Selected</div>
            </div>

            <div class="position-relative text-center" style="z-index: 2;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger text-white fw-bold shadow border border-light" style="width: 42px; height: 42px; box-shadow: 0 0 15px rgba(229,9,20,0.8) !important;">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div class="small fw-bold text-danger mt-1">5. Booking Summary</div>
                <div class="text-light small" style="font-size: 0.72rem;">Review & Payment</div>
            </div>

            <div class="position-relative text-center opacity-50" style="z-index: 2;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-dark text-secondary fw-bold border border-secondary" style="width: 38px; height: 38px;">
                    <i class="fa-solid fa-ticket"></i>
                </div>
                <div class="small fw-bold text-secondary mt-1">6. Confirm Booking</div>
                <div class="text-secondary small" style="font-size: 0.72rem;">E-Ticket Voucher</div>
            </div>
        </div>

        <div class="d-md-none text-center">
            <span class="badge bg-danger-subtle text-danger border border-danger mb-1">Step 5 of 6</span>
            <h5 class="text-white fw-bold mb-0">Booking Summary & Payment</h5>
            <small class="text-secondary"><?= htmlspecialchars($show['movie_title']) ?> &bull; <?= $seatCount ?> Seats</small>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="cine-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center border-bottom border-secondary pb-3 mb-4 flex-wrap gap-2">
                    <div>
                        <h4 class="text-white fw-bold mb-1">
                            <i class="fa-solid fa-film text-danger me-2"></i> Cinema Ticket Details
                        </h4>
                        <p class="text-secondary small mb-0">Please verify your chosen showtime, screen, and seat numbers</p>
                    </div>
                    <a href="<?= url('user/select-seats.php?show_id=' . $show['id']) ?>" class="btn btn-outline-warning btn-sm">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Change Seats
                    </a>
                </div>

                <div class="row g-3 align-items-center mb-4 p-3 rounded bg-dark border border-secondary">
                    <div class="col-auto">
                        <?php if (!empty($show['poster_image'])): ?>
                            <img src="<?= url('assets/images/' . $show['poster_image']) ?>" 
                                 alt="<?= htmlspecialchars($show['movie_title']) ?>" 
                                 class="rounded shadow" 
                                 style="width: 80px; height: 110px; object-fit: cover; border: 1px solid var(--cine-card-border);"
                                 onerror="this.style.display='none'">
                        <?php endif; ?>
                    </div>
                    <div class="col">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="badge bg-danger-subtle text-danger border border-danger small">
                                <?= htmlspecialchars($show['screen_type']) ?>
                            </span>
                            <span class="badge bg-dark border border-secondary text-light small">
                                <i class="fa-regular fa-clock me-1 text-danger"></i> <?= htmlspecialchars($show['duration_minutes']) ?> Mins
                            </span>
                            <span class="badge bg-warning-subtle text-warning border border-warning small">
                                <i class="fa-solid fa-star me-1"></i> <?= htmlspecialchars($show['rating']) ?>/10
                            </span>
                        </div>
                        <h4 class="text-white fw-bold mb-1"><?= htmlspecialchars($show['movie_title']) ?></h4>
                        <p class="text-secondary small mb-1">
                            <i class="fa-solid fa-video text-danger me-1"></i> <strong><?= htmlspecialchars($show['cinema_name']) ?></strong> (<?= htmlspecialchars($show['cinema_city']) ?>)
                        </p>
                        <p class="text-secondary small mb-0">
                            <i class="fa-solid fa-tv text-danger me-1"></i> <?= htmlspecialchars($show['screen_name']) ?> &bull; <?= htmlspecialchars($show['cinema_location']) ?>
                        </p>
                    </div>
                </div>

                <div class="p-3 mb-4 rounded bg-dark border border-secondary d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="brand-icon" style="width: 44px; height: 44px;">
                            <i class="fa-solid fa-calendar-check text-danger"></i>
                        </div>
                        <div>
                            <span class="text-secondary small d-block">Scheduled Show Date</span>
                            <strong class="text-white fs-6"><?= date('l, d F Y', strtotime($show['show_date'])) ?></strong>
                        </div>
                    </div>
                    <div class="text-md-end">
                        <span class="text-secondary small d-block">Show Starting Time</span>
                        <strong class="text-danger fs-5">
                            <i class="fa-regular fa-clock me-1"></i> <?= date('h:i A', strtotime($show['start_time'])) ?>
                        </strong>
                        <small class="text-muted d-block">(Ends approx. <?= date('h:i A', strtotime($show['end_time'])) ?>)</small>
                    </div>
                </div>

                <h5 class="text-white fw-bold mb-3">
                    <i class="fa-solid fa-chair text-danger me-2"></i> Reserved Seats (<?= $seatCount ?>)
                </h5>
                <div class="table-responsive mb-4">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-secondary small">
                                <th>Seat No.</th>
                                <th>Row</th>
                                <th>Seating Tier</th>
                                <th>Rate Multiplier</th>
                                <th class="text-end">Ticket Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itemizedSeats as $item): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-success-subtle text-success border border-success fw-bold px-2 py-1 fs-6">
                                            <?= htmlspecialchars($item['number']) ?>
                                        </span>
                                    </td>
                                    <td class="text-secondary"><?= substr($item['number'], 0, 1) ?></td>
                                    <td>
                                        <span class="badge <?= $item['tier'] === 'VIP' ? 'bg-warning text-dark' : ($item['tier'] === 'Premium' ? 'bg-info text-dark' : 'bg-secondary') ?>">
                                            <?= htmlspecialchars($item['tier']) ?>
                                        </span>
                                    </td>
                                    <td class="text-secondary"><?= number_format($item['multiplier'], 2) ?>&times;</td>
                                    <td class="text-end text-light fw-bold">Rs. <?= number_format($item['price'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <h5 class="text-white fw-bold mb-3">
                    <i class="fa-solid fa-user-check text-danger me-2"></i> Ticket Holder Information
                </h5>
                <div class="p-3 rounded bg-dark border border-secondary mb-3">
                    <div class="row g-2 small">
                        <div class="col-sm-4">
                            <span class="text-secondary d-block">Customer Name:</span>
                            <strong class="text-light"><?= htmlspecialchars($user['name']) ?></strong>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-secondary d-block">Email Address:</span>
                            <strong class="text-light"><?= htmlspecialchars($user['email']) ?></strong>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-secondary d-block">Contact Phone:</span>
                            <strong class="text-light"><?= htmlspecialchars($_SESSION['user_phone'] ?? '03001234567') ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="cine-card p-4 sticky-top" style="top: 90px;">
                <h4 class="text-white fw-bold mb-3 border-bottom border-secondary pb-3">
                    <i class="fa-solid fa-calculator text-danger me-2"></i> Payment & Confirmation
                </h4>

                <div class="d-flex flex-column gap-2 mb-3 small">
                    <div class="d-flex justify-content-between text-secondary">
                        <span>Show Base Price:</span>
                        <span class="text-light fw-semibold">Rs. <?= number_format($basePrice, 2) ?> / seat</span>
                    </div>
                    <div class="d-flex justify-content-between text-secondary">
                        <span>Seats Quantity:</span>
                        <span class="text-light fw-semibold"><?= $seatCount ?> Ticket(s)</span>
                    </div>
                    <div class="d-flex justify-content-between text-secondary">
                        <span>Auditorium Subtotal:</span>
                        <span class="text-light fw-semibold">Rs. <?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-secondary">
                        <span>Cinema Service Tax (5%):</span>
                        <span class="text-light fw-semibold">Rs. <?= number_format($serviceTax, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-secondary">
                        <span>E-Ticket Generation Fee:</span>
                        <span class="text-success fw-semibold">FREE (Rs. 0.00)</span>
                    </div>
                </div>

                <hr class="border-secondary my-3">

                <div class="d-flex justify-content-between align-items-center p-3 mb-4 rounded bg-dark border border-danger">
                    <div>
                        <span class="text-secondary small text-uppercase fw-bold d-block">Grand Total Payable</span>
                        <span class="text-success fw-bold fs-3">Rs. <?= number_format($grandTotal, 2) ?></span>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success px-3 py-2">
                        PKR All Inclusive
                    </span>
                </div>

                <form method="POST" action="<?= url('user/payment.php') ?>" id="confirmBookingForm">
                    <input type="hidden" name="show_id" value="<?= $showId ?>">
                    <input type="hidden" name="selected_seat_ids" value="<?= htmlspecialchars($seatIdsRaw) ?>">
                    <input type="hidden" name="selected_seat_numbers" value="<?= htmlspecialchars($seatNumbersRaw) ?>">
                    <input type="hidden" name="total_amount" value="<?= $grandTotal ?>">

                    <label class="form-label text-white fw-bold mb-2 small text-uppercase">
                        <i class="fa-solid fa-credit-card text-danger me-1"></i> Choose Payment Method
                    </label>

                    <div class="d-flex flex-column gap-2 mb-4">
                        <label class="p-3 rounded bg-dark border border-secondary d-flex align-items-center justify-content-between cursor-pointer payment-option">
                            <div class="d-flex align-items-center gap-3">
                                <input type="radio" name="payment_method" value="Debit/Credit Card" class="form-check-input mt-0" checked>
                                <div>
                                    <strong class="text-light d-block small">Debit / Credit Card</strong>
                                    <small class="text-secondary" style="font-size: 0.72rem;">Visa, MasterCard, PayPak</small>
                                </div>
                            </div>
                            <span class="text-warning fs-5"><i class="fa-brands fa-cc-visa me-1"></i><i class="fa-brands fa-cc-mastercard"></i></span>
                        </label>

                        <label class="p-3 rounded bg-dark border border-secondary d-flex align-items-center justify-content-between cursor-pointer payment-option">
                            <div class="d-flex align-items-center gap-3">
                                <input type="radio" name="payment_method" value="EasyPaisa" class="form-check-input mt-0">
                                <div>
                                    <strong class="text-light d-block small">EasyPaisa Mobile Wallet</strong>
                                    <small class="text-secondary" style="font-size: 0.72rem;">Instant Mobile Account Debit</small>
                                </div>
                            </div>
                            <span class="text-success fs-5"><i class="fa-solid fa-mobile-screen-button"></i></span>
                        </label>

                        <label class="p-3 rounded bg-dark border border-secondary d-flex align-items-center justify-content-between cursor-pointer payment-option">
                            <div class="d-flex align-items-center gap-3">
                                <input type="radio" name="payment_method" value="JazzCash" class="form-check-input mt-0">
                                <div>
                                    <strong class="text-light d-block small">JazzCash Mobile Wallet</strong>
                                    <small class="text-secondary" style="font-size: 0.72rem;">Fast biometric OTP authorization</small>
                                </div>
                            </div>
                            <span class="text-danger fs-5"><i class="fa-solid fa-wallet"></i></span>
                        </label>

                        <label class="p-3 rounded bg-dark border border-secondary d-flex align-items-center justify-content-between cursor-pointer payment-option">
                            <div class="d-flex align-items-center gap-3">
                                <input type="radio" name="payment_method" value="Cash" class="form-check-input mt-0">
                                <div>
                                    <strong class="text-light d-block small">Cash at Box Office Counter</strong>
                                    <small class="text-secondary" style="font-size: 0.72rem;">Pay at cinema 20 mins before showtime</small>
                                </div>
                            </div>
                            <span class="text-info fs-5"><i class="fa-solid fa-money-bill-wave"></i></span>
                        </label>
                    </div>

                    <div class="form-check mb-4 small text-secondary">
                        <input class="form-check-input bg-dark border-secondary" type="checkbox" id="termsCheck" required checked>
                        <label class="form-check-label" for="termsCheck">
                            I agree to the cinema admission terms, age ratings, and ticket reservation policy.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-cine-primary w-100 py-3 fw-bold fs-6 shadow-lg mb-2">
                        <i class="fa-solid fa-credit-card me-2"></i> Proceed to Payment &rarr; (Rs. <?= number_format($grandTotal) ?>)
                    </button>

                    <a href="<?= url('user/select-seats.php?show_id=' . $showId) ?>" class="btn btn-outline-secondary w-100 btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Seat Selection
                    </a>
                </form>

                <div class="text-center mt-3">
                    <small class="text-secondary" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-lock text-success me-1"></i> 256-bit SSL encrypted. Atomic double-booking lock active.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-option:has(input:checked) {
    border-color: var(--cine-primary) !important;
    background: rgba(229, 9, 20, 0.08) !important;
}
.cursor-pointer {
    cursor: pointer;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
