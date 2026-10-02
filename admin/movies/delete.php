<?php

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
    setFlash('movie_error', 'Database offline. Could not delete record.', 'danger');
    redirect('admin/movies/index.php');
}

try {
    $db = getDB();

$stmt = $db->prepare("SELECT title, poster_image FROM movies WHERE id = ? LIMIT 1");
    $stmt->execute([$movieId]);
    $movie = $stmt->fetch();

    if ($movie) {
        $title = $movie['title'];
        $posterFile = $movie['poster_image'];

$delStmt = $db->prepare("DELETE FROM movies WHERE id = ?");
        $delStmt->execute([$movieId]);

if (!empty($posterFile)) {
            $filePath = ASSETS_PATH . 'images' . DIRECTORY_SEPARATOR . 'posters' . DIRECTORY_SEPARATOR . $posterFile;
            if (file_exists($filePath) && !in_array($posterFile, ['oppenheimer.jpg', 'dune2.jpg', 'interstellar.jpg', 'gladiator2.jpg'])) {
                @unlink($filePath);
            }
        }

        setFlash('movie_success', "Movie '" . htmlspecialchars($title) . "' was deleted successfully.", 'success');
    } else {
        setFlash('movie_error', 'Movie record not found.', 'danger');
    }
} catch (Exception $e) {
    setFlash('movie_error', 'Failed to delete movie: ' . $e->getMessage(), 'danger');
}

redirect('admin/movies/index.php');
