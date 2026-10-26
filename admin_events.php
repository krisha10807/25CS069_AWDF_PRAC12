<?php
/**
 * StudentHub Portal - Practical 12: Admin Event Management Dashboard (admin_events.php)
 * Course: Web Development Frameworks (WDF)
 * Student Name: Krisha Patel
 * Student ID: 25CS069
 * 
 * Instructions satisfied:
 * - Admin login and dashboard
 * - Event list with uploaded posters
 * - Event creation, update, and deletion
 * - Poster upload validations (file type, file size limit)
 * - Success and error message feedback
 * - Unauthorized user access restriction
 */

session_start();
require_once __DIR__ . '/db.php';

// Authentication Guard
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=unauthorized");
    exit();
}

$current_role = $_SESSION['role'] ?? 'Student';
$full_name    = $_SESSION['full_name'] ?? 'User';
$email        = $_SESSION['email'] ?? '';

// RBAC Guard: If role is not Admin, display dedicated 403 Forbidden Screen!
if ($current_role !== 'Admin') {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 Forbidden - Unauthorized Access Denied</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <header class="navbar">
            <a href="login.php" class="brand">
                <span class="brand-badge" style="background: var(--danger);">ACCESS GUARD</span>
                StudentHub Security
            </a>
            <div class="nav-links">
                <div class="user-pill">
                    <span><?= htmlspecialchars($full_name) ?></span>
                    <span class="role-badge role-student"><?= htmlspecialchars($current_role) ?></span>
                </div>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Sign Out</a>
            </div>
        </header>

        <main class="container">
            <div class="unauthorized-card" id="unauthorizedBox">
                <div class="unauthorized-icon">&#128683;</div>
                <h2 style="color: #991b1b; margin-bottom: 0.5rem;">403 - Unauthorized Access Denied!</h2>
                <div class="alert alert-danger" style="text-align: left; margin: 1.5rem 0;">
                    <div>
                        <strong>Privilege Violation Detected:</strong><br>
                        You are logged in as <strong><?= htmlspecialchars($full_name) ?></strong> with role <span class="role-badge role-student">Student</span>.<br>
                        The resource <code>admin_events.php</code> is restricted exclusively to <strong>Administrators</strong> with event creation and poster uploading privileges.
                    </div>
                </div>
                <p style="color: var(--muted); margin-bottom: 1.5rem;">
                    Role-Based Access Control (RBAC) active. Your request has been blocked and logged according to institutional security protocol.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="student_events.php" class="btn btn-primary">&larr; Return to Student Event Portal</a>
                    <a href="logout.php" class="btn btn-outline-danger">Log Out</a>
                </div>
            </div>
        </main>

        <footer class="footer">
            StudentHub Portal &bull; Practical 12: Role-Based Event Security &bull; Krisha Patel (25CS069)
        </footer>
    </body>
    </html>
    <?php
    exit();
}

// Fetch all events from database
$events = [];
$res = $conn->query("SELECT id, title, category, event_date, venue, description, poster_image, created_by, created_at FROM events ORDER BY event_date ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $events[] = $row;
    }
}

// Process Alert Messages
$success_msg = '';
$error_msg   = '';

if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'login_success':
            $success_msg = "Welcome, Administrator! You have securely authenticated to the Event Management Dashboard.";
            break;
        case 'event_created':
            $success_msg = "Event Created Successfully! Valid poster image has been uploaded and stored in the repository.";
            break;
        case 'event_updated':
            $success_msg = "Event Updated Successfully! The database record and poster information have been updated.";
            break;
        case 'event_deleted':
            $success_msg = "Event Deleted Successfully! The event record and associated files have been removed.";
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_type':
            $error_msg = "Invalid File Type Validation Failed! Only JPG, JPEG, PNG, and WEBP poster images are permitted.";
            break;
        case 'size_exceeded':
            $error_msg = "File Size Limit Validation Failed! Uploaded poster exceeds the 2 MB maximum allowable limit.";
            break;
        case 'empty_fields':
            $error_msg = "Validation Error: Please fill in all mandatory event fields before submitting.";
            break;
        case 'no_poster':
            $error_msg = "Validation Error: An event poster file is required when publishing a new event.";
            break;
        case 'upload_failed':
            $error_msg = "Upload Failed: Unable to securely move the uploaded poster to the destination directory.";
            break;
    }
}

// Compute metrics
$total_events = count($events);
$technical_events = count(array_filter($events, fn($e) => $e['category'] === 'Technical'));
$workshop_events  = count(array_filter($events, fn($e) => $e['category'] === 'Workshop'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Admin Event Management Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Header Navigation -->
    <header class="navbar">
        <a href="admin_events.php" class="brand">
            <span class="brand-badge">ADMIN</span>
            StudentHub Event Control Center
        </a>
        <div class="nav-links">
            <div class="user-pill">
                <span><?= htmlspecialchars($full_name) ?></span>
                <span class="role-badge role-admin">Administrator</span>
            </div>
            <a href="student_events.php" class="btn btn-outline-primary btn-sm" id="btnStudentView">
                Preview Student Portal &rarr;
            </a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm" id="btnLogout">
                Sign Out
            </a>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="container">

        <!-- Welcome Banner -->
        <div class="hero-card">
            <div class="hero-title">
                <h1>Event Management & Poster Repository</h1>
                <p>Practical 12: Admin Event CRUD with Secure Poster Upload Validation (Krisha Patel - 25CS069)</p>
            </div>
            <div>
                <button type="button" class="btn btn-success" id="btnOpenCreateModal" onclick="openCreateModal()">
                    &#10010; Create New Event
                </button>
            </div>
        </div>

        <!-- Success Alert Notification -->
        <?php if ($success_msg): ?>
            <div class="alert alert-success" id="alertSuccess">
                <span style="font-size: 1.25rem;">&#10004;</span>
                <div>
                    <strong>Action Completed:</strong> <?= htmlspecialchars($success_msg) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Error Alert Notification -->
        <?php if ($error_msg): ?>
            <div class="alert alert-danger" id="alertError">
                <span style="font-size: 1.25rem;">&#9888;</span>
                <div>
                    <strong>Validation Error:</strong> <?= htmlspecialchars($error_msg) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Key Metrics Dashboard -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon" style="background: var(--primary-light); color: var(--primary);">&#128197;</div>
                <div>
                    <div class="metric-value"><?= $total_events ?></div>
                    <div class="metric-label">Total Scheduled Events</div>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon" style="background: var(--success-light); color: var(--success);">&#128187;</div>
                <div>
                    <div class="metric-value"><?= $technical_events ?></div>
                    <div class="metric-label">Technical Hackathons</div>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon" style="background: var(--warning-light); color: var(--warning);">&#127891;</div>
                <div>
                    <div class="metric-value"><?= $workshop_events ?></div>
                    <div class="metric-label">Workshops & Seminars</div>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon" style="background: #f1f5f9; color: var(--dark);">&#128274;</div>
                <div>
                    <div class="metric-value" style="font-size: 1rem; color: #059669;">2 MB / BCRYPT</div>
                    <div class="metric-label">Poster Max Size & Security</div>
                </div>
            </div>
        </div>

        <!-- Events List Section -->
        <div class="events-header">
            <h2>Scheduled Campus Events (<?= count($events) ?>)</h2>
            <div style="font-size: 0.85rem; color: var(--muted);">
                Showing all events stored in the database with uploaded poster artwork
            </div>
        </div>

        <?php if (empty($events)): ?>
            <div class="metric-card" style="justify-content: center; text-align: center; padding: 3rem;">
                <p style="color: var(--muted);">No events found in the database. Click <strong>"Create New Event"</strong> to add an event with an uploaded poster.</p>
            </div>
        <?php else: ?>
            <div class="events-grid" id="eventsContainer">
                <?php foreach ($events as $event): ?>
                    <div class="event-card" id="eventCard-<?= $event['id'] ?>">
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
                                <span style="font-size: 0.78rem; color: var(--muted); font-family: monospace;">
                                    &#128206; <?= htmlspecialchars($event['poster_image']) ?>
                                </span>
                                <div class="event-actions">
                                    <button type="button" class="btn btn-outline-primary btn-sm btn-edit" 
                                            onclick='openEditModal(<?= json_encode($event) ?>)'>
                                        &#9998; Edit
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm btn-delete" 
                                            onclick="confirmDelete(<?= $event['id'] ?>, '<?= htmlspecialchars(addslashes($event['title'])) ?>')">
                                        &#128465; Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- Modal: Create / Update Event Form -->
    <div class="modal-overlay" id="eventModal" style="display: none;">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="modalTitle">Create New Event</h3>
                <button type="button" class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form action="event_actions.php" method="POST" enctype="multipart/form-data" id="eventForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="event_id" id="formEventId" value="">

                    <div class="form-group">
                        <label class="form-label" for="title">Event Title <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="e.g. National Robotics Championship 2026" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label" for="category">Category <span style="color:var(--danger)">*</span></label>
                            <select id="category" name="category" class="form-control" required>
                                <option value="Technical">Technical</option>
                                <option value="Workshop">Workshop</option>
                                <option value="Cultural">Cultural</option>
                                <option value="Sports">Sports</option>
                                <option value="Seminar">Seminar</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="event_date">Date & Time <span style="color:var(--danger)">*</span></label>
                            <input type="datetime-local" id="event_date" name="event_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="venue">Venue / Campus Location <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="venue" name="venue" class="form-control" placeholder="e.g. CSPIT Auditorium / Lab 301" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Event Description <span style="color:var(--danger)">*</span></label>
                        <textarea id="description" name="description" class="form-control" rows="3" placeholder="Provide an overview of the event, eligibility, and rules..." required></textarea>
                    </div>

                    <!-- File Upload Input with Live Preview & Validation Indicator -->
                    <div class="form-group">
                        <label class="form-label" for="poster">
                            Event Poster Image <span id="posterRequiredStar" style="color:var(--danger)">*</span>
                        </label>
                        <div class="file-upload-box" onclick="document.getElementById('poster').click()">
                            <div class="upload-icon">&#128444;</div>
                            <div style="font-weight: 600; font-size: 0.9rem;">Click or drag to select an event poster</div>
                            <div class="file-hint">Supported formats: JPG, JPEG, PNG, WEBP &bull; Max allowed size: 2 MB</div>
                            <input type="file" id="poster" name="poster" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt" style="display: none;" onchange="handleFileSelect(this)">
                        </div>
                        <div id="fileSelectedBadge" style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--primary); display: none;"></div>
                        <div class="preview-container" id="previewContainer">
                            <img id="previewImage" src="" alt="Poster Preview" class="preview-img">
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                        <button type="button" class="btn btn-outline-danger" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Publish Event &rarr;</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Delete Confirmation -->
    <div class="modal-overlay" id="deleteModal" style="display: none;">
        <div class="modal-card" style="max-width: 440px;">
            <div class="modal-header">
                <h3 style="color: var(--danger);">&#128465; Confirm Event Deletion</h3>
                <button type="button" class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center;">
                <p style="margin-bottom: 1rem; color: var(--dark);" id="deleteConfirmText">
                    Are you sure you want to permanently delete this event?
                </p>
                <form action="event_actions.php" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="event_id" id="deleteEventId" value="">
                    <div style="display: flex; gap: 0.75rem; justify-content: center;">
                        <button type="button" class="btn btn-outline-primary" onclick="closeDeleteModal()">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">Yes, Delete Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        StudentHub Portal &bull; Practical 12: Event Management & File Upload &bull; Krisha Patel (25CS069)
    </footer>

    <script>
        function openCreateModal() {
            document.getElementById('formAction').value = 'create';
            document.getElementById('formEventId').value = '';
            document.getElementById('modalTitle').innerText = 'Create New Event';
            document.getElementById('modalSubmitBtn').innerText = 'Publish Event \u2192';
            document.getElementById('posterRequiredStar').style.display = 'inline';
            document.getElementById('eventForm').reset();
            document.getElementById('previewContainer').style.display = 'none';
            document.getElementById('fileSelectedBadge').style.display = 'none';
            document.getElementById('eventModal').style.display = 'flex';
        }

        function openEditModal(event) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('formEventId').value = event.id;
            document.getElementById('modalTitle').innerText = 'Update Event Details';
            document.getElementById('modalSubmitBtn').innerText = 'Save Changes \u2192';
            document.getElementById('posterRequiredStar').style.display = 'none';

            document.getElementById('title').value = event.title;
            document.getElementById('category').value = event.category;
            
            // Format datetime-local
            const d = new Date(event.event_date);
            const iso = new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
            document.getElementById('event_date').value = iso;
            
            document.getElementById('venue').value = event.venue;
            document.getElementById('description').value = event.description;

            // Existing poster preview
            const previewContainer = document.getElementById('previewContainer');
            const previewImage = document.getElementById('previewImage');
            previewImage.src = 'uploads/posters/' + event.poster_image;
            previewContainer.style.display = 'block';

            const badge = document.getElementById('fileSelectedBadge');
            badge.innerText = 'Current poster: ' + event.poster_image + ' (Select new file only to replace)';
            badge.style.display = 'block';

            document.getElementById('eventModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('eventModal').style.display = 'none';
        }

        function confirmDelete(id, title) {
            document.getElementById('deleteEventId').value = id;
            document.getElementById('deleteConfirmText').innerHTML = 'Are you sure you want to delete event: <strong>' + title + '</strong>? This action cannot be undone.';
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function handleFileSelect(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const badge = document.getElementById('fileSelectedBadge');
                badge.innerText = '\uD83D\uDCCE Selected: ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                badge.style.display = 'block';

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewImage = document.getElementById('previewImage');
                        previewImage.src = e.target.result;
                        document.getElementById('previewContainer').style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    document.getElementById('previewContainer').style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>
