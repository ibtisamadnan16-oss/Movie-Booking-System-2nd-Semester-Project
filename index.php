<?php
$pageTitle = "Home - Watch & Book Latest Movies";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$dbStatus = checkDBStatus();

$nowShowingMovies = [];
$upcomingMovies = [];
$featuredMovie = null;
$theatres = [];

if ($dbStatus['status']) {
    try {
        $db = getDB();

$stmtNow = $db->query("SELECT * FROM movies WHERE status = 'now_showing' ORDER BY rating DESC");
        $nowShowingMovies = $stmtNow->fetchAll();

$stmtUp = $db->query("SELECT * FROM movies WHERE status = 'upcoming' ORDER BY release_date ASC");
        $upcomingMovies = $stmtUp->fetchAll();

if (!empty($nowShowingMovies)) {
            $featuredMovie = $nowShowingMovies[0];
        }

$stmtCinemas = $db->query("SELECT * FROM cinemas LIMIT 3");
        $theatres = $stmtCinemas->fetchAll();
    } catch (Exception $e) {
        $nowShowingMovies = [];
        $upcomingMovies = [];
        $theatres = [];
    }
}

if (empty($nowShowingMovies)) {
    $nowShowingMovies = [
        [
            'id' => 1,
            'title' => 'Oppenheimer',
            'genre' => 'Drama, History, Biography',
            'duration_minutes' => 180,
            'rating' => 8.9,
            'language' => 'English',
            'poster_image' => 'oppenheimer.jpg',
            'trailer_url' => 'https://www.youtube.com/watch?v=uYPbbksJxIg',
            'description' => 'The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb.',
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
            'trailer_url' => 'https://www.youtube.com/watch?v=Way9Dexny3w',
            'description' => 'Paul Atreides unites with Chani and the Fremen while seeking revenge against the conspirators.',
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
            'trailer_url' => 'https://www.youtube.com/watch?v=zSWdZVtXT7E',
            'description' => 'When Earth becomes uninhabitable, a team of explorers travel through a wormhole in space.',
            'status' => 'now_showing',
            'release_date' => '2014-11-07'
        ]
    ];
    $featuredMovie = $nowShowingMovies[0];
}

if (empty($upcomingMovies)) {
    $upcomingMovies = [
        [
            'id' => 4,
            'title' => 'Gladiator II',
            'genre' => 'Action, Adventure, Drama',
            'duration_minutes' => 148,
            'rating' => 8.2,
            'language' => 'English',
            'poster_image' => 'gladiator2.jpg',
            'trailer_url' => 'https://www.youtube.com/watch?v=4rgYUipGJNo',
            'description' => 'Years after witnessing the death of Maximus, Lucius must enter the Colosseum to fight the emperors of Rome.',
            'status' => 'upcoming',
            'release_date' => '2024-11-22'
        ]
    ];
}

if (empty($theatres)) {
    $theatres = [
        ['name' => 'Atrium Cinemas', 'city' => 'Karachi', 'location' => '3rd Floor, Atrium Mall, Staff Lines, Saddar', 'contact_number' => '021-111-287-486'],
        ['name' => 'Nueplex Cinemas DHA', 'city' => 'Karachi', 'location' => 'The Place Mall, Khayaban-e-Shaheen, Phase 8, DHA', 'contact_number' => '021-111-683-683'],
        ['name' => 'Cinepax Ocean Mall', 'city' => 'Karachi', 'location' => '4th Floor, Ocean Mall, Clifton Block 9', 'contact_number' => '021-111-246-372']
    ];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<section class="py-4 py-lg-5">
    <div class="container">
        <?php if ($featuredMovie): ?>
            <div class="featured-banner shadow-lg" style="background-image: url('<?= getMoviePoster($featuredMovie['poster_image'] ?? '', $featuredMovie['title']) ?>');">
                <div class="featured-banner-overlay"></div>
                <div class="featured-banner-content">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-danger text-uppercase px-3 py-1">Featured Premiere</span>
                        <span class="text-warning fw-bold small"><i class="fa-solid fa-star me-1"></i><?= htmlspecialchars($featuredMovie['rating']) ?> IMDb</span>
                        <span class="text-light small opacity-75"><i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars($featuredMovie['duration_minutes']) ?> mins</span>
                    </div>
                    <h1 class="text-white display-5 fw-bold mb-3"><?= htmlspecialchars($featuredMovie['title']) ?></h1>
                    <p class="text-light opacity-75 small mb-4 line-clamp-3">
                        <?= htmlspecialchars($featuredMovie['description']) ?>
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?= url('movie-details.php?id=' . $featuredMovie['id']) ?>" class="btn btn-cine-primary">
                            <i class="fa-solid fa-ticket me-2"></i> Book Tickets Now
                        </a>
                        <a href="<?= url('movie-details.php?id=' . $featuredMovie['id'] . '#trailer') ?>" class="btn btn-cine-outline">
                            <i class="fa-solid fa-play me-2 text-danger"></i> Watch Trailer
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="py-3">
    <div class="container">
        <div class="cine-card p-3 p-md-4 shadow">
            <form action="<?= url('movies.php') ?>" method="GET" class="row g-3 align-items-center">
                <div class="col-lg-5 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-danger"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="search" class="form-control cine-form-control" placeholder="Search movie name, genre, language...">
                    </div>
                </div>

                <div class="col-lg-3 col-md-3">
                    <select name="genre" class="form-select cine-form-control">
                        <option value="">All Genres</option>
                        <option value="Action">Action</option>
                        <option value="Sci-Fi">Sci-Fi</option>
                        <option value="Drama">Drama</option>
                        <option value="Adventure">Adventure</option>
                        <option value="Thriller">Thriller</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-3">
                    <select name="status" class="form-select cine-form-control">
                        <option value="">All Status</option>
                        <option value="now_showing">Now Showing</option>
                        <option value="upcoming">Upcoming</option>
                    </select>
                </div>

                <div class="col-lg-2 col-12">
                    <button type="submit" class="btn btn-cine-primary w-100">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<section id="now-showing" class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
            <div>
                <span class="text-danger fw-bold small text-uppercase tracking-wider">In Theatres Today</span>
                <h2 class="text-white fw-bold mb-0">Now Showing Movies</h2>
            </div>
            <a href="<?= url('movies.php?status=now_showing') ?>" class="text-danger fw-semibold text-decoration-none small">
                Explore All Movies <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($nowShowingMovies as $movie): ?>
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="cine-card h-100 d-flex flex-column">
                        <div class="movie-poster-wrap">
                            <img src="<?= getMoviePoster($movie['poster_image'] ?? '', $movie['title']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" loading="lazy">
                            <span class="badge bg-success movie-badge-status">Now Showing</span>
                            <span class="movie-rating"><i class="fa-solid fa-star me-1"></i><?= htmlspecialchars($movie['rating']) ?></span>
                        </div>
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <h5 class="text-white fw-bold mb-1 text-truncate" title="<?= htmlspecialchars($movie['title']) ?>">
                                <?= htmlspecialchars($movie['title']) ?>
                            </h5>
                            <div class="text-secondary small mb-2 text-truncate"><?= htmlspecialchars($movie['genre']) ?></div>
                            <div class="d-flex justify-content-between align-items-center text-muted small mb-3">
                                <span><i class="fa-regular fa-clock me-1 text-danger"></i> <?= htmlspecialchars($movie['duration_minutes']) ?>m</span>
                                <span><i class="fa-solid fa-globe me-1 text-danger"></i> <?= htmlspecialchars($movie['language'] ?? 'English') ?></span>
                            </div>
                            <div class="mt-auto d-grid gap-2">
                                <a href="<?= url('movie-details.php?id=' . $movie['id']) ?>" class="btn btn-cine-primary btn-sm">
                                    <i class="fa-solid fa-ticket me-1"></i> View Shows & Book
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="upcoming" class="py-5 bg-black bg-opacity-25">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
            <div>
                <span class="text-warning fw-bold small text-uppercase tracking-wider">Coming Soon</span>
                <h2 class="text-white fw-bold mb-0">Upcoming Blockbusters</h2>
            </div>
            <a href="<?= url('movies.php?status=upcoming') ?>" class="text-warning fw-semibold text-decoration-none small">
                See All Upcoming <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($upcomingMovies as $movie): ?>
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="cine-card h-100 d-flex flex-column border-warning-subtle">
                        <div class="movie-poster-wrap">
                            <img src="<?= getMoviePoster($movie['poster_image'] ?? '', $movie['title']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" loading="lazy">
                            <span class="badge bg-warning text-dark movie-badge-status">Releasing <?= date('d M', strtotime($movie['release_date'])) ?></span>
                            <span class="movie-rating"><i class="fa-solid fa-star me-1"></i><?= htmlspecialchars($movie['rating']) ?></span>
                        </div>
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <h5 class="text-white fw-bold mb-1 text-truncate" title="<?= htmlspecialchars($movie['title']) ?>">
                                <?= htmlspecialchars($movie['title']) ?>
                            </h5>
                            <div class="text-secondary small mb-2 text-truncate"><?= htmlspecialchars($movie['genre']) ?></div>
                            <div class="d-flex justify-content-between align-items-center text-muted small mb-3">
                                <span><i class="fa-regular fa-clock me-1 text-warning"></i> <?= htmlspecialchars($movie['duration_minutes']) ?>m</span>
                                <span><i class="fa-regular fa-calendar me-1 text-warning"></i> <?= date('M Y', strtotime($movie['release_date'])) ?></span>
                            </div>
                            <div class="mt-auto d-grid gap-2">
                                <a href="<?= url('movie-details.php?id=' . $movie['id']) ?>" class="btn btn-outline-warning btn-sm">
                                    <i class="fa-solid fa-circle-info me-1"></i> Details & Trailer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-danger fw-bold small text-uppercase">Top Cineplexes</span>
            <h2 class="text-white fw-bold">Partner Theatres in Karachi</h2>
            <p class="text-secondary small">Immerse yourself in Dolby Atmos sound, 4DX motion seats, and crystal-clear laser projection.</p>
        </div>

        <div class="row g-4">
            <?php foreach ($theatres as $t): ?>
                <div class="col-md-4">
                    <div class="cine-card p-4 text-center h-100">
                        <div class="brand-icon mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.3rem;">
                            <i class="fa-solid fa-video"></i>
                        </div>
                        <h5 class="text-white fw-bold mb-1"><?= htmlspecialchars($t['name']) ?></h5>
                        <p class="text-danger small fw-semibold mb-2"><?= htmlspecialchars($t['city']) ?></p>
                        <p class="text-secondary small mb-3"><?= htmlspecialchars($t['location']) ?></p>
                        <span class="badge bg-dark border border-secondary text-secondary">
                            <i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars($t['contact_number'] ?? '021-111-287-486') ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
