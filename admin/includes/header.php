<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$adminTitle = isset($adminTitle) ? $adminTitle . " | Admin Panel" : "Admin Panel | " . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($adminTitle) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/responsive.css') ?>">
</head>
<body class="cine-body">
<nav class="navbar navbar-dark bg-black border-bottom border-secondary px-3 sticky-top">
    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= url('admin/dashboard.php') ?>">
        <span class="brand-icon" style="width: 32px; height: 32px; font-size: 0.9rem;"><i class="fa-solid fa-shield-halved"></i></span>
        <span>Cine<span class="text-danger">Pass</span> <small class="badge bg-secondary text-light ms-1">Admin</small></span>
    </a>
    <div class="d-flex align-items-center gap-2">
        <span class="text-secondary small d-none d-md-inline">
            <i class="fa-solid fa-user-shield text-secondary me-1"></i>
            <span class="text-light fw-semibold"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator') ?></span>
        </span>
        <a href="<?= url('') ?>" class="btn btn-outline-light btn-sm" target="_blank">
            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View Website
        </a>
        <a href="<?= url('admin/logout.php') ?>" class="btn btn-danger btn-sm">
            <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
        </a>
    </div>
</nav>
