<?php
$pageTitle = "Sign In - User Login";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$email = '';

$flashSuccess = getFlash('auth_success');
$flashError   = getFlash('auth_error');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $dbStatus = checkDBStatus();
        if (!$dbStatus['status']) {
            $error = 'Database connection error: ' . $dbStatus['message'] . '. Please ensure MySQL is running in XAMPP.';
        } else {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] !== 'active') {
                        $error = 'Your account has been deactivated. Please contact cinema administration.';
                    } else {
 
                        session_regenerate_id(true);
                        $_SESSION['user_id']    = $user['id'];
                        $_SESSION['user_name']  = $user['full_name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_phone'] = $user['phone'] ?? '';
                        $_SESSION['user_role']  = 'user';

setFlash('dashboard_msg', 'Welcome back, ' . htmlspecialchars($user['full_name']) . '! You have signed in successfully.', 'success');

$redirectUrl = 'dashboard.php';
                        if (!empty($_SESSION['redirect_url'])) {
                            $redirectUrl = $_SESSION['redirect_url'];
                            unset($_SESSION['redirect_url']);
                        }

                        redirect($redirectUrl);
                    }
                } else {
                    $error = 'Invalid email address or password. Please try again.';
                }
            } catch (Exception $e) {
                $error = 'Login error: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container my-5 flex-grow-1">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="cine-card shadow-lg p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="brand-icon mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.4rem;">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <h3 class="fw-bold text-white">Welcome Back</h3>
                    <p class="text-secondary small">Sign in to book tickets, manage reservations, and access your profile</p>
                </div>

                <?php if ($flashSuccess): ?>
                    <div class="alert alert-success py-2 small shadow-sm mb-3">
                        <i class="fa-solid fa-circle-check me-1"></i> <?= htmlspecialchars($flashSuccess['message']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($flashError): ?>
                    <div class="alert alert-warning py-2 small shadow-sm mb-3">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($flashError['message']) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small shadow-sm mb-3">
                        <i class="fa-solid fa-circle-xmark me-1"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form action="<?= url('login.php') ?>" method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label text-light small fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control cine-form-control" placeholder="user@example.com" value="<?= htmlspecialchars($email) ?>" required autofocus>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <label class="form-label text-light small fw-semibold">Password <span class="text-danger">*</span></label>
                        </div>
                        <div class="input-group has-validation">
                            <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-key"></i></span>
                            <input type="password" name="password" id="loginPassword" class="form-control cine-form-control" placeholder="Enter your password" required>
                            <button type="button" class="btn btn-dark border-secondary text-secondary" onclick="togglePasswordVisibility('loginPassword')">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                            <div class="invalid-feedback">Please enter your password.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-cine-primary w-100 py-2 mb-3">
                        <i class="fa-solid fa-right-to-bracket me-2"></i> Sign In to Account
                    </button>

                    <div class="text-center small text-secondary">
                        Don't have an account yet? 
                        <a href="<?= url('register.php') ?>" class="text-danger fw-semibold text-decoration-none">Create Account</a>
                    </div>
                </form>

                <div class="mt-4 pt-3 border-top border-secondary opacity-75 small text-center text-muted">
                    <i class="fa-solid fa-circle-info me-1"></i> Pre-seeded testing account: <br>
                    <strong>Email:</strong> <code>user@example.com</code> &bull; <strong>Pass:</strong> <code>user123</code>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility(id) {
    const input = document.getElementById(id);
    if (input) {
        input.type = input.type === 'password' ? 'text' : 'password';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
