-- HobbyBloom database schema + sample data
--
-- Table definitions match the live Railway database (copied 2026-11-05),
-- plus events.gc_event_id from database/migrations/001 (needed for
-- Google Calendar sync).
--
-- WARNING: this drops and recreates every table. Use it only for a fresh
-- local or test database, never against the live Railway database.
--
-- Demo account data lives separately in database/demo_seed.sql.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `activities`;
DROP TABLE IF EXISTS `circle`;
DROP TABLE IF EXISTS `circle_members`;
DROP TABLE IF EXISTS `circle_messages`;
DROP TABLE IF EXISTS `event_invites`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `feed`;
DROP TABLE IF EXISTS `log`;
DROP TABLE IF EXISTS `module`;
DROP TABLE IF EXISTS `module_comments`;
DROP TABLE IF EXISTS `module_stage`;
DROP TABLE IF EXISTS `module_stage_progress`;
DROP TABLE IF EXISTS `module_stage_questions`;
DROP TABLE IF EXISTS `module_stage_questions_answers`;
DROP TABLE IF EXISTS `module_stage_questions_user_answers`;
DROP TABLE IF EXISTS `module_stage_videos`;
DROP TABLE IF EXISTS `module_user_completion`;
DROP TABLE IF EXISTS `module_user_favorite`;
DROP TABLE IF EXISTS `reports`;
DROP TABLE IF EXISTS `user_follows`;
DROP TABLE IF EXISTS `user_profiles`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `actor_id` int NOT NULL,
  `activity_type` enum('module_created','module_completed','circle_created','circle_joined') COLLATE utf8mb4_unicode_ci NOT NULL,
  `module_id` int DEFAULT NULL,
  `circle_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `actor_id` (`actor_id`),
  KEY `module_id` (`module_id`),
  KEY `circle_id` (`circle_id`),
  CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `activities_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE,
  CONSTRAINT `activities_ibfk_3` FOREIGN KEY (`circle_id`) REFERENCES `circle` (`circle_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `circle` (
  `circle_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uid` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#1f5077',
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'General',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`circle_id`),
  KEY `uid` (`uid`),
  CONSTRAINT `circle_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `circle_members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `circle_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` enum('member','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `circle_id` (`circle_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `circle_members_ibfk_1` FOREIGN KEY (`circle_id`) REFERENCES `circle` (`circle_id`) ON DELETE CASCADE,
  CONSTRAINT `circle_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `circle_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hobby_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `circle_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `event_invites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `user_id` int NOT NULL,
  `status` enum('pending','accepted','declined') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `event_invites_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_invites_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `gc_event_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_events_user` (`created_by`),
  CONSTRAINT `fk_events_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `feed` (
  `uid` int NOT NULL,
  `mid` int NOT NULL,
  `lid` int NOT NULL,
  PRIMARY KEY (`uid`,`mid`),
  KEY `mid` (`mid`),
  KEY `lid` (`lid`),
  CONSTRAINT `feed_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `feed_ibfk_2` FOREIGN KEY (`mid`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `feed_ibfk_3` FOREIGN KEY (`lid`) REFERENCES `log` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mid` int NOT NULL,
  `uid` int NOT NULL,
  `last_visited` date DEFAULT NULL,
  `times_visited` int NOT NULL DEFAULT '0',
  `complete` int NOT NULL DEFAULT '0',
  `feedback` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mid` (`mid`),
  KEY `uid` (`uid`),
  CONSTRAINT `log_ibfk_1` FOREIGN KEY (`mid`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `log_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `module` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cid` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `img_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating` int DEFAULT '0',
  `exp_level` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `num_lessons` int NOT NULL,
  `est_comp_time` int NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  CONSTRAINT `module_ibfk_1` FOREIGN KEY (`cid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `module_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `module_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `module_comments_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE,
  CONSTRAINT `module_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `module_stage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mid` int NOT NULL,
  `stage_num` int NOT NULL DEFAULT '1',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mid` (`mid`,`stage_num`),
  CONSTRAINT `module_stage_ibfk_1` FOREIGN KEY (`mid`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `module_stage_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uid` int NOT NULL,
  `mid` int NOT NULL,
  `msid` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `mid` (`mid`),
  KEY `msid` (`msid`),
  CONSTRAINT `module_stage_progress_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `module_stage_progress_ibfk_2` FOREIGN KEY (`mid`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `module_stage_progress_ibfk_3` FOREIGN KEY (`msid`) REFERENCES `module_stage` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `module_stage_questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `msid` int NOT NULL,
  `question_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_num` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `msid` (`msid`),
  CONSTRAINT `module_stage_questions_ibfk_1` FOREIGN KEY (`msid`) REFERENCES `module_stage` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `module_stage_questions_answers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `msqid` int NOT NULL,
  `answer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_correct` int NOT NULL DEFAULT '0',
  `ans_num` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `msqid` (`msqid`),
  CONSTRAINT `module_stage_questions_answers_ibfk_1` FOREIGN KEY (`msqid`) REFERENCES `module_stage_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `module_stage_questions_user_answers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uid` int NOT NULL,
  `msqaid` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `msqaid` (`msqaid`),
  CONSTRAINT `module_stage_questions_user_answers_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `module_stage_questions_user_answers_ibfk_2` FOREIGN KEY (`msqaid`) REFERENCES `module_stage_questions_answers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `module_stage_videos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `msid` int DEFAULT NULL,
  `video_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lesson_number` int DEFAULT NULL,
  `mid` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `msid` (`msid`),
  KEY `fk_msv_mid` (`mid`),
  CONSTRAINT `fk_msv_mid` FOREIGN KEY (`mid`) REFERENCES `module` (`id`) ON DELETE CASCADE,
  CONSTRAINT `module_stage_videos_ibfk_1` FOREIGN KEY (`msid`) REFERENCES `module_stage` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `module_user_completion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mid` int NOT NULL,
  `uid` int NOT NULL,
  `is_complete` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `mid` (`mid`),
  KEY `uid` (`uid`),
  CONSTRAINT `module_user_completion_ibfk_1` FOREIGN KEY (`mid`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `module_user_completion_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `module_user_favorite` (
  `mid` int NOT NULL,
  `uid` int NOT NULL,
  `is_favorite` int DEFAULT NULL,
  PRIMARY KEY (`mid`,`uid`),
  KEY `uid` (`uid`),
  CONSTRAINT `module_user_favorite_ibfk_1` FOREIGN KEY (`mid`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `module_user_favorite_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reporter_id` int NOT NULL,
  `reported_user_id` int DEFAULT NULL,
  `module_id` int DEFAULT NULL,
  `circle_id` int DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','reviewed','resolved') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `reporter_id` (`reporter_id`),
  KEY `reported_user_id` (`reported_user_id`),
  KEY `module_id` (`module_id`),
  KEY `circle_id` (`circle_id`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reports_ibfk_4` FOREIGN KEY (`circle_id`) REFERENCES `circle` (`circle_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_follows` (
  `follower_id` int NOT NULL,
  `followed_id` int NOT NULL,
  `status` enum('pending','accepted') COLLATE utf8mb4_unicode_ci DEFAULT 'accepted',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`follower_id`,`followed_id`),
  KEY `followed_id` (`followed_id`),
  CONSTRAINT `user_follows_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_follows_ibfk_2` FOREIGN KEY (`followed_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_profiles` (
  `profile_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `profile_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#1f5077',
  `hometown` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `hobbies` text COLLATE utf8mb4_unicode_ci,
  `last_login` date DEFAULT NULL,
  `login_streak` int DEFAULT '0',
  `is_private` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`profile_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `google_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `age` int DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `gc_access_token` text COLLATE utf8mb4_unicode_ci,
  `gc_refresh_token` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gc_token_expires` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Sample data
-- ---------------------------------------------------------------------------

INSERT INTO users (id, first_name, last_name, username, email, password, google_id) VALUES 
(1, 'System', 'Admin', 'Admin', 'admin@hobbybloom.com', 'password123', NULL),
(2, 'Chris', 'Martin', 'ChrisM201', 'martichc@iu.edu', NULL, '110679650095682993887');

INSERT INTO module (id, cid, name, description, rating, exp_level, num_lessons, est_comp_time, notes)
VALUES
(1, 1, 'Intro to SQL', 'Learn fundamental SQL concepts.', 5, 'beginner', 2, 60, 'Core foundations'),
(2, 1, 'Advanced Query Optimization', 'Deep dive into indexing and performance.', 4, 'expert', 3, 120, 'Performance focused'),
(3, 2, 'Making Eggs', 'Watch the video and view the article, then take the quiz if you want to test your knowledge!', 0, 'intermediate', 4, 20, 'Come watch some informational videos and/or test your knowledge with a quiz?');

INSERT INTO module_stage (id, mid, stage_num, title)
VALUES
(1, 1, 1, 'SELECT Statements'),
(2, 1, 2, 'Filtering Data'),
(3, 2, 1, 'Indexing Basics'),
(4, 2, 2, 'Execution Plans'),
(5, 2, 3, 'Query Refactoring');

INSERT INTO module_stage_questions (id, msid, question_text, order_num)
VALUES
(1, 1, 'Which clause retrieves data from a table?', 1),
(2, 3, 'What improves query lookup speed?', 1);

INSERT INTO module_stage_videos (id, msid, mid, video_url, lesson_number)
VALUES
(1, 1, 1, 'https://example.com/sql-select', 1),
(2, 3, 2, 'https://example.com/indexing-basics', 1);

INSERT INTO module_stage_questions_answers (msqid, answer, is_correct, ans_num)
VALUES
(1, 'SELECT', 1, 1),
(1, 'DROP', 0, 2),
(1, 'CREATE', 0, 3),
(1, 'GRAB', 0, 4);

INSERT INTO circle (name, uid, description, color, category) VALUES 
('🥖 Baking', 2, 'Sweet treats and sourdough. Share your best oven-baked results.', '#f4a460', 'Wellness'),
('👑 Clash Royale', 2, 'Come discuss decks, recent updates, and learn some tips :)', '#9370db', 'Technical'),
('🧹 Cleaning', 2, 'All about how to best clean your living spaces!', '#cccccc', 'Wellness'),
('🍳 Cooking', 2, 'A place for beginners and chefs to share recipes and culinary adventures.', '#ff9999', 'Wellness'),
('🍿 Film Lovers', 2, 'A place to meet fellow lovers of film, talk about the most recent box office hits, flops, or hidden gems.', '#5cacee', 'Arts'),
('🎮 Gaming', 2, 'Find teammates, talk strategy, and discuss new releases.', '#9370db', 'Technical'),
('🪴 Gardening', 2, 'Grow your own food and flowers! Share tips for happy plants.', '#26f749', 'Wellness'),
('🥾 Hiking', 2, 'Let''s hit the trails! Share gear reviews and scenic paths.', '#90ee90', 'Wellness'),
('🧶 Knitting', 2, 'Yarn lovers unite!! Share your latest patterns and cozy creations.', '#e6e6fa', 'Arts'),
('🧱 Lego', 2, 'Brick by brick, show off your sets and creative masterpieces.', '#ffd700', 'Technical'),
('🧘 Meditation', 2, 'Find your zen. Share mindfulness techniques and peaceful spots.', '#afeeee', 'Wellness'),
('🎬 Movies', 2, 'The silver screen community. Discuss reviews, actors, and directing.', '#5cacee', 'Arts'),
('🎵 Music', 2, 'For the listeners and the players. Discuss theory, gear, and hits.', '#ac58ca', 'Arts'),
('🏴‍☠️ One Piece', 2, 'Join a community that engulfs One Piece as a whole. Talk about the card game, the manga, the show, your favorite or least favorite characters, and more!', '#dc2626', 'Arts'),
('🎨 Painting', 2, 'From watercolors to acrylics, share your canvas and techniques.', '#ffcc00', 'Arts'),
('📚 Reading', 2, 'A sanctuary for book lovers. Discuss your latest reads and favorites.', '#deb887', 'Arts'),
('🏎️ Rocket League', 2, 'Come chat about all things rocket league, Learn things if your new or expand knowledge if your an experienced play. Connects with the community.', '#1f5077', 'Technical'),
('🧵 Sewing', 2, 'Stitch your way to success. Share patterns and garment projects.', '#ff66b2', 'Arts'),
('👽 Star Wars', 2, 'Join a community that is interested in Star Wars! Talk about favorite scenes, favorite or least favorite characters, the books, the shows, and more!', '#000000', 'Arts'),
('🌍 Travel', 2, 'Join a community that loves to travel! Talk about where you have been, where you want to go, and more!', '#2563eb', 'Wellness'),
('✍️ Writing', 2, 'Find other people interested in writing! Talk about your favorite books, book adaptations, and more!', '#f59e0b', 'Arts'),
('🧘 Yoga', 2, 'Stretch, breathe, and flow. A community for all skill levels.', '#ff881a', 'Wellness');

INSERT INTO module_stage (id, mid, stage_num, title) VALUES
(6, 3, 1, 'Sunny Side Up Rules'),
(7, 3, 2, 'Peeling Perfection'),
(8, 3, 3, 'Scrambling Secrets'),
(9, 3, 4, 'Mastering the Over-Easy');

INSERT INTO module_stage_videos (msid, mid, video_url, lesson_number) VALUES
(6, 3, 'https://youtu.be/zgpK5eeZ4Jg?si=cyD_1DobnDeNTiOj', 1),
(7, 3, 'https://youtu.be/FTha4zARGN4?si=bYogXm4_MVVWutkO', 2),
(8, 3, 'https://youtu.be/7goNbTdFwNM?si=VvDj4adROp4v7CgD', 3),
(9, 3, 'https://youtu.be/pIygps4v98c?si=3PsFdZZV1QBOYDjh', 4);

INSERT INTO module_stage_questions (id, msid, question_text, order_num) VALUES
(3, 6, 'If you want a "Sunny Side Up" egg, what is the one thing you should NOT do?', 1),
(4, 7, 'What is the best way to get the shell off a hard-boiled egg easily?', 1),
(5, 8, 'When making scrambled eggs, why should you stir them while they cook?', 1),
(6, 9, 'What does it mean if an egg is "Over Easy"?', 1);

INSERT INTO module_stage_questions_answers (msqid, answer, is_correct, ans_num) VALUES
(3, 'Use a frying pan', 0, 1),
(3, 'Flip the egg over', 1, 2),
(3, 'Use a little bit of butter', 0, 3),
(3, 'Crack the egg carefully', 0, 4);

INSERT INTO module_stage_questions_answers (msqid, answer, is_correct, ans_num) VALUES
(4, 'Peel it while it is still burning hot', 0, 1),
(4, 'Put it in a bowl of ice water right after cooking', 1, 2),
(4, 'Let it sit on the counter for three hours', 0, 3),
(4, 'Use the freshest egg you can find', 0, 4);

INSERT INTO module_stage_questions_answers (msqid, answer, is_correct, ans_num) VALUES
(5, 'To keep them from getting too big and chunky', 1, 1),
(5, 'To make the stove heat up faster', 0, 2),
(5, 'To change the color of the yolk', 0, 3),
(5, 'To make sure the shell doesn''t get in', 0, 4);

INSERT INTO module_stage_questions_answers (msqid, answer, is_correct, ans_num) VALUES
(6, 'The yolk is hard and dry', 0, 1),
(6, 'The egg was cooked in a microwave', 0, 2),
(6, 'The egg was flipped, but the yolk is still runny', 1, 3),
(6, 'The egg was boiled in its shell', 0, 4);
