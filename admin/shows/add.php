<?php
$adminTitle = "Schedule Show - Admin Panel";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$errors = [];
$movieId     = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
$cinemaId    = isset($_GET['cinema_id']) ? (int)$_GET['cinema_id'] : 0;
$screenId    = 0;
$showDate    = date('Y-m-d');
$startTime   = '18:00';
$endTime     = '21:00';
$ticketPrice = 850.00;
$status      = 'scheduled';

$movies = [];
$cinemas = [];
$screens = [];

$dbStatus = checkDBStatus();
if ($dbStatus['status']) {
    try {
        $db = getDB();
        $movies = $db->query("SELECT id, title, duration_minutes FROM movies WHERE status != 'archived' ORDER BY title ASC")->fetchAll();
        $cinemas = $db->query("SELECT id, name, city FROM cinemas ORDER BY name ASC")->fetchAll();
        $screens = $db->query("SELECT s.id, s.screen_name, s.screen_type, s.cinema_id, c.name as cinema_name 
                               FROM screens s 
                               JOIN cinemas c ON s.cinema_id = c.id 
                               ORDER BY c.name ASC, s.screen_name ASC")->fetchAll();
    } catch (Exception $e) {
        $errors['general'] = 'Database error: ' . $e->getMessage();
    }
}

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
            $db = getDB();

$stmt = $db->prepare("INSERT INTO shows (movie_id, cinema_id, screen_id, show_date, start_time, end_time, ticket_price, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$movieId, $cinemaId, $screenId, $showDate, $startTime, $endTime, $ticketPrice, $status]);

$mStmt = $db->prepare("SELECT title FROM movies WHERE id = ? LIMIT 1");
            $mStmt->execute([$movieId]);
            $movieTitle = $mStmt->fetchColumn();

            setFlash('show_success', "Showtime for '" . htmlspecialchars($movieTitle) . "' scheduled successfully!", 'success');
            redirect('admin/shows/index.php');
        } catch (Exception $e) {
            $errors['general'] = 'Failed to schedule show: ' . $e->getMessage();
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
                    <h3 class="text-white fw-bold mb-1"><i class="fa-solid fa-calendar-plus text-danger me-2"></i> Schedule Movie Showtime</h3>
                    <p class="text-secondary small mb-0">Assign a movie to a cinema hall, configure show dates, times, and pricing</p>
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
                <form action="<?= url('admin/shows/add.php') ?>" method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Select Movie <span class="text-danger">*</span></label>
                            <select name="movie_id" id="movieSelect" class="form-select cine-form-control <?= isset($errors['movie_id']) ? 'border-danger' : '' ?>" required onchange="updateEstimatedEndTime()">
                                <option value="">Choose Movie Title</option>
                                <?php foreach ($movies as $m): ?>
                                    <option value="<?= $m['id'] ?>" data-duration="<?= $m['duration_minutes'] ?>" <?= $movieId === (int)$m['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['title']) ?> (<?= htmlspecialchars($m['duration_minutes']) ?> mins)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['movie_id'])): ?>
                                <div class="text-danger small mt-1"><?= $errors['movie_id'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Cinema Venue <span class="text-danger">*</span></label>
                            <select name="cinema_id" id="cinemaSelect" class="form-select cine-form-control <?= isset($errors['cinema_id']) ? 'border-danger' : '' ?>" required onchange="filterScreensByCinema()">
                                <option value="">Choose Cinema Branch</option>
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
                            <label class="form-label text-light small fw-semibold">Screen / Hall <span class="text-danger">*</span></label>
                            <select name="screen_id" id="screenSelect" class="form-select cine-form-control <?= isset($errors['screen_id']) ? 'border-danger' : '' ?>" required>
                                <option value="">Choose Screen</option>
                                <?php foreach ($screens as $sc): ?>
                                    <option value="<?= $sc['id'] ?>" data-cinema="<?= $sc['cinema_id'] ?>" <?= $screenId === (int)$sc['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sc['cinema_name']) ?> &rarr; <?= htmlspecialchars($sc['screen_name']) ?> (<?= htmlspecialchars($sc['screen_type']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['screen_id'])): ?>
                                <div class="text-danger small mt-1"><?= $errors['screen_id'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Show Date <span class="text-danger">*</span></label>
                            <input type="date" name="show_date" class="form-control cine-form-control <?= isset($errors['show_date']) ? 'border-danger' : '' ?>" value="<?= htmlspecialchars($showDate) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" id="startTimeInput" class="form-control cine-form-control <?= isset($errors['start_time']) ? 'border-danger' : '' ?>" value="<?= htmlspecialchars($startTime) ?>" required onchange="updateEstimatedEndTime()">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="endTimeInput" class="form-control cine-form-control <?= isset($errors['end_time']) ? 'border-danger' : '' ?>" value="<?= htmlspecialchars($endTime) ?>" required>
                            <?php if (isset($errors['end_time'])): ?>
                                <div class="text-danger small mt-1"><?= $errors['end_time'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">Ticket Price (PKR) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-success fw-bold">Rs.</span>
                                <input type="number" step="10" name="ticket_price" class="form-control cine-form-control <?= isset($errors['ticket_price']) ? 'border-danger' : '' ?>" placeholder="850" value="<?= htmlspecialchars($ticketPrice) ?>" required min="100">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">Schedule Status</label>
                            <select name="status" class="form-select cine-form-control">
                                <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Scheduled (Active for Booking)</option>
                                <option value="ongoing" <?= $status === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>

                        <div class="col-12 pt-3 border-top border-secondary d-flex gap-3">
                            <button type="submit" class="btn btn-cine-primary px-4">
                                <i class="fa-solid fa-circle-check me-1"></i> Save Showtime Schedule
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

<script>
function filterScreensByCinema() {
    const selectedCinema = document.getElementById('cinemaSelect').value;
    const screenOptions = document.querySelectorAll('#screenSelect option');
    
    screenOptions.forEach(opt => {
        if (!opt.value) return;
        const cinema = opt.getAttribute('data-cinema');
        if (!selectedCinema || cinema === selectedCinema) {
            opt.style.display = 'block';
        } else {
            opt.style.display = 'none';
        }
    });
}

function updateEstimatedEndTime() {
    const movieSelect = document.getElementById('movieSelect');
    const selectedOption = movieSelect.options[movieSelect.selectedIndex];
    const duration = parseInt(selectedOption ? selectedOption.getAttribute('data-duration') : 0) || 120;
    const startTimeVal = document.getElementById('startTimeInput').value;

    if (startTimeVal) {
        const parts = startTimeVal.split(':');
        let hours = parseInt(parts[0]);
        let minutes = parseInt(parts[1]);

        // Add movie duration + 15 min clean-up intermission
        minutes += duration + 15;
        hours += Math.floor(minutes / 60);
        minutes = minutes % 60;
        hours = hours % 24;

        const formatted = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
        document.getElementById('endTimeInput').value = formatted;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
