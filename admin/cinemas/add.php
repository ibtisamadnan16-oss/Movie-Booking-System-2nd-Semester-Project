<?php
$adminTitle = "Add Cinema - Admin Panel";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$errors = [];
$name = '';
$city = 'Karachi';
$location = '';
$contactNumber = '';
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $name          = trim($_POST['name'] ?? '');
    $city          = trim($_POST['city'] ?? 'Karachi');
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
        $errors['location'] = 'Full address / location description is required.';
    }

    if (empty($contactNumber)) {
        $errors['contact_number'] = 'Contact phone number is required.';
    }

    if (empty($errors)) {
        $dbStatus = checkDBStatus();
        if (!$dbStatus['status']) {
            $errors['general'] = 'Database error: ' . $dbStatus['message'];
        } else {
            try {
                $db = getDB();
                $stmt = $db->prepare("INSERT INTO cinemas (name, city, location, contact_number, email) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $city, $location, $contactNumber, $email]);

                $newCinemaId = $db->lastInsertId();
                setFlash('cinema_success', "Cinema '" . htmlspecialchars($name) . "' has been added successfully!", 'success');
                redirect('admin/cinemas/index.php');
            } catch (Exception $e) {
                $errors['general'] = 'Failed to insert cinema: ' . $e->getMessage();
            }
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
                    <h3 class="text-white fw-bold mb-1"><i class="fa-solid fa-plus-circle text-danger me-2"></i> Register New Cinema</h3>
                    <p class="text-secondary small mb-0">Add cinema theatre details, city location, and contact information</p>
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
                <form action="<?= url('admin/cinemas/add.php') ?>" method="POST">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <label class="form-label text-light small fw-semibold">Cinema Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control cine-form-control <?= isset($errors['name']) ? 'border-danger' : '' ?>" placeholder="e.g. Nueplex Cinemas DHA / Atrium Cinemas" value="<?= htmlspecialchars($name) ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="text-danger small mt-1"><?= $errors['name'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label text-light small fw-semibold">City / Location <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control cine-form-control" placeholder="Karachi" value="<?= htmlspecialchars($city) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Contact Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="contact_number" class="form-control cine-form-control <?= isset($errors['contact_number']) ? 'border-danger' : '' ?>" placeholder="021-111-287-486" value="<?= htmlspecialchars($contactNumber) ?>" required>
                            <?php if (isset($errors['contact_number'])): ?>
                                <div class="text-danger small mt-1"><?= $errors['contact_number'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Contact Email</label>
                            <input type="email" name="email" class="form-control cine-form-control" placeholder="info@cinema.com" value="<?= htmlspecialchars($email) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label text-light small fw-semibold">Full Address & Landmark Details <span class="text-danger">*</span></label>
                            <textarea name="location" rows="3" class="form-control cine-form-control <?= isset($errors['location']) ? 'border-danger' : '' ?>" placeholder="e.g. 3rd Floor, Atrium Mall, Staff Lines, Saddar, Karachi" required><?= htmlspecialchars($location) ?></textarea>
                            <?php if (isset($errors['location'])): ?>
                                <div class="text-danger small mt-1"><?= $errors['location'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 pt-3 border-top border-secondary d-flex gap-3">
                            <button type="submit" class="btn btn-cine-primary px-4">
                                <i class="fa-solid fa-circle-check me-1"></i> Save Cinema
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
