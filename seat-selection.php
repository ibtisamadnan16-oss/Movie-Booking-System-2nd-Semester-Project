<?php
 
require_once __DIR__ . '/config/config.php';
$showId = isset($_GET['show_id']) ? (int)$_GET['show_id'] : 0;
header("Location: " . url('user/select-seats.php' . ($showId > 0 ? '?show_id=' . $showId : '')));
exit;
