<?php
 
$currentPage = basename($_SERVER['PHP_SELF']);
$currentUri  = $_SERVER['PHP_SELF'];

$isDashboardSection = ($currentPage === 'dashboard.php');
$isMovieSection     = (strpos($currentUri, '/admin/movies/') !== false || $currentPage === 'movies.php');
$isCinemaSection    = (strpos($currentUri, '/admin/cinemas/') !== false || $currentPage === 'cinemas.php');
$isScreenSection    = (strpos($currentUri, '/admin/screens/') !== false || $currentPage === 'screens.php');
$isShowSection      = (strpos($currentUri, '/admin/shows/') !== false || $currentPage === 'shows.php');
$isBookingSection   = ($currentPage === 'bookings.php');
$isUserSection      = (strpos($currentUri, '/admin/users/') !== false || $currentPage === 'users.php');
$isReportSection    = ($currentPage === 'reports.php');
?>
<div class="cine-card admin-sidebar-card p-3 mb-4 shadow">
    <div class="d-flex align-items-center gap-3 p-2 mb-3 border-bottom border-secondary border-opacity-25 admin-user-profile-badge">
        <div class="brand-icon" style="width: 38px; height: 38px; font-size: 1rem; background: #232736; color: #cbd5e1; border: 1px solid #333a4d;">
            <i class="fa-solid fa-user-shield"></i>
        </div>
        <div class="overflow-hidden">
            <div class="text-white fw-bold small text-truncate"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator') ?></div>
            <div class="text-secondary small" style="font-size: 0.75rem;"><i class="fa-solid fa-circle text-success me-1" style="font-size: 7px;"></i> <?= ucfirst(htmlspecialchars($_SESSION['admin_role'] ?? 'Admin')) ?> Active</div>
        </div>
    </div>
    
    <div class="nav flex-column nav-pills gap-1 admin-sidebar-nav">
        <a class="nav-link <?= $isDashboardSection ? 'active' : '' ?>" href="<?= url('admin/dashboard.php') ?>">
            <i class="fa-solid fa-gauge me-2"></i> Dashboard
        </a>
        <a class="nav-link <?= $isMovieSection ? 'active' : '' ?>" href="<?= url('admin/movies/index.php') ?>">
            <i class="fa-solid fa-film me-2"></i> Movies
        </a>
        <a class="nav-link <?= $isCinemaSection ? 'active' : '' ?>" href="<?= url('admin/cinemas/index.php') ?>">
            <i class="fa-solid fa-video me-2"></i> Cinemas
        </a>
        <a class="nav-link <?= $isScreenSection ? 'active' : '' ?>" href="<?= url('admin/screens/index.php') ?>">
            <i class="fa-solid fa-tv me-2"></i> Screens
        </a>
        <a class="nav-link <?= $isShowSection ? 'active' : '' ?>" href="<?= url('admin/shows/index.php') ?>">
            <i class="fa-solid fa-calendar-check me-2"></i> Shows
        </a>
        <a class="nav-link <?= $isBookingSection ? 'active' : '' ?>" href="<?= url('admin/bookings.php') ?>">
            <i class="fa-solid fa-ticket me-2"></i> Bookings
        </a>
        <a class="nav-link <?= $isUserSection ? 'active' : '' ?>" href="<?= url('admin/users/index.php') ?>">
            <i class="fa-solid fa-users me-2"></i> Users
        </a>
        <a class="nav-link <?= $isReportSection ? 'active' : '' ?>" href="<?= url('admin/reports.php') ?>">
            <i class="fa-solid fa-chart-line me-2"></i> Reports
        </a>

        <hr class="border-secondary my-2">

        <a class="nav-link text-danger" href="<?= url('admin/logout.php') ?>">
            <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
        </a>
    </div>
</div>
