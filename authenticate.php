<?php
/**
 * StudentHub Portal - Practical 12: Authentication Processor (authenticate.php)
 * Course: Web Development Frameworks (WDF)
 * Student Name: Krisha Patel
 * Student ID: 25CS069
 */

session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    header("Location: login.php?error=empty");
    exit();
}

// Prepared Statement Query
$stmt = $conn->prepare("SELECT id, full_name, email, password, role, department FROM users WHERE email = ? LIMIT 1");
if (!$stmt) {
    die("Database prepare failure: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && ($user = $result->fetch_assoc())) {
    // Verify BCRYPT cryptographic password hash
    if (password_verify($password, $user['password'])) {
        // Regenerate Session ID to prevent session fixation attacks
        session_regenerate_id(true);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['full_name']  = $user['full_name'];
        $_SESSION['email']      = $user['email'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['department'] = $user['department'];

        // Role-Based Redirection
        if ($user['role'] === 'Admin') {
            header("Location: admin_events.php?msg=login_success");
        } else {
            header("Location: student_events.php?msg=login_success");
        }
        exit();
    }
}

// Failed Authentication
header("Location: login.php?error=invalid");
exit();
