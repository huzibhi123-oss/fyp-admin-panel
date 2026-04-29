-- Database: mood_recommender_db

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Mood History Table (Tracks all detections)
CREATE TABLE IF NOT EXISTS `user_mood_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `mood` varchar(20) NOT NULL,
  `input_type` enum('text','voice','face') NOT NULL,
  `detected_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Cached Movies Table (Fallback System)
CREATE TABLE IF NOT EXISTS `cached_movies` (
  `tmdb_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `overview` text DEFAULT NULL,
  `poster_path` varchar(255) DEFAULT NULL,
  `vote_average` decimal(3,1) DEFAULT NULL,
  `genre_id` int(11) NOT NULL,
  `original_language` varchar(10) DEFAULT NULL,
  `cached_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tmdb_id`, `genre_id`) -- Composite key to allow same movie in different genres
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Favorites Table (Saved movies)
CREATE TABLE IF NOT EXISTS `user_favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tmdb_movie_id` int(11) NOT NULL,
  `movie_title` varchar(255) NOT NULL,
  `movie_poster` varchar(255) DEFAULT NULL,
  `mood_tag` varchar(20) DEFAULT NULL, -- The mood associated with this recommendation
  `saved_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
