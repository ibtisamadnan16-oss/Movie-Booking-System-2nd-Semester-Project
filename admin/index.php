<?php
 
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (isAdmin()) {
    redirect('admin/dashboard.php');
} else {
    redirect('admin/login.php');
}
