<?php
 
$pageTitle = "Ticket Confirmation - CinePass";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if ($bookingId <= 0) {
    setFlash('booking_error', 'Invalid ticket identifier.', 'danger');
    redirect('user/bookings.php');
}

$dbStatus = checkDBStatus();
if (!$dbStatus['status']) {
    setFlash('system_error', 'Database offline. Please check MySQL in XAMPP.', 'danger');
    redirect('user/bookings.php');
}

$db = getDB();
$userId = (int)$_SESSION['user_id'];

$stmt = $db->prepare("
    SELECT 
        b.*,
        s.show_date,
        s.start_time,
        s.end_time,
        s.ticket_price AS base_price,
        m.title AS movie_title,
        m.poster_image,
        m.genre,
        m.duration_minutes,
        m.language,
        m.rating,
        c.name AS cinema_name,
        c.city AS cinema_city,
        c.location AS cinema_location,
        scr.screen_name,
        scr.screen_type,
        p.payment_method,
        p.transaction_id,
        p.payment_status,
        p.payment_date,
        u.full_name AS customer_name,
        u.email AS customer_email
    FROM bookings b
    JOIN shows s ON b.show_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN cinemas c ON s.cinema_id = c.id
    JOIN screens scr ON s.screen_id = scr.id
    JOIN users u ON b.user_id = u.id
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE b.id = ? AND b.user_id = ?
    LIMIT 1
");
$stmt->execute([$bookingId, $userId]);
$booking = $stmt->fetch();

if (!$booking) {
    setFlash('booking_error', 'Ticket record not found or access unauthorized.', 'danger');
    redirect('user/bookings.php');
}

$seatsStmt = $db->prepare("
    SELECT bs.*, s.seat_number, s.seat_row, s.seat_column, s.seat_type 
    FROM booking_seats bs 
    JOIN seats s ON bs.seat_id = s.id 
    WHERE bs.booking_id = ? 
    ORDER BY s.seat_row ASC, s.seat_column ASC
");
$seatsStmt->execute([$bookingId]);
$reservedSeats = $seatsStmt->fetchAll();

$seatNumbers = array_column($reservedSeats, 'seat_number');
$seatCount = count($reservedSeats);

$qrData = urlencode("CINEPASS-TICKET|ID:" . $booking['id'] . "|CODE:" . $booking['booking_code'] . "|MOVIE:" . $booking['movie_title'] . "|SEATS:" . implode(',', $seatNumbers));
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . $qrData . "&color=0-0-0&bgcolor=255-255-255";

$flashSuccess = getFlash('booking_success');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container my-4 my-lg-5 flex-grow-1">
    <nav aria-label="breadcrumb" class="mb-4 d-print-none">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-secondary text-decoration-none"><i class="fa-solid fa-house me-1"></i>Home</a></li>
            <li class="breadcrumb-item"><a href="<?= url('user/bookings.php') ?>" class="text-secondary text-decoration-none">My Bookings</a></li>
            <li class="breadcrumb-item active text-danger fw-bold" aria-current="page">Ticket #<?= htmlspecialchars($booking['id']) ?></li>
        </ol>
    </nav>

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4 d-print-none" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($flashSuccess['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-xl-9 col-lg-10">
            <div class="text-center mb-4 d-print-none">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-25 text-success mb-3 shadow" style="width: 72px; height: 72px;">
                    <i class="fa-solid fa-circle-check fs-1"></i>
                </div>
                <h3 class="text-white fw-bold mb-1">Booking Confirmed Successfully!</h3>
                <p class="text-secondary small mb-0">Your seats are locked and tickets are issued. Present this digital voucher or printed pass at the auditorium entrance.</p>
            </div>

            <div class="cine-ticket-container shadow-2xl position-relative mb-4" id="printableTicket">
                <div class="ticket-notch notch-left d-none d-md-block"></div>
                <div class="ticket-notch notch-right d-none d-md-block"></div>

                <div class="ticket-header p-4 d-flex justify-content-between align-items-center flex-wrap gap-2 text-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="brand-icon bg-white text-danger" style="width: 44px; height: 44px; border-radius: 10px;">
                            <i class="fa-solid fa-film fs-5"></i>
                        </div>
                        <div>
                            <span class="small text-uppercase fw-bold letter-spacing-1 opacity-75 d-block">Official Cinema Admission E-Ticket</span>
                            <h4 class="fw-bold mb-0 text-white"><?= htmlspecialchars($booking['movie_title']) ?></h4>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-white text-danger fw-bold px-3 py-2 fs-6 shadow-sm">
                            <i class="fa-solid fa-circle-check text-success me-1"></i> <?= strtoupper(htmlspecialchars($booking['booking_status'])) ?>
                        </span>
                    </div>
                </div>

                <div class="ticket-body p-4 p-md-5">
                    <div class="row g-4 align-items-center">
                        <div class="col-md-3 text-center">
                            <?php if (!empty($booking['poster_image'])): ?>
                                <img src="<?= url('assets/images/' . $booking['poster_image']) ?>" 
                                     alt="<?= htmlspecialchars($booking['movie_title']) ?>" 
                                     class="rounded-3 shadow-lg img-fluid" 
                                     style="max-height: 220px; object-fit: cover; border: 2px solid rgba(255,255,255,0.15);"
                                     onerror="this.style.display='none'">
                            <?php else: ?>
                                <div class="rounded-3 bg-dark border border-secondary d-flex align-items-center justify-content-center text-secondary mx-auto" style="width: 140px; height: 190px;">
                                    <i class="fa-solid fa-film fs-1"></i>
                                </div>
                            <?php endif; ?>
                            <div class="mt-2 text-secondary small">
                                <span class="badge bg-danger-subtle text-danger border border-danger"><?= htmlspecialchars($booking['screen_type']) ?> Projection</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="row g-3">
                                <div class="col-6">
                                    <span class="ticket-meta-label">Booking ID</span>
                                    <strong class="ticket-meta-value text-white fs-5">#<?= htmlspecialchars($booking['id']) ?></strong>
                                </div>

                                <div class="col-6">
                                    <span class="ticket-meta-label">Booking Reference Code</span>
                                    <strong class="ticket-meta-value text-warning font-monospace fs-6"><?= htmlspecialchars($booking['booking_code']) ?></strong>
                                </div>

                                <div class="col-12">
                                    <span class="ticket-meta-label">Movie Name</span>
                                    <strong class="ticket-meta-value text-white fs-5"><?= htmlspecialchars($booking['movie_title']) ?></strong>
                                    <small class="text-secondary d-block"><?= htmlspecialchars($booking['genre']) ?> &bull; <?= htmlspecialchars($booking['duration_minutes']) ?> mins &bull; <?= htmlspecialchars($booking['language']) ?></small>
                                </div>

                                <div class="col-6">
                                    <span class="ticket-meta-label">Cinema Theatre</span>
                                    <strong class="ticket-meta-value text-light"><?= htmlspecialchars($booking['cinema_name']) ?></strong>
                                    <small class="text-muted d-block"><?= htmlspecialchars($booking['cinema_city']) ?></small>
                                </div>

                                <div class="col-6">
                                    <span class="ticket-meta-label">Auditorium Screen</span>
                                    <strong class="ticket-meta-value text-light"><?= htmlspecialchars($booking['screen_name']) ?></strong>
                                    <small class="text-muted d-block"><?= htmlspecialchars($booking['screen_type']) ?> Audio/Visual</small>
                                </div>

                                <div class="col-6">
                                    <span class="ticket-meta-label">Show Date</span>
                                    <strong class="ticket-meta-value text-light">
                                        <i class="fa-solid fa-calendar-day text-danger me-1"></i> <?= date('D, d M Y', strtotime($booking['show_date'])) ?>
                                    </strong>
                                </div>

                                <div class="col-6">
                                    <span class="ticket-meta-label">Show Time</span>
                                    <strong class="ticket-meta-value text-danger fs-5">
                                        <i class="fa-regular fa-clock me-1"></i> <?= date('h:i A', strtotime($booking['start_time'])) ?>
                                    </strong>
                                </div>

                                <div class="col-12">
                                    <span class="ticket-meta-label">Allocated Seats (<?= $seatCount ?>)</span>
                                    <div class="d-flex flex-wrap gap-2 mt-1">
                                        <?php foreach ($reservedSeats as $rs): ?>
                                            <span class="badge bg-success text-white px-2 py-1 fs-6 fw-bold shadow-sm">
                                                <i class="fa-solid fa-chair me-1"></i> <?= htmlspecialchars($rs['seat_number']) ?>
                                                <small class="opacity-75" style="font-size: 0.7rem;">(<?= htmlspecialchars($rs['seat_type']) ?>)</small>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <span class="ticket-meta-label">Total Amount Paid</span>
                                    <strong class="ticket-meta-value text-success fs-4">Rs. <?= number_format($booking['total_amount'], 2) ?></strong>
                                </div>

                                <div class="col-6">
                                    <span class="ticket-meta-label">Booking Status</span>
                                    <span class="badge bg-success-subtle text-success border border-success px-3 py-2 fw-bold text-uppercase">
                                        <i class="fa-solid fa-circle-check me-1"></i> <?= htmlspecialchars($booking['booking_status']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 text-center border-start-md border-secondary border-dashed ps-md-4">
                            <span class="ticket-meta-label d-block mb-2">Turnstile Entry Pass</span>
                            
                            <div class="p-2 bg-white rounded-3 d-inline-block shadow-sm mb-2">
                                <img src="<?= $qrCodeUrl ?>" 
                                     alt="Ticket QR Code" 
                                     class="img-fluid" 
                                     style="width: 130px; height: 130px;"
                                     onerror="this.src='https://chart.googleapis.com/chart?cht=qr&chs=140x140&chl=<?= $qrData ?>'">
                            </div>
                            <div class="font-monospace text-warning small fw-bold mb-1"><?= htmlspecialchars($booking['booking_code']) ?></div>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Scan at turnstile or usher checkpoint</small>
                            
                            <hr class="border-secondary my-2">
                            
                            <div class="small text-secondary text-start" style="font-size: 0.72rem;">
                                <div>Ticket Holder: <strong class="text-light"><?= htmlspecialchars($booking['customer_name']) ?></strong></div>
                                <div>Payment: <strong class="text-light"><?= htmlspecialchars($booking['payment_method'] ?? 'Online') ?></strong></div>
                                <div>Txn: <span class="font-monospace text-muted"><?= htmlspecialchars(substr($booking['transaction_id'] ?? 'N/A', 0, 14)) ?></span></div>
                            </div>
                        </div>
                    </div>

                    <div class="ticket-perforation my-4"></div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 text-secondary small">
                        <div>
                            <span class="text-light fw-bold"><i class="fa-solid fa-shield-halved text-success me-1"></i> CinePass Verified Ticket</span> &bull; 
                            Issued on <?= date('d M Y, h:i A', strtotime($booking['booking_date'])) ?>
                        </div>
                        <div class="text-end">
                            <span class="font-monospace text-light" style="letter-spacing: 4px;">||| | ||||| || |||||| | ||</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4 d-print-none">
                <a href="<?= url('user/bookings.php') ?>" class="btn btn-outline-light">
                    <i class="fa-solid fa-ticket me-1"></i> View All My Bookings
                </a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-warning text-dark fw-bold px-4" onclick="window.print()">
                        <i class="fa-solid fa-print me-1"></i> Print Ticket Voucher
                    </button>
                    <a href="<?= url('movies.php') ?>" class="btn btn-cine-primary">
                        <i class="fa-solid fa-film me-1"></i> Book Another Movie
                    </a>
                </div>
            </div>

            <div class="text-center mt-3 d-print-none">
                <small class="text-secondary" style="font-size: 0.75rem;">
                    <i class="fa-solid fa-info-circle me-1"></i> You can reprint or re-access this ticket anytime from your <a href="<?= url('user/bookings.php') ?>" class="text-danger text-decoration-none">My Bookings</a> dashboard.
                </small>
            </div>
        </div>
    </div>
</div>

<style>
.cine-ticket-container {
    background: #11131c;
    border: 2px solid #2d3142;
    border-radius: 20px;
    overflow: hidden;
}

.ticket-header {
    background: linear-gradient(135deg, #e50914 0%, #8b0000 100%);
    border-bottom: 2px dashed rgba(255,255,255,0.2);
}

.ticket-meta-label {
    display: block;
    color: #94a3b8;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}

.ticket-meta-value {
    display: block;
}

.ticket-notch {
    position: absolute;
    top: 50%;
    width: 28px;
    height: 28px;
    background: var(--cine-bg, #0b0c10);
    border-radius: 50%;
    z-index: 10;
    transform: translateY(-50%);
}

.notch-left {
    left: -14px;
    border-right: 2px solid #2d3142;
}

.notch-right {
    right: -14px;
    border-left: 2px solid #2d3142;
}

.ticket-perforation {
    border-bottom: 2px dashed #2d3142;
    position: relative;
    opacity: 0.7;
}

.border-dashed {
    border-style: dashed !important;
}

@media (min-width: 768px) {
    .border-start-md {
        border-left: 2px dashed #2d3142 !important;
    }
}

@media print {
    body {
        background: #fff !important;
        color: #000 !important;
    }
    nav, footer, .d-print-none, .cine-navbar, .cine-footer {
        display: none !important;
    }
    .cine-ticket-container {
        border: 2px solid #000 !important;
        background: #fff !important;
        color: #000 !important;
        box-shadow: none !important;
        margin: 0 !important;
        width: 100% !important;
    }
    .ticket-header {
        background: #e50914 !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .ticket-meta-label {
        color: #555 !important;
    }
    .text-white, .text-light {
        color: #000 !important;
    }
    .text-danger {
        color: #c00 !important;
    }
    .text-success {
        color: #080 !important;
    }
    .badge {
        border: 1px solid #000 !important;
        color: #000 !important;
        background: transparent !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
