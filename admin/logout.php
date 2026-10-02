<?php
 
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_role']);

if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    unset($_SESSION['user_role']);
}

setFlash('admin_success', 'You have been safely signed out from the Administrator Console.', 'info');
redirect('admin/login.php');
