<?php
/**
 * StudentHub Portal - Practical 12: Student Event Viewer (student_events.php)
 * Course: Web Development Frameworks (WDF)
 * Student Name: Krisha Patel
 * Student ID: 25CS069
 */

session_start();
require_once __DIR__ . '/db.php';

// Authentication Guard
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=unauthorized");
    exit();
}

$full_name    = $_SESSION['full_name'] ?? 'Student';
$email        = $_SESSION['email'] ?? '';
$current_role = $_SESSION['role'] ?? 'Student';

// Fetch all events
$events = [];
$res = $conn->query("SELECT id, title, category, event_date, venue, description, poster_image, created_by FROM events ORDER BY event_date ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $events[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Student Event Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Header Navigation -->
    <header class="navbar">
        <a href="student_events.php" class="brand">
            <span class="brand-badge" style="background: #059669;">STUDENT</span>
            StudentHub Event Portal
        </a>
        <div class="nav-links">
            <div class="user-pill">
                <span><?= htmlspecialchars($full_name) ?></span>
                <span class="role-badge role-student">Student</span>
            </div>
            <!-- Test RBAC Button: Attempting Admin Dashboard Access -->
            <a href="admin_events.php" class="btn btn-outline-danger btn-sm" id="btnAttemptAdminAccess">
                &#128683; Test Access Admin Dashboard &rarr;
            </a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Sign Out</a>
        </div>
    </header>

    <main class="container">
        
        <!-- Welcome Hero Banner -->
        <div class="hero-card" style="background: linear-gradient(135deg, #064e3b 0%, #022c22 100%);">
            <div class="hero-title">
                <h1>Campus Events & Workshop Showcase</h1>
                <p>Welcome, <?= htmlspecialchars($full_name) ?>! Explore upcoming university hackathons, workshops, and fests.</p>
            </div>
            <div>
                <a href="admin_events.php" class="btn btn-danger btn-sm" id="heroAttemptAdminBtn">
                    &#128274; Test RBAC Unauthorized Access
                </a>
            </div>
        </div>

        <!-- Student Event List -->
        <div class="events-header">
            <h2>Upcoming University Events (<?= count($events) ?>)</h2>
            <div style="font-size: 0.85rem; color: var(--muted);">
                Read-only student access mode. Administrative privileges required to publish or modify posters.
            </div>
        </div>

        <div class="events-grid">
            <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <div class="event-poster-wrap">
                        <span class="category-tag"><?= htmlspecialchars($event['category']) ?></span>
                        <?php 
                            $poster_path = 'uploads/posters/' . $event['poster_image'];
                            $display_src = file_exists(__DIR__ . '/' . $poster_path) ? $poster_path : 'uploads/posters/hackathon.png';
                        ?>
                        <img src="<?= htmlspecialchars($display_src) ?>" alt="<?= htmlspecialchars($event['title']) ?>" class="event-poster">
                    </div>
                    <div class="event-body">
                        <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                        <div class="event-meta">
                            <div class="event-meta-item">
                                <span>&#128197;</span>
                                <span><?= date('D, d M Y - h:i A', strtotime($event['event_date'])) ?></span>
                            </div>
                            <div class="event-meta-item">
                                <span>&#128205;</span>
                                <span><?= htmlspecialchars($event['venue']) ?></span>
                            </div>
                        </div>
                        <p class="event-desc"><?= htmlspecialchars($event['description']) ?></p>
                        <div class="event-footer">
                            <span style="font-size: 0.78rem; color: #059669; font-weight: 600;">
                                &#10004; Registrations Open
                            </span>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="alert('Registered successfully for event: <?= htmlspecialchars(addslashes($event['title'])) ?>!')">
                                Register Now &rarr;
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>

    <!-- Footer -->
    <footer class="footer">
        StudentHub Portal &bull; Practical 12: Event Management & File Upload &bull; Krisha Patel (25CS069)
    </footer>

</body>
</html>
