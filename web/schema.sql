-- Passapp Database Schema
-- MySQL/MariaDB

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- --------------------------------------------------------
-- Table: users
-- Stores user accounts and encrypted data
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(32) NOT NULL DEFAULT 'await',
  `login` varchar(128) NOT NULL,
  `password` varchar(255) NOT NULL,
  `event` varchar(64) DEFAULT NULL,
  `data` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login_UNIQUE` (`login`),
  KEY `idx_users_event` (`event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: sessions
-- Stores active user sessions
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `login` varchar(128) NOT NULL,
  `hash` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `hash_index` (`hash`),
  KEY `login_index` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Example user (optional)
-- Email: demo@example.com
-- Password: demo123
-- Status: active (no email confirmation needed)
-- --------------------------------------------------------

INSERT INTO `users` (`status`, `login`, `password`, `event`, `data`) VALUES
('active', 'demo@example.com', '$2y$10$YourHashedPasswordHere', NULL, NULL);

-- Note: Generate proper password hash using PHP:
-- echo password_hash('demo123', PASSWORD_DEFAULT);

