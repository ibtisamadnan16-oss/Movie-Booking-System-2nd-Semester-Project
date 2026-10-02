<?php
$adminTitle = "Edit Showtime - Admin Panel";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$showId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($showId <= 0) {
    setFlash('show_error', 'Invalid show identifier.', 'danger');
    redirect('admin/shows/index.php');
}

$dbStatus = checkDBStatus();
if (!$dbStatus['status']) {
    setFlash('show_error', 'Database offline. Please check XAMPP MySQL.', 'danger');
    redirect('admin/shows/index.php');
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM shows WHERE id = ? LIMIT 1");
$stmt->execute([$showId]);
$show = $stmt->fetch();

if (!$show) {
    setFlash('show_error', 'Showtime record not found.', 'danger');
    redirect('admin/shows/index.php');
}

$movies = $db->query("SELECT id, title, duration_minutes FROM movies WHERE status != 'archived' ORDER BY title ASC")->fetchAll();
$cinemas = $db->query("SELECT id, name, city FROM cinemas ORDER BY name ASC")->fetchAll();
$screens = $db->query("SELECT s.id, s.screen_name, s.screen_type, s.cinema_id, c.name as cinema_name 
                       FROM screens s 
                       JOIN cinemas c ON s.cinema_id = c.id 
                       ORDER BY c.name ASC, s.screen_name ASC")->fetchAll();

$errors = [];
$movieId     = $show['movie_id'];
$cinemaId    = $show['cinema_id'];
$screenId    = $show['screen_id'];
$showDate    = $show['show_date'];
$startTime   = substr($show['start_time'], 0, 5);
$endTime     = substr($show['end_time'], 0, 5);
$ticketPrice = (float)$show['ticket_price'];
$status      = $show['status'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $movieId     = (int)($_POST['movie_id'] ?? 0);
    $cinemaId    = (int)($_POST['cinema_id'] ?? 0);
    $screenId    = (int)($_POST['screen_id'] ?? 0);
    $showDate    = trim($_POST['show_date'] ?? '');
    $startTime   = trim($_POST['start_time'] ?? '');
    $endTime     = trim($_POST['end_time'] ?? '');
    $ticketPrice = (float)($_POST['ticket_price'] ?? 0);
    $status      = trim($_POST['status'] ?? 'scheduled');

    if ($movieId <= 0) {
        $errors['movie_id'] = 'Please select a movie.';
    }

    if ($cinemaId <= 0) {
        $errors['cinema_id'] = 'Please select a cinema venue.';
    }

    if ($screenId <= 0) {
        $errors['screen_id'] = 'Please select an auditorium screen.';
    }

    if (empty($showDate)) {
        $errors['show_date'] = 'Show date is required.';
    }

    if (empty($startTime)) {
        $errors['start_time'] = 'Start time is required.';
    }

    if (empty($endTime)) {
        $errors['end_time'] = 'End time is required.';
    } elseif ($startTime >= $endTime) {
        $errors['end_time'] = 'End time must be after start time.';
    }

    if ($ticketPrice <= 0) {
        $errors['ticket_price'] = 'Please specify a valid ticket price in PKR.';
    }

    if (empty($errors)) {
        try {
            $updateStmt = $db->prepare("UPDATE shows SET 
                                        movie_id = ?, 
                                        cinema_id = ?, 
                                        screen_id = ?, 
                                        show_date = ?, 
                                        start_time = ?, 
                                        end_time = ?, 
                                        ticket_price = ?, 
                                        status = ? 
                                        WHERE id = ?");
            $updateStmt->execute([$movieId, $cinemaId, $screenId, $showDate, $startTime, $endTime, $ticketPrice, $status, $showId]);

            setFlash('show_success', "Showtime schedule updated successfully!", 'success');
            redirect('admin/shows/index.php');
        } catch (Exception $e) {
            $errors['general'] = 'Failed to update show: ' . $e->getMessage();
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
                    <h3 class="text-white fw-bold mb-1"><i class="fa-solid fa-pen-to-square text-warning me-2"></i> Edit Showtime Schedule</h3>
                    <p class="text-secondary small mb-0">Update show date, hall allocation, timing, and ticket pricing for Show ID: #<?= htmlspecialchars($showId) ?></p>
                </div>
                <div>
                    <a href="<?= url('admin/shows/index.php') ?>" class="btn btn-outline-light btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Shows
                    </a>
                </div>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger py-2 small shadow-sm mb-4">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($errors['general']) ?>
                </div>
            <?php endif; ?>

            <div class="cine-card p-4 p-md-5">
                <form action="<?= url('admin/shows/edit.php?id=' . $showId) ?>" method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Movie Title <span class="text-danger">*</span></label>
                            <select name="movie_id" class="form-select cine-form-control" required>
                                <?php foreach ($movies as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= $movieId === (int)$m['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['title']) ?> (<?= htmlspecialchars($m['duration_minutes']) ?> mins)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

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
                            <label class="form-label text-light small fw-semibold">Screen / Hall <span class="text-danger">*</span></label>
                            <select name="screen_id" class="form-select cine-form-control" required>
                                <?php foreach ($screens as $sc): ?>
                                    <option value="<?= $sc['id'] ?>" <?= $screenId === (int)$sc['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sc['cinema_name']) ?> &rarr; <?= htmlspecialchars($sc['screen_name']) ?> (<?= htmlspecialchars($sc['screen_type']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Show Date <span class="text-danger">*</span></label>
                            <input type="date" name="show_date" class="form-control cine-form-control" value="<?= htmlspecialchars($showDate) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control cine-form-control" value="<?= htmlspecialchars($startTime) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control cine-form-control" value="<?= htmlspecialchars($endTime) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">Ticket Price (PKR) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-success fw-bold">Rs.</span>
                                <input type="number" step="10" name="ticket_price" class="form-control cine-form-control" value="<?= htmlspecialchars($ticketPrice) ?>" required min="100">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">Status</label>
                            <select name="status" class="form-select cine-form-control">
                                <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Scheduled (Active)</option>
                                <option value="ongoing" <?= $status === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>

                        <div class="col-12 pt-3 border-top border-secondary d-flex gap-3">
                            <button type="submit" class="btn btn-warning fw-bold px-4">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Update Showtime
                            </button>
                            <a href="<?= url('admin/shows/index.php') ?>" class="btn btn-outline-secondary px-4">
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
