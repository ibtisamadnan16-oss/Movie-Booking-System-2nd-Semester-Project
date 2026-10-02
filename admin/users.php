<?php
 
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$qs = $_SERVER['QUERY_STRING'] ?? '';
$target = 'admin/users/index.php' . ($qs ? '?' . $qs : '');
redirect($target);
