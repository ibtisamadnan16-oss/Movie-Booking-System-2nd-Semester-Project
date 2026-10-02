<?php
 
$pageTitle = "My Bookings & Tickets - CinePass";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$user = currentUser();
$userId = (int)$_SESSION['user_id'];

$dbStatus = checkDBStatus();
$allBookings = [];
$upcomingBookings = [];
$pastBookings = [];
$cancelledBookings = [];

$activeTab = $_GET['tab'] ?? 'all';
if (!in_array($activeTab, ['all', 'upcoming', 'past', 'cancelled'])) {
    $activeTab = 'all';
}

if ($dbStatus['status']) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT 
                b.*,
                s.show_date,
                s.start_time,
                s.end_time,
                s.ticket_price,
                m.title AS movie_title,
                m.poster_image,
                m.genre,
                m.duration_minutes,
                m.rating,
                m.language,
                c.name AS cinema_name,
                c.city AS cinema_city,
                c.address AS cinema_address,
                scr.screen_name,
                scr.screen_type,
                p.payment_method,
                p.payment_status,
                p.transaction_id,
                GROUP_CONCAT(st.seat_number ORDER BY st.seat_row ASC, st.seat_column ASC SEPARATOR ', ') AS seat_numbers
            FROM bookings b
            JOIN shows s ON b.show_id = s.id
            JOIN movies m ON s.movie_id = m.id
            JOIN cinemas c ON s.cinema_id = c.id
            JOIN screens scr ON s.screen_id = scr.id
            LEFT JOIN payments p ON b.id = p.booking_id
            LEFT JOIN booking_seats bs ON b.id = bs.booking_id
            LEFT JOIN seats st ON bs.seat_id = st.id
            WHERE b.user_id = ?
            GROUP BY b.id
            ORDER BY s.show_date DESC, s.start_time DESC
        ");
        $stmt->execute([$userId]);
        $allBookings = $stmt->fetchAll();

        $today = date('Y-m-d');
        $currentTime = date('H:i:s');

        foreach ($allBookings as $bk) {
            $isCancelled = ($bk['booking_status'] === 'cancelled');
            $isShowFuture = ($bk['show_date'] > $today) || ($bk['show_date'] === $today && $bk['start_time'] >= $currentTime);

            if ($isCancelled) {
                $cancelledBookings[] = $bk;
            } elseif ($isShowFuture) {
                $upcomingBookings[] = $bk;
            } else {
                $pastBookings[] = $bk;
            }
        }
    } catch (Exception $e) {
        $allBookings = [];
    }
}

$flashSuccess = getFlash('booking_success');
$flashError   = getFlash('booking_error');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container my-4 my-lg-5 flex-grow-1">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= url('') ?>" class="text-secondary text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="<?= url('dashboard.php') ?>" class="text-secondary text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active text-danger" aria-current="page">My Bookings</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="text-white fw-bold mb-1">
                <i class="fa-solid fa-ticket text-danger me-2"></i> My Bookings & Tickets
            </h2>
            <p class="text-secondary small mb-0">
                View your confirmed e-tickets, track upcoming show schedules, review past cinema visits, or manage cancellations.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('dashboard.php') ?>" class="btn btn-outline-light btn-sm">
                <i class="fa-solid fa-gauge me-1"></i> Dashboard Overview
            </a>
            <a href="<?= url('movies.php') ?>" class="btn btn-cine-primary btn-sm">
                <i class="fa-solid fa-plus me-1"></i> Book New Movie
            </a>
        </div>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($flashSuccess['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($flashError['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="cine-card p-2 mb-4">
        <ul class="nav nav-pills nav-fill flex-column flex-sm-row gap-2" id="bookingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link text-white fw-semibold <?= $activeTab === 'all' ? 'active bg-danger' : '' ?>" 
                   href="<?= url('my-bookings.php?tab=all') ?>">
                    <i class="fa-solid fa-list-ul me-1"></i> All Bookings 
                    <span class="badge bg-dark border border-secondary text-light ms-1"><?= count($allBookings) ?></span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link text-white fw-semibold <?= $activeTab === 'upcoming' ? 'active bg-danger' : '' ?>" 
                   href="<?= url('my-bookings.php?tab=upcoming') ?>">
                    <i class="fa-solid fa-clock me-1"></i> Upcoming Shows 
                    <span class="badge bg-warning text-dark ms-1"><?= count($upcomingBookings) ?></span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link text-white fw-semibold <?= $activeTab === 'past' ? 'active bg-danger' : '' ?>" 
                   href="<?= url('my-bookings.php?tab=past') ?>">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Previous Bookings 
                    <span class="badge bg-dark border border-secondary text-secondary ms-1"><?= count($pastBookings) ?></span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link text-white fw-semibold <?= $activeTab === 'cancelled' ? 'active bg-danger' : '' ?>" 
                   href="<?= url('my-bookings.php?tab=cancelled') ?>">
                    <i class="fa-solid fa-ban me-1"></i> Cancelled 
                    <span class="badge bg-secondary ms-1"><?= count($cancelledBookings) ?></span>
                </a>
            </li>
        </ul>
    </div>

    <?php
    $displayBookings = [];
    if ($activeTab === 'upcoming') {
        $displayBookings = $upcomingBookings;
        $emptyTitle = "No Upcoming Movie Shows";
        $emptyDesc = "You don't have any upcoming reservations scheduled. Explore the latest movies currently showing!";
    } elseif ($activeTab === 'past') {
        $displayBookings = $pastBookings;
        $emptyTitle = "No Previous Bookings";
        $emptyDesc = "You haven't attended any completed movie shows yet.";
    } elseif ($activeTab === 'cancelled') {
        $displayBookings = $cancelledBookings;
        $emptyTitle = "No Cancelled Bookings";
        $emptyDesc = "You have not cancelled any cinema tickets.";
    } else {
        $displayBookings = $allBookings;
        $emptyTitle = "No Bookings Found";
        $emptyDesc = "You haven't made any movie reservations yet. Pick a blockbuster and select your seats today!";
    }
    ?>

    <?php if (!empty($displayBookings)): ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($displayBookings as $bk): ?>
                <?php
                $isCancelled = ($bk['booking_status'] === 'cancelled');
                $todayStr = date('Y-m-d');
                $currentTimeStr = date('H:i:s');
                $isFuture = ($bk['show_date'] > $todayStr) || ($bk['show_date'] === $todayStr && $bk['start_time'] >= $currentTimeStr);
                $canCancel = (!$isCancelled && $isFuture);
                ?>
                <div class="cine-card p-3 p-md-4 transition-hover border <?= $isCancelled ? 'border-secondary-subtle opacity-75' : ($isFuture ? 'border-danger-subtle' : 'border-secondary') ?>">
                    <div class="row align-items-center g-3">
                        <div class="col-auto">
                            <?php if (!empty($bk['poster_image'])): ?>
                                <img src="<?= url('assets/images/' . $bk['poster_image']) ?>" 
                                     alt="<?= htmlspecialchars($bk['movie_title']) ?>" 
                                     class="rounded shadow" 
                                     style="width: 75px; height: 110px; object-fit: cover; border: 1px solid var(--cine-card-border);"
                                     onerror="this.src='https://placehold.co/75x110/1f2130/ffffff?text=Poster'">
                            <?php else: ?>
                                <div class="rounded bg-dark d-flex align-items-center justify-content-center text-secondary" style="width: 75px; height: 110px; border: 1px solid var(--cine-card-border);">
                                    <i class="fa-solid fa-film fs-3"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col">
                            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                <span class="badge bg-dark border border-warning text-warning small font-monospace">
                                    <i class="fa-solid fa-hashtag me-1"></i><?= htmlspecialchars($bk['booking_code']) ?>
                                </span>

                                <?php if ($isCancelled): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger">
                                        <i class="fa-solid fa-ban me-1"></i> CANCELLED & REFUNDED
                                    </span>
                                <?php elseif ($isFuture): ?>
                                    <span class="badge bg-success-subtle text-success border border-success">
                                        <i class="fa-solid fa-circle-check me-1"></i> CONFIRMED (UPCOMING)
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary">
                                        <i class="fa-solid fa-check-double me-1"></i> WATCHED (COMPLETED)
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($bk['payment_method'])): ?>
                                    <span class="badge bg-dark border border-secondary text-secondary small text-uppercase">
                                        <i class="fa-solid fa-credit-card me-1"></i> <?= htmlspecialchars($bk['payment_method']) ?>
                                    </span>
                                <?php endif; ?>

                                <span class="text-secondary small ms-auto d-none d-md-inline">
                                    Booked on <?= date('d M Y, h:i A', strtotime($bk['booking_date'])) ?>
                                </span>
                            </div>

                            <h4 class="text-white fw-bold mb-1">
                                <?= htmlspecialchars($bk['movie_title']) ?>
                                <?php if (!empty($bk['rating'])): ?>
                                    <span class="badge bg-warning text-dark fs-6 ms-1 py-1">
                                        <i class="fa-solid fa-star text-dark small"></i> <?= htmlspecialchars($bk['rating']) ?>
                                    </span>
                                <?php endif; ?>
                            </h4>

                            <div class="text-secondary small mb-2">
                                <i class="fa-solid fa-location-dot text-danger me-1"></i> 
                                <strong class="text-light"><?= htmlspecialchars($bk['cinema_name']) ?></strong> 
                                (<?= htmlspecialchars($bk['cinema_city']) ?>) &bull; 
                                <span class="text-warning fw-semibold"><?= htmlspecialchars($bk['screen_name']) ?></span> 
                                <span class="badge bg-dark border border-secondary text-secondary ms-1"><?= htmlspecialchars($bk['screen_type']) ?></span>
                            </div>

                            <div class="d-flex align-items-center gap-3 small flex-wrap">
                                <div class="text-light">
                                    <i class="fa-solid fa-calendar-day text-danger me-1"></i> 
                                    <strong class="text-white"><?= date('D, d M Y', strtotime($bk['show_date'])) ?></strong>
                                </div>
                                <div class="text-danger fw-bold">
                                    <i class="fa-regular fa-clock me-1"></i> 
                                    <?= date('h:i A', strtotime($bk['start_time'])) ?> - <?= date('h:i A', strtotime($bk['end_time'])) ?>
                                </div>
                                <div class="text-light">
                                    <i class="fa-solid fa-chair text-success me-1"></i> 
                                    Seats: <span class="badge bg-success-subtle text-success border border-success fw-bold font-monospace"><?= htmlspecialchars($bk['seat_numbers'] ?? 'N/A') ?></span>
                                    <span class="text-secondary ms-1">(<?= (int)$bk['total_seats'] ?> Seat<?= (int)$bk['total_seats'] > 1 ? 's' : '' ?>)</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-auto text-md-end border-top border-md-top-0 pt-3 pt-md-0 border-secondary">
                            <div class="text-secondary small">Total Paid</div>
                            <div class="text-success fw-bold fs-4 mb-2">
                                <?= formatPrice($bk['total_amount']) ?>
                            </div>

                            <div class="d-flex flex-md-column gap-2 justify-content-end">
                                <a href="<?= url('booking-success.php?booking_id=' . $bk['id']) ?>" 
                                   class="btn btn-cine-primary btn-sm px-3">
                                    <i class="fa-solid fa-ticket me-1"></i> View Ticket
                                </a>

                                <?php if ($canCancel): ?>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm px-3" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#cancelModal<?= $bk['id'] ?>">
                                        <i class="fa-solid fa-xmark me-1"></i> Cancel
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($canCancel): ?>
                    <div class="modal fade" id="cancelModal<?= $bk['id'] ?>" tabindex="-1" aria-labelledby="cancelModalLabel<?= $bk['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-dark text-light border border-secondary shadow-lg">
                                <div class="modal-header border-secondary">
                                    <h5 class="modal-title text-danger fw-bold" id="cancelModalLabel<?= $bk['id'] ?>">
                                        <i class="fa-solid fa-triangle-exclamation me-2"></i> Cancel Movie Reservation
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="<?= url('actions/booking_action.php') ?>" method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="cancel_booking">
                                        <input type="hidden" name="booking_id" value="<?= (int)$bk['id'] ?>">

                                        <p class="mb-3 text-secondary">
                                            Are you sure you want to cancel this booking? Once cancelled, your reserved seats will be released immediately and a refund will be initiated to your simulated payment method.
                                        </p>

                                        <div class="p-3 rounded bg-black border border-secondary mb-3 small">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-secondary">Booking Code:</span>
                                                <strong class="text-warning font-monospace"><?= htmlspecialchars($bk['booking_code']) ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-secondary">Movie:</span>
                                                <strong class="text-light"><?= htmlspecialchars($bk['movie_title']) ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-secondary">Showtime:</span>
                                                <span class="text-light"><?= date('D, d M Y', strtotime($bk['show_date'])) ?> at <?= date('h:i A', strtotime($bk['start_time'])) ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-secondary">Seats:</span>
                                                <strong class="text-success"><?= htmlspecialchars($bk['seat_numbers']) ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between border-top border-secondary pt-1 mt-2">
                                                <span class="text-secondary">Refundable Amount:</span>
                                                <strong class="text-success fs-6"><?= formatPrice($bk['total_amount']) ?></strong>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small text-secondary">Reason for cancellation (optional):</label>
                                            <select class="form-select form-select-sm bg-dark text-light border-secondary">
                                                <option value="change_of_plans">Change of plans / Schedule conflict</option>
                                                <option value="booked_wrong_show">Booked wrong show or seats</option>
                                                <option value="emergency">Personal emergency</option>
                                                <option value="other">Other reason</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-secondary">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Keep Booking</button>
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fa-solid fa-check me-1"></i> Yes, Cancel & Refund
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="cine-card p-5 text-center">
            <div class="brand-icon mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                <i class="fa-solid fa-ticket-simple text-secondary"></i>
            </div>
            <h4 class="text-white fw-bold mb-2"><?= htmlspecialchars($emptyTitle) ?></h4>
            <p class="text-secondary small mb-4" style="max-width: 500px; margin: 0 auto;">
                <?= htmlspecialchars($emptyDesc) ?>
            </p>
            <div class="d-flex justify-content-center gap-2">
                <a href="<?= url('movies.php') ?>" class="btn btn-cine-primary btn-sm px-4">
                    <i class="fa-solid fa-film me-1"></i> Browse Current Movies
                </a>
                <a href="<?= url('booking.php') ?>" class="btn btn-outline-light btn-sm px-3">
                    <i class="fa-solid fa-ticket me-1"></i> Quick Booking
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
