<?php

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
    setFlash('show_error', 'Database offline. Could not delete show record.', 'danger');
    redirect('admin/shows/index.php');
}

try {
    $db = getDB();

    $stmt = $db->prepare("SELECT s.id, m.title, s.show_date, s.start_time 
                          FROM shows s 
                          JOIN movies m ON s.movie_id = m.id 
                          WHERE s.id = ? LIMIT 1");
    $stmt->execute([$showId]);
    $show = $stmt->fetch();

    if ($show) {
        $delStmt = $db->prepare("DELETE FROM shows WHERE id = ?");
        $delStmt->execute([$showId]);

        setFlash('show_success', "Show for '" . htmlspecialchars($show['title']) . "' on " . date('d M', strtotime($show['show_date'])) . " at " . date('h:i A', strtotime($show['start_time'])) . " was deleted successfully.", 'success');
    } else {
        setFlash('show_error', 'Show record not found.', 'danger');
    }
} catch (Exception $e) {
    setFlash('show_error', 'Failed to delete show: ' . $e->getMessage(), 'danger');
}

redirect('admin/shows/index.php');
