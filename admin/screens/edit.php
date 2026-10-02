<?php
$adminTitle = "Edit Screen - Admin Panel";
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
$stmt = $db->prepare("SELECT * FROM screens WHERE id = ? LIMIT 1");
$stmt->execute([$screenId]);
$screen = $stmt->fetch();

if (!$screen) {
    setFlash('screen_error', 'Screen record not found.', 'danger');
    redirect('admin/screens/index.php');
}

$cinemas = $db->query("SELECT id, name, city FROM cinemas ORDER BY name ASC")->fetchAll();

$errors = [];
$cinemaId   = $screen['cinema_id'];
$screenName = $screen['screen_name'];
$screenType = $screen['screen_type'];
$rowsCount  = $screen['rows_count'];
$colsCount  = $screen['cols_count'];
$totalSeats = $screen['total_seats'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $cinemaId   = (int)($_POST['cinema_id'] ?? 0);
    $screenName = trim($_POST['screen_name'] ?? '');
    $screenType = trim($_POST['screen_type'] ?? '2D');

    if ($cinemaId <= 0) {
        $errors['cinema_id'] = 'Please select a valid cinema location.';
    }

    if (empty($screenName)) {
        $errors['screen_name'] = 'Screen name is required.';
    }

    if (empty($errors)) {
        try {
            $updateStmt = $db->prepare("UPDATE screens SET cinema_id = ?, screen_name = ?, screen_type = ? WHERE id = ?");
            $updateStmt->execute([$cinemaId, $screenName, $screenType, $screenId]);

            setFlash('screen_success', "Screen '" . htmlspecialchars($screenName) . "' updated successfully!", 'success');
            redirect('admin/screens/index.php');
        } catch (Exception $e) {
            $errors['general'] = 'Failed to update screen: ' . $e->getMessage();
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
                    <h3 class="text-white fw-bold mb-1"><i class="fa-solid fa-pen-to-square text-warning me-2"></i> Edit Screen Specs</h3>
                    <p class="text-secondary small mb-0">Update cinema hall name, projection standard, and venue assignment</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('admin/screens/configure.php?id=' . $screenId) ?>" class="btn btn-outline-info btn-sm">
                        <i class="fa-solid fa-sliders me-1"></i> Seat Map Layout
                    </a>
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
                <form action="<?= url('admin/screens/edit.php?id=' . $screenId) ?>" method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Cinema Venue <span class="text-danger">*</span></label>
                            <select name="cinema_id" class="form-select cine-form-control" required>
                                <?php foreach ($cinemas as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $cinemaId === (int)$c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['city']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Screen / Hall Name <span class="text-danger">*</span></label>
                            <input type="text" name="screen_name" class="form-control cine-form-control" value="<?= htmlspecialchars($screenName) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Projection Format <span class="text-danger">*</span></label>
                            <select name="screen_type" class="form-select cine-form-control">
                                <option value="2D" <?= $screenType === '2D' ? 'selected' : '' ?>>Standard 2D</option>
                                <option value="3D" <?= $screenType === '3D' ? 'selected' : '' ?>>RealD 3D</option>
                                <option value="IMAX" <?= $screenType === 'IMAX' ? 'selected' : '' ?>>IMAX with Laser</option>
                                <option value="4DX" <?= $screenType === '4DX' ? 'selected' : '' ?>>4DX Motion Recliners</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Auditorium Capacity</label>
                            <input type="text" class="form-control cine-form-control text-muted" value="<?= htmlspecialchars($totalSeats) ?> Seats (<?= htmlspecialchars($rowsCount) ?> Rows &times; <?= htmlspecialchars($colsCount) ?> Columns)" readonly disabled>
                        </div>

                        <div class="col-12 pt-3 border-top border-secondary d-flex gap-3">
                            <button type="submit" class="btn btn-warning fw-bold px-4">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Update Screen
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
