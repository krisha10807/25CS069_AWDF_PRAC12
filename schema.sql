-- ============================================================================
-- StudentHub Portal - Practical 12: Event Management & Secure File Upload
-- Course: Web Development Frameworks (WDF)
-- Student Name: Krisha Patel
-- Student ID: 25CS069
-- Database Name: studenthub_events
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `studenthub_events` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `studenthub_events`;

-- ----------------------------------------------------------------------------
-- Table Structure for Table `users`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('Admin', 'Student') NOT NULL DEFAULT 'Student',
    `department` VARCHAR(100) DEFAULT 'Computer Science & Engineering',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table Structure for Table `events`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `event_date` DATETIME NOT NULL,
    `venue` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `poster_image` VARCHAR(255) NOT NULL,
    `created_by` VARCHAR(100) DEFAULT 'Admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_event_date` (`event_date`),
    INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Test Initial Data
-- Passwords encrypted via PHP password_hash() using BCRYPT:
-- Admin: admin@charusat.ac.in / Admin@123
-- Student: student@charusat.ac.in / Student@123
-- ----------------------------------------------------------------------------
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `department`, `created_at`) VALUES
(1, 'Krisha Patel (Admin)', 'admin@charusat.ac.in', '$2y$10$XK58foVH77BwuIoym5BzLeCoLALi5BV44DbPEcBK3SWKNLUoTQNb2', 'Admin', 'Department of Computer Science', '2026-10-15 09:00:00'),
(2, 'Krisha Patel (25CS069)', 'student@charusat.ac.in', '$2y$10$a1OFKaxlroTWBQtdOGoXx.0.cZz9aGk24EahRM0/RsdgtSQSMnKDi', 'Student', 'Computer Science & Engineering', '2026-10-15 09:15:00');

INSERT INTO `events` (`id`, `title`, `category`, `event_date`, `venue`, `description`, `poster_image`, `created_by`, `created_at`) VALUES
(1, 'CHARUSAT Annual Hackathon 2026', 'Technical', '2026-11-15 09:00:00', 'CSPIT Lab 301 & 302', '48-hour national coding and AI hackathon bringing together top engineering minds.', 'hackathon.png', 'Krisha Patel (Admin)', '2026-10-16 10:00:00'),
(2, 'Gen-AI & Autonomous Agents Masterclass', 'Workshop', '2026-11-22 10:30:00', 'Seminar Hall 1, CSPIT', 'Hands-on workshop exploring LLM fine-tuning, RAG pipelines, and multi-agent coordination.', 'ai_workshop.png', 'Krisha Patel (Admin)', '2026-10-16 11:30:00'),
(3, 'Spandan Youth Cultural Gala 2026', 'Cultural', '2026-11-28 17:30:00', 'Central University Amphitheatre', 'Grand annual university festival featuring inter-college music, drama, and art competitions.', 'cultural_fest.png', 'Krisha Patel (Admin)', '2026-10-16 12:00:00');
