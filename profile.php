<?php
 
$pageTitle = "My Profile & Settings - CinePass";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$user = currentUser();
$userId = (int)$_SESSION['user_id'];

$dbStatus = checkDBStatus();
$userDetails = null;
$totalBookings = 0;
$upcomingShows = 0;
$totalSpent = 0;

if ($dbStatus['status']) {
    try {
        $db = getDB();

$uStmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $uStmt->execute([$userId]);
        $userDetails = $uStmt->fetch();

$bStmt = $db->prepare("
            SELECT 
                b.id,
                b.booking_status,
                b.total_amount,
                s.show_date,
                s.start_time
            FROM bookings b
            JOIN shows s ON b.show_id = s.id
            WHERE b.user_id = ?
        ");
        $bStmt->execute([$userId]);
        $bookings = $bStmt->fetchAll();

        $today = date('Y-m-d');
        $currentTime = date('H:i:s');

        foreach ($bookings as $b) {
            if ($b['booking_status'] === 'confirmed') {
                $totalBookings++;
                $totalSpent += (float)$b['total_amount'];

                $isFuture = ($b['show_date'] > $today) || ($b['show_date'] === $today && $b['start_time'] >= $currentTime);
                if ($isFuture) {
                    $upcomingShows++;
                }
            }
        }
    } catch (Exception $e) {
 
    }
}

$fullName = $userDetails['full_name'] ?? $user['name'] ?? '';
$email    = $userDetails['email'] ?? $user['email'] ?? '';
$phone    = $userDetails['phone'] ?? $user['phone'] ?? '';
$role     = $userDetails['role'] ?? $user['role'] ?? 'user';
$status   = $userDetails['status'] ?? 'active';
$createdAt= $userDetails['created_at'] ?? null;

$flashProfileSuccess  = getFlash('profile_success');
$flashProfileError    = getFlash('profile_error');
$flashPasswordSuccess = getFlash('password_success');
$flashPasswordError   = getFlash('password_error');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container my-4 my-lg-5 flex-grow-1">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= url('') ?>" class="text-secondary text-decoration-none"><i class="fa-solid fa-house me-1"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="<?= url('dashboard.php') ?>" class="text-secondary text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active text-danger" aria-current="page">Profile & Security</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="text-white fw-bold mb-1">
                <i class="fa-solid fa-user-gear text-danger me-2"></i> Account Profile & Settings
            </h2>
            <p class="text-secondary small mb-0">
                Manage your personal details, contact information, and security credentials.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('dashboard.php') ?>" class="btn btn-outline-light btn-sm">
                <i class="fa-solid fa-gauge me-1"></i> Dashboard Overview
            </a>
            <a href="<?= url('my-bookings.php') ?>" class="btn btn-cine-primary btn-sm">
                <i class="fa-solid fa-ticket me-1"></i> My Bookings
            </a>
        </div>
    </div>

    <?php if ($flashProfileSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($flashProfileSuccess['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($flashProfileError): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($flashProfileError['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($flashPasswordSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-lock me-2"></i> <?= htmlspecialchars($flashPasswordSuccess['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($flashPasswordError): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-shield-halved me-2"></i> <?= htmlspecialchars($flashPasswordError['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="cine-card p-4 text-center mb-4">
                <div class="brand-icon mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2.2rem; background: radial-gradient(circle, #e50914 0%, #171824 100%);">
                    <i class="fa-solid fa-user-astronaut text-white"></i>
                </div>
                <h4 class="text-white fw-bold mb-1"><?= htmlspecialchars($fullName) ?></h4>
                <p class="text-secondary small mb-3"><?= htmlspecialchars($email) ?></p>

                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-success-subtle text-success border border-success px-3 py-1">
                        <i class="fa-solid fa-check-circle me-1"></i> <?= ucfirst(htmlspecialchars($status)) ?>
                    </span>
                    <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-1 text-uppercase">
                        <?= htmlspecialchars($role) ?>
                    </span>
                </div>

                <hr class="border-secondary my-3">

                <div class="row g-2 text-center mb-3">
                    <div class="col-6">
                        <div class="p-2 rounded bg-black border border-secondary">
                            <div class="text-secondary small">Confirmed Shows</div>
                            <div class="text-white fw-bold fs-5"><?= $totalBookings ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded bg-black border border-secondary">
                            <div class="text-secondary small">Upcoming</div>
                            <div class="text-warning fw-bold fs-5"><?= $upcomingShows ?></div>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="p-2 rounded bg-black border border-secondary">
                            <div class="text-secondary small">Total Spent</div>
                            <div class="text-success fw-bold fs-5"><?= formatPrice($totalSpent) ?></div>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 text-start">
                    <a href="<?= url('dashboard.php') ?>" class="btn btn-dark border-secondary text-start">
                        <i class="fa-solid fa-gauge me-2 text-danger"></i> My Dashboard
                    </a>
                    <a href="<?= url('my-bookings.php') ?>" class="btn btn-dark border-secondary text-start">
                        <i class="fa-solid fa-ticket me-2 text-danger"></i> My Bookings
                    </a>
                    <a href="<?= url('movies.php') ?>" class="btn btn-dark border-secondary text-start">
                        <i class="fa-solid fa-film me-2 text-danger"></i> Browse Movies
                    </a>
                    <a href="<?= url('logout.php') ?>" class="btn btn-outline-danger text-start mt-2">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> Log Out
                    </a>
                </div>
            </div>

            <div class="cine-card p-4">
                <h6 class="text-white fw-bold mb-3 border-bottom border-secondary pb-2">
                    <i class="fa-solid fa-id-card text-danger me-2"></i> Membership Details
                </h6>
                <div class="d-flex flex-column gap-2 small">
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary">User ID:</span>
                        <strong class="text-light font-monospace">#USER-<?= str_pad($userId, 4, '0', STR_PAD_LEFT) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary">Account Created:</span>
                        <strong class="text-light"><?= !empty($createdAt) ? date('d M Y, h:i A', strtotime($createdAt)) : 'October 2026' ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary">Account Type:</span>
                        <strong class="text-danger">Standard Customer</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary">Payment Method:</span>
                        <strong class="text-light">Simulated Multi-Gateway</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="cine-card p-4 p-md-5 mb-4" id="personal-info">
                <div class="d-flex align-items-center gap-3 mb-4 border-bottom border-secondary pb-3">
                    <div class="rounded-circle bg-danger bg-opacity-25 text-danger d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; font-size: 1.25rem;">
                        <i class="fa-solid fa-user-pen"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-0">Personal Information</h4>
                        <small class="text-secondary">Update your full name, primary email address, and contact number</small>
                    </div>
                </div>

                <form action="<?= url('actions/auth_action.php') ?>" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label text-light small fw-semibold">
                                Full Name <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-secondary">
                                    <i class="fa-solid fa-user"></i>
                                </span>
                                <input type="text" 
                                       name="full_name" 
                                       class="form-control bg-dark border-secondary text-light ps-2" 
                                       placeholder="Your Full Name" 
                                       value="<?= htmlspecialchars($fullName) ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">
                                Email Address <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-secondary">
                                    <i class="fa-solid fa-envelope"></i>
                                </span>
                                <input type="email" 
                                       name="email" 
                                       class="form-control bg-dark border-secondary text-light ps-2" 
                                       placeholder="name@example.com" 
                                       value="<?= htmlspecialchars($email) ?>" 
                                       required>
                            </div>
                            <div class="form-text text-secondary small">Your account login & booking confirmations are sent here.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">
                                Phone Number <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-secondary">
                                    <i class="fa-solid fa-phone"></i>
                                </span>
                                <input type="tel" 
                                       name="phone" 
                                       class="form-control bg-dark border-secondary text-light ps-2" 
                                       placeholder="0300-1234567" 
                                       value="<?= htmlspecialchars($phone) ?>" 
                                       required>
                            </div>
                            <div class="form-text text-secondary small">Used for SMS e-ticket notifications.</div>
                        </div>

                        <div class="col-12 mt-4 text-end">
                            <button type="submit" class="btn btn-cine-primary px-4">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="cine-card p-4 p-md-5" id="security-settings">
                <div class="d-flex align-items-center gap-3 mb-4 border-bottom border-secondary pb-3">
                    <div class="rounded-circle bg-warning bg-opacity-25 text-warning d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; font-size: 1.25rem;">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold mb-0">Change Security Password</h4>
                        <small class="text-secondary">Keep your account secure with a strong and unique password</small>
                    </div>
                </div>

                <form action="<?= url('actions/auth_action.php') ?>" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="change_password">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-light small fw-semibold">
                                Current Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-secondary">
                                    <i class="fa-solid fa-key"></i>
                                </span>
                                <input type="password" 
                                       name="current_password" 
                                       class="form-control bg-dark border-secondary text-light ps-2" 
                                       placeholder="Enter your current password" 
                                       required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">
                                New Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-secondary">
                                    <i class="fa-solid fa-lock-open"></i>
                                </span>
                                <input type="password" 
                                       name="new_password" 
                                       class="form-control bg-dark border-secondary text-light ps-2" 
                                       placeholder="At least 6 characters" 
                                       minlength="6" 
                                       required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">
                                Confirm New Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-secondary">
                                    <i class="fa-solid fa-circle-check"></i>
                                </span>
                                <input type="password" 
                                       name="confirm_password" 
                                       class="form-control bg-dark border-secondary text-light ps-2" 
                                       placeholder="Re-enter new password" 
                                       minlength="6" 
                                       required>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="p-3 rounded bg-black border border-secondary small text-secondary">
                                <strong class="text-light d-block mb-1"><i class="fa-solid fa-circle-info text-warning me-1"></i> Password Requirements:</strong>
                                <ul class="mb-0 ps-3">
                                    <li>Minimum length of 6 characters.</li>
                                    <li>Both new password and confirmation must match exactly.</li>
                                    <li>You will stay signed in after updating your password.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-12 mt-4 text-end">
                            <button type="submit" class="btn btn-warning text-dark fw-bold px-4">
                                <i class="fa-solid fa-shield-halved me-1"></i> Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
