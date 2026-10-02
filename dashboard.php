<?php
 
$pageTitle = "My Dashboard - CinePass";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$user = currentUser();
$userId = (int)$_SESSION['user_id'];

$dbStatus = checkDBStatus();
$userBookings = [];
$upcomingBookings = [];
$pastBookings = [];
$totalSpent = 0;
$totalTicketsCount = 0;
$userDetails = null;

if ($dbStatus['status']) {
    try {
        $db = getDB();

$uStmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $uStmt->execute([$userId]);
        $userDetails = $uStmt->fetch();

$stmt = $db->prepare("
            SELECT 
                b.*, 
                m.title AS movie_title, 
                m.poster_image, 
                m.genre,
                m.duration_minutes,
                m.rating,
                c.name AS cinema_name, 
                c.city AS cinema_city,
                s.show_date, 
                s.start_time, 
                sc.screen_name,
                sc.screen_type,
                p.payment_method,
                p.payment_status,
                GROUP_CONCAT(st.seat_number ORDER BY st.seat_row ASC, st.seat_column ASC SEPARATOR ', ') AS seat_numbers
            FROM bookings b
            JOIN shows s ON b.show_id = s.id
            JOIN movies m ON s.movie_id = m.id
            JOIN cinemas c ON s.cinema_id = c.id
            JOIN screens sc ON s.screen_id = sc.id
            LEFT JOIN payments p ON b.id = p.booking_id
            LEFT JOIN booking_seats bs ON b.id = bs.booking_id
            LEFT JOIN seats st ON bs.seat_id = st.id
            WHERE b.user_id = ?
            GROUP BY b.id
            ORDER BY s.show_date DESC, s.start_time DESC
        ");
        $stmt->execute([$userId]);
        $userBookings = $stmt->fetchAll();

        $today = date('Y-m-d');
        $currentTime = date('H:i:s');

        foreach ($userBookings as $bk) {
            if ($bk['booking_status'] === 'confirmed') {
                $totalSpent += (float)$bk['total_amount'];
                $totalTicketsCount += (int)$bk['total_seats'];
            }

            $isShowFuture = ($bk['show_date'] > $today) || ($bk['show_date'] === $today && $bk['start_time'] >= $currentTime);

            if ($isShowFuture && $bk['booking_status'] === 'confirmed') {
                $upcomingBookings[] = $bk;
            } else {
                $pastBookings[] = $bk;
            }
        }
    } catch (Exception $e) {
        $userBookings = [];
    }
}

$flashDashboard = getFlash('dashboard_msg');
$flashSuccess   = getFlash('booking_success');
$flashError     = getFlash('booking_error');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container my-4 my-lg-5 flex-grow-1">
    <?php if ($flashDashboard): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($flashDashboard['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

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

    <div class="cine-card p-4 p-md-5 mb-4 position-relative overflow-hidden" style="background: linear-gradient(135deg, #171824 0%, #0c0d14 100%);">
        <div class="row align-items-center g-4">
            <div class="col-md-8">
                <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-2 text-uppercase mb-2">
                    <i class="fa-solid fa-user-check me-1"></i> Member Dashboard
                </span>
                <h2 class="text-white fw-bold mb-1">
                    Welcome back, <?= htmlspecialchars($userDetails['full_name'] ?? $user['name']) ?>!
                </h2>
                <p class="text-secondary mb-0 small" style="max-width: 600px;">
                    Track your upcoming movie tickets, review past theatre visits, manage your cinema reservations, or update your account settings.
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="<?= url('booking.php') ?>" class="btn btn-cine-primary px-4 py-2 shadow-lg me-2">
                    <i class="fa-solid fa-plus me-1"></i> Book New Movie
                </a>
                <a href="<?= url('my-bookings.php') ?>" class="btn btn-outline-light px-3 py-2">
                    <i class="fa-solid fa-ticket me-1"></i> My Tickets
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4 mb-xl-5">
        <div class="col-sm-6 col-xl-3">
            <div class="cine-card p-4 h-100 transition-hover border border-secondary border-opacity-25 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="text-secondary small fw-medium text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Upcoming Shows</span>
                        <span class="brand-icon" style="width: 38px; height: 38px; font-size: 0.95rem; background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.08);">
                            <i class="fa-regular fa-clock"></i>
                        </span>
                    </div>
                    <h3 class="text-white fw-bold mb-2 fs-2"><?= count($upcomingBookings) ?></h3>
                </div>
                <small class="text-secondary mt-2 d-inline-block"><a href="<?= url('my-bookings.php?tab=upcoming') ?>" class="text-secondary text-decoration-none">Active reservations &rarr;</a></small>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="cine-card p-4 h-100 transition-hover border border-secondary border-opacity-25 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="text-secondary small fw-medium text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Total Bookings</span>
                        <span class="brand-icon" style="width: 38px; height: 38px; font-size: 0.95rem; background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.08);">
                            <i class="fa-solid fa-ticket"></i>
                        </span>
                    </div>
                    <h3 class="text-white fw-bold mb-2 fs-2"><?= count($userBookings) ?></h3>
                </div>
                <small class="text-secondary mt-2 d-inline-block"><a href="<?= url('my-bookings.php') ?>" class="text-secondary text-decoration-none"><?= $totalTicketsCount ?> total tickets &rarr;</a></small>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="cine-card p-4 h-100 transition-hover border border-secondary border-opacity-25 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="text-secondary small fw-medium text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Past Visits</span>
                        <span class="brand-icon" style="width: 38px; height: 38px; font-size: 0.95rem; background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.08);">
                            <i class="fa-solid fa-film"></i>
                        </span>
                    </div>
                    <h3 class="text-white fw-bold mb-2 fs-2"><?= count($pastBookings) ?></h3>
                </div>
                <small class="text-secondary mt-2 d-inline-block">Movies watched</small>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="cine-card p-4 h-100 transition-hover border border-secondary border-opacity-25 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="text-secondary small fw-medium text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Total Spent</span>
                        <span class="brand-icon" style="width: 38px; height: 38px; font-size: 0.95rem; background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.08);">
                            <i class="fa-solid fa-wallet"></i>
                        </span>
                    </div>
                    <h3 class="text-white fw-bold mb-2 fs-2">Rs. <?= number_format($totalSpent) ?></h3>
                </div>
                <small class="text-secondary mt-2 d-inline-block">Confirmed purchases</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="cine-card p-4 text-center mb-4">
                <div class="brand-icon mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2.2rem;">
                    <i class="fa-solid fa-user-astronaut text-danger"></i>
                </div>
                <h4 class="text-white fw-bold mb-1"><?= htmlspecialchars($userDetails['full_name'] ?? $user['name']) ?></h4>
                <p class="text-secondary small mb-3"><?= htmlspecialchars($userDetails['email'] ?? $user['email']) ?></p>
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-success-subtle text-success border border-success px-3 py-1">
                        <i class="fa-solid fa-circle-check me-1"></i> Verified Account
                    </span>
                    <span class="badge bg-dark border border-secondary text-secondary px-3 py-1">
                        Member
                    </span>
                </div>

                <hr class="border-secondary my-3">

                <div class="d-grid gap-2 text-start">
                    <a href="<?= url('dashboard.php') ?>" class="btn btn-danger text-start fw-semibold">
                        <i class="fa-solid fa-gauge me-2"></i> Dashboard Overview
                    </a>
                    <a href="<?= url('my-bookings.php') ?>" class="btn btn-dark border-secondary text-start">
                        <i class="fa-solid fa-ticket me-2 text-danger"></i> My Bookings & Tickets
                    </a>
                    <a href="<?= url('profile.php') ?>" class="btn btn-dark border-secondary text-start">
                        <i class="fa-solid fa-user-gear me-2 text-danger"></i> Profile & Security
                    </a>
                    <a href="<?= url('booking.php') ?>" class="btn btn-dark border-secondary text-start">
                        <i class="fa-solid fa-film me-2 text-danger"></i> Book Movie Tickets
                    </a>
                    <a href="<?= url('logout.php') ?>" class="btn btn-outline-danger text-start mt-2">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> Sign Out
                    </a>
                </div>
            </div>

            <div class="cine-card p-4">
                <h6 class="text-white fw-bold mb-3 border-bottom border-secondary pb-2">
                    <i class="fa-solid fa-address-card text-danger me-2"></i> Account Snapshot
                </h6>
                <div class="d-flex flex-column gap-2 small">
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary">Full Name:</span>
                        <strong class="text-light"><?= htmlspecialchars($userDetails['full_name'] ?? $user['name']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary">Email:</span>
                        <strong class="text-light"><?= htmlspecialchars($userDetails['email'] ?? $user['email']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary">Phone:</span>
                        <strong class="text-light"><?= htmlspecialchars($userDetails['phone'] ?? 'N/A') ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary">Member Since:</span>
                        <strong class="text-light"><?= !empty($userDetails['created_at']) ? date('M Y', strtotime($userDetails['created_at'])) : 'October 2026' ?></strong>
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <a href="<?= url('profile.php') ?>" class="small text-danger text-decoration-none">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Profile &rarr;
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <?php if (!empty($upcomingBookings)): ?>
                <?php $nextShow = $upcomingBookings[0]; ?>
                <div class="cine-card p-4 mb-4 border border-danger-subtle position-relative overflow-hidden" 
                     style="background: linear-gradient(135deg, rgba(229, 9, 20, 0.12) 0%, #10111a 100%);">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-danger text-white px-3 py-1 fw-bold">
                            <i class="fa-solid fa-bell me-1"></i> UPCOMING SHOW ALERT
                        </span>
                        <span class="text-secondary small font-monospace">Ref: <?= htmlspecialchars($nextShow['booking_code']) ?></span>
                    </div>

                    <div class="row align-items-center g-3">
                        <div class="col-auto">
                            <?php if (!empty($nextShow['poster_image'])): ?>
                                <img src="<?= url('assets/images/' . $nextShow['poster_image']) ?>" 
                                     alt="<?= htmlspecialchars($nextShow['movie_title']) ?>" 
                                     class="rounded shadow" 
                                     style="width: 75px; height: 105px; object-fit: cover; border: 1px solid var(--cine-card-border);"
                                     onerror="this.style.display='none'">
                            <?php endif; ?>
                        </div>
                        <div class="col">
                            <h4 class="text-white fw-bold mb-1"><?= htmlspecialchars($nextShow['movie_title']) ?></h4>
                            <p class="text-secondary small mb-1">
                                <i class="fa-solid fa-video text-danger me-1"></i> <?= htmlspecialchars($nextShow['cinema_name']) ?> &bull; <?= htmlspecialchars($nextShow['screen_name']) ?> (<?= htmlspecialchars($nextShow['screen_type']) ?>)
                            </p>
                            <div class="d-flex flex-wrap gap-2 align-items-center small mt-2">
                                <span class="badge bg-dark border border-secondary text-light">
                                    <i class="fa-solid fa-calendar-day text-danger me-1"></i> <?= date('D, d M Y', strtotime($nextShow['show_date'])) ?>
                                </span>
                                <span class="badge bg-dark border border-secondary text-danger fw-bold">
                                    <i class="fa-regular fa-clock me-1"></i> <?= date('h:i A', strtotime($nextShow['start_time'])) ?>
                                </span>
                                <span class="badge bg-success-subtle text-success border border-success">
                                    <i class="fa-solid fa-chair me-1"></i> Seats: <?= htmlspecialchars($nextShow['seat_numbers']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-auto text-md-end">
                            <a href="<?= url('booking-success.php?booking_id=' . $nextShow['id']) ?>" class="btn btn-cine-primary btn-sm px-3 py-2">
                                <i class="fa-solid fa-ticket me-1"></i> View Ticket Voucher
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="cine-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h5 class="text-white fw-bold mb-0">
                            <i class="fa-solid fa-clock-rotate-left text-danger me-2"></i> Recent Booking Activity
                        </h5>
                        <small class="text-secondary">Summary of your latest ticket purchases</small>
                    </div>
                    <a href="<?= url('my-bookings.php') ?>" class="btn btn-outline-light btn-sm">
                        View All Bookings (<?= count($userBookings) ?>) &rarr;
                    </a>
                </div>

                <?php if (!empty($userBookings)): ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-secondary small">
                                    <th>Movie</th>
                                    <th>Showtime</th>
                                    <th>Seats</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($userBookings, 0, 5) as $b): ?>
                                    <?php 
                                    $isCancelled = ($b['booking_status'] === 'cancelled');
                                    $isFuture = ($b['show_date'] > date('Y-m-d')) || ($b['show_date'] === date('Y-m-d') && $b['start_time'] >= date('H:i:s'));
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="text-white fw-bold"><?= htmlspecialchars($b['movie_title']) ?></div>
                                            <small class="text-muted font-monospace"><?= htmlspecialchars($b['booking_code']) ?></small>
                                        </td>
                                        <td>
                                            <div class="small text-light"><?= date('d M Y', strtotime($b['show_date'])) ?></div>
                                            <div class="small text-danger fw-semibold"><?= date('h:i A', strtotime($b['start_time'])) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark border border-secondary text-success">
                                                <?= htmlspecialchars($b['seat_numbers'] ?? 'None') ?>
                                            </span>
                                            <small class="text-muted d-block"><?= (int)$b['total_seats'] ?> seat(s)</small>
                                        </td>
                                        <td class="text-success fw-bold"><?= formatPrice($b['total_amount']) ?></td>
                                        <td>
                                            <?php if ($isCancelled): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger">CANCELLED</span>
                                            <?php elseif ($isFuture): ?>
                                                <span class="badge bg-success-subtle text-success border border-success">CONFIRMED</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">COMPLETED</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('booking-success.php?booking_id=' . $b['id']) ?>" class="btn btn-outline-light btn-sm py-1 px-2" title="View Ticket">
                                                <i class="fa-solid fa-ticket"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="fa-solid fa-ticket-simple fs-1 mb-3 text-secondary"></i>
                        <h6 class="text-white">No Bookings Found</h6>
                        <p class="small mb-3">You haven't made any cinema ticket reservations yet.</p>
                        <a href="<?= url('booking.php') ?>" class="btn btn-cine-primary btn-sm px-3">
                            <i class="fa-solid fa-plus me-1"></i> Book Your First Movie
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
