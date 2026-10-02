<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $subject  = trim($_POST['subject'] ?? '');
    $message  = trim($_POST['message'] ?? '');

    if (empty($fullName) || empty($email) || empty($subject) || empty($message)) {
        setFlash('contact_msg', 'Please fill out all fields in the contact form.', 'danger');
        redirect('contact.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('contact_msg', 'Please provide a valid email address.', 'danger');
        redirect('contact.php');
    }

    $dbStatus = checkDBStatus();
    if ($dbStatus['status']) {
        try {
            $db = getDB();
 
            $db->exec("CREATE TABLE IF NOT EXISTS `contact_messages` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `full_name` VARCHAR(100) NOT NULL,
                `email` VARCHAR(150) NOT NULL,
                `subject` VARCHAR(200) NOT NULL,
                `message` TEXT NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $stmt = $db->prepare("INSERT INTO contact_messages (full_name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$fullName, $email, $subject, $message]);
        } catch (Exception $e) {
 
        }
    }

    setFlash('contact_msg', 'Thank you, ' . htmlspecialchars($fullName) . '! Your message has been received. Our cinema support team will get back to you shortly.', 'success');
    redirect('contact.php');
} else {
    redirect('contact.php');
}
