<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect('user/dashboard.php');
} else {
    redirect('user/login.php');
}
