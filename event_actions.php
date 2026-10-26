<?php
/**
 * StudentHub Portal - Practical 12: Event Action Processor (event_actions.php)
 * Course: Web Development Frameworks (WDF)
 * Student Name: Krisha Patel
 * Student ID: 25CS069
 * 
 * Handles Server-Side CRUD with Strict File Type & File Size Validations.
 */

session_start();
require_once __DIR__ . '/db.php';

// RBAC Guard: Only Administrator can perform modifications
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: login.php?error=unauthorized");
    exit();
}

$action = $_POST['action'] ?? '';
$upload_dir = __DIR__ . '/uploads/posters/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Maximum allowed poster file size: 2 Megabytes (2 * 1024 * 1024 bytes)
define('MAX_POSTER_SIZE', 2 * 1024 * 1024);

// Whitelisted File Extensions & MIME Types for Poster Uploads
$allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
$allowed_mimes      = ['image/jpeg', 'image/png', 'image/webp'];

/**
 * Validates uploaded poster file
 * Returns array: ['valid' => bool, 'error' => string, 'filename' => string]
 */
function validateAndSavePoster($file, $upload_dir, $allowed_extensions, $allowed_mimes) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['valid' => false, 'error' => 'no_poster', 'filename' => ''];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'upload_failed', 'filename' => ''];
    }

    // 1. File Size Limit Validation (Max 2 MB)
    if ($file['size'] > MAX_POSTER_SIZE) {
        return ['valid' => false, 'error' => 'size_exceeded', 'filename' => ''];
    }

    // 2. File Extension Validation
    $original_name = $file['name'];
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions, true)) {
        return ['valid' => false, 'error' => 'invalid_type', 'filename' => ''];
    }

    // 3. MIME Content-Type Validation
    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    } elseif (function_exists('mime_content_type')) {
        $mime = mime_content_type($file['tmp_name']);
    } else {
        $mime = $file['type'];
    }

    if (!in_array($mime, $allowed_mimes, true)) {
        return ['valid' => false, 'error' => 'invalid_type', 'filename' => ''];
    }

    // 4. Secure Unique Filename Generation
    $new_filename = 'poster_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $target_path  = $upload_dir . $new_filename;

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        // Fallback copy if move_uploaded_file is constrained
        if (!copy($file['tmp_name'], $target_path)) {
            return ['valid' => false, 'error' => 'upload_failed', 'filename' => ''];
        }
    }

    return ['valid' => true, 'error' => '', 'filename' => $new_filename];
}

// -------------------------------------------------------------
// 1. EVENT CREATION
// -------------------------------------------------------------
if ($action === 'create') {
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $event_date  = trim($_POST['event_date'] ?? '');
    $venue       = trim($_POST['venue'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($title) || empty($category) || empty($event_date) || empty($venue) || empty($description)) {
        header("Location: admin_events.php?error=empty_fields");
        exit();
    }

    // Validate Poster Upload
    $file_res = validateAndSavePoster($_FILES['poster'] ?? null, $upload_dir, $allowed_extensions, $allowed_mimes);
    if (!$file_res['valid']) {
        header("Location: admin_events.php?error=" . urlencode($file_res['error']));
        exit();
    }

    $poster_image = $file_res['filename'];
    $created_by   = $_SESSION['full_name'] ?? 'Krisha Patel (Admin)';

    $stmt = $conn->prepare("INSERT INTO events (title, category, event_date, venue, description, poster_image, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $title, $category, $event_date, $venue, $description, $poster_image, $created_by);
    $stmt->execute();

    header("Location: admin_events.php?msg=event_created");
    exit();
}

// -------------------------------------------------------------
// 2. EVENT UPDATE
// -------------------------------------------------------------
if ($action === 'update') {
    $event_id    = intval($_POST['event_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $event_date  = trim($_POST['event_date'] ?? '');
    $venue       = trim($_POST['venue'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($event_id <= 0 || empty($title) || empty($category) || empty($event_date) || empty($venue) || empty($description)) {
        header("Location: admin_events.php?error=empty_fields");
        exit();
    }

    // Check if new poster uploaded
    $new_poster_uploaded = isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE;

    if ($new_poster_uploaded) {
        $file_res = validateAndSavePoster($_FILES['poster'], $upload_dir, $allowed_extensions, $allowed_mimes);
        if (!$file_res['valid']) {
            header("Location: admin_events.php?error=" . urlencode($file_res['error']));
            exit();
        }
        $new_poster = $file_res['filename'];

        $stmt = $conn->prepare("UPDATE events SET title = ?, category = ?, event_date = ?, venue = ?, description = ?, poster_image = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $title, $category, $event_date, $venue, $description, $new_poster, $event_id);
    } else {
        $stmt = $conn->prepare("UPDATE events SET title = ?, category = ?, event_date = ?, venue = ?, description = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $title, $category, $event_date, $venue, $description, $event_id);
    }

    $stmt->execute();
    header("Location: admin_events.php?msg=event_updated");
    exit();
}

// -------------------------------------------------------------
// 3. EVENT DELETION
// -------------------------------------------------------------
if ($action === 'delete') {
    $event_id = intval($_POST['event_id'] ?? 0);

    if ($event_id > 0) {
        // Fetch poster filename for file removal if needed
        $stmt_fetch = $conn->prepare("SELECT poster_image FROM events WHERE id = ? LIMIT 1");
        $stmt_fetch->bind_param("i", $event_id);
        $stmt_fetch->execute();
        $res = $stmt_fetch->get_result();
        if ($res && ($row = $res->fetch_assoc())) {
            $poster_to_delete = $row['poster_image'];
            // Keep default sample posters intact, delete user uploaded ones
            if (strpos($poster_to_delete, 'poster_') === 0) {
                @unlink($upload_dir . $poster_to_delete);
            }
        }

        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
    }

    header("Location: admin_events.php?msg=event_deleted");
    exit();
}

header("Location: admin_events.php");
exit();
