<?php
$adminTitle = "Screen Configuration & Seat Layout - Admin Panel";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$screenId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($screenId <= 0) {
    setFlash('screen_error', 'Invalid screen identifier.', 'danger');
    redirect('admin/screens/index.php');
}

$dbStatus = checkDBStatus();
if (!$dbStatus['status']) {
    setFlash('screen_error', 'Database offline. Please check XAMPP MySQL.', 'danger');
    redirect('admin/screens/index.php');
}

$db = getDB();

$stmt = $db->prepare("SELECT s.*, c.name as cinema_name, c.city, c.location as cinema_location 
                      FROM screens s 
                      JOIN cinemas c ON s.cinema_id = c.id 
                      WHERE s.id = ? LIMIT 1");
$stmt->execute([$screenId]);
$screen = $stmt->fetch();

if (!$screen) {
    setFlash('screen_error', 'Screen record not found.', 'danger');
    redirect('admin/screens/index.php');
}

if (isset($_GET['action']) && $_GET['action'] === 'toggle_seat' && isset($_GET['seat_id'])) {
    $toggleSeatId = (int)$_GET['seat_id'];
    $seatStmt = $db->prepare("SELECT status FROM seats WHERE id = ? AND screen_id = ? LIMIT 1");
    $seatStmt->execute([$toggleSeatId, $screenId]);
    $seatRecord = $seatStmt->fetch();

    if ($seatRecord) {
        $newStatus = ($seatRecord['status'] === 'active') ? 'under_maintenance' : 'active';
        $updateStmt = $db->prepare("UPDATE seats SET status = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $toggleSeatId]);

        setFlash('screen_success', "Seat status updated to '" . strtoupper(str_replace('_', ' ', $newStatus)) . "'.", 'info');
        redirect('admin/screens/configure.php?id=' . $screenId);
    }
}

$seatQuery = $db->prepare("SELECT * FROM seats WHERE screen_id = ? ORDER BY seat_row ASC, seat_column ASC");
$seatQuery->execute([$screenId]);
$seats = $seatQuery->fetchAll();

$groupedSeats = [];
foreach ($seats as $seat) {
    $groupedSeats[$seat['seat_row']][] = $seat;
}

$flashSuccess = getFlash('screen_success');
$flashError   = getFlash('screen_error');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4 px-lg-5 flex-grow-1">
    <div class="row g-4">
        <div class="col-lg-3 col-xl-2">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <div class="col-lg-9 col-xl-10">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="text-white fw-bold mb-1">
                        <i class="fa-solid fa-sliders text-danger me-2"></i> Screen Configuration: <?= htmlspecialchars($screen['screen_name']) ?>
                    </h3>
                    <p class="text-secondary small mb-0">
                        <?= htmlspecialchars($screen['cinema_name']) ?> &bull; <?= htmlspecialchars($screen['city']) ?> &bull; 
                        <span class="badge bg-danger-subtle text-danger border border-danger"><?= htmlspecialchars($screen['screen_type']) ?></span>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('admin/screens/edit.php?id=' . $screenId) ?>" class="btn btn-outline-warning btn-sm">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Screen Specs
                    </a>
                    <a href="<?= url('admin/screens/index.php') ?>" class="btn btn-outline-light btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Screens
                    </a>
                </div>
            </div>

            <?php if ($flashSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show small shadow-sm mb-4" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($flashSuccess['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($flashError): ?>
                <div class="alert alert-danger alert-dismissible fade show small shadow-sm mb-4" role="alert">
                    <i class="fa-solid fa-circle-xmark me-2"></i> <?= htmlspecialchars($flashError['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-md-3">
                    <div class="cine-card p-3">
                        <span class="text-secondary small d-block">Total Seating</span>
                        <h4 class="text-white fw-bold mb-0"><?= htmlspecialchars($screen['total_seats']) ?> Seats</h4>
                        <small class="text-muted"><?= htmlspecialchars($screen['rows_count']) ?> Rows &times; <?= htmlspecialchars($screen['cols_count']) ?> Columns</small>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="cine-card p-3">
                        <span class="text-secondary small d-block">Screen Format</span>
                        <h4 class="text-warning fw-bold mb-0"><?= htmlspecialchars($screen['screen_type']) ?></h4>
                        <small class="text-muted">Digital Audio/Visual</small>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="cine-card p-3">
                        <span class="text-secondary small d-block">Configured Seats</span>
                        <h4 class="text-success fw-bold mb-0"><?= count($seats) ?> Units</h4>
                        <small class="text-muted">In Database Inventory</small>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="cine-card p-3">
                        <span class="text-secondary small d-block">Cinema Venue</span>
                        <h5 class="text-white fw-bold mb-0 text-truncate"><?= htmlspecialchars($screen['cinema_name']) ?></h5>
                        <small class="text-muted"><?= htmlspecialchars($screen['city']) ?></small>
                    </div>
                </div>
            </div>

            <div class="cine-card p-4 p-md-5 mb-4 text-center">
                <h5 class="text-white fw-bold mb-4">
                    <i class="fa-solid fa-chair text-danger me-2"></i> Auditorium Seat Layout Map
                </h5>

                <div class="mx-auto mb-5 text-center" style="max-width: 600px;">
                    <div class="p-2 rounded-pill bg-danger bg-opacity-25 border border-danger text-danger fw-bold small text-uppercase tracking-wider shadow">
                        <i class="fa-solid fa-tv me-2"></i> CINEMA THEATRE SCREEN THIS WAY
                    </div>
                    <div class="border-bottom border-danger border-2 mt-2 opacity-50" style="border-radius: 50%;"></div>
                </div>

                <div class="table-responsive d-flex justify-content-center">
                    <div class="d-inline-flex flex-column gap-3 p-3 bg-dark bg-opacity-50 rounded border border-secondary">
                        <?php foreach ($groupedSeats as $rowName => $rowSeats): ?>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary fw-bold px-2 py-2" style="width: 32px;">
                                    <?= htmlspecialchars($rowName) ?>
                                </span>

                                <div class="d-flex gap-2">
                                    <?php foreach ($rowSeats as $st): ?>
                                        <?php 
                                        $seatClass = 'bg-success';
                                        $titleText = "Seat {$st['seat_number']} - {$st['seat_type']} (Active)";
                                        if ($st['seat_type'] === 'VIP') $seatClass = 'bg-warning text-dark';
                                        if ($st['seat_type'] === 'Premium') $seatClass = 'bg-info text-dark';
                                        if ($st['status'] === 'under_maintenance') {
                                            $seatClass = 'bg-secondary text-white opacity-50 text-decoration-line-through';
                                            $titleText = "Seat {$st['seat_number']} - Under Maintenance";
                                        }
                                        ?>
                                        <a href="<?= url('admin/screens/configure.php?id=' . $screenId . '&action=toggle_seat&seat_id=' . $st['id']) ?>" 
                                           class="badge <?= $seatClass ?> text-decoration-none d-inline-flex align-items-center justify-content-center fw-bold shadow-sm"
                                           style="width: 38px; height: 38px; font-size: 0.78rem;"
                                           title="<?= $titleText ?> (Click to toggle maintenance)"
                                           onclick="return confirm('Toggle status for Seat <?= $st['seat_number'] ?>?');">
                                            <?= htmlspecialchars($st['seat_number']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>

                                <span class="badge bg-secondary fw-bold px-2 py-2" style="width: 32px;">
                                    <?= htmlspecialchars($rowName) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-center flex-wrap gap-4 mt-4 pt-3 border-top border-secondary small text-secondary">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success p-2">A1</span>
                        <span>Standard Seat (1.0x Rate)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-info text-dark p-2">B1</span>
                        <span>Premium Seat (1.15x Rate)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark p-2">C1</span>
                        <span>VIP Recliner (1.30x Rate)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-secondary text-white opacity-50 p-2 text-decoration-line-through">X1</span>
                        <span>Under Maintenance</span>
                    </div>
                </div>
                <div class="text-muted small mt-2">
                    <i class="fa-solid fa-circle-info me-1"></i> Tip: Click on any seat above to toggle its maintenance status on/off.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
