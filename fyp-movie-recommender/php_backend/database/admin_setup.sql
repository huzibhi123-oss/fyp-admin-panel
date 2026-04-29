-- Step 1: Expand Users Table
ALTER TABLE `users`
ADD COLUMN `role` ENUM('user', 'admin') DEFAULT 'user' AFTER `password`,
ADD COLUMN `status` ENUM('active', 'blocked') DEFAULT 'active' AFTER `role`;

-- Step 2: Create Moods Table
CREATE TABLE IF NOT EXISTS `moods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `related_genres` varchar(255) DEFAULT NULL, -- Comma separated IDs
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 3: Create Admin-Curated Movies Table
CREATE TABLE IF NOT EXISTS `admin_movies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tmdb_id` int(11) DEFAULT NULL, -- Optional if manually added
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `release_year` int(4) DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT NULL,
  `poster_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 4: Mood-Movie Mapping (Core Mapping Feature)
CREATE TABLE IF NOT EXISTS `movie_mood_mapping` (
  `movie_id` int(11) NOT NULL,
  `mood_id` int(11) NOT NULL,
  PRIMARY KEY (`movie_id`, `mood_id`),
  FOREIGN KEY (`movie_id`) REFERENCES `admin_movies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`mood_id`) REFERENCES `moods`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 5: Admin Settings Table
CREATE TABLE IF NOT EXISTS `admin_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 6: Initial Data
INSERT INTO `admin_settings` (`setting_key`, `setting_value`) VALUES
('app_name', 'MoodAI'),
('recommendation_count', '10'),
('recommendation_logic', 'top-rated'),
('theme_color', '#950101');

INSERT INTO `moods` (`name`, `description`, `related_genres`) VALUES
('Happy', 'Movies that make you feel good and laugh.', '35'),
('Sad', 'Emotional stories that touch the heart.', '18'),
('Angry', 'High-energy action and revenge plots.', '28'),
('Excited', 'Thrilling adventures and fun for everyone.', '10751');

-- Insert default admin (Password: admin123)
-- Note: In a real app, we'd use a more secure method, but for setup:
INSERT INTO `users` (`username`, `email`, `password`, `role`)
VALUES ('SuperAdmin', 'admin@moodai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE role='admin';
