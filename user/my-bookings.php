<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
$tab = $_GET['tab'] ?? '';
$target = 'my-bookings.php' . ($tab ? '?tab=' . urlencode($tab) : '');
redirect($target);
