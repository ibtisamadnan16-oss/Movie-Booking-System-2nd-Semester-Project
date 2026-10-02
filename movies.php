<?php
 
$pageTitle = "Explore Movies - Now Showing & Upcoming | CinePass";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$searchQuery    = trim($_GET['search'] ?? '');
$statusFilter   = trim($_GET['status'] ?? '');
$genreFilter    = trim($_GET['genre'] ?? '');
$languageFilter = trim($_GET['language'] ?? '');
$cinemaFilter   = isset($_GET['cinema']) && $_GET['cinema'] !== '' ? (int)$_GET['cinema'] : null;
$dateFilter     = trim($_GET['date'] ?? '');

$movies = [];
$cinemasList = [];
$languagesList = [];
$availableDates = [];
$counts = ['all' => 0, 'now_showing' => 0, 'upcoming' => 0];

$dbStatus = checkDBStatus();
if ($dbStatus['status']) {
    try {
        $db = getDB();

$cinemasList = $db->query("SELECT id, name, city FROM cinemas ORDER BY name ASC")->fetchAll();
        $languagesList = $db->query("SELECT DISTINCT language FROM movies WHERE language IS NOT NULL AND language != '' ORDER BY language ASC")->fetchAll(PDO::FETCH_COLUMN);
        $availableDates = $db->query("SELECT DISTINCT show_date FROM shows WHERE show_date >= CURDATE() ORDER BY show_date ASC LIMIT 7")->fetchAll(PDO::FETCH_COLUMN);

$counts['all']         = (int)$db->query("SELECT COUNT(*) FROM movies")->fetchColumn();
        $counts['now_showing'] = (int)$db->query("SELECT COUNT(*) FROM movies WHERE status = 'now_showing'")->fetchColumn();
        $counts['upcoming']    = (int)$db->query("SELECT COUNT(*) FROM movies WHERE status = 'upcoming'")->fetchColumn();

$sql = "
            SELECT DISTINCT 
                m.*,
                (SELECT COUNT(*) FROM shows s2 WHERE s2.movie_id = m.id AND s2.show_date >= CURDATE()) AS active_shows_count
            FROM movies m
        ";

if (!empty($cinemaFilter) || !empty($dateFilter)) {
            $sql .= " JOIN shows s ON m.id = s.movie_id ";
            if (!empty($cinemaFilter)) {
                $sql .= " JOIN cinemas c ON s.cinema_id = c.id ";
            }
        }

        $sql .= " WHERE 1=1 ";
        $params = [];

if (!empty($searchQuery)) {
            $sql .= " AND (m.title LIKE ? OR m.genre LIKE ? OR m.language LIKE ? OR m.description LIKE ?) ";
            $term = "%{$searchQuery}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

if (!empty($statusFilter)) {
            $sql .= " AND m.status = ? ";
            $params[] = $statusFilter;
        }

if (!empty($genreFilter)) {
            $sql .= " AND m.genre LIKE ? ";
            $params[] = "%{$genreFilter}%";
        }

if (!empty($languageFilter)) {
            $sql .= " AND m.language = ? ";
            $params[] = $languageFilter;
        }

if (!empty($cinemaFilter)) {
            $sql .= " AND s.cinema_id = ? ";
            $params[] = $cinemaFilter;
        }

if (!empty($dateFilter)) {
            $sql .= " AND s.show_date = ? ";
            $params[] = $dateFilter;
        }

        $sql .= " ORDER BY (m.status = 'now_showing') DESC, m.rating DESC, m.release_date DESC ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $movies = $stmt->fetchAll();

    } catch (Exception $e) {
        $movies = [];
    }
}

if (empty($movies) && empty($searchQuery) && empty($statusFilter) && empty($genreFilter) && empty($cinemaFilter) && empty($dateFilter)) {
    $movies = [
        [
            'id' => 1,
            'title' => 'Oppenheimer',
            'genre' => 'Drama, History, Biography',
            'duration_minutes' => 180,
            'rating' => 8.9,
            'language' => 'English',
            'poster_image' => 'oppenheimer.jpg',
            'status' => 'now_showing',
            'release_date' => '2023-07-21'
        ],
        [
            'id' => 2,
            'title' => 'Dune: Part Two',
            'genre' => 'Action, Adventure, Sci-Fi',
            'duration_minutes' => 166,
            'rating' => 8.6,
            'language' => 'English',
            'poster_image' => 'dune2.jpg',
            'status' => 'now_showing',
            'release_date' => '2024-03-01'
        ],
        [
            'id' => 3,
            'title' => 'Interstellar',
            'genre' => 'Adventure, Drama, Sci-Fi',
            'duration_minutes' => 169,
            'rating' => 8.7,
            'language' => 'English',
            'poster_image' => 'interstellar.jpg',
            'status' => 'now_showing',
            'release_date' => '2014-11-07'
        ],
        [
            'id' => 4,
            'title' => 'Gladiator II',
            'genre' => 'Action, Adventure, Drama',
            'duration_minutes' => 148,
            'rating' => 8.2,
            'language' => 'English',
            'poster_image' => 'gladiator2.jpg',
            'status' => 'upcoming',
            'release_date' => '2024-11-22'
        ]
    ];
}

$hasActiveFilters = (!empty($searchQuery) || !empty($statusFilter) || !empty($genreFilter) || !empty($languageFilter) || !empty($cinemaFilter) || !empty($dateFilter));

$selectedCinemaName = '';
if (!empty($cinemaFilter)) {
    foreach ($cinemasList as $c) {
        if ($c['id'] == $cinemaFilter) {
            $selectedCinemaName = $c['name'];
            break;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<section class="py-4 bg-black bg-opacity-40 border-bottom border-secondary border-opacity-25">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="text-white fw-bold mb-1">
                    <i class="fa-solid fa-film text-danger me-2"></i> Movies Catalog & Showtimes
                </h2>
                <p class="text-secondary small mb-0">Browse blockbuster movies, filter by cinema, date, genre, and book your tickets</p>
            </div>

            <div class="d-flex gap-2">
                <a href="<?= url('movies.php' . ($searchQuery ? '?search=' . urlencode($searchQuery) : '')) ?>" 
                   class="filter-btn <?= empty($statusFilter) ? 'active' : '' ?>">
                    All Movies <span class="badge bg-dark ms-1"><?= $counts['all'] ?></span>
                </a>
                <a href="<?= url('movies.php?status=now_showing' . ($searchQuery ? '&search=' . urlencode($searchQuery) : '')) ?>" 
                   class="filter-btn <?= $statusFilter === 'now_showing' ? 'active' : '' ?>">
                    <i class="fa-solid fa-circle-play me-1 text-success"></i> Now Showing <span class="badge bg-dark ms-1"><?= $counts['now_showing'] ?></span>
                </a>
                <a href="<?= url('movies.php?status=upcoming' . ($searchQuery ? '&search=' . urlencode($searchQuery) : '')) ?>" 
                   class="filter-btn <?= $statusFilter === 'upcoming' ? 'active' : '' ?>">
                    <i class="fa-regular fa-clock me-1 text-warning"></i> Upcoming <span class="badge bg-dark ms-1"><?= $counts['upcoming'] ?></span>
                </a>
            </div>
        </div>
    </div>
</section>

<section class="py-4">
    <div class="container">
        <div class="cine-card p-3 p-md-4 shadow mb-4">
            <form action="<?= url('movies.php') ?>" method="GET" id="searchFilterForm">
                <?php if (!empty($statusFilter)): ?>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                <?php endif; ?>

                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-dark border-secondary text-danger">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" 
                                   name="search" 
                                   class="form-control cine-form-control text-light" 
                                   placeholder="Search by Movie Name, Genre (e.g. Action, Sci-Fi), Language, or Story..." 
                                   value="<?= htmlspecialchars($searchQuery) ?>">
                            <button class="btn btn-cine-primary px-4 fw-bold" type="submit">
                                <i class="fa-solid fa-search me-1"></i> Search
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row g-3 align-items-center">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label text-secondary small mb-1 fw-semibold">
                            <i class="fa-solid fa-tags text-danger me-1"></i> Genre
                        </label>
                        <select name="genre" class="form-select form-select-sm cine-form-control">
                            <option value="">All Genres</option>
                            <?php 
                            $commonGenres = ['Action', 'Adventure', 'Biography', 'Comedy', 'Drama', 'History', 'Horror', 'Sci-Fi', 'Thriller'];
                            foreach ($commonGenres as $g): ?>
                                <option value="<?= $g ?>" <?= $genreFilter === $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <label class="form-label text-secondary small mb-1 fw-semibold">
                            <i class="fa-solid fa-language text-info me-1"></i> Language
                        </label>
                        <select name="language" class="form-select form-select-sm cine-form-control">
                            <option value="">All Languages</option>
                            <?php foreach ($languagesList as $lang): ?>
                                <option value="<?= htmlspecialchars($lang) ?>" <?= $languageFilter === $lang ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($lang) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if (!in_array('English', $languagesList)): ?>
                                <option value="English" <?= $languageFilter === 'English' ? 'selected' : '' ?>>English</option>
                            <?php endif; ?>
                            <option value="Urdu" <?= $languageFilter === 'Urdu' ? 'selected' : '' ?>>Urdu</option>
                            <option value="Hindi" <?= $languageFilter === 'Hindi' ? 'selected' : '' ?>>Hindi</option>
                        </select>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <label class="form-label text-secondary small mb-1 fw-semibold">
                            <i class="fa-solid fa-video text-warning me-1"></i> Cinema Venue
                        </label>
                        <select name="cinema" class="form-select form-select-sm cine-form-control">
                            <option value="">All Cinema Venues</option>
                            <?php foreach ($cinemasList as $cin): ?>
                                <option value="<?= (int)$cin['id'] ?>" <?= $cinemaFilter === (int)$cin['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cin['name']) ?> (<?= htmlspecialchars($cin['city']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <label class="form-label text-secondary small mb-1 fw-semibold">
                            <i class="fa-solid fa-calendar-day text-success me-1"></i> Screening Date
                        </label>
                        <input type="date" 
                               name="date" 
                               class="form-control form-control-sm cine-form-control text-light" 
                               value="<?= htmlspecialchars($dateFilter) ?>" 
                               min="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="col-md-2 col-12 d-flex gap-2 align-self-end">
                        <button type="submit" class="btn btn-cine-primary btn-sm flex-grow-1">
                            <i class="fa-solid fa-filter me-1"></i> Filter
                        </button>
                        <?php if ($hasActiveFilters): ?>
                            <a href="<?= url('movies.php') ?>" class="btn btn-outline-secondary btn-sm" title="Clear All Filters">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-secondary small fw-semibold">
                    <strong class="text-white"><?= count($movies) ?></strong> movie<?= count($movies) != 1 ? 's' : '' ?> found
                </span>

                <?php if (!empty($searchQuery)): ?>
                    <span class="badge bg-dark border border-secondary text-light py-1 px-2 small">
                        Search: "<?= htmlspecialchars($searchQuery) ?>"
                        <a href="<?= url('movies.php?' . http_build_query(array_merge($_GET, ['search' => '']))) ?>" class="text-danger ms-1 text-decoration-none">&times;</a>
                    </span>
                <?php endif; ?>

                <?php if (!empty($statusFilter)): ?>
                    <span class="badge bg-dark border border-danger text-danger py-1 px-2 small text-uppercase">
                        <?= str_replace('_', ' ', htmlspecialchars($statusFilter)) ?>
                        <a href="<?= url('movies.php?' . http_build_query(array_merge($_GET, ['status' => '']))) ?>" class="text-danger ms-1 text-decoration-none">&times;</a>
                    </span>
                <?php endif; ?>

                <?php if (!empty($genreFilter)): ?>
                    <span class="badge bg-dark border border-secondary text-warning py-1 px-2 small">
                        Genre: <?= htmlspecialchars($genreFilter) ?>
                        <a href="<?= url('movies.php?' . http_build_query(array_merge($_GET, ['genre' => '']))) ?>" class="text-danger ms-1 text-decoration-none">&times;</a>
                    </span>
                <?php endif; ?>

                <?php if (!empty($languageFilter)): ?>
                    <span class="badge bg-dark border border-secondary text-info py-1 px-2 small">
                        Language: <?= htmlspecialchars($languageFilter) ?>
                        <a href="<?= url('movies.php?' . http_build_query(array_merge($_GET, ['language' => '']))) ?>" class="text-danger ms-1 text-decoration-none">&times;</a>
                    </span>
                <?php endif; ?>

                <?php if (!empty($selectedCinemaName)): ?>
                    <span class="badge bg-dark border border-secondary text-warning py-1 px-2 small">
                        Cinema: <?= htmlspecialchars($selectedCinemaName) ?>
                        <a href="<?= url('movies.php?' . http_build_query(array_merge($_GET, ['cinema' => '']))) ?>" class="text-danger ms-1 text-decoration-none">&times;</a>
                    </span>
                <?php endif; ?>

                <?php if (!empty($dateFilter)): ?>
                    <span class="badge bg-dark border border-secondary text-success py-1 px-2 small">
                        Date: <?= date('d M Y', strtotime($dateFilter)) ?>
                        <a href="<?= url('movies.php?' . http_build_query(array_merge($_GET, ['date' => '']))) ?>" class="text-danger ms-1 text-decoration-none">&times;</a>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($hasActiveFilters): ?>
                <a href="<?= url('movies.php') ?>" class="small text-danger text-decoration-none">
                    <i class="fa-solid fa-trash-can me-1"></i> Clear All Filters
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($movies)): ?>
            <div class="row g-4">
                <?php foreach ($movies as $movie): ?>
                    <div class="col-sm-6 col-lg-4 col-xl-3">
                        <div class="cine-card h-100 d-flex flex-column border border-secondary transition-hover">
                            <div class="movie-poster-wrap position-relative">
                                <img src="<?= getMoviePoster($movie['poster_image'] ?? '', $movie['title']) ?>" 
                                     alt="<?= htmlspecialchars($movie['title']) ?>" 
                                     loading="lazy" 
                                     class="w-100" 
                                     style="height: 360px; object-fit: cover;">

                                <span class="badge <?= $movie['status'] === 'now_showing' ? 'bg-success' : 'bg-warning text-dark' ?> movie-badge-status position-absolute top-0 start-0 m-2 fw-bold text-uppercase">
                                    <?= $movie['status'] === 'now_showing' ? 'Now Showing' : 'Coming Soon' ?>
                                </span>

                                <span class="movie-rating position-absolute top-0 end-0 m-2 badge bg-black bg-opacity-75 text-warning fw-bold border border-warning">
                                    <i class="fa-solid fa-star me-1"></i><?= htmlspecialchars($movie['rating']) ?>
                                </span>
                            </div>

                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <h5 class="text-white fw-bold mb-1 text-truncate" title="<?= htmlspecialchars($movie['title']) ?>">
                                    <?= htmlspecialchars($movie['title']) ?>
                                </h5>

                                <div class="text-secondary small mb-2 text-truncate">
                                    <?= htmlspecialchars($movie['genre']) ?>
                                </div>

                                <div class="d-flex justify-content-between align-items-center text-muted small mb-3">
                                    <span>
                                        <i class="fa-regular fa-clock me-1 text-danger"></i> <?= htmlspecialchars($movie['duration_minutes']) ?> mins
                                    </span>
                                    <span class="badge bg-dark border border-secondary text-info">
                                        <?= htmlspecialchars($movie['language'] ?? 'English') ?>
                                    </span>
                                </div>

                                <div class="mt-auto d-grid gap-2">
                                    <a href="<?= url('movie-details.php?id=' . $movie['id']) ?>" class="btn btn-cine-primary btn-sm">
                                        <?= $movie['status'] === 'now_showing' ? '<i class="fa-solid fa-ticket me-1"></i> View Shows & Book' : '<i class="fa-solid fa-circle-info me-1"></i> Movie Details' ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="cine-card p-5 text-center my-4">
                <div class="brand-icon mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                    <i class="fa-solid fa-magnifying-glass text-secondary"></i>
                </div>
                <h4 class="text-white fw-bold mb-2">No Movies Found</h4>
                <p class="text-secondary small mb-4" style="max-width: 500px; margin: 0 auto;">
                    No cinema titles matched your current search and filter combination. Try adjusting your keyword, choosing another genre, cinema venue, or date.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="<?= url('movies.php') ?>" class="btn btn-cine-primary btn-sm px-4">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset All Filters
                    </a>
                    <a href="<?= url('booking.php') ?>" class="btn btn-outline-light btn-sm px-3">
                        <i class="fa-solid fa-ticket me-1"></i> Quick Booking
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
