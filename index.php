<?php
/**
 * Practical 12 - Index Router
 */
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Admin') {
        header("Location: admin_events.php");
    } else {
        header("Location: student_events.php");
    }
    exit();
}

header("Location: login.php");
exit();
