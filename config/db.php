<?php

require_once __DIR__ . '/config.php';

$pdo = null;
$db_error = null;

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
 
    $db_error = $e->getMessage();
}

function getDB() {
    global $pdo, $db_error;
    if ($pdo === null) {
        throw new Exception("Database connection failed: " . ($db_error ?: "Unknown error. Please ensure MySQL is running in XAMPP."));
    }
    return $pdo;
}

function checkDBStatus() {
    global $pdo, $db_error;
    if ($pdo instanceof PDO) {
        return [
            'status'  => true,
            'message' => 'Successfully connected to database (' . DB_NAME . ') on ' . DB_HOST . ':' . DB_PORT,
            'pdo'     => $pdo
        ];
    }

    return [
        'status'  => false,
        'message' => $db_error ?: 'Could not establish connection to MySQL database.',
        'pdo'     => null
    ];
}
