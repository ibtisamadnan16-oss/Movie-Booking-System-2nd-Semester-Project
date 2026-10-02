<?php
$adminTitle = "Edit Cinema - Admin Panel";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$cinemaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($cinemaId <= 0) {
    setFlash('cinema_error', 'Invalid cinema identifier.', 'danger');
    redirect('admin/cinemas/index.php');
}

$dbStatus = checkDBStatus();
if (!$dbStatus['status']) {
    setFlash('cinema_error', 'Database offline. Please check XAMPP MySQL.', 'danger');
    redirect('admin/cinemas/index.php');
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM cinemas WHERE id = ? LIMIT 1");
$stmt->execute([$cinemaId]);
$cinema = $stmt->fetch();

if (!$cinema) {
    setFlash('cinema_error', 'Cinema record not found.', 'danger');
    redirect('admin/cinemas/index.php');
}

$errors = [];
$name          = $cinema['name'];
$city          = $cinema['city'];
$location      = $cinema['location'];
$contactNumber = $cinema['contact_number'];
$email         = $cinema['email'] ?? '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $name          = trim($_POST['name'] ?? '');
    $city          = trim($_POST['city'] ?? '');
    $location      = trim($_POST['location'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $email         = trim($_POST['email'] ?? '');

    if (empty($name)) {
        $errors['name'] = 'Cinema name is required.';
    }

    if (empty($city)) {
        $errors['city'] = 'City / Location is required.';
    }

    if (empty($location)) {
        $errors['location'] = 'Address / Location is required.';
    }

    if (empty($contactNumber)) {
        $errors['contact_number'] = 'Contact phone number is required.';
    }

    if (empty($errors)) {
        try {
            $updateStmt = $db->prepare("UPDATE cinemas SET name = ?, city = ?, location = ?, contact_number = ?, email = ? WHERE id = ?");
            $updateStmt->execute([$name, $city, $location, $contactNumber, $email, $cinemaId]);

            setFlash('cinema_success', "Cinema '" . htmlspecialchars($name) . "' has been updated successfully!", 'success');
            redirect('admin/cinemas/index.php');
        } catch (Exception $e) {
            $errors['general'] = 'Failed to update cinema: ' . $e->getMessage();
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
                    <h3 class="text-white fw-bold mb-1"><i class="fa-solid fa-pen-to-square text-warning me-2"></i> Edit Cinema</h3>
                    <p class="text-secondary small mb-0">Update contact info, location address, and venue details for ID: #<?= htmlspecialchars($cinemaId) ?></p>
                </div>
                <div>
                    <a href="<?= url('admin/cinemas/index.php') ?>" class="btn btn-outline-light btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Cinemas
                    </a>
                </div>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger py-2 small shadow-sm mb-4">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($errors['general']) ?>
                </div>
            <?php endif; ?>

            <div class="cine-card p-4 p-md-5">
                <form action="<?= url('admin/cinemas/edit.php?id=' . $cinemaId) ?>" method="POST">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <label class="form-label text-light small fw-semibold">Cinema Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control cine-form-control" value="<?= htmlspecialchars($name) ?>" required>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label text-light small fw-semibold">City / Location <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control cine-form-control" value="<?= htmlspecialchars($city) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Contact Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="contact_number" class="form-control cine-form-control" value="<?= htmlspecialchars($contactNumber) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Contact Email</label>
                            <input type="email" name="email" class="form-control cine-form-control" value="<?= htmlspecialchars($email) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label text-light small fw-semibold">Full Address & Location Details <span class="text-danger">*</span></label>
                            <textarea name="location" rows="3" class="form-control cine-form-control" required><?= htmlspecialchars($location) ?></textarea>
                        </div>

                        <div class="col-12 pt-3 border-top border-secondary d-flex gap-3">
                            <button type="submit" class="btn btn-warning fw-bold px-4">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Update Cinema
                            </button>
                            <a href="<?= url('admin/cinemas/index.php') ?>" class="btn btn-outline-secondary px-4">
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
