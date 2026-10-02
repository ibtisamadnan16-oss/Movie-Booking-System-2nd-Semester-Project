<?php
$adminTitle = "Add Screen - Admin Panel";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$errors = [];
$cinemaId   = isset($_GET['cinema_id']) ? (int)$_GET['cinema_id'] : 0;
$screenName = '';
$screenType = '2D';
$rowsCount  = 3;
$colsCount  = 10;
$totalSeats = 30;

$cinemas = [];
$dbStatus = checkDBStatus();

if ($dbStatus['status']) {
    try {
        $db = getDB();
        $cinemas = $db->query("SELECT id, name, city FROM cinemas ORDER BY name ASC")->fetchAll();
    } catch (Exception $e) {
        $errors['general'] = 'Database error: ' . $e->getMessage();
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $cinemaId   = (int)($_POST['cinema_id'] ?? 0);
    $screenName = trim($_POST['screen_name'] ?? '');
    $screenType = trim($_POST['screen_type'] ?? '2D');
    $rowsCount  = (int)($_POST['rows_count'] ?? 3);
    $colsCount  = (int)($_POST['cols_count'] ?? 10);
    $totalSeats = $rowsCount * $colsCount;

    if ($cinemaId <= 0) {
        $errors['cinema_id'] = 'Please select a cinema venue.';
    }

    if (empty($screenName)) {
        $errors['screen_name'] = 'Screen name is required.';
    }

    if ($rowsCount <= 0 || $rowsCount > 26) {
        $errors['rows_count'] = 'Rows count must be between 1 and 26 (A-Z).';
    }

    if ($colsCount <= 0 || $colsCount > 30) {
        $errors['cols_count'] = 'Seats per row must be between 1 and 30.';
    }

    if (empty($errors)) {
        try {
            $db = getDB();

$stmt = $db->prepare("INSERT INTO screens (cinema_id, screen_name, screen_type, total_seats, rows_count, cols_count) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cinemaId, $screenName, $screenType, $totalSeats, $rowsCount, $colsCount]);
            $newScreenId = $db->lastInsertId();

$seatStmt = $db->prepare("INSERT INTO seats (screen_id, seat_number, seat_row, seat_column, seat_type, price_multiplier, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            
            $alphabet = range('A', 'Z');
            for ($r = 0; $r < $rowsCount; $r++) {
                $rowLetter = $alphabet[$r];

if ($r === 0) {
                    $seatType = 'Standard';
                    $multiplier = 1.00;
                } elseif ($r === $rowsCount - 1) {
                    $seatType = 'VIP';
                    $multiplier = 1.30;
                } else {
                    $seatType = 'Premium';
                    $multiplier = 1.15;
                }

                for ($c = 1; $c <= $colsCount; $c++) {
                    $seatNumber = $rowLetter . $c;
                    $seatStmt->execute([$newScreenId, $seatNumber, $rowLetter, $c, $seatType, $multiplier]);
                }
            }

            setFlash('screen_success', "Screen '" . htmlspecialchars($screenName) . "' created with {$totalSeats} seats generated successfully!", 'success');
            redirect('admin/screens/configure.php?id=' . $newScreenId);
        } catch (Exception $e) {
            $errors['general'] = 'Failed to create screen: ' . $e->getMessage();
        }
    }
}

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
                    <h3 class="text-white fw-bold mb-1"><i class="fa-solid fa-plus-circle text-danger me-2"></i> Add New Screen</h3>
                    <p class="text-secondary small mb-0">Configure cinema auditorium, screen projection, and seating grid</p>
                </div>
                <div>
                    <a href="<?= url('admin/screens/index.php') ?>" class="btn btn-outline-light btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Screens
                    </a>
                </div>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger py-2 small shadow-sm mb-4">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($errors['general']) ?>
                </div>
            <?php endif; ?>

            <div class="cine-card p-4 p-md-5">
                <form action="<?= url('admin/screens/add.php') ?>" method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Cinema Venue <span class="text-danger">*</span></label>
                            <select name="cinema_id" class="form-select cine-form-control <?= isset($errors['cinema_id']) ? 'border-danger' : '' ?>" required>
                                <option value="">Select Cinema Location</option>
                                <?php foreach ($cinemas as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $cinemaId === (int)$c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['city']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['cinema_id'])): ?>
                                <div class="text-danger small mt-1"><?= $errors['cinema_id'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Screen / Hall Name <span class="text-danger">*</span></label>
                            <input type="text" name="screen_name" class="form-control cine-form-control <?= isset($errors['screen_name']) ? 'border-danger' : '' ?>" placeholder="e.g. Cinema Hall 1 (Gold) / IMAX Laser Hall" value="<?= htmlspecialchars($screenName) ?>" required>
                            <?php if (isset($errors['screen_name'])): ?>
                                <div class="text-danger small mt-1"><?= $errors['screen_name'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">Screen Projection Type <span class="text-danger">*</span></label>
                            <select name="screen_type" class="form-select cine-form-control">
                                <option value="2D" <?= $screenType === '2D' ? 'selected' : '' ?>>Standard 2D</option>
                                <option value="3D" <?= $screenType === '3D' ? 'selected' : '' ?>>RealD 3D</option>
                                <option value="IMAX" <?= $screenType === 'IMAX' ? 'selected' : '' ?>>IMAX with Laser</option>
                                <option value="4DX" <?= $screenType === '4DX' ? 'selected' : '' ?>>4DX Motion Recliners</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">Number of Rows (A, B, C...) <span class="text-danger">*</span></label>
                            <input type="number" id="rowsInput" name="rows_count" class="form-control cine-form-control" value="<?= htmlspecialchars($rowsCount) ?>" min="1" max="26" required oninput="calcSeats()">
                            <small class="text-secondary">Rows will be named A, B, C, etc.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">Seats per Row <span class="text-danger">*</span></label>
                            <input type="number" id="colsInput" name="cols_count" class="form-control cine-form-control" value="<?= htmlspecialchars($colsCount) ?>" min="1" max="30" required oninput="calcSeats()">
                            <small class="text-secondary">Columns will be numbered 1, 2, 3...</small>
                        </div>

                        <div class="col-12">
                            <div class="p-3 rounded bg-dark border border-secondary d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-secondary small d-block">Automatic Calculation</span>
                                    <strong class="text-white">Total Seating Capacity: <span id="totalSeatsDisplay" class="text-success"><?= $totalSeats ?> Seats</span></strong>
                                </div>
                                <span class="badge bg-success-subtle text-success border border-success">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Auto-generates Seat Layout Map
                                </span>
                            </div>
                        </div>

                        <div class="col-12 pt-3 border-top border-secondary d-flex gap-3">
                            <button type="submit" class="btn btn-cine-primary px-4">
                                <i class="fa-solid fa-circle-check me-1"></i> Create Screen & Generate Seats
                            </button>
                            <a href="<?= url('admin/screens/index.php') ?>" class="btn btn-outline-secondary px-4">
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function calcSeats() {
    const rows = parseInt(document.getElementById('rowsInput').value) || 0;
    const cols = parseInt(document.getElementById('colsInput').value) || 0;
    const total = rows * cols;
    document.getElementById('totalSeatsDisplay').innerText = total + " Seats";
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
