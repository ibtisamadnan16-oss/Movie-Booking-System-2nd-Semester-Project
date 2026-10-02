<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin('user');
        break;

    case 'admin_login':
        handleLogin('admin');
        break;

    case 'register':
        handleRegister();
        break;

    case 'update_profile':
        handleUpdateProfile();
        break;

    case 'change_password':
        handleChangePassword();
        break;

    case 'logout':
        handleLogout();
        break;

    case 'toggle_user_status':
        handleToggleUserStatus();
        break;

    default:
        redirect('');
        break;
}

function handleLogin($targetRole = 'user') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $redirectPath = ($targetRole === 'admin') ? 'admin/login.php' : 'login.php';
    $flashKey = ($targetRole === 'admin') ? 'admin_error' : 'auth_error';

    if (empty($email) || empty($password)) {
        setFlash($flashKey, 'Please fill in all required credentials.', 'danger');
        redirect($redirectPath);
    }

    $dbStatus = checkDBStatus();
    if (!$dbStatus['status']) {
        setFlash($flashKey, 'Database is not yet connected. Please import database/movie_booking.sql in phpMyAdmin.', 'danger');
        redirect($redirectPath);
    }

    try {
        $db = getDB();

        if ($targetRole === 'admin') {
            $stmt = $db->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                if ($admin['status'] !== 'active') {
                    setFlash($flashKey, 'Your admin account is inactive.', 'warning');
                    redirect($redirectPath);
                }

                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['user_role'] = 'admin';

                setFlash('admin_success', 'Welcome back, ' . htmlspecialchars($admin['full_name']) . '! You have accessed the Administrator Console.', 'success');
                redirect('admin/dashboard.php');
            } else {
                setFlash($flashKey, 'Invalid admin email address or password.', 'danger');
                redirect($redirectPath);
            }
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    setFlash($flashKey, 'Your account has been deactivated. Please contact support.', 'warning');
                    redirect($redirectPath);
                }

                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_phone'] = $user['phone'] ?? '';
                $_SESSION['user_role'] = 'user';

                setFlash('dashboard_msg', 'Welcome back, ' . htmlspecialchars($user['full_name']) . '! You have signed in successfully.', 'success');
                redirect('dashboard.php');
            } else {
                setFlash($flashKey, 'Invalid email address or password.', 'danger');
                redirect($redirectPath);
            }
        }
    } catch (Exception $e) {
        setFlash($flashKey, 'System error: ' . $e->getMessage(), 'danger');
        redirect($redirectPath);
    }
}

function handleRegister() {
    $fullName        = trim($_POST['full_name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($fullName) || empty($email) || empty($password)) {
        setFlash('auth_error', 'Please fill in all required fields.', 'danger');
        redirect('register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('auth_error', 'Invalid Email: Please provide a valid email address.', 'danger');
        redirect('register.php');
    }

    if (strlen($password) < 6) {
        setFlash('auth_error', 'Password must be at least 6 characters long.', 'danger');
        redirect('register.php');
    }

    if ($password !== $confirmPassword) {
        setFlash('auth_error', 'Passwords do not match. Please re-enter your password confirmation.', 'danger');
        redirect('register.php');
    }

    $dbStatus = checkDBStatus();
    if (!$dbStatus['status']) {
        setFlash('auth_error', 'Database is not yet connected. Please import database/movie_booking.sql in phpMyAdmin.', 'danger');
        redirect('register.php');
    }

    try {
        $db = getDB();

$stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            setFlash('auth_error', 'An account with this email address already exists. Please login.', 'danger');
            redirect('register.php');
        }

$hashed = password_hash($password, PASSWORD_BCRYPT);
        $insertStmt = $db->prepare("INSERT INTO users (full_name, email, phone, password, status) VALUES (?, ?, ?, ?, 'active')");
        $insertStmt->execute([$fullName, $email, $phone, $hashed]);

        setFlash('auth_success', 'Account registered successfully! Please login with your credentials.', 'success');
        redirect('login.php');
    } catch (Exception $e) {
        setFlash('auth_error', 'Registration error: ' . $e->getMessage(), 'danger');
        redirect('register.php');
    }
}

function handleLogout() {
    $target = $_GET['target'] ?? '';
    if ($target === 'admin' || (isAdmin() && !isLoggedIn())) {
        redirect('admin/logout.php');
    }
    redirect('logout.php');
}

function handleUpdateProfile() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }

    $userId   = (int)$_SESSION['user_id'];
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');

    if (empty($fullName) || empty($email) || empty($phone)) {
        setFlash('profile_error', 'Please fill in all profile fields.', 'danger');
        redirect('profile.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('profile_error', 'Please provide a valid email address.', 'danger');
        redirect('profile.php');
    }

    $dbStatus = checkDBStatus();
    if (!$dbStatus['status']) {
        setFlash('profile_error', 'Database offline.', 'danger');
        redirect('profile.php');
    }

    try {
        $db = getDB();

$chk = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $chk->execute([$email, $userId]);
        if ($chk->fetch()) {
            setFlash('profile_error', 'This email address is already registered to another account.', 'danger');
            redirect('profile.php');
        }

        $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$fullName, $email, $phone, $userId]);

$_SESSION['user_name']  = $fullName;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_phone'] = $phone;

        setFlash('profile_success', 'Your profile information has been updated successfully.', 'success');
        redirect('profile.php');

    } catch (Exception $e) {
        setFlash('profile_error', 'Failed to update profile: ' . $e->getMessage(), 'danger');
        redirect('profile.php');
    }
}

function handleChangePassword() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }

    $userId          = (int)$_SESSION['user_id'];
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        setFlash('password_error', 'Please fill in all password fields.', 'danger');
        redirect('profile.php');
    }

    if ($newPassword !== $confirmPassword) {
        setFlash('password_error', 'New password and confirmation password do not match.', 'danger');
        redirect('profile.php');
    }

    if (strlen($newPassword) < 6) {
        setFlash('password_error', 'New password must be at least 6 characters long.', 'danger');
        redirect('profile.php');
    }

    $dbStatus = checkDBStatus();
    if (!$dbStatus['status']) {
        setFlash('password_error', 'Database offline.', 'danger');
        redirect('profile.php');
    }

    try {
        $db = getDB();

$stmt = $db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPassword, $row['password'])) {
            setFlash('password_error', 'The current password you entered is incorrect.', 'danger');
            redirect('profile.php');
        }

$newHashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $upd = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->execute([$newHashed, $userId]);

        setFlash('password_success', 'Your security password has been changed successfully.', 'success');
        redirect('profile.php');

    } catch (Exception $e) {
        setFlash('password_error', 'Password update error: ' . $e->getMessage(), 'danger');
        redirect('profile.php');
    }
}

function handleToggleUserStatus() {
    requireAdmin();

    $userId = (int)($_POST['user_id'] ?? 0);
    if ($userId <= 0) {
        setFlash('admin_error', 'Invalid user ID.', 'danger');
        redirect('admin/users/index.php');
    }

    $dbStatus = checkDBStatus();
    if (!$dbStatus['status']) {
        setFlash('admin_error', 'Database offline.', 'danger');
        redirect('admin/users/index.php');
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, status, full_name FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $targetUser = $stmt->fetch();

        if (!$targetUser) {
            setFlash('admin_error', 'User record not found.', 'danger');
            redirect('admin/users/index.php');
        }

        $newStatus = ($targetUser['status'] === 'active') ? 'inactive' : 'active';
        $upd = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $upd->execute([$newStatus, $userId]);

        $statusLabel = strtoupper($newStatus);
        setFlash('admin_success', "Customer account '{$targetUser['full_name']}' status set to {$statusLabel}.", 'success');
        redirect('admin/users/index.php');

    } catch (Exception $e) {
        setFlash('admin_error', 'Failed to update user status: ' . $e->getMessage(), 'danger');
        redirect('admin/users/index.php');
    }
}

