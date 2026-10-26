<?php
/**
 * StudentHub Portal - Practical 12: Database Connection & Adapter (db.php)
 * Course: Web Development Frameworks (WDF)
 * Student Name: Krisha Patel
 * Student ID: 25CS069
 * 
 * Provides database connection with prepared statements and resilient portable SQLite fallback.
 */

$host     = "localhost";
$username = "root";
$password = "";
$dbname   = "studenthub_events";
$port     = 3306;

mysqli_report(MYSQLI_REPORT_OFF);

$conn = null;
$db_driver_name = "MySQL (Native)";

try {
    $conn = @new mysqli($host, $username, $password, $dbname, $port);
    if ($conn->connect_error) {
        $conn = null;
    } else {
        $conn->set_charset("utf8mb4");
    }
} catch (Throwable $e) {
    $conn = null;
}

// Resilient SQLite Fallback Adapter if MySQL Server is offline
if (!$conn) {
    class MockMySQLiResult {
        private $rows;
        private $currentIndex = 0;
        public $num_rows = 0;

        public function __construct(array $rows) {
            $this->rows = array_values($rows);
            $this->num_rows = count($rows);
        }

        public function fetch_assoc() {
            if ($this->currentIndex < $this->num_rows) {
                return $this->rows[$this->currentIndex++];
            }
            return null;
        }

        public function fetch_all($mode = null) {
            return $this->rows;
        }
    }

    class MockMySQLiStmt {
        private $pdo;
        private $sql;
        private $types = '';
        private $params = [];
        private $pdoStmt;
        private $lastResult = null;

        public function __construct(PDO $pdo, $sql) {
            $this->pdo = $pdo;
            $this->sql = $sql;
        }

        public function bind_param($types, ...$vars) {
            $this->types = $types;
            $this->params = $vars;
            return true;
        }

        public function execute() {
            $pdoSql = preg_replace('/\?/', '?', $this->sql);
            $this->pdoStmt = $this->pdo->prepare($pdoSql);
            $success = $this->pdoStmt->execute($this->params);

            if (stripos(trim($this->sql), 'SELECT') === 0) {
                $allRows = $this->pdoStmt->fetchAll(PDO::FETCH_ASSOC);
                $this->lastResult = new MockMySQLiResult($allRows);
            }
            return $success;
        }

        public function get_result() {
            return $this->lastResult;
        }

        public function close() {
            return true;
        }
    }

    class MockMySQLi {
        public $connect_error = null;
        public $error = '';
        public $errno = 0;
        public $insert_id = 0;
        private $pdo;

        public function __construct() {
            $sqlitePath = __DIR__ . '/events.db';
            $isNew = !file_exists($sqlitePath) || filesize($sqlitePath) === 0;
            $this->pdo = new PDO("sqlite:" . $sqlitePath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($isNew) {
                $this->pdo->exec("
                    CREATE TABLE IF NOT EXISTS users (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        full_name TEXT NOT NULL,
                        email TEXT NOT NULL UNIQUE,
                        password TEXT NOT NULL,
                        role TEXT NOT NULL DEFAULT 'Student',
                        department TEXT DEFAULT 'Computer Science & Engineering',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    );

                    CREATE TABLE IF NOT EXISTS events (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        title TEXT NOT NULL,
                        category TEXT NOT NULL,
                        event_date DATETIME NOT NULL,
                        venue TEXT NOT NULL,
                        description TEXT NOT NULL,
                        poster_image TEXT NOT NULL,
                        created_by TEXT DEFAULT 'Admin',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    );
                ");

                // Seed Default Users
                $admin_hash = password_hash('Admin@123', PASSWORD_BCRYPT);
                $student_hash = password_hash('Student@123', PASSWORD_BCRYPT);

                $user_stmt = $this->pdo->prepare("
                    INSERT INTO users (full_name, email, password, role, department, created_at)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $user_stmt->execute(['Krisha Patel (Admin)', 'admin@charusat.ac.in', $admin_hash, 'Admin', 'Department of Computer Science', '2026-10-15 09:00:00']);
                $user_stmt->execute(['Krisha Patel (25CS069)', 'student@charusat.ac.in', $student_hash, 'Student', 'Computer Science & Engineering', '2026-10-15 09:15:00']);

                // Seed Default Events with initial posters
                $event_stmt = $this->pdo->prepare("
                    INSERT INTO events (title, category, event_date, venue, description, poster_image, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $event_stmt->execute([
                    'CHARUSAT Annual Hackathon 2026',
                    'Technical',
                    '2026-11-15 09:00:00',
                    'CSPIT Lab 301 & 302',
                    '48-hour national coding and AI hackathon bringing together top engineering minds.',
                    'hackathon.png',
                    'Krisha Patel (Admin)',
                    '2026-10-16 10:00:00'
                ]);
                $event_stmt->execute([
                    'Gen-AI & Autonomous Agents Masterclass',
                    'Workshop',
                    '2026-11-22 10:30:00',
                    'Seminar Hall 1, CSPIT',
                    'Hands-on workshop exploring LLM fine-tuning, RAG pipelines, and multi-agent coordination.',
                    'ai_workshop.png',
                    'Krisha Patel (Admin)',
                    '2026-10-16 11:30:00'
                ]);
                $event_stmt->execute([
                    'Spandan Youth Cultural Gala 2026',
                    'Cultural',
                    '2026-11-28 17:30:00',
                    'Central University Amphitheatre',
                    'Grand annual university festival featuring inter-college music, drama, and art competitions.',
                    'cultural_fest.png',
                    'Krisha Patel (Admin)',
                    '2026-10-16 12:00:00'
                ]);
            }
        }

        public function prepare($sql) {
            return new MockMySQLiStmt($this->pdo, $sql);
        }

        public function query($sql) {
            try {
                $stmt = $this->pdo->query($sql);
                if ($stmt === false) return false;
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return new MockMySQLiResult($rows);
            } catch (Throwable $e) {
                $this->error = $e->getMessage();
                return false;
            }
        }

        public function real_escape_string($str) {
            return SQLite3::escapeString($str);
        }

        public function set_charset($charset) {
            return true;
        }

        public function close() {
            return true;
        }
    }

    $conn = new MockMySQLi();
    $db_driver_name = "SQLite Portable Adapter";
}
