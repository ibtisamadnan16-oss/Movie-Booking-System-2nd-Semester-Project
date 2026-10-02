<?php
 
$adminTitle = "Manage Bookings - Admin Console";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$search        = trim($_GET['search'] ?? '');
$statusFilter  = trim($_GET['status'] ?? '');
$paymentFilter = trim($_GET['payment_status'] ?? '');

$bookings          = [];
$totalBookingsCount = 0;
$confirmedCount    = 0;
$cancelledCount    = 0;
$totalRevenue      = 0;

$status = checkDBStatus();
if ($status['status']) {
    try {
        $db = getDB();

$query = "
            SELECT 
                b.*, 
                u.full_name AS customer_name, 
                u.email AS customer_email,
                u.phone AS customer_phone,
                m.title AS movie_title, 
                m.poster_image,
                m.genre,
                m.duration_minutes,
                m.rating,
                s.show_date, 
                s.start_time,
                s.end_time,
                s.ticket_price,
                c.name AS cinema_name,
                c.city AS cinema_city,
                scr.screen_name,
                scr.screen_type,
                p.payment_method,
                p.payment_status,
                p.transaction_id,
                p.amount AS payment_amount,
                GROUP_CONCAT(st.seat_number ORDER BY st.seat_row ASC, st.seat_column ASC SEPARATOR ', ') AS seat_numbers
            FROM bookings b 
            LEFT JOIN users u ON b.user_id = u.id 
            LEFT JOIN shows s ON b.show_id = s.id 
            LEFT JOIN movies m ON s.movie_id = m.id 
            LEFT JOIN cinemas c ON s.cinema_id = c.id
            LEFT JOIN screens scr ON s.screen_id = scr.id
            LEFT JOIN payments p ON b.id = p.booking_id
            LEFT JOIN booking_seats bs ON b.id = bs.booking_id
            LEFT JOIN seats st ON bs.seat_id = st.id
            WHERE 1=1
        ";
        $params = [];

if (!empty($search)) {
            $query .= " AND (b.booking_code LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR m.title LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

if (!empty($statusFilter)) {
            $query .= " AND b.booking_status = ?";
            $params[] = $statusFilter;
        }

if (!empty($paymentFilter)) {
            $query .= " AND p.payment_status = ?";
            $params[] = $paymentFilter;
        }

        $query .= " GROUP BY b.id ORDER BY b.id DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $bookings = $stmt->fetchAll();

$totalBookingsCount = (int)$db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
        $confirmedCount     = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'confirmed'")->fetchColumn();
        $cancelledCount     = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'cancelled'")->fetchColumn();
        $totalRevenue       = (float)$db->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE booking_status = 'confirmed'")->fetchColumn();

    } catch (Exception $e) {
        $bookings = [];
    }
}

$flashAdminSuccess = getFlash('admin_success');
$flashAdminError   = getFlash('admin_error');

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4 px-lg-5 flex-grow-1">
    <div class="row g-4">
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

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="text-white fw-bold mb-1">
                        <i class="fa-solid fa-ticket text-danger me-2"></i> Booking Management
                    </h3>
                    <p class="text-secondary small mb-0">Phase 15: Monitor, inspect, confirm, and cancel customer cinema reservations</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('admin/reports.php') ?>" class="btn btn-outline-light btn-sm">
                        <i class="fa-solid fa-chart-line me-1"></i> Sales Analytics
                    </a>
                </div>
            </div>

            <div class="row g-4 mb-4 mb-xl-5">
                <div class="col-sm-6 col-xl-3">
                    <div class="cine-card p-4 h-100 transition-hover border border-secondary border-opacity-25 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="text-secondary small fw-medium text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Total Reservations</span>
                                <span class="brand-icon" style="width: 38px; height: 38px; font-size: 0.95rem; background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.08);">
                                    <i class="fa-solid fa-ticket"></i>
                                </span>
                            </div>
                            <h3 class="text-white fw-bold mb-2 fs-2"><?= $totalBookingsCount ?></h3>
                        </div>
                        <small class="text-secondary mt-2 d-inline-block">All-time booking history</small>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="cine-card p-4 h-100 transition-hover border border-secondary border-opacity-25 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="text-secondary small fw-medium text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Confirmed Bookings</span>
                                <span class="brand-icon" style="width: 38px; height: 38px; font-size: 0.95rem; background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.08);">
                                    <i class="fa-solid fa-check"></i>
                                </span>
                            </div>
                            <h3 class="text-white fw-bold mb-2 fs-2"><?= $confirmedCount ?></h3>
                        </div>
                        <small class="text-secondary mt-2 d-inline-block">Active auditorium tickets</small>
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
                            <h3 class="text-white fw-bold mb-2 fs-2"><?= $cancelledCount ?></h3>
                        </div>
                        <small class="text-secondary mt-2 d-inline-block">Refunded / Released seats</small>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="cine-card p-4 h-100 transition-hover border border-secondary border-opacity-25 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="text-secondary small fw-medium text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Confirmed Revenue</span>
                                <span class="brand-icon" style="width: 38px; height: 38px; font-size: 0.95rem; background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.08);">
                                    <i class="fa-solid fa-wallet"></i>
                                </span>
                            </div>
                            <h3 class="text-white fw-bold mb-2 fs-2"><?= formatPrice($totalRevenue) ?></h3>
                        </div>
                        <small class="text-secondary mt-2 d-inline-block">Collected box office sales</small>
                    </div>
                </div>
            </div>

            <div class="cine-card p-4 mb-4 mb-xl-5">
                <form action="<?= url('admin/bookings.php') ?>" method="GET" class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-dark border-secondary text-secondary">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" 
                                   name="search" 
                                   class="form-control bg-dark border-secondary text-light ps-2" 
                                   placeholder="Search booking code, customer, movie..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm bg-dark border-secondary text-light">
                            <option value="">All Booking Statuses</option>
                            <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <select name="payment_status" class="form-select form-select-sm bg-dark border-secondary text-light">
                            <option value="">All Payment Statuses</option>
                            <option value="completed" <?= $paymentFilter === 'completed' ? 'selected' : '' ?>>Paid (Completed)</option>
                            <option value="refunded" <?= $paymentFilter === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                            <option value="pending" <?= $paymentFilter === 'pending' ? 'selected' : '' ?>>Payment Pending</option>
                        </select>
                    </div>

                    <div class="col-auto">
                        <button type="submit" class="btn btn-danger btn-sm">Filter</button>
                        <a href="<?= url('admin/bookings.php') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                </form>
            </div>

            <div class="cine-card p-4">
                <?php if (!empty($bookings)): ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0 small">
                            <thead>
                                <tr class="text-secondary border-secondary">
                                    <th>#ID & Ref</th>
                                    <th>Customer</th>
                                    <th>Movie</th>
                                    <th>Cinema & Show</th>
                                    <th>Seats</th>
                                    <th>Amount</th>
                                    <th>Payment Status</th>
                                    <th>Booking Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $b): ?>
                                    <?php 
                                    $isConfirmed = ($b['booking_status'] === 'confirmed');
                                    $isCancelled = ($b['booking_status'] === 'cancelled');
                                    ?>
                                    <tr class="border-secondary">
                                        <td>
                                            <div class="font-monospace text-secondary small">#<?= str_pad($b['id'], 4, '0', STR_PAD_LEFT) ?></div>
                                            <span class="badge bg-dark border border-warning text-warning font-monospace">
                                                <?= htmlspecialchars($b['booking_code']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <div class="text-white fw-bold"><?= htmlspecialchars($b['customer_name'] ?? 'Guest Customer') ?></div>
                                            <small class="text-secondary d-block"><?= htmlspecialchars($b['customer_email'] ?? '') ?></small>
                                            <small class="text-secondary"><?= htmlspecialchars($b['customer_phone'] ?? '') ?></small>
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty($b['poster_image'])): ?>
                                                    <img src="<?= url('assets/images/' . $b['poster_image']) ?>" 
                                                         alt="" 
                                                         class="rounded" 
                                                         style="width: 32px; height: 44px; object-fit: cover;"
                                                         onerror="this.style.display='none'">
                                                <?php endif; ?>
                                                <div>
                                                    <div class="text-white fw-semibold"><?= htmlspecialchars($b['movie_title']) ?></div>
                                                    <small class="text-secondary"><?= htmlspecialchars($b['genre']) ?></small>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="text-light fw-semibold"><?= htmlspecialchars($b['cinema_name']) ?></div>
                                            <small class="text-warning"><?= htmlspecialchars($b['screen_name']) ?> (<?= htmlspecialchars($b['screen_type']) ?>)</small>
                                            <div class="text-secondary small mt-1">
                                                <i class="fa-solid fa-calendar-day text-danger me-1"></i><?= date('d M Y', strtotime($b['show_date'])) ?>
                                                <span class="text-danger fw-bold ms-1"><?= date('h:i A', strtotime($b['start_time'])) ?></span>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="badge bg-dark border border-success text-success font-monospace">
                                                <?= htmlspecialchars($b['seat_numbers'] ?? 'N/A') ?>
                                            </span>
                                            <small class="text-secondary d-block mt-1"><?= (int)$b['total_seats'] ?> seat<?= (int)$b['total_seats'] > 1 ? 's' : '' ?></small>
                                        </td>

                                        <td>
                                            <div class="text-success fw-bold fs-6">
                                                <?= formatPrice($b['total_amount']) ?>
                                            </div>
                                        </td>

                                        <td>
                                            <?php if ($b['payment_status'] === 'completed'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success">PAID</span>
                                            <?php elseif ($b['payment_status'] === 'refunded'): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger">REFUNDED</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning">PENDING</span>
                                            <?php endif; ?>
                                            <small class="text-secondary d-block mt-1 text-uppercase">
                                                <?= htmlspecialchars($b['payment_method'] ?? 'Online') ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?php if ($isCancelled): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger">CANCELLED</span>
                                            <?php elseif ($isConfirmed): ?>
                                                <span class="badge bg-success-subtle text-success border border-success">CONFIRMED</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning">PENDING</span>
                                            <?php endif; ?>
                                            <small class="text-secondary d-block mt-1">
                                                <?= date('d M, h:i A', strtotime($b['booking_date'])) ?>
                                            </small>
                                        </td>

                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <button type="button" 
                                                        class="btn btn-outline-light btn-sm" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#viewModal<?= $b['id'] ?>" 
                                                        title="View Complete Details">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>

                                                <?php if (!$isConfirmed): ?>
                                                    <form action="<?= url('actions/booking_action.php') ?>" method="POST" class="d-inline" onsubmit="return confirm('Confirm this booking? Seats will remain reserved.');">
                                                        <input type="hidden" name="action" value="admin_confirm_booking">
                                                        <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                                        <button type="submit" class="btn btn-success btn-sm" title="Confirm Booking">
                                                            <i class="fa-solid fa-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if (!$isCancelled): ?>
                                                    <button type="button" 
                                                            class="btn btn-outline-danger btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#cancelModal<?= $b['id'] ?>" 
                                                            title="Cancel Booking & Release Seats">
                                                        <i class="fa-solid fa-ban"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php foreach ($bookings as $b): ?>
                        <?php 
                        $isConfirmed = ($b['booking_status'] === 'confirmed');
                        $isCancelled = ($b['booking_status'] === 'cancelled');
                        ?>
                        <div class="modal fade" id="viewModal<?= $b['id'] ?>" tabindex="-1" aria-labelledby="viewModalLabel<?= $b['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content bg-dark text-light border border-secondary shadow-lg">
                                                <div class="modal-header border-secondary">
                                                    <h5 class="modal-title text-white fw-bold" id="viewModalLabel<?= $b['id'] ?>">
                                                        <i class="fa-solid fa-ticket text-danger me-2"></i> Booking Details: <span class="font-monospace text-warning"><?= htmlspecialchars($b['booking_code']) ?></span>
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="row g-3 mb-4">
                                                        <div class="col-md-4">
                                                            <div class="p-3 rounded bg-black border border-secondary text-center">
                                                                <span class="text-secondary small d-block">Booking Status</span>
                                                                <?php if ($isCancelled): ?>
                                                                    <strong class="text-danger fs-6"><i class="fa-solid fa-ban me-1"></i> CANCELLED</strong>
                                                                <?php elseif ($isConfirmed): ?>
                                                                    <strong class="text-success fs-6"><i class="fa-solid fa-circle-check me-1"></i> CONFIRMED</strong>
                                                                <?php else: ?>
                                                                    <strong class="text-warning fs-6"><i class="fa-solid fa-clock me-1"></i> PENDING</strong>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="p-3 rounded bg-black border border-secondary text-center">
                                                                <span class="text-secondary small d-block">Payment Status</span>
                                                                <strong class="<?= $b['payment_status'] === 'completed' ? 'text-success' : ($b['payment_status'] === 'refunded' ? 'text-danger' : 'text-warning') ?> fs-6 text-uppercase">
                                                                    <?= htmlspecialchars($b['payment_status'] ?? 'pending') ?>
                                                                </strong>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="p-3 rounded bg-black border border-secondary text-center">
                                                                <span class="text-secondary small d-block">Total Paid</span>
                                                                <strong class="text-success fs-5"><?= formatPrice($b['total_amount']) ?></strong>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row g-4">
                                                        <div class="col-md-6 border-end border-secondary">
                                                            <h6 class="text-danger fw-bold border-bottom border-secondary pb-2 mb-3">
                                                                <i class="fa-solid fa-film me-1"></i> Movie & Screening
                                                            </h6>
                                                            <div class="d-flex gap-3 mb-3">
                                                                <?php if (!empty($b['poster_image'])): ?>
                                                                    <img src="<?= url('assets/images/' . $b['poster_image']) ?>" 
                                                                         alt="" 
                                                                         class="rounded shadow" 
                                                                         style="width: 70px; height: 100px; object-fit: cover;"
                                                                         onerror="this.style.display='none'">
                                                                <?php endif; ?>
                                                                <div>
                                                                    <h5 class="text-white fw-bold mb-1"><?= htmlspecialchars($b['movie_title']) ?></h5>
                                                                    <p class="text-secondary small mb-1"><?= htmlspecialchars($b['genre']) ?> &bull; <?= (int)$b['duration_minutes'] ?> mins</p>
                                                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-star me-1"></i><?= htmlspecialchars($b['rating']) ?></span>
                                                                </div>
                                                            </div>
                                                            <ul class="list-unstyled small text-secondary mb-0">
                                                                <li class="mb-1"><strong class="text-light">Cinema:</strong> <?= htmlspecialchars($b['cinema_name']) ?> (<?= htmlspecialchars($b['cinema_city']) ?>)</li>
                                                                <li class="mb-1"><strong class="text-light">Screen:</strong> <?= htmlspecialchars($b['screen_name']) ?> <span class="badge bg-dark border border-secondary text-secondary ms-1"><?= htmlspecialchars($b['screen_type']) ?></span></li>
                                                                <li class="mb-1"><strong class="text-light">Date:</strong> <?= date('l, d F Y', strtotime($b['show_date'])) ?></li>
                                                                <li class="mb-1"><strong class="text-light">Showtime:</strong> <?= date('h:i A', strtotime($b['start_time'])) ?> - <?= date('h:i A', strtotime($b['end_time'])) ?></li>
                                                                <li class="mb-1"><strong class="text-light">Reserved Seats:</strong> <span class="text-success fw-bold font-monospace"><?= htmlspecialchars($b['seat_numbers']) ?></span> (<?= (int)$b['total_seats'] ?> seats)</li>
                                                            </ul>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <h6 class="text-danger fw-bold border-bottom border-secondary pb-2 mb-3">
                                                                <i class="fa-solid fa-user-circle me-1"></i> Customer & Payment
                                                            </h6>
                                                            <ul class="list-unstyled small text-secondary mb-4">
                                                                <li class="mb-2"><strong class="text-light">Customer Name:</strong> <?= htmlspecialchars($b['customer_name'] ?? 'Guest Customer') ?></li>
                                                                <li class="mb-2"><strong class="text-light">Email:</strong> <?= htmlspecialchars($b['customer_email'] ?? 'N/A') ?></li>
                                                                <li class="mb-2"><strong class="text-light">Phone:</strong> <?= htmlspecialchars($b['customer_phone'] ?? 'N/A') ?></li>
                                                                <li class="mb-2"><strong class="text-light">Booked Timestamp:</strong> <?= date('d M Y, h:i:s A', strtotime($b['booking_date'])) ?></li>
                                                                <li class="mb-2"><strong class="text-light">Payment Method:</strong> <span class="text-uppercase text-light"><?= htmlspecialchars($b['payment_method'] ?? 'Online') ?></span></li>
                                                                <li class="mb-2"><strong class="text-light">Transaction ID:</strong> <span class="font-monospace text-secondary"><?= htmlspecialchars($b['transaction_id'] ?? 'N/A') ?></span></li>
                                                                <li class="mb-2"><strong class="text-light">Ticket Unit Price:</strong> <?= formatPrice($b['ticket_price']) ?></li>
                                                            </ul>

                                                            <div class="p-3 rounded bg-black border border-secondary text-center">
                                                                <a href="<?= url('booking-success.php?booking_id=' . $b['id']) ?>" target="_blank" class="btn btn-cine-primary btn-sm w-100">
                                                                    <i class="fa-solid fa-print me-1"></i> Open Customer Ticket Voucher &rarr;
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-secondary justify-content-between">
                                                    <div>
                                                        <span class="text-secondary small">System ID: #<?= (int)$b['id'] ?></span>
                                                    </div>
                                                    <div class="d-flex gap-2">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!$isCancelled): ?>
                                        <div class="modal fade" id="cancelModal<?= $b['id'] ?>" tabindex="-1" aria-labelledby="cancelModalLabel<?= $b['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content bg-dark text-light border border-secondary shadow-lg">
                                                    <div class="modal-header border-secondary">
                                                        <h5 class="modal-title text-danger fw-bold" id="cancelModalLabel<?= $b['id'] ?>">
                                                            <i class="fa-solid fa-triangle-exclamation me-2"></i> Cancel Reservation
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="<?= url('actions/booking_action.php') ?>" method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="admin_cancel_booking">
                                                            <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">

                                                            <p class="text-secondary mb-3">
                                                                Are you sure you want to cancel booking <strong class="text-warning font-monospace"><?= htmlspecialchars($b['booking_code']) ?></strong>?
                                                            </p>

                                                            <div class="p-3 rounded bg-black border border-secondary mb-3 small">
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <span class="text-secondary">Customer:</span>
                                                                    <strong class="text-light"><?= htmlspecialchars($b['customer_name'] ?? 'N/A') ?></strong>
                                                                </div>
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <span class="text-secondary">Movie:</span>
                                                                    <strong class="text-light"><?= htmlspecialchars($b['movie_title']) ?></strong>
                                                                </div>
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <span class="text-secondary">Seats Released:</span>
                                                                    <strong class="text-success"><?= htmlspecialchars($b['seat_numbers']) ?></strong>
                                                                </div>
                                                                <div class="d-flex justify-content-between border-top border-secondary pt-1 mt-2">
                                                                    <span class="text-secondary">Amount to Refund:</span>
                                                                    <strong class="text-danger"><?= formatPrice($b['total_amount']) ?></strong>
                                                                </div>
                                                            </div>

                                                            <div class="alert alert-warning py-2 small mb-0">
                                                                <i class="fa-solid fa-circle-info me-1"></i>
                                                                Cancelling will immediately release these seats back to available for customer booking.
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-secondary">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Keep Booking</button>
                                                            <button type="submit" class="btn btn-danger btn-sm">
                                                                <i class="fa-solid fa-ban me-1"></i> Confirm Cancellation
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="fa-solid fa-ticket-simple fs-1 mb-3 text-secondary"></i>
                        <h6 class="text-white">No Bookings Found</h6>
                        <p class="small mb-0">No customer reservations match the current filter criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
