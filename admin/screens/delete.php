<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$screenId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($screenId <= 0) {
    setFlash('screen_error', 'Invalid screen identifier.', 'danger');
    redirect('admin/screens/index.php');
}

$dbStatus = checkDBStatus();
if (!$dbStatus['status']) {
    setFlash('screen_error', 'Database offline. Could not delete screen.', 'danger');
    redirect('admin/screens/index.php');
}

try {
    $db = getDB();

    $stmt = $db->prepare("SELECT screen_name FROM screens WHERE id = ? LIMIT 1");
    $stmt->execute([$screenId]);
    $screen = $stmt->fetch();

    if ($screen) {
        $screenName = $screen['screen_name'];

        $delStmt = $db->prepare("DELETE FROM screens WHERE id = ?");
        $delStmt->execute([$screenId]);

        setFlash('screen_success', "Screen '" . htmlspecialchars($screenName) . "' and its generated seat layout were deleted successfully.", 'success');
    } else {
        setFlash('screen_error', 'Screen record not found.', 'danger');
    }
} catch (Exception $e) {
    setFlash('screen_error', 'Failed to delete screen: ' . $e->getMessage(), 'danger');
}

redirect('admin/screens/index.php');
