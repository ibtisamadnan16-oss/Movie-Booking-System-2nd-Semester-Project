<?php

require_once __DIR__ . '/../config/config.php';

function url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

function asset($path = '') {
    return url('assets/' . ltrim($path, '/'));
}

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function validateCsrfToken($token = null) {
    $provided = $token ?? $_POST['csrf_token'] ?? '';
    if (empty($provided) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $provided);
}

function redirect($path) {
    header("Location: " . url($path));
    exit;
}

function setFlash($key, $message, $type = 'success') {
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type'    => $type
    ];
}

function getFlash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $flash = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $flash;
    }
    return null;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'] ?? '';
        setFlash('auth_error', 'Please log in to access this page.', 'warning');
        redirect('login.php');
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        setFlash('admin_error', 'Access denied. Administrator privileges required.', 'danger');
        redirect('admin/login.php');
    }
}

function currentUser() {
    if (isAdmin()) {
        return [
            'id'    => $_SESSION['admin_id'] ?? 1,
            'name'  => $_SESSION['admin_name'] ?? 'Administrator',
            'email' => $_SESSION['admin_email'] ?? '',
            'role'  => 'admin',
        ];
    }
    if (isLoggedIn()) {
        return [
            'id'    => $_SESSION['user_id'] ?? null,
            'name'  => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email'] ?? '',
            'role'  => 'user',
        ];
    }
    return null;
}

function formatPrice($amount) {
    return 'Rs. ' . number_format((float)$amount, 2);
}

function formatDateTime($dateTimeString, $format = 'd M Y, h:i A') {
    if (empty($dateTimeString)) return '-';
    $date = new DateTime($dateTimeString);
    return $date->format($format);
}

function getMoviePoster($posterFile, $movieTitle = '') {
    if (!empty($posterFile) && file_exists(ASSETS_PATH . 'images/posters/' . $posterFile)) {
        return asset('images/posters/' . $posterFile);
    }

$curatedPosters = [
        'oppenheimer.jpg' => 'https://image.tmdb.org/t/p/w500/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg',
        'dune2.jpg'        => 'https://image.tmdb.org/t/p/w500/1pdfLvkbY9ohJlCjQH2CZjjYVvJ.jpg',
        'interstellar.jpg' => 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg',
        'gladiator2.jpg'   => 'https://image.tmdb.org/t/p/w500/2cxhvwyEwRlysAmRH4iodkvo0z5.jpg',
    ];

    if (!empty($posterFile) && isset($curatedPosters[$posterFile])) {
        return $curatedPosters[$posterFile];
    }

    return 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=600&auto=format&fit=crop&q=80';
}

function getYouTubeEmbedUrl($url) {
    if (empty($url)) return '';
    if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1] . '?autoplay=1&rel=0';
    }
    return $url;
}
