<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$cinemaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($cinemaId <= 0) {
    setFlash('cinema_error', 'Invalid cinema identifier.', 'danger');
    redirect('admin/cinemas/index.php');
}

$dbStatus = checkDBStatus();
if (!$dbStatus['status']) {
    setFlash('cinema_error', 'Database offline. Could not delete record.', 'danger');
    redirect('admin/cinemas/index.php');
}

try {
    $db = getDB();

    $stmt = $db->prepare("SELECT name FROM cinemas WHERE id = ? LIMIT 1");
    $stmt->execute([$cinemaId]);
    $cinema = $stmt->fetch();

    if ($cinema) {
        $name = $cinema['name'];

        $delStmt = $db->prepare("DELETE FROM cinemas WHERE id = ?");
        $delStmt->execute([$cinemaId]);

        setFlash('cinema_success', "Cinema '" . htmlspecialchars($name) . "' and its associated screens were deleted successfully.", 'success');
    } else {
        setFlash('cinema_error', 'Cinema record not found.', 'danger');
    }
} catch (Exception $e) {
    setFlash('cinema_error', 'Failed to delete cinema: ' . $e->getMessage(), 'danger');
}

redirect('admin/cinemas/index.php');
