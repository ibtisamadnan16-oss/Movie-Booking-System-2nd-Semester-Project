<?php
 
$adminTitle = "Admin Dashboard";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$movieCount    = 0;
$userCount     = 0;
$bookingCount  = 0;
$totalRevenue  = 0;
$todayShowsCount = 0;
$cinemaCount   = 0;
$screenCount   = 0;

$todayShows    = [];
$recentBookings = [];
$cinemaSummary = [];

$status = checkDBStatus();
if ($status['status']) {
    try {
        $db = getDB();

$movieCount = (int)$db->query("SELECT COUNT(*) FROM movies")->fetchColumn();

$userCount = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();

$bookingCount = (int)$db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

$revStmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE booking_status = 'confirmed'");
        $totalRevenue = (float)$revStmt->fetchColumn();

$todayStr = date('Y-m-d');
        $tCountStmt = $db->prepare("SELECT COUNT(*) FROM shows WHERE show_date = ?");
        $tCountStmt->execute([$todayStr]);
        $todayShowsCount = (int)$tCountStmt->fetchColumn();

$cinemaCount = (int)$db->query("SELECT COUNT(*) FROM cinemas")->fetchColumn();
        $screenCount = (int)$db->query("SELECT COUNT(*) FROM screens")->fetchColumn();

$tShowsStmt = $db->prepare("
            SELECT 
                s.*,
                m.title AS movie_title,
                m.poster_image,
                m.duration_minutes,
                m.genre,
                c.name AS cinema_name,
                c.city AS cinema_city,
                scr.screen_name,
                scr.screen_type,
                (SELECT COUNT(*) FROM bookings b WHERE b.show_id = s.id AND b.booking_status = 'confirmed') AS confirmed_bookings,
                (SELECT COALESCE(SUM(b.total_seats), 0) FROM bookings b WHERE b.show_id = s.id AND b.booking_status = 'confirmed') AS seats_booked
            FROM shows s
            JOIN movies m ON s.movie_id = m.id
            JOIN cinemas c ON s.cinema_id = c.id
            JOIN screens scr ON s.screen_id = scr.id
            WHERE s.show_date = ?
            ORDER BY s.start_time ASC
        ");
        $tShowsStmt->execute([$todayStr]);
        $todayShows = $tShowsStmt->fetchAll();

$rBookingsStmt = $db->query("
            SELECT 
                b.*,
                u.full_name AS customer_name,
                u.email AS customer_email,
                m.title AS movie_title,
                s.show_date,
                s.start_time,
                c.name AS cinema_name,
                scr.screen_name,
                p.payment_method,
                p.payment_status
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN shows s ON b.show_id = s.id
            JOIN movies m ON s.movie_id = m.id
            JOIN cinemas c ON s.cinema_id = c.id
            JOIN screens scr ON s.screen_id = scr.id
            LEFT JOIN payments p ON b.id = p.booking_id
            ORDER BY b.booking_date DESC
            LIMIT 6
        ");
        $recentBookings = $rBookingsStmt->fetchAll();

$cinemasStmt = $db->query("
            SELECT 
                c.*, 
                COUNT(scr.id) AS screen_count, 
                COALESCE(SUM(scr.total_seats), 0) AS total_capacity
            FROM cinemas c
            LEFT JOIN screens scr ON c.id = scr.cinema_id
            GROUP BY c.id
            ORDER BY c.name ASC
        ");
        $cinemaSummary = $cinemasStmt->fetchAll();

    } catch (Exception $e) {
 
    }
}

$flashAdminSuccess = getFlash('admin_success');
$flashAdminError   = getFlash('admin_error');

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4 py-lg-4 px-lg-4 px-xl-5 flex-grow-1">
    <div class="row g-4 g-xxl-5">
        <div class="col-lg-3 col-xl-2">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <div class="col-lg-9 col-xl-10">
            <?php if ($flashAdminSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($flashAdminSuccess['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($flashAdminError): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($flashAdminError['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4 pb-1 flex-wrap gap-3">
                <div>
                    <h3 class="text-white fw-bold mb-1">
                        <i class="fa-solid fa-gauge-high text-danger me-2"></i> Administrator Dashboard
                    </h3>
                    <p class="text-secondary small mb-0">
                        Live operations center &bull; Real-time statistics, show schedules, revenue, and reservations
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= url('admin/movies/add.php') ?>" class="btn btn-outline-light btn-sm px-3">
                        <i class="fa-solid fa-plus me-1"></i> Add Movie
                    </a>
                    <a href="<?= url('admin/shows/add.php') ?>" class="btn btn-outline-light btn-sm px-3">
                        <i class="fa-solid fa-calendar-plus me-1"></i> Schedule Show
                    </a>
                    <a href="<?= url('admin/reports.php') ?>" class="btn btn-outline-light btn-sm px-3">
                        <i class="fa-solid fa-chart-pie me-1"></i> View Reports
                    </a>
                </div>
            </div>

            <div class="row g-4 mb-4 mb-xl-5">
                <div class="col-sm-6 col-md-4 col-xl">
                    <a href="<?= url('admin/movies/index.php') ?>" class="text-decoration-none h-100 d-block">
                        <div class="cine-card p-4 h-100 transition-hover border border-secondary border-opacity-25 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="text-secondary small fw-medium text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Total Movies</span>
                                    <span class="brand-icon" style="width: 38px; height: 38px; font-size: 0.95rem; background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.08);">
                                        <i class="fa-solid fa-film"></i>
                                    </span>
                                </div>
                                <h3 class="text-white fw-bold mb-2 fs-2"><?= $movieCount ?></h3>
                            </div>
                            <small class="text-secondary mt-2 d-inline-block">View Catalog &rarr;</small>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-md-4 col-xl">
                    <a href="<?= url('admin/users/index.php') ?>" class="text-decoration-none h-100 d-block">
                        <div class="cine-card p-4 h-100 transition-hover border border-secondary border-opacity-25 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="text-secondary small fw-medium text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Total Users</span>
                                    <span class="brand-icon" style="width: 38px; height: 38px; font-size: 0.95rem; background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.08);">
                                        <i class="fa-solid fa-users"></i>
                                    </span>
                                </div>
                                <h3 class="text-white fw-bold mb-2 fs-2"><?= $userCount ?></h3>
                            </div>
                            <small class="text-secondary mt-2 d-inline-block">View Members &rarr;</small>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-md-4 col-xl">
                    <a href="<?= url('admin/bookings.php') ?>" class="text-decoration-none h-100 d-block">
                        <div class="cine-card p-4 h-100 transition-hover border border-secondary border-opacity-25 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="text-secondary small fw-medium text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Total Bookings</span>
                                    <span class="brand-icon" style="width: 38px; height: 38px; font-size: 0.95rem; background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.08);">
                                        <i class="fa-solid fa-ticket"></i>
                                    </span>
                                </div>
                                <h3 class="text-white fw-bold mb-2 fs-2"><?= $bookingCount ?></h3>
                            </div>
                            <small class="text-secondary mt-2 d-inline-block">View Bookings &rarr;</small>
                        </div>
                    </a>
                </div>

                <div class="col-sm-6 col-md-6 col-xl">
                    <a href="<?= url('admin/reports.php') ?>" class="text-decoration-none h-100 d-block">
                        <div class="cine-card p-4 h-100 transition-hover border border-secondary border-opacity-25 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="text-secondary small fw-medium text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Total Revenue</span>
                                    <span class="brand-icon" style="width: 38px; height: 38px; font-size: 0.95rem; background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.08);">
                                        <i class="fa-solid fa-wallet"></i>
                                    </span>
                                </div>
                                <h3 class="text-white fw-bold mb-2 text-truncate" style="font-size: 1.45rem;" title="<?= formatPrice($totalRevenue) ?>"><?= formatPrice($totalRevenue) ?></h3>
                            </div>
                            <small class="text-secondary mt-2 d-inline-block">View Financials &rarr;</small>
                        </div>
                    </a>
                </div>

                <div class="col-sm-12 col-md-6 col-xl">
                    <a href="<?= url('admin/shows/index.php') ?>" class="text-decoration-none h-100 d-block">
                        <div class="cine-card p-4 h-100 transition-hover border border-secondary border-opacity-25 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="text-secondary small fw-medium text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Today's Shows</span>
                                    <span class="brand-icon" style="width: 38px; height: 38px; font-size: 0.95rem; background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.08);">
                                        <i class="fa-solid fa-calendar-day"></i>
                                    </span>
                                </div>
                                <h3 class="text-white fw-bold mb-2 fs-2"><?= $todayShowsCount ?></h3>
                            </div>
                            <small class="text-secondary mt-2 d-inline-block"><?= date('D, d M') ?> Schedule &rarr;</small>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row g-4 mb-4 mb-xl-5">
                <div class="col-xl-7">
                    <div class="cine-card p-4 p-xl-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                            <div>
                                <h5 class="text-white fw-bold mb-1">
                                    <i class="fa-solid fa-clock text-secondary me-2"></i> Today's Movie Schedule
                                </h5>
                                <small class="text-secondary"><?= date('l, F j, Y') ?> &bull; Live Screenings</small>
                            </div>
                            <a href="<?= url('admin/shows/add.php') ?>" class="btn btn-outline-light btn-sm px-3">
                                <i class="fa-solid fa-plus me-1"></i> Schedule Show
                            </a>
                        </div>

                        <?php if (!empty($todayShows)): ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover align-middle mb-0">
                                    <thead>
                                        <tr class="text-secondary border-secondary">
                                            <th class="py-3 px-3">Movie</th>
                                            <th class="py-3 px-3">Cinema & Screen</th>
                                            <th class="py-3 px-3">Showtime</th>
                                            <th class="py-3 px-3">Price</th>
                                            <th class="py-3 px-3">Bookings</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($todayShows as $ts): ?>
                                            <tr class="border-secondary">
                                                <td class="py-3 px-3">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <?php if (!empty($ts['poster_image'])): ?>
                                                            <img src="<?= url('assets/images/' . $ts['poster_image']) ?>" 
                                                                 alt="<?= htmlspecialchars($ts['movie_title']) ?>" 
                                                                 class="rounded shadow-sm" 
                                                                 style="width: 34px; height: 48px; object-fit: cover;"
                                                                 onerror="this.style.display='none'">
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="text-white fw-semibold mb-1"><?= htmlspecialchars($ts['movie_title']) ?></div>
                                                            <small class="text-secondary"><?= htmlspecialchars($ts['genre']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3 px-3">
                                                    <div class="text-light fw-medium mb-1"><?= htmlspecialchars($ts['cinema_name']) ?></div>
                                                    <small class="text-secondary"><?= htmlspecialchars($ts['screen_name']) ?> &bull; <?= htmlspecialchars($ts['screen_type']) ?></small>
                                                </td>
                                                <td class="py-3 px-3">
                                                    <span class="badge bg-dark border border-secondary text-light px-2 py-1">
                                                        <?= date('h:i A', strtotime($ts['start_time'])) ?>
                                                    </span>
                                                </td>
                                                <td class="py-3 px-3 text-light fw-bold">
                                                    <?= formatPrice($ts['ticket_price']) ?>
                                                </td>
                                                <td class="py-3 px-3">
                                                    <span class="badge bg-dark border border-secondary text-secondary px-2 py-1">
                                                        <?= (int)$ts['seats_booked'] ?> seats
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-secondary">
                                <i class="fa-solid fa-calendar-xmark fs-2 mb-2 text-secondary"></i>
                                <h6 class="text-white">No Shows Scheduled for Today</h6>
                                <p class="small mb-3">Add movie showtimes for today's schedule to open seat bookings.</p>
                                <a href="<?= url('admin/shows/add.php') ?>" class="btn btn-outline-light btn-sm px-3">
                                    <i class="fa-solid fa-plus me-1"></i> Add Today's Show
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="cine-card p-4 p-xl-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="text-white fw-bold mb-1">
                                    <i class="fa-solid fa-video text-secondary me-2"></i> Cinema Venues
                                </h5>
                                <small class="text-secondary"><?= $cinemaCount ?> Venues &bull; <?= $screenCount ?> Auditoriums</small>
                            </div>
                            <a href="<?= url('admin/cinemas/index.php') ?>" class="btn btn-outline-light btn-sm px-3">
                                Manage &rarr;
                            </a>
                        </div>

                        <?php if (!empty($cinemaSummary)): ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($cinemaSummary as $cs): ?>
                                    <div class="p-3 p-xl-4 rounded bg-dark border border-secondary border-opacity-50">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="text-white fw-bold mb-0"><?= htmlspecialchars($cs['name']) ?></h6>
                                            <span class="badge bg-dark border border-secondary text-secondary small px-2 py-1">
                                                <?= htmlspecialchars($cs['city']) ?>
                                            </span>
                                        </div>
                                        <p class="text-secondary small mb-3 text-truncate"><?= htmlspecialchars($cs['address'] ?? 'No address provided') ?></p>
                                        <div class="d-flex justify-content-between align-items-center small border-top border-secondary border-opacity-25 pt-3">
                                            <span class="text-secondary">
                                                <i class="fa-solid fa-tv me-1"></i> <?= (int)$cs['screen_count'] ?> Screens
                                            </span>
                                            <span class="text-secondary">
                                                <i class="fa-solid fa-chair me-1"></i> <?= (int)$cs['total_capacity'] ?> Seats
                                            </span>
                                            <a href="<?= url('admin/screens/index.php?cinema_id=' . $cs['id']) ?>" class="text-secondary text-decoration-none">
                                                Screens &rarr;
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-secondary">
                                <p class="small mb-2">No cinemas registered.</p>
                                <a href="<?= url('admin/cinemas/add.php') ?>" class="btn btn-outline-light btn-sm">Add Cinema</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="cine-card p-4 p-xl-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <div>
                        <h5 class="text-white fw-bold mb-1">
                            <i class="fa-solid fa-ticket text-secondary me-2"></i> Recent Booking Activity
                        </h5>
                        <small class="text-secondary">Latest customer ticket purchases across all cinemas</small>
                    </div>
                    <a href="<?= url('admin/bookings.php') ?>" class="btn btn-outline-light btn-sm px-3">
                        View All Bookings (<?= $bookingCount ?>) &rarr;
                    </a>
                </div>

                <?php if (!empty($recentBookings)): ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-secondary border-secondary">
                                    <th class="py-3 px-3">Ref Code</th>
                                    <th class="py-3 px-3">Customer</th>
                                    <th class="py-3 px-3">Movie & Venue</th>
                                    <th class="py-3 px-3">Showtime</th>
                                    <th class="py-3 px-3">Total Paid</th>
                                    <th class="py-3 px-3">Payment</th>
                                    <th class="py-3 px-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings as $rb): ?>
                                    <?php $isCancelled = ($rb['booking_status'] === 'cancelled'); ?>
                                    <tr class="border-secondary">
                                        <td class="py-3 px-3">
                                            <span class="badge bg-dark border border-secondary text-light font-monospace px-2 py-1">
                                                <?= htmlspecialchars($rb['booking_code']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-3">
                                            <div class="text-white fw-semibold mb-1"><?= htmlspecialchars($rb['customer_name']) ?></div>
                                            <small class="text-secondary"><?= htmlspecialchars($rb['customer_email']) ?></small>
                                        </td>
                                        <td class="py-3 px-3">
                                            <div class="text-light fw-semibold mb-1"><?= htmlspecialchars($rb['movie_title']) ?></div>
                                            <small class="text-secondary"><?= htmlspecialchars($rb['cinema_name']) ?> &bull; <?= htmlspecialchars($rb['screen_name']) ?></small>
                                        </td>
                                        <td class="py-3 px-3">
                                            <div class="text-light mb-1"><?= date('d M Y', strtotime($rb['show_date'])) ?></div>
                                            <small class="text-secondary"><?= date('h:i A', strtotime($rb['start_time'])) ?></small>
                                        </td>
                                        <td class="py-3 px-3 text-light fw-bold">
                                            <?= formatPrice($rb['total_amount']) ?>
                                        </td>
                                        <td class="py-3 px-3">
                                            <span class="badge bg-dark border border-secondary text-secondary text-uppercase px-2 py-1">
                                                <?= htmlspecialchars($rb['payment_method'] ?? 'Simulated') ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-3">
                                            <?php if ($isCancelled): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">CANCELLED</span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border border-success px-2 py-1">CONFIRMED</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="fa-solid fa-ticket-simple fs-2 mb-2 text-secondary"></i>
                        <p class="small mb-0">No booking transactions recorded yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
