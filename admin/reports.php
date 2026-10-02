<?php
 
$adminTitle = "Business Reports & Analytics - Admin Console";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$totalRevenue = 0;
$totalConfirmedBookings = 0;
$totalCancelledBookings = 0;
$totalTicketsSold = 0;

$movieReports = [];
$cinemaReports = [];
$paymentReports = [];

$dbStatus = checkDBStatus();
if ($dbStatus['status']) {
    try {
        $db = getDB();

$totalRevenue = (float)$db->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE booking_status = 'confirmed'")->fetchColumn();
        $totalConfirmedBookings = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'confirmed'")->fetchColumn();
        $totalCancelledBookings = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'cancelled'")->fetchColumn();
        $totalTicketsSold = (int)$db->query("SELECT COALESCE(SUM(total_seats), 0) FROM bookings WHERE booking_status = 'confirmed'")->fetchColumn();

$movieStmt = $db->query("
            SELECT 
                m.id,
                m.title,
                m.genre,
                m.poster_image,
                COUNT(b.id) AS booking_count,
                COALESCE(SUM(b.total_seats), 0) AS tickets_sold,
                COALESCE(SUM(b.total_amount), 0) AS gross_revenue
            FROM movies m
            LEFT JOIN shows s ON m.id = s.movie_id
            LEFT JOIN bookings b ON s.id = b.show_id AND b.booking_status = 'confirmed'
            GROUP BY m.id
            ORDER BY gross_revenue DESC, tickets_sold DESC
        ");
        $movieReports = $movieStmt->fetchAll();

$cinemaStmt = $db->query("
            SELECT 
                c.id,
                c.name,
                c.city,
                COUNT(DISTINCT scr.id) AS screens_count,
                COUNT(b.id) AS booking_count,
                COALESCE(SUM(b.total_seats), 0) AS tickets_sold,
                COALESCE(SUM(b.total_amount), 0) AS gross_revenue
            FROM cinemas c
            LEFT JOIN screens scr ON c.id = scr.cinema_id
            LEFT JOIN shows s ON c.id = s.cinema_id
            LEFT JOIN bookings b ON s.id = b.show_id AND b.booking_status = 'confirmed'
            GROUP BY c.id
            ORDER BY gross_revenue DESC
        ");
        $cinemaReports = $cinemaStmt->fetchAll();

$payStmt = $db->query("
            SELECT 
                COALESCE(p.payment_method, 'Unspecified') AS method,
                COUNT(p.id) AS transaction_count,
                COALESCE(SUM(p.amount), 0) AS total_collected
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            WHERE p.payment_status = 'completed' AND b.booking_status = 'confirmed'
            GROUP BY p.payment_method
            ORDER BY total_collected DESC
        ");
        $paymentReports = $payStmt->fetchAll();

    } catch (Exception $e) {
 
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4 px-lg-5 flex-grow-1">
    <div class="row g-4">
        <div class="col-lg-3 col-xl-2">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <div class="col-lg-9 col-xl-10">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="text-white fw-bold mb-1">
                        <i class="fa-solid fa-chart-pie text-danger me-2"></i> Business Analytics & Reports
                    </h3>
                    <p class="text-secondary small mb-0">Financial performance, gross revenue, movie box office, and cinema occupancy</p>
                </div>
                <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i> Print / Export Report
                </button>
            </div>

            <div class="row g-4 mb-4 mb-xl-5">
                <div class="col-sm-6 col-xl-3">
                    <div class="cine-card p-4 h-100 transition-hover border border-secondary border-opacity-25 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="text-secondary small fw-medium text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Gross Revenue</span>
                                <span class="brand-icon" style="width: 38px; height: 38px; font-size: 0.95rem; background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.08);">
                                    <i class="fa-solid fa-wallet"></i>
                                </span>
                            </div>
                            <h3 class="text-white fw-bold mb-2 fs-2"><?= formatPrice($totalRevenue) ?></h3>
                        </div>
                        <small class="text-secondary mt-2 d-inline-block">Total confirmed collections</small>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="cine-card p-4 h-100 transition-hover border border-secondary border-opacity-25 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="text-secondary small fw-medium text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Confirmed Bookings</span>
                                <span class="brand-icon" style="width: 38px; height: 38px; font-size: 0.95rem; background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.08);">
                                    <i class="fa-solid fa-ticket"></i>
                                </span>
                            </div>
                            <h3 class="text-white fw-bold mb-2 fs-2"><?= $totalConfirmedBookings ?></h3>
                        </div>
                        <small class="text-secondary mt-2 d-inline-block"><?= $totalTicketsSold ?> total seats sold</small>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="cine-card p-4 h-100 transition-hover border border-secondary border-opacity-25 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="text-secondary small fw-medium text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Cancelled Bookings</span>
                                <span class="brand-icon" style="width: 38px; height: 38px; font-size: 0.95rem; background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.08);">
                                    <i class="fa-solid fa-ban"></i>
                                </span>
                            </div>
                            <h3 class="text-white fw-bold mb-2 fs-2"><?= $totalCancelledBookings ?></h3>
                        </div>
                        <small class="text-secondary mt-2 d-inline-block">Refunded transactions</small>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="cine-card p-4 h-100 transition-hover border border-secondary border-opacity-25 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="text-secondary small fw-medium text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Cancellation Rate</span>
                                <span class="brand-icon" style="width: 38px; height: 38px; font-size: 0.95rem; background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.08);">
                                    <i class="fa-solid fa-chart-pie"></i>
                                </span>
                            </div>
                            <?php 
                            $totalAll = $totalConfirmedBookings + $totalCancelledBookings;
                            $rate = $totalAll > 0 ? round(($totalCancelledBookings / $totalAll) * 100, 1) : 0;
                            ?>
                            <h3 class="text-white fw-bold mb-2 fs-2"><?= $rate ?>%</h3>
                        </div>
                        <small class="text-secondary mt-2 d-inline-block">Of total reservation volume</small>
                    </div>
                </div>
            </div>

            <div class="cine-card p-4 mb-4">
                <h5 class="text-white fw-bold mb-3">
                    <i class="fa-solid fa-film text-danger me-2"></i> Box Office Performance by Movie
                </h5>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0 small">
                        <thead>
                            <tr class="text-secondary border-secondary">
                                <th>Movie Title</th>
                                <th>Genre</th>
                                <th>Bookings</th>
                                <th>Seats Sold</th>
                                <th>Gross Revenue</th>
                                <th>Share of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($movieReports)): ?>
                                <?php foreach ($movieReports as $mr): ?>
                                    <?php 
                                    $share = $totalRevenue > 0 ? round(($mr['gross_revenue'] / $totalRevenue) * 100, 1) : 0;
                                    ?>
                                    <tr class="border-secondary">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty($mr['poster_image'])): ?>
                                                    <img src="<?= url('assets/images/' . $mr['poster_image']) ?>" 
                                                         alt="" 
                                                         class="rounded" 
                                                         style="width: 28px; height: 38px; object-fit: cover;"
                                                         onerror="this.style.display='none'">
                                                <?php endif; ?>
                                                <strong class="text-white"><?= htmlspecialchars($mr['title']) ?></strong>
                                            </div>
                                        </td>
                                        <td class="text-secondary"><?= htmlspecialchars($mr['genre']) ?></td>
                                        <td><?= (int)$mr['booking_count'] ?></td>
                                        <td><span class="badge bg-dark border border-secondary text-light"><?= (int)$mr['tickets_sold'] ?> seats</span></td>
                                        <td class="text-success fw-bold"><?= formatPrice($mr['gross_revenue']) ?></td>
                                        <td style="min-width: 140px;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1 bg-dark border border-secondary" style="height: 6px;">
                                                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $share ?>%;" aria-valuenow="<?= $share ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <span class="text-secondary small font-monospace"><?= $share ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-secondary">No movie sales recorded yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="cine-card p-4 h-100">
                        <h5 class="text-white fw-bold mb-3">
                            <i class="fa-solid fa-video text-warning me-2"></i> Cinema Revenue Distribution
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover align-middle mb-0 small">
                                <thead>
                                    <tr class="text-secondary border-secondary">
                                        <th>Cinema</th>
                                        <th>City</th>
                                        <th>Screens</th>
                                        <th>Tickets Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($cinemaReports)): ?>
                                        <?php foreach ($cinemaReports as $cr): ?>
                                            <tr class="border-secondary">
                                                <td><strong class="text-white"><?= htmlspecialchars($cr['name']) ?></strong></td>
                                                <td><span class="badge bg-dark border border-secondary text-secondary"><?= htmlspecialchars($cr['city']) ?></span></td>
                                                <td><?= (int)$cr['screens_count'] ?></td>
                                                <td><?= (int)$cr['tickets_sold'] ?></td>
                                                <td class="text-success fw-bold"><?= formatPrice($cr['gross_revenue']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-secondary">No cinema sales recorded yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="cine-card p-4 h-100">
                        <h5 class="text-white fw-bold mb-3">
                            <i class="fa-solid fa-credit-card text-success me-2"></i> Payment Gateways
                        </h5>
                        <?php if (!empty($paymentReports)): ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($paymentReports as $pr): ?>
                                    <div class="p-3 rounded bg-dark border border-secondary">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="text-white text-uppercase"><?= htmlspecialchars($pr['method']) ?></strong>
                                            <span class="text-success fw-bold"><?= formatPrice($pr['total_collected']) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between text-secondary small">
                                            <span>Transactions</span>
                                            <span><?= (int)$pr['transaction_count'] ?> completed</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-secondary">
                                <p class="small mb-0">No payment transaction records found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
