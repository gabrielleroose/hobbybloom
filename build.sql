SET FOREIGN_KEY_CHECKS = 0;  
DROP TABLE IF EXISTS module_comments;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS circle_members;
DROP TABLE IF EXISTS event_invites;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS module_stage_videos;
DROP TABLE IF EXISTS feed;
DROP TABLE IF EXISTS log;
DROP TABLE IF EXISTS module_stage_progress;
DROP TABLE IF EXISTS module_stage_questions_user_answers;
DROP TABLE IF EXISTS module_stage_questions_answers;
DROP TABLE IF EXISTS module_stage_questions;
DROP TABLE IF EXISTS module_stage;
DROP TABLE IF EXISTS module;
DROP TABLE IF EXISTS tag;
DROP TABLE IF EXISTS user_follows;
DROP TABLE IF EXISTS user_profiles;
DROP TABLE IF EXISTS circle_messages;
DROP TABLE IF EXISTS circle;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    google_id VARCHAR(255) UNIQUE,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255),
    age INT,
    phone VARCHAR(20),
    type TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE user_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    profile_color VARCHAR(7) DEFAULT '#1f5077',
    hometown VARCHAR(100),
    bio TEXT,
    hobbies TEXT,
    last_login DATE,
    login_streak INT DEFAULT 0,
    is_private TINYINT(1) DEFAULT 0, 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_follows (
    follower_id INT NOT NULL,
    followed_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (follower_id, followed_id),
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE circle (
    circle_id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(40) NOT NULL,
    uid INT,
    description TEXT,
    color VARCHAR(7) DEFAULT '#1f5077',
    category VARCHAR(50) DEFAULT 'General',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (circle_id),
    FOREIGN KEY (uid) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE circle_messages (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    hobby_name VARCHAR(50) NOT NULL, 
    user_id INT NOT NULL, 
    message TEXT NOT NULL, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE circle_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    circle_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('member','admin') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(circle_id, user_id),
    FOREIGN KEY (circle_id) REFERENCES circle(circle_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE module (
    id INT NOT NULL AUTO_INCREMENT,
    cid INT NOT NULL, 
    name VARCHAR(100) NOT NULL,
    description VARCHAR(500),
    img_path VARCHAR(255),
    rating INT DEFAULT 0,
    exp_level VARCHAR(20) NOT NULL,
    num_lessons INT NOT NULL,
    est_comp_time INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (cid) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE module_stage (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    mid INT NOT NULL,
    stage_num INT NOT NULL DEFAULT 1,
    title varchar(100),
    UNIQUE (mid, stage_num),
    FOREIGN KEY (mid) REFERENCES module(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE module_stage_questions (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    msid INT NOT NULL,
    question_text varchar(255),
    order_num INT NOT NULL,
    FOREIGN KEY (msid) REFERENCES module_stage(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE module_stage_questions_answers (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    msqid INT NOT NULL,
    answer varchar(255),
    is_correct INT NOT NULL DEFAULT 0,
    ans_num INT NOT NULL,
    FOREIGN KEY (msqid) REFERENCES module_stage_questions(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE module_stage_questions_user_answers (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL, 
    msqaid INT NOT NULL,
    FOREIGN KEY (uid) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (msqaid) REFERENCES module_stage_questions_answers(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE module_stage_progress (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    mid INT NOT NULL,
    msid INT NOT NULL,
    FOREIGN KEY (uid) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (mid) REFERENCES module(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (msid) REFERENCES module_stage(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE module_stage_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    msid INT NOT NULL,
    video_url VARCHAR(500) NOT NULL,
    lesson_number INT NOT NULL,
    FOREIGN KEY (msid) REFERENCES module_stage(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE module_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES module(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE log (
    id INT NOT NULL AUTO_INCREMENT,
    mid INT NOT NULL,
    uid INT NOT NULL,
    last_visited DATE,
    times_visited INT NOT NULL DEFAULT 0,
    complete INT NOT NULL DEFAULT 0,
    feedback VARCHAR(500),
    PRIMARY KEY (id),
    FOREIGN KEY (mid) REFERENCES module(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (uid) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE feed (
    uid INT NOT NULL,
    mid INT NOT NULL,
    lid INT NOT NULL,
    PRIMARY KEY (uid, mid),
    FOREIGN KEY (uid) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (mid) REFERENCES module(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (lid) REFERENCES log(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tag (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(40) NOT NULL,
    cid INT NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (cid) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME,
    description TEXT,
    location VARCHAR(255),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE event_invites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending','accepted','declined') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    reported_user_id INT NULL,
    module_id INT NULL,
    circle_id INT NULL,
    reason TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','reviewed','resolved') DEFAULT 'pending',
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES module(id) ON DELETE CASCADE,
    FOREIGN KEY (circle_id) REFERENCES circle(circle_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- TEST DATA --
INSERT INTO users (id, username, email, password, google_id) VALUES 
(1, 'Admin', 'admin@hobbybloom.com', 'password123', NULL),
(2, 'ChrisM201', 'martichc@iu.edu', NULL, '110679650095682993887');

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

INSERT INTO module_stage_videos (id, msid, video_url, lesson_number)
VALUES
(1, 1, 'https://example.com/sql-select', 1),
(2, 3, 'https://example.com/indexing-basics', 1);

INSERT INTO module_stage_questions_answers (msqid, answer, is_correct, ans_num)
VALUES
(1, 'SELECT', 1, 1),
(1, 'DROP', 0, 2),
(1, 'CREATE', 0, 3),
(1, 'GRAB', 0, 4);

INSERT INTO circle (name, uid, description, color, category) VALUES 
('Cooking', 2, 'A place for beginners and chefs to share recipes and culinary adventures.', '#ff9999', 'Wellness'),
('Knitting', 2, 'Yarn lovers unite!! Share your latest patterns and cozy creations.', '#e6e6fa', 'Arts'),
('Lego', 2, 'Brick by brick, show off your sets and creative masterpieces.', '#ffd700', 'Technical'),
('Sewing', 2, 'Stitch your way to success. Share patterns and garment projects.', '#ff66b2', 'Arts'),
('Painting', 2, 'From watercolors to acrylics, share your canvas and techniques.', '#ffcc00', 'Arts'),
('Hiking', 2, 'Let\'s hit the trails! Share gear reviews and scenic paths.', '#90ee90', 'Wellness'),
('Reading', 2, 'A sanctuary for book lovers. Discuss your latest reads and favorites.', '#deb887', 'Arts'),
('Gardening', 2, 'Grow your own food and flowers! Share tips for happy plants.', '#26f749', 'Wellness'),
('Baking', 2, 'Sweet treats and sourdough. Share your best oven-baked results.', '#f4a460', 'Wellness'),
('Meditation', 2, 'Find your zen. Share mindfulness techniques and peaceful spots.', '#afeeee', 'Wellness'),
('Music', 2, 'For the listeners and the players. Discuss theory, gear, and hits.', '#ac58ca', 'Arts'),
('Movies', 2, 'The silver screen community. Discuss reviews, actors, and directing.', '#5cacee', 'Arts'),
('Gaming', 2, 'Find teammates, talk strategy, and discuss new releases.', '#9370db', 'Technical'),
('Yoga', 2, 'Stretch, breathe, and flow. A community for all skill levels.', '#ff881a', 'Wellness');
