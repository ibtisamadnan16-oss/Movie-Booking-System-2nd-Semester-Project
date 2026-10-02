<?php
 
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isAdmin()) {
    redirect('admin/dashboard.php');
}

$adminTitle = "Administrator Sign In";
$flashError   = getFlash('admin_error');
$flashSuccess = getFlash('admin_success');

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5 flex-grow-1 d-flex align-items-center">
    <div class="row justify-content-center w-100">
        <div class="col-md-7 col-lg-5 col-xl-4">
            <div class="cine-card shadow-lg p-4 p-md-5 border border-secondary border-opacity-25 position-relative overflow-hidden">
                <div class="position-absolute top-0 start-0 w-100" style="height: 3px; background: #e50914;"></div>

                <div class="text-center mb-4">
                    <div class="brand-icon mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.4rem; background: #1f2333; color: #cbd5e1; border: 1px solid #333a4d;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <span class="badge bg-dark border border-secondary text-secondary fw-semibold px-3 py-1 text-uppercase mb-2">
                        <i class="fa-solid fa-lock me-1"></i> Admin Portal
                    </span>
                    <h3 class="fw-bold text-white mb-1">Cine<span class="text-danger">Pass</span> Control</h3>
                    <p class="text-secondary small mb-0">Authorized cinema management & administration system</p>
                </div>

                <?php if ($flashError): ?>
                    <div class="alert alert-danger py-2 small d-flex align-items-center gap-2 mb-3" role="alert">
                        <i class="fa-solid fa-circle-exclamation fs-5 flex-shrink-0"></i>
                        <div><?= htmlspecialchars($flashError['message']) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($flashSuccess): ?>
                    <div class="alert alert-success py-2 small d-flex align-items-center gap-2 mb-3" role="alert">
                        <i class="fa-solid fa-circle-check fs-5 flex-shrink-0"></i>
                        <div><?= htmlspecialchars($flashSuccess['message']) ?></div>
                    </div>
                <?php endif; ?>

                <form action="<?= url('actions/auth_action.php') ?>" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="admin_login">

                    <div class="mb-3">
                        <label class="form-label text-light small fw-semibold">
                            Admin Email <span class="text-danger">*</span>
                        </label>
                        <div class="input-group has-validation">
                            <span class="input-group-text bg-dark border-secondary text-secondary">
                                <i class="fa-solid fa-user-shield"></i>
                            </span>
                            <input type="email" 
                                   name="email" 
                                   class="form-control bg-dark border-secondary text-light ps-2" 
                                   placeholder="admin@moviebooking.com" 
                                   required 
                                   autocomplete="username">
                            <div class="invalid-feedback">Please provide a valid administrator email.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-light small fw-semibold">
                            Security Password <span class="text-danger">*</span>
                        </label>
                        <div class="input-group has-validation">
                            <span class="input-group-text bg-dark border-secondary text-secondary">
                                <i class="fa-solid fa-key"></i>
                            </span>
                            <input type="password" 
                                   name="password" 
                                   class="form-control bg-dark border-secondary text-light ps-2" 
                                   placeholder="Enter admin password" 
                                   required 
                                   autocomplete="current-password">
                            <div class="invalid-feedback">Please enter your security password.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-danger w-100 fw-semibold py-2 mb-3 shadow-sm">
                        <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Access Admin Console
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top border-secondary">
                    <a href="<?= url('') ?>" class="text-secondary text-decoration-none small">
                        <i class="fa-solid fa-arrow-left me-1"></i> Return to CinePass Website
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
