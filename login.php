<?php
/**
 * StudentHub Portal - Practical 12: Login Interface (login.php)
 * Course: Web Development Frameworks (WDF)
 * Student Name: Krisha Patel
 * Student ID: 25CS069
 */

session_start();

// If already logged in, redirect to appropriate portal
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Admin') {
        header("Location: admin_events.php");
        exit();
    } else {
        header("Location: student_events.php");
        exit();
    }
}

$error_msg   = '';
$success_msg = '';

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid':
            $error_msg = "Invalid Email or Password! Please verify your login credentials.";
            break;
        case 'empty':
            $error_msg = "All fields are required! Please fill in both email and password.";
            break;
        case 'unauthorized':
            $error_msg = "Access Denied! You must log in as Administrator to access the Event Management Dashboard.";
            break;
    }
}

if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'logged_out':
            $success_msg = "Logged Out Successfully! Your secure session has been terminated.";
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Practical 12: Event Portal Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Header Navigation -->
    <header class="navbar">
        <a href="login.php" class="brand">
            <span class="brand-badge">PRACTICAL 12</span>
            StudentHub Event Portal
        </a>
        <div class="nav-links">
            <span style="font-size: 0.85rem; color: var(--muted); font-weight: 600;">
                Practical 12 &bull; 25CS069
            </span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <div class="auth-wrapper">
            <div class="auth-card">
                <h2>Event Portal Login</h2>
                <p class="auth-sub">Role-Based Authentication & Secure Poster Management</p>

                <?php if ($error_msg): ?>
                    <div class="alert alert-danger" id="alertError">
                        <span style="font-size: 1.2rem;">&#9888;</span>
                        <div><strong>Login Failed:</strong> <?= htmlspecialchars($error_msg) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($success_msg): ?>
                    <div class="alert alert-success" id="alertSuccess">
                        <span style="font-size: 1.2rem;">&#10004;</span>
                        <div><?= htmlspecialchars($success_msg) ?></div>
                    </div>
                <?php endif; ?>

                <form action="authenticate.php" method="POST" id="loginForm">
                    <div class="form-group">
                        <label class="form-label" for="email">Institutional Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="e.g. admin@charusat.ac.in" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-primary" id="loginSubmitBtn" style="width: 100%; justify-content: center; margin-top: 0.5rem;">
                        Sign In to Portal &rarr;
                    </button>
                </form>

                <!-- Quick Evaluation Test Credentials -->
                <div class="demo-box">
                    <div class="demo-title">&#128273; Quick Credentials for Evaluation:</div>
                    <div class="demo-grid">
                        <button type="button" class="demo-btn" id="btnFillAdmin" onclick="fillCreds('admin@charusat.ac.in', 'Admin@123')">
                            <strong>Admin Account</strong>
                            <span>admin@charusat.ac.in</span>
                            <span>Pass: Admin@123</span>
                        </button>
                        <button type="button" class="demo-btn" id="btnFillStudent" onclick="fillCreds('student@charusat.ac.in', 'Student@123')">
                            <strong>Student Account</strong>
                            <span>student@charusat.ac.in</span>
                            <span>Pass: Student@123</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        StudentHub Portal &bull; Practical 12: Event Management & File Upload &bull; Krisha Patel (25CS069)
    </footer>

    <script>
        function fillCreds(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>
