<?php
 
$pageTitle = "Book Movie Tickets - CinePass";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$dbStatus = checkDBStatus();
if (!$dbStatus['status']) {
    setFlash('system_error', 'Database offline. Please ensure MySQL is running in XAMPP.', 'danger');
    redirect('movies.php');
}

$db = getDB();

$moviesStmt = $db->query("
    SELECT DISTINCT m.* 
    FROM movies m 
    JOIN shows s ON m.id = s.movie_id 
    WHERE m.status = 'now_showing' AND s.status != 'cancelled'
    ORDER BY m.title ASC
");
$activeMovies = $moviesStmt->fetchAll();

$selectedMovieId  = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : (!empty($activeMovies) ? (int)$activeMovies[0]['id'] : 0);
$selectedCinemaId = isset($_GET['cinema_id']) ? (int)$_GET['cinema_id'] : 0;
$selectedDate     = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');

$cinemas = [];
if ($selectedMovieId > 0) {
    $cinemasStmt = $db->prepare("
        SELECT DISTINCT c.* 
        FROM cinemas c 
        JOIN shows s ON c.id = s.cinema_id 
        WHERE s.movie_id = ? AND s.status != 'cancelled'
        ORDER BY c.name ASC
    ");
    $cinemasStmt->execute([$selectedMovieId]);
    $cinemas = $cinemasStmt->fetchAll();

    if ($selectedCinemaId <= 0 && !empty($cinemas)) {
        $selectedCinemaId = (int)$cinemas[0]['id'];
    }
}

$availableDates = [];
if ($selectedMovieId > 0 && $selectedCinemaId > 0) {
    $datesStmt = $db->prepare("
        SELECT DISTINCT s.show_date 
        FROM shows s 
        WHERE s.movie_id = ? AND s.cinema_id = ? AND s.status != 'cancelled'
        ORDER BY s.show_date ASC
    ");
    $datesStmt->execute([$selectedMovieId, $selectedCinemaId]);
    $availableDates = $datesStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($availableDates) && !in_array($selectedDate, $availableDates)) {
        $selectedDate = $availableDates[0];
    }
}

$shows = [];
if ($selectedMovieId > 0 && $selectedCinemaId > 0 && !empty($selectedDate)) {
    $showsStmt = $db->prepare("
        SELECT s.*, scr.screen_name, scr.screen_type, scr.total_seats,
               (SELECT COUNT(*) FROM booking_seats bs JOIN bookings b ON bs.booking_id = b.id WHERE bs.show_id = s.id AND b.booking_status != 'cancelled') as booked_seats
        FROM shows s
        JOIN screens scr ON s.screen_id = scr.id
        WHERE s.movie_id = ? AND s.cinema_id = ? AND s.show_date = ? AND s.status != 'cancelled'
        ORDER BY s.start_time ASC
    ");
    $showsStmt->execute([$selectedMovieId, $selectedCinemaId, $selectedDate]);
    $shows = $showsStmt->fetchAll();
}

$currentMovie = null;
foreach ($activeMovies as $m) {
    if ((int)$m['id'] === $selectedMovieId) {
        $currentMovie = $m;
        break;
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container my-5 flex-grow-1">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <span class="badge bg-danger text-uppercase px-3 py-2 mb-2">Phase 9 Booking Engine</span>
            <h2 class="text-white fw-bold mb-1">
                <i class="fa-solid fa-ticket text-danger me-2"></i> Book Movie Tickets
            </h2>
            <p class="text-secondary small mb-0">Follow our quick 6-step flow: Select Movie &rarr; Select Cinema &rarr; Select Showtime &rarr; Select Seats &rarr; Summary &rarr; Confirm</p>
        </div>
        <a href="<?= url('movies.php') ?>" class="btn btn-outline-light btn-sm">
            <i class="fa-solid fa-film me-1"></i> Browse Catalog
        </a>
    </div>

    <div class="cine-card p-3 p-md-4 mb-4">
        <div class="d-flex justify-content-between align-items-center position-relative flex-wrap gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill bg-danger px-3 py-2"><i class="fa-solid fa-film me-1"></i> 1. Movie</span>
                <span class="text-secondary">&rarr;</span>
                <span class="badge rounded-pill bg-danger px-3 py-2"><i class="fa-solid fa-video me-1"></i> 2. Cinema</span>
                <span class="text-secondary">&rarr;</span>
                <span class="badge rounded-pill bg-danger px-3 py-2"><i class="fa-regular fa-clock me-1"></i> 3. Showtime</span>
                <span class="text-secondary">&rarr;</span>
                <span class="badge rounded-pill bg-dark border border-secondary text-secondary px-3 py-2"><i class="fa-solid fa-chair me-1"></i> 4. Seats</span>
                <span class="text-secondary">&rarr;</span>
                <span class="badge rounded-pill bg-dark border border-secondary text-secondary px-3 py-2"><i class="fa-solid fa-receipt me-1"></i> 5. Summary</span>
                <span class="text-secondary">&rarr;</span>
                <span class="badge rounded-pill bg-dark border border-secondary text-secondary px-3 py-2"><i class="fa-solid fa-circle-check me-1"></i> 6. Confirm</span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="cine-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-white fw-bold mb-0">
                        <span class="badge bg-danger me-2">Step 1</span> Select Movie
                    </h5>
                    <small class="text-secondary"><?= count($activeMovies) ?> now showing</small>
                </div>

                <div class="row g-3">
                    <?php foreach ($activeMovies as $mov): ?>
                        <?php $isSelectedMov = ((int)$mov['id'] === $selectedMovieId); ?>
                        <div class="col-sm-6 col-md-3">
                            <a href="<?= url('booking.php?movie_id=' . $mov['id']) ?>" class="text-decoration-none">
                                <div class="p-2 rounded bg-dark border <?= $isSelectedMov ? 'border-danger shadow' : 'border-secondary' ?> h-100 text-center transition-all"
                                     style="<?= $isSelectedMov ? 'background: rgba(229, 9, 20, 0.1) !important; border-width: 2px !important;' : '' ?>">
                                    <?php if (!empty($mov['poster_image'])): ?>
                                        <img src="<?= url('assets/images/' . $mov['poster_image']) ?>" 
                                             alt="<?= htmlspecialchars($mov['title']) ?>" 
                                             class="rounded mb-2 w-100" 
                                             style="height: 120px; object-fit: cover;"
                                             onerror="this.style.display='none'">
                                    <?php else: ?>
                                        <div class="rounded mb-2 bg-black d-flex align-items-center justify-content-center" style="height: 120px;">
                                            <i class="fa-solid fa-film text-secondary fs-3"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-light fw-bold small text-truncate"><?= htmlspecialchars($mov['title']) ?></div>
                                    <small class="text-warning" style="font-size: 0.72rem;"><i class="fa-solid fa-star me-1"></i><?= $mov['rating'] ?></small>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="cine-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-white fw-bold mb-0">
                        <span class="badge bg-danger me-2">Step 2</span> Select Cinema Theatre
                    </h5>
                    <small class="text-secondary"><?= count($cinemas) ?> theatre branches available</small>
                </div>

                <?php if (!empty($cinemas)): ?>
                    <div class="row g-3">
                        <?php foreach ($cinemas as $cin): ?>
                            <?php $isSelectedCin = ((int)$cin['id'] === $selectedCinemaId); ?>
                            <div class="col-md-6">
                                <a href="<?= url('booking.php?movie_id=' . $selectedMovieId . '&cinema_id=' . $cin['id']) ?>" class="text-decoration-none">
                                    <div class="p-3 rounded bg-dark border <?= $isSelectedCin ? 'border-danger shadow' : 'border-secondary' ?> h-100 d-flex align-items-center gap-3 transition-all"
                                         style="<?= $isSelectedCin ? 'background: rgba(229, 9, 20, 0.1) !important; border-width: 2px !important;' : '' ?>">
                                        <div class="brand-icon" style="width: 44px; height: 44px;">
                                            <i class="fa-solid fa-video <?= $isSelectedCin ? 'text-danger' : 'text-secondary' ?>"></i>
                                        </div>
                                        <div>
                                            <div class="text-light fw-bold"><?= htmlspecialchars($cin['name']) ?></div>
                                            <small class="text-secondary d-block"><i class="fa-solid fa-location-dot me-1 text-danger"></i> <?= htmlspecialchars($cin['city']) ?> &bull; <?= htmlspecialchars($cin['location']) ?></small>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-secondary">
                        <p class="mb-0">No cinemas currently screening this movie.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="cine-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-white fw-bold mb-0">
                        <span class="badge bg-danger me-2">Step 3</span> Select Showtime
                    </h5>
                    <small class="text-secondary">Available dates & sessions</small>
                </div>

                <?php if (!empty($availableDates)): ?>
                    <div class="d-flex gap-2 flex-wrap mb-4">
                        <?php foreach ($availableDates as $d): ?>
                            <?php 
                            $isDateActive = ($d === $selectedDate);
                            $dayName = ($d === date('Y-m-d')) ? 'Today' : date('D', strtotime($d));
                            ?>
                            <a href="<?= url('booking.php?movie_id=' . $selectedMovieId . '&cinema_id=' . $selectedCinemaId . '&date=' . $d) ?>" 
                               class="btn btn-sm <?= $isDateActive ? 'btn-danger' : 'btn-dark border-secondary text-light' ?> px-3 py-2 text-center" style="min-width: 90px;">
                                <div class="small fw-bold"><?= $dayName ?></div>
                                <div class="small opacity-75"><?= date('d M', strtotime($d)) ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($shows)): ?>
                    <div class="row g-3">
                        <?php foreach ($shows as $s): ?>
                            <?php 
                            $availSeats = max(0, (int)$s['total_seats'] - (int)$s['booked_seats']);
                            ?>
                            <div class="col-sm-6">
                                <div class="p-3 rounded bg-dark border border-secondary h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-danger-subtle text-danger border border-danger small">
                                                <?= htmlspecialchars($s['screen_name']) ?> &bull; <?= htmlspecialchars($s['screen_type']) ?>
                                            </span>
                                            <span class="text-success fw-bold small">
                                                <?= formatPrice($s['ticket_price']) ?>
                                            </span>
                                        </div>
                                        <div class="fs-5 text-white fw-bold mb-1">
                                            <i class="fa-regular fa-clock text-danger me-1"></i>
                                            <?= date('h:i A', strtotime($s['start_time'])) ?>
                                        </div>
                                        <small class="text-secondary d-block mb-3">
                                            <i class="fa-solid fa-chair text-success me-1"></i> <?= $availSeats ?> of <?= $s['total_seats'] ?> seats free
                                        </small>
                                    </div>

                                    <a href="<?= url('user/select-seats.php?show_id=' . $s['id']) ?>" class="btn btn-cine-primary btn-sm w-100">
                                        <i class="fa-solid fa-chair me-1"></i> Step 4: Select Seats &rarr;
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-secondary">
                        <i class="fa-solid fa-calendar-xmark fs-2 mb-2"></i>
                        <p class="mb-0">No showtimes found for the selected date. Please pick another date above.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="cine-card p-4 sticky-top" style="top: 90px;">
                <h5 class="text-white fw-bold mb-3 border-bottom border-secondary pb-3">
                    <i class="fa-solid fa-layer-group text-danger me-2"></i> Booking Progress
                </h5>

                <?php if ($currentMovie): ?>
                    <div class="text-center mb-3">
                        <?php if (!empty($currentMovie['poster_image'])): ?>
                            <img src="<?= url('assets/images/' . $currentMovie['poster_image']) ?>" 
                                 alt="<?= htmlspecialchars($currentMovie['title']) ?>" 
                                 class="rounded shadow mb-2 img-fluid" 
                                 style="max-height: 200px; object-fit: cover; border: 1px solid var(--cine-card-border);"
                                 onerror="this.style.display='none'">
                        <?php endif; ?>
                        <h5 class="text-white fw-bold mb-0"><?= htmlspecialchars($currentMovie['title']) ?></h5>
                        <small class="text-secondary"><?= htmlspecialchars($currentMovie['genre']) ?> &bull; <?= $currentMovie['duration_minutes'] ?> mins</small>
                    </div>

                    <div class="d-flex flex-column gap-2 small mb-4">
                        <div class="p-2 rounded bg-dark border border-secondary d-flex justify-content-between">
                            <span class="text-secondary">Selected Cinema:</span>
                            <strong class="text-light"><?= !empty($cinemas) ? htmlspecialchars($cinemas[0]['name']) : 'None' ?></strong>
                        </div>
                        <div class="p-2 rounded bg-dark border border-secondary d-flex justify-content-between">
                            <span class="text-secondary">Selected Date:</span>
                            <strong class="text-light"><?= date('D, d M Y', strtotime($selectedDate)) ?></strong>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="alert alert-dark border-secondary small mb-0">
                    <strong class="text-danger d-block mb-1"><i class="fa-solid fa-circle-info me-1"></i> Quick Tip:</strong>
                    Select your preferred session time slot on the left to launch the interactive graphical seating chart where you can pick your exact seats!
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
