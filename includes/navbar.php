<?php
 
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark cine-navbar sticky-top shadow">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= url('') ?>">
            <span class="brand-icon"><i class="fa-solid fa-film"></i></span>
            <span>Cine<span class="text-danger">Pass</span></span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'index.php' || $currentPage === '') ? 'active' : '' ?>" href="<?= url('') ?>">
                        <i class="fa-solid fa-house me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'movies.php' || $currentPage === 'movie-details.php') ? 'active' : '' ?>" href="<?= url('movies.php') ?>">
                        <i class="fa-solid fa-film me-1"></i> Movies
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'booking.php' || $currentPage === 'booking-summary.php' || $currentPage === 'select-seats.php') ? 'active text-danger fw-bold' : '' ?>" href="<?= url('booking.php') ?>">
                        <i class="fa-solid fa-ticket me-1"></i> Book Tickets
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'about.php') ? 'active' : '' ?>" href="<?= url('about.php') ?>">
                        <i class="fa-solid fa-circle-info me-1"></i> About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'contact.php') ? 'active' : '' ?>" href="<?= url('contact.php') ?>">
                        <i class="fa-solid fa-envelope me-1"></i> Contact
                    </a>
                </li>
            </ul>

            <form action="<?= url('movies.php') ?>" method="GET" class="d-flex align-items-center me-lg-3 my-2 my-lg-0 navbar-search-form">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control bg-dark border-secondary text-light ps-3" placeholder="Search movie, genre, language..." aria-label="Search" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <button class="btn btn-outline-danger" type="submit" title="Search Movies">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
            </form>

            <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
                <?php if ($user): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-user-circle text-danger"></i>
                            <span><?= htmlspecialchars($user['name']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow">
                            <?php if (isAdmin()): ?>
                                <li><a class="dropdown-item text-warning fw-bold" href="<?= url('admin/dashboard.php') ?>"><i class="fa-solid fa-shield-halved me-2"></i> Admin Panel</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="<?= url('dashboard.php') ?>"><i class="fa-solid fa-gauge me-2"></i> My Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?= url('my-bookings.php') ?>"><i class="fa-solid fa-ticket me-2"></i> My Bookings</a></li>
                                <li><a class="dropdown-item" href="<?= url('profile.php') ?>"><i class="fa-solid fa-user-gear me-2"></i> My Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger" href="<?= url('logout.php') ?>"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= url('login.php') ?>" class="btn btn-sm btn-outline-light px-3">
                        <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                    </a>
                    <a href="<?= url('register.php') ?>" class="btn btn-sm btn-danger px-3">
                        <i class="fa-solid fa-user-plus me-1"></i> Register
                    </a>
                    <a href="<?= url('admin/login.php') ?>" class="btn btn-sm btn-outline-warning ms-1" title="Admin Portal">
                        <i class="fa-solid fa-shield-halved me-1"></i> Admin
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
