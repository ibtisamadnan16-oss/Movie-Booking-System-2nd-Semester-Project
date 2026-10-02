<?php
$pageTitle = "Create an Account - Register";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$fullName = '';
$email = '';
$phone = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $fullName        = trim($_POST['full_name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($fullName)) {
        $errors['full_name'] = 'Full name is required.';
    }

    if (empty($email)) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters long.';
    }

    if (empty($confirmPassword)) {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

if (empty($errors)) {
        $dbStatus = checkDBStatus();
        if (!$dbStatus['status']) {
            $errors['general'] = 'Database connection error: ' . $dbStatus['message'] . '. Please ensure MySQL is running in XAMPP.';
        } else {
            try {
                $db = getDB();

$stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmtCheck->execute([$email]);
                if ($stmtCheck->fetch()) {
                    $errors['email'] = 'An account with this email address is already registered. Please login instead.';
                } else {
 
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$insertStmt = $db->prepare("INSERT INTO users (full_name, email, phone, password, status) VALUES (?, ?, ?, ?, 'active')");
                    $insertStmt->execute([$fullName, $email, $phone, $hashedPassword]);

setFlash('auth_success', 'Account registered successfully! Please login with your credentials.', 'success');
                    redirect('login.php');
                }
            } catch (Exception $e) {
                $errors['general'] = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container my-5 flex-grow-1">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="cine-card shadow-lg p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="brand-icon mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.4rem;">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <h3 class="fw-bold text-white">Create an Account</h3>
                    <p class="text-secondary small">Join CinePass to reserve seats and book movie tickets online</p>
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger py-2 small shadow-sm">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($errors['general']) ?>
                    </div>
                <?php endif; ?>

                <form action="<?= url('register.php') ?>" method="POST" novalidate>
                    <div class="mb-3">
                        <label class="form-label text-light small fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-user"></i></span>
                            <input type="text" name="full_name" class="form-control cine-form-control <?= isset($errors['full_name']) ? 'is-invalid border-danger' : '' ?>" placeholder="e.g. Ali Khan" value="<?= htmlspecialchars($fullName) ?>" required>
                        </div>
                        <?php if (isset($errors['full_name'])): ?>
                            <div class="text-danger small mt-1"><i class="fa-solid fa-circle-xmark me-1"></i><?= $errors['full_name'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-light small fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control cine-form-control <?= isset($errors['email']) ? 'is-invalid border-danger' : '' ?>" placeholder="name@example.com" value="<?= htmlspecialchars($email) ?>" required>
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <div class="text-danger small mt-1"><i class="fa-solid fa-circle-xmark me-1"></i><?= $errors['email'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-light small fw-semibold">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-phone"></i></span>
                            <input type="text" name="phone" class="form-control cine-form-control" placeholder="03001234567" value="<?= htmlspecialchars($phone) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-light small fw-semibold">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-key"></i></span>
                            <input type="password" name="password" id="regPassword" class="form-control cine-form-control <?= isset($errors['password']) ? 'is-invalid border-danger' : '' ?>" placeholder="Minimum 6 characters" required>
                            <button type="button" class="btn btn-dark border-secondary text-secondary" onclick="togglePasswordVisibility('regPassword')">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <div class="text-danger small mt-1"><i class="fa-solid fa-circle-xmark me-1"></i><?= $errors['password'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-light small fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-shield-check"></i></span>
                            <input type="password" name="confirm_password" id="regConfirmPassword" class="form-control cine-form-control <?= isset($errors['confirm_password']) ? 'is-invalid border-danger' : '' ?>" placeholder="Repeat your password" required>
                            <button type="button" class="btn btn-dark border-secondary text-secondary" onclick="togglePasswordVisibility('regConfirmPassword')">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="text-danger small mt-1"><i class="fa-solid fa-circle-xmark me-1"></i><?= $errors['confirm_password'] ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-cine-primary w-100 py-2 mb-3">
                        <i class="fa-solid fa-user-check me-2"></i> Register Account
                    </button>

                    <div class="text-center small text-secondary">
                        Already have an account? 
                        <a href="<?= url('login.php') ?>" class="text-danger fw-semibold text-decoration-none">Sign In</a>
                    </div>
                </form>
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
