<?php
$adminTitle = "Edit Movie - Admin Panel";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($movieId <= 0) {
    setFlash('movie_error', 'Invalid movie identifier provided.', 'danger');
    redirect('admin/movies/index.php');
}

$dbStatus = checkDBStatus();
if (!$dbStatus['status']) {
    setFlash('movie_error', 'Database offline. Please check XAMPP MySQL.', 'danger');
    redirect('admin/movies/index.php');
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM movies WHERE id = ? LIMIT 1");
$stmt->execute([$movieId]);
$movie = $stmt->fetch();

if (!$movie) {
    setFlash('movie_error', 'Movie record not found.', 'danger');
    redirect('admin/movies/index.php');
}

$errors = [];
$title       = $movie['title'];
$genre       = $movie['genre'];
$duration    = (int)$movie['duration_minutes'];
$language    = $movie['language'] ?? 'English';
$releaseDate = $movie['release_date'];
$rating      = (float)$movie['rating'];
$trailerUrl  = $movie['trailer_url'] ?? '';
$status      = $movie['status'];
$description = $movie['description'];
$posterImage = $movie['poster_image'] ?? '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $genre       = trim($_POST['genre'] ?? '');
    $duration    = (int)($_POST['duration_minutes'] ?? 0);
    $language    = trim($_POST['language'] ?? 'English');
    $releaseDate = trim($_POST['release_date'] ?? '');
    $rating      = (float)($_POST['rating'] ?? 8.0);
    $trailerUrl  = trim($_POST['trailer_url'] ?? '');
    $status      = trim($_POST['status'] ?? 'now_showing');
    $description = trim($_POST['description'] ?? '');
    $posterUrl   = trim($_POST['poster_url'] ?? '');

if (empty($title)) {
        $errors['title'] = 'Movie title is required.';
    }

    if (empty($genre)) {
        $errors['genre'] = 'Genre is required.';
    }

    if ($duration <= 0) {
        $errors['duration_minutes'] = 'Please provide a valid duration in minutes.';
    }

    if (empty($releaseDate)) {
        $errors['release_date'] = 'Release date is required.';
    }

    if (empty($description)) {
        $errors['description'] = 'Movie description / synopsis is required.';
    }

$newPoster = $posterImage;
    if (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp  = $_FILES['poster_file']['tmp_name'];
        $fileName = $_FILES['poster_file']['name'];
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($fileExt, $allowedExts)) {
            $newFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $fileName);
            $destination = ASSETS_PATH . 'images' . DIRECTORY_SEPARATOR . 'posters' . DIRECTORY_SEPARATOR . $newFileName;
            
            if (move_uploaded_file($fileTmp, $destination)) {
                $newPoster = $newFileName;
            } else {
                $errors['poster'] = 'Failed to upload new poster file.';
            }
        } else {
            $errors['poster'] = 'Invalid file format. Allowed formats: JPG, PNG, WEBP.';
        }
    } elseif (!empty($posterUrl)) {
        $newPoster = $posterUrl;
    }

    if (empty($errors)) {
        try {
            $updateStmt = $db->prepare("UPDATE movies SET 
                                        title = ?, 
                                        description = ?, 
                                        genre = ?, 
                                        language = ?, 
                                        duration_minutes = ?, 
                                        release_date = ?, 
                                        rating = ?, 
                                        poster_image = ?, 
                                        trailer_url = ?, 
                                        status = ? 
                                        WHERE id = ?");
            $updateStmt->execute([
                $title,
                $description,
                $genre,
                $language,
                $duration,
                $releaseDate,
                $rating,
                $newPoster,
                $trailerUrl,
                $status,
                $movieId
            ]);

            setFlash('movie_success', "Movie '" . htmlspecialchars($title) . "' has been updated successfully!", 'success');
            redirect('admin/movies/index.php');
        } catch (Exception $e) {
            $errors['general'] = 'Failed to update movie: ' . $e->getMessage();
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
                    <h3 class="text-white fw-bold mb-1"><i class="fa-solid fa-pen-to-square text-warning me-2"></i> Edit Movie</h3>
                    <p class="text-secondary small mb-0">Update metadata, posters, or show status for ID: #<?= htmlspecialchars($movieId) ?></p>
                </div>
                <div>
                    <a href="<?= url('admin/movies/index.php') ?>" class="btn btn-outline-light btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Movies
                    </a>
                </div>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger py-2 small shadow-sm mb-4">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($errors['general']) ?>
                </div>
            <?php endif; ?>

            <div class="cine-card p-4 p-md-5">
                <form action="<?= url('admin/movies/edit.php?id=' . $movieId) ?>" method="POST" enctype="multipart/form-data">
                    <div class="row g-4">
                        <div class="col-md-3 text-center">
                            <label class="form-label text-light small fw-semibold d-block">Current Poster</label>
                            <img src="<?= getMoviePoster($posterImage, $title) ?>" alt="<?= htmlspecialchars($title) ?>" class="rounded shadow mb-2" style="max-width: 140px; height: 190px; object-fit: cover;">
                            <div class="text-secondary small text-truncate"><?= htmlspecialchars($posterImage ?: 'Default Image') ?></div>
                        </div>

                        <div class="col-md-9">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label text-light small fw-semibold">Movie Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control cine-form-control" value="<?= htmlspecialchars($title) ?>" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label text-light small fw-semibold">Release Status <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select cine-form-control">
                                        <option value="now_showing" <?= $status === 'now_showing' ? 'selected' : '' ?>>Now Showing</option>
                                        <option value="upcoming" <?= $status === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                                        <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label text-light small fw-semibold">Genre(s) <span class="text-danger">*</span></label>
                                    <input type="text" name="genre" class="form-control cine-form-control" value="<?= htmlspecialchars($genre) ?>" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label text-light small fw-semibold">Language</label>
                                    <input type="text" name="language" class="form-control cine-form-control" value="<?= htmlspecialchars($language) ?>">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label text-light small fw-semibold">Duration (Mins) <span class="text-danger">*</span></label>
                                    <input type="number" name="duration_minutes" class="form-control cine-form-control" value="<?= htmlspecialchars($duration) ?>" required min="1">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">Release Date <span class="text-danger">*</span></label>
                            <input type="date" name="release_date" class="form-control cine-form-control" value="<?= htmlspecialchars($releaseDate) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">IMDb Rating</label>
                            <input type="number" step="0.1" name="rating" class="form-control cine-form-control" value="<?= htmlspecialchars($rating) ?>" min="0" max="10">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-light small fw-semibold">YouTube Trailer Link</label>
                            <input type="url" name="trailer_url" class="form-control cine-form-control" value="<?= htmlspecialchars($trailerUrl) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Replace Poster File</label>
                            <input type="file" name="poster_file" class="form-control cine-form-control" accept="image/*">
                            <small class="text-secondary">Leave empty to keep existing poster image.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Or Update Poster URL/Name</label>
                            <input type="text" name="poster_url" class="form-control cine-form-control" placeholder="oppenheimer.jpg or https://..." value="<?= htmlspecialchars($posterImage) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label text-light small fw-semibold">Movie Synopsis / Storyline <span class="text-danger">*</span></label>
                            <textarea name="description" rows="4" class="form-control cine-form-control" required><?= htmlspecialchars($description) ?></textarea>
                        </div>

                        <div class="col-12 pt-3 border-top border-secondary d-flex gap-3">
                            <button type="submit" class="btn btn-warning fw-bold px-4">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Update Movie
                            </button>
                            <a href="<?= url('admin/movies/index.php') ?>" class="btn btn-outline-secondary px-4">
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
