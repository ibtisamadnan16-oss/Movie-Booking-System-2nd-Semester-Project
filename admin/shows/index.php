<?php
$adminTitle = "Manage Shows & Timings - Admin Panel";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$selectedMovieId  = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
$selectedCinemaId = isset($_GET['cinema_id']) ? (int)$_GET['cinema_id'] : 0;
$selectedDate     = trim($_GET['show_date'] ?? '');
$statusFilter     = trim($_GET['status'] ?? '');

$shows = [];
$moviesList = [];
$cinemasList = [];

$dbStatus = checkDBStatus();
$flashSuccess = getFlash('show_success');
$flashError   = getFlash('show_error');

if ($dbStatus['status']) {
    try {
        $db = getDB();

$moviesList = $db->query("SELECT id, title FROM movies WHERE status != 'archived' ORDER BY title ASC")->fetchAll();
        $cinemasList = $db->query("SELECT id, name, city FROM cinemas ORDER BY name ASC")->fetchAll();

$sql = "SELECT s.*, m.title as movie_title, m.poster_image, m.duration_minutes,
                       c.name as cinema_name, c.city,
                       sc.screen_name, sc.screen_type, sc.total_seats,
                       (SELECT COUNT(*) FROM booking_seats bs WHERE bs.show_id = s.id) as booked_seats_count
                FROM shows s
                JOIN movies m ON s.movie_id = m.id
                JOIN cinemas c ON s.cinema_id = c.id
                JOIN screens sc ON s.screen_id = sc.id
                WHERE 1=1";
        $params = [];

        if ($selectedMovieId > 0) {
            $sql .= " AND s.movie_id = ?";
            $params[] = $selectedMovieId;
        }

        if ($selectedCinemaId > 0) {
            $sql .= " AND s.cinema_id = ?";
            $params[] = $selectedCinemaId;
        }

        if (!empty($selectedDate)) {
            $sql .= " AND s.show_date = ?";
            $params[] = $selectedDate;
        }

        if (!empty($statusFilter)) {
            $sql .= " AND s.status = ?";
            $params[] = $statusFilter;
        }

        $sql .= " ORDER BY s.show_date DESC, s.start_time ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $shows = $stmt->fetchAll();
    } catch (Exception $e) {
        $flashError = ['message' => 'Error querying shows: ' . $e->getMessage(), 'type' => 'danger'];
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
                    <h3 class="text-white fw-bold mb-1"><i class="fa-solid fa-calendar-check text-danger me-2"></i> Showtime Schedules</h3>
                    <p class="text-secondary small mb-0">Schedule movie showtimes across cinema screens, ticket pricing, and availability</p>
                </div>
                <div>
                    <a href="<?= url('admin/shows/add.php') ?>" class="btn btn-cine-primary">
                        <i class="fa-solid fa-plus me-1"></i> Schedule New Show
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

            <div class="cine-card p-3 mb-4">
                <form action="<?= url('admin/shows/index.php') ?>" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <select name="movie_id" class="form-select form-select-sm bg-dark border-secondary text-light">
                            <option value="">All Movies</option>
                            <?php foreach ($moviesList as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= $selectedMovieId === (int)$m['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <select name="cinema_id" class="form-select form-select-sm bg-dark border-secondary text-light">
                            <option value="">All Cinema Venues</option>
                            <?php foreach ($cinemasList as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $selectedCinemaId === (int)$c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['city']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <input type="date" name="show_date" class="form-control form-control-sm bg-dark border-secondary text-light" value="<?= htmlspecialchars($selectedDate) ?>">
                    </div>

                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm bg-dark border-secondary text-light">
                            <option value="">All Statuses</option>
                            <option value="scheduled" <?= $statusFilter === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                            <option value="ongoing" <?= $statusFilter === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                            <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">Filter</button>
                        <?php if ($selectedMovieId > 0 || $selectedCinemaId > 0 || !empty($selectedDate) || !empty($statusFilter)): ?>
                            <a href="<?= url('admin/shows/index.php') ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="cine-card p-4">
                <?php if (!empty($shows)): ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-secondary small border-secondary">
                                    <th>#</th>
                                    <th>Movie</th>
                                    <th>Cinema & Hall</th>
                                    <th>Show Date</th>
                                    <th>Time Slot</th>
                                    <th>Ticket Price</th>
                                    <th>Seats Booked</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shows as $s): ?>
                                    <tr>
                                        <td class="text-secondary small"><?= htmlspecialchars($s['id']) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?= getMoviePoster($s['poster_image'] ?? '', $s['movie_title']) ?>" alt="<?= htmlspecialchars($s['movie_title']) ?>" class="rounded" style="width: 38px; height: 50px; object-fit: cover;">
                                                <div>
                                                    <strong class="text-white d-block"><?= htmlspecialchars($s['movie_title']) ?></strong>
                                                    <small class="text-secondary"><?= htmlspecialchars($s['duration_minutes']) ?> mins</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-light fw-semibold small">
                                                <i class="fa-solid fa-video text-danger me-1"></i> <?= htmlspecialchars($s['cinema_name']) ?>
                                            </div>
                                            <div class="small text-secondary">
                                                <?= htmlspecialchars($s['screen_name']) ?> &bull; 
                                                <span class="badge bg-secondary"><?= htmlspecialchars($s['screen_type']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-white small fw-semibold">
                                                <i class="fa-regular fa-calendar text-danger me-1"></i>
                                                <?= date('d M Y (D)', strtotime($s['show_date'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-white fw-bold small">
                                                <?= date('h:i A', strtotime($s['start_time'])) ?> &rarr; <?= date('h:i A', strtotime($s['end_time'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-success fw-bold">
                                                <?= formatPrice($s['ticket_price']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark border border-secondary text-info">
                                                <?= (int)$s['booked_seats_count'] ?> / <?= (int)$s['total_seats'] ?> Reserved
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $stClass = 'bg-secondary';
                                            if ($s['status'] === 'scheduled') $stClass = 'bg-success';
                                            if ($s['status'] === 'ongoing') $stClass = 'bg-primary';
                                            if ($s['status'] === 'cancelled') $stClass = 'bg-danger';
                                            ?>
                                            <span class="badge <?= $stClass ?> text-uppercase"><?= htmlspecialchars($s['status']) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url('movie-details.php?id=' . $s['movie_id']) ?>" target="_blank" class="btn btn-outline-info" title="Preview Movie Details">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                <a href="<?= url('admin/shows/edit.php?id=' . $s['id']) ?>" class="btn btn-outline-warning" title="Edit Show">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <a href="<?= url('admin/shows/delete.php?id=' . $s['id']) ?>" class="btn btn-outline-danger" title="Delete Show" onclick="return confirm('Are you sure you want to delete this show? Any confirmed bookings for this time will also be removed.');">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-calendar-xmark text-secondary fs-1 mb-3"></i>
                        <h5 class="text-white">No Shows Scheduled</h5>
                        <p class="text-secondary small mb-3">No showtime schedules matched your filter options.</p>
                        <a href="<?= url('admin/shows/add.php') ?>" class="btn btn-cine-primary btn-sm">
                            <i class="fa-solid fa-plus me-1"></i> Schedule First Show
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
