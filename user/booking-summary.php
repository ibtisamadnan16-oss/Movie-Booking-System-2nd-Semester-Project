<?php
 
require_once __DIR__ . '/../config/config.php';
$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: " . url('booking-summary.php' . $queryString));
exit;
