<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$selectedDate = trim($_GET['date'] ?? date('Y-m-d'));

$movie = null;
$shows = [];
$uniqueDates = [];
$cinemasWithShows = [];
$dbStatus = checkDBStatus();

if ($dbStatus['status']) {
    try {
        $db = getDB();

$stmt = $db->prepare("SELECT * FROM movies WHERE id = ? LIMIT 1");
        $stmt->execute([$movieId]);
        $movie = $stmt->fetch();

        if ($movie) {
 
            $dateStmt = $db->prepare("SELECT DISTINCT show_date FROM shows WHERE movie_id = ? AND status = 'scheduled' AND show_date >= CURDATE() ORDER BY show_date ASC");
            $dateStmt->execute([$movieId]);
            $uniqueDates = $dateStmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($uniqueDates) && !in_array($selectedDate, $uniqueDates)) {
                $selectedDate = $uniqueDates[0];
            }

$showStmt = $db->prepare("SELECT s.*, c.id as cinema_id, c.name as cinema_name, c.location as cinema_location, c.city,
                                             sc.screen_name, sc.screen_type, sc.total_seats,
                                             (SELECT COUNT(*) FROM booking_seats bs WHERE bs.show_id = s.id) as booked_seats_count
                                      FROM shows s
                                      JOIN cinemas c ON s.cinema_id = c.id
                                      JOIN screens sc ON s.screen_id = sc.id
                                      WHERE s.movie_id = ? AND s.show_date = ? AND s.status = 'scheduled'
                                      ORDER BY c.name ASC, s.start_time ASC");
            $showStmt->execute([$movieId, $selectedDate]);
            $shows = $showStmt->fetchAll();

foreach ($shows as $s) {
                $cId = $s['cinema_id'];
                if (!isset($cinemasWithShows[$cId])) {
                    $cinemasWithShows[$cId] = [
                        'cinema_name'     => $s['cinema_name'],
                        'cinema_location' => $s['cinema_location'],
                        'city'            => $s['city'],
                        'shows'           => []
                    ];
                }
                $cinemasWithShows[$cId]['shows'][] = $s;
            }
        }
    } catch (Exception $e) {
        $movie = null;
        $shows = [];
    }
}

if (!$movie) {
    $demoMovies = [
        1 => [
            'id' => 1,
            'title' => 'Oppenheimer',
            'genre' => 'Drama, History, Biography',
            'duration_minutes' => 180,
            'rating' => 8.9,
            'language' => 'English',
            'poster_image' => 'oppenheimer.jpg',
            'trailer_url' => 'https://www.youtube.com/watch?v=uYPbbksJxIg',
            'description' => 'The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb during the Manhattan Project.',
            'status' => 'now_showing',
            'release_date' => '2023-07-21'
        ],
        2 => [
            'id' => 2,
            'title' => 'Dune: Part Two',
            'genre' => 'Action, Adventure, Sci-Fi',
            'duration_minutes' => 166,
            'rating' => 8.6,
            'language' => 'English',
            'poster_image' => 'dune2.jpg',
            'trailer_url' => 'https://www.youtube.com/watch?v=Way9Dexny3w',
            'description' => 'Paul Atreides unites with Chani and the Fremen while seeking revenge against the conspirators who destroyed his family.',
            'status' => 'now_showing',
            'release_date' => '2024-03-01'
        ]
    ];

    $movie = $demoMovies[$movieId] ?? $demoMovies[1];
    $uniqueDates = [date('Y-m-d'), date('Y-m-d', strtotime('+1 day')), date('Y-m-d', strtotime('+2 days'))];
    $selectedDate = date('Y-m-d');

    $cinemasWithShows = [
        1 => [
            'cinema_name'     => 'Atrium Cinemas',
            'cinema_location' => '3rd Floor, Atrium Mall, Staff Lines, Saddar',
            'city'            => 'Karachi',
            'shows'           => [
                [
                    'id'                 => 1,
                    'screen_name'        => 'Cinema Hall 1 (Gold)',
                    'screen_type'        => '3D',
                    'start_time'         => '15:00:00',
                    'end_time'           => '18:00:00',
                    'ticket_price'       => 900.00,
                    'total_seats'        => 30,
                    'booked_seats_count' => 2
                ],
                [
                    'id'                 => 2,
                    'screen_name'        => 'Cinema Hall 1 (Gold)',
                    'screen_type'        => '3D',
                    'start_time'         => '19:00:00',
                    'end_time'           => '22:00:00',
                    'ticket_price'       => 1000.00,
                    'total_seats'        => 30,
                    'booked_seats_count' => 0
                ]
            ]
        ],
        2 => [
            'cinema_name'     => 'Nueplex Cinemas DHA',
            'cinema_location' => 'The Place Mall, Khayaban-e-Shaheen, Phase 8, DHA',
            'city'            => 'Karachi',
            'shows'           => [
                [
                    'id'                 => 3,
                    'screen_name'        => 'Royal IMAX Hall',
                    'screen_type'        => 'IMAX',
                    'start_time'         => '18:30:00',
                    'end_time'           => '21:15:00',
                    'ticket_price'       => 1200.00,
                    'total_seats'        => 40,
                    'booked_seats_count' => 1
                ]
            ]
        ]
    ];
}

if (!$movie) {
    setFlash('booking_error', 'Invalid Movie: The requested movie title was not found in our catalog.', 'danger');
    redirect('movies.php');
}

$pageTitle = $movie['title'] . " - Movie Details & Showtimes";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<section class="movie-details-hero" style="background-image: url('<?= getMoviePoster($movie['poster_image'] ?? '', $movie['title']) ?>');">
    <div class="movie-details-overlay"></div>
    <div class="container position-relative z-1">
        <div class="row g-4 align-items-center">
            <div class="col-md-4 col-lg-3 text-center text-md-start">
                <img src="<?= getMoviePoster($movie['poster_image'] ?? '', $movie['title']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" class="movie-details-poster img-fluid">
            </div>

            <div class="col-md-8 col-lg-9 text-light">
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="badge <?= $movie['status'] === 'now_showing' ? 'bg-success' : 'bg-warning text-dark' ?> text-uppercase px-3 py-1">
                        <?= $movie['status'] === 'now_showing' ? 'Now Showing' : 'Coming Soon' ?>
                    </span>
                    <span class="text-warning fw-bold"><i class="fa-solid fa-star me-1"></i><?= htmlspecialchars($movie['rating']) ?> / 10 IMDb</span>
                    <span class="text-secondary">&bull;</span>
                    <span class="text-secondary"><i class="fa-regular fa-clock me-1 text-danger"></i><?= htmlspecialchars($movie['duration_minutes']) ?> minutes</span>
                    <span class="text-secondary">&bull;</span>
                    <span class="text-secondary"><i class="fa-solid fa-globe me-1 text-danger"></i><?= htmlspecialchars($movie['language'] ?? 'English') ?></span>
                </div>

                <h1 class="text-white display-4 fw-bold mb-3"><?= htmlspecialchars($movie['title']) ?></h1>

                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <?php 
                    $genres = explode(',', $movie['genre']);
                    foreach ($genres as $g): 
                    ?>
                        <span class="badge bg-dark border border-secondary text-light px-3 py-2"><?= htmlspecialchars(trim($g)) ?></span>
                    <?php endforeach; ?>
                </div>

                <p class="lead fs-6 text-light opacity-90 mb-4" style="max-width: 750px;">
                    <?= htmlspecialchars($movie['description']) ?>
                </p>

                <div class="d-flex flex-wrap gap-3">
                    <?php if ($movie['status'] === 'now_showing'): ?>
                        <a href="#showtimes" class="btn btn-cine-primary">
                            <i class="fa-solid fa-ticket me-2"></i> Select Cinema & Showtimes
                        </a>
                    <?php else: ?>
                        <button class="btn btn-outline-warning" disabled>
                            <i class="fa-solid fa-bell me-2"></i> Advance Booking Opening Soon
                        </button>
                    <?php endif; ?>

                    <a href="#trailer" class="btn btn-cine-outline">
                        <i class="fa-solid fa-play me-2 text-danger"></i> Official Trailer
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5" id="showtimes">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <span class="text-danger fw-bold small text-uppercase">Booking Flow: Movie &rarr; Cinema &rarr; Date &rarr; Time</span>
                        <h3 class="text-white fw-bold mb-0">Select Cinema & Showtimes</h3>
                    </div>
                </div>

                <div class="cine-card p-3 mb-4">
                    <span class="text-secondary small fw-semibold d-block mb-2">Step 1: Choose Show Date</span>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if (!empty($uniqueDates)): ?>
                            <?php foreach ($uniqueDates as $d): ?>
                                <?php 
                                $isToday = ($d === date('Y-m-d'));
                                $dayName = $isToday ? 'Today' : date('D', strtotime($d));
                                $isActive = ($d === $selectedDate);
                                ?>
                                <a href="<?= url('movie-details.php?id=' . $movie['id'] . '&date=' . $d . '#showtimes') ?>" 
                                   class="btn btn-sm <?= $isActive ? 'btn-danger' : 'btn-dark border-secondary text-light' ?> px-3 py-2 text-center" style="min-width: 100px;">
                                    <div class="small fw-bold"><?= $dayName ?></div>
                                    <div class="small opacity-75"><?= date('d M', strtotime($d)) ?></div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <a href="#" class="btn btn-sm btn-danger active px-3 py-2 text-center">
                                <div class="small fw-bold">Today</div>
                                <div class="small opacity-75"><?= date('d M') ?></div>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($cinemasWithShows)): ?>
                    <span class="text-secondary small fw-semibold d-block mb-3">Step 2: Choose Cinema Theatre & Showtime</span>
                    <div class="d-flex flex-column gap-4">
                        <?php foreach ($cinemasWithShows as $cData): ?>
                            <div class="cine-card p-4">
                                <div class="d-flex justify-content-between align-items-start border-bottom border-secondary pb-3 mb-3 flex-wrap gap-2">
                                    <div>
                                        <h5 class="text-white fw-bold mb-1">
                                            <i class="fa-solid fa-video text-danger me-2"></i>
                                            <?= htmlspecialchars($cData['cinema_name']) ?>
                                        </h5>
                                        <p class="text-secondary small mb-0">
                                            <i class="fa-solid fa-location-dot me-1 text-danger"></i> <?= htmlspecialchars($cData['cinema_location']) ?> (<?= htmlspecialchars($cData['city']) ?>)
                                        </p>
                                    </div>
                                    <span class="badge bg-dark border border-secondary text-light px-3 py-2">
                                        <?= count($cData['shows']) ?> Available Show(s)
                                    </span>
                                </div>

                                <div class="row g-3">
                                    <?php foreach ($cData['shows'] as $slot): ?>
                                        <div class="col-md-6">
                                            <div class="p-3 rounded bg-dark border border-secondary h-100 d-flex flex-column justify-content-between">
                                                <div>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="badge bg-danger-subtle text-danger border border-danger small">
                                                            <?= htmlspecialchars($slot['screen_name']) ?> &bull; <?= htmlspecialchars($slot['screen_type']) ?>
                                                        </span>
                                                        <span class="text-success fw-bold small">
                                                            <?= formatPrice($slot['ticket_price']) ?>
                                                        </span>
                                                    </div>
                                                    <div class="fs-5 text-white fw-bold mb-1">
                                                        <i class="fa-regular fa-clock text-danger me-1"></i>
                                                        <?= date('h:i A', strtotime($slot['start_time'])) ?>
                                                    </div>
                                                    <small class="text-secondary d-block mb-3">
                                                        Ends approx. <?= date('h:i A', strtotime($slot['end_time'])) ?>
                                                    </small>
                                                </div>

                                                <div>
                                                    <?php $bookUrl = url('user/select-seats.php?show_id=' . $slot['id']); ?>
                                                    <a href="<?= $bookUrl ?>" class="btn btn-cine-primary btn-sm w-100">
                                                        <i class="fa-solid fa-chair me-1"></i> Select Seats & Book
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="cine-card p-5 text-center">
                        <i class="fa-solid fa-calendar-xmark text-secondary fs-1 mb-3"></i>
                        <h5 class="text-white">No Shows Scheduled on <?= date('d M Y', strtotime($selectedDate)) ?></h5>
                        <p class="text-secondary small mb-4">Please choose another date above or browse other movies in theatres.</p>
                        <a href="<?= url('movies.php') ?>" class="btn btn-outline-light btn-sm">
                            <i class="fa-solid fa-film me-1"></i> Browse Other Movies
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="cine-card p-4 mb-4">
                    <h5 class="text-white fw-bold mb-3 border-bottom border-secondary pb-2">
                        <i class="fa-solid fa-circle-info text-danger me-2"></i> Movie Overview
                    </h5>
                    <ul class="list-unstyled small text-secondary d-flex flex-column gap-2 mb-0">
                        <li class="d-flex justify-content-between">
                            <span>Release Date:</span>
                            <span class="text-light fw-semibold"><?= date('d M Y', strtotime($movie['release_date'])) ?></span>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span>Audio Language:</span>
                            <span class="text-light fw-semibold"><?= htmlspecialchars($movie['language'] ?? 'English') ?></span>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span>Runtime:</span>
                            <span class="text-light fw-semibold"><?= htmlspecialchars($movie['duration_minutes']) ?> Minutes</span>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span>Age Classification:</span>
                            <span class="badge bg-dark border border-secondary text-warning">PG-13</span>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span>Screen Types:</span>
                            <span class="text-light fw-semibold">2D, 3D, IMAX</span>
                        </li>
                    </ul>
                </div>

                <div class="cine-card p-4" id="trailer">
                    <h5 class="text-white fw-bold mb-3 border-bottom border-secondary pb-2">
                        <i class="fa-solid fa-play text-danger me-2"></i> Official Trailer
                    </h5>
                    <?php if (!empty($movie['trailer_url'])): ?>
                        <div class="trailer-iframe-container rounded overflow-hidden mb-2">
                            <iframe src="<?= getYouTubeEmbedUrl($movie['trailer_url']) ?>" title="Movie Trailer" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    <?php else: ?>
                        <p class="text-secondary small mb-0">Trailer video preview unavailable.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
