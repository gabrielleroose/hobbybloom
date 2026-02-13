SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS user_profiles;
DROP TABLE IF EXISTS feed;
DROP TABLE IF EXISTS log;
DROP TABLE IF EXISTS circle;
DROP TABLE IF EXISTS module;
DROP TABLE IF EXISTS tag;
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

CREATE TABLE circle (
    circle_id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(40) NOT NULL,
    uid INT NOT NULL,
    description TEXT,
    PRIMARY KEY (circle_id),
    FOREIGN KEY (uid) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    gender VARCHAR(50),
    hometown VARCHAR(100),
    bio TEXT,
    hobbies TEXT,
    last_login DATE,
    login_streak INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tag (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(40) NOT NULL,
    cid INT NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (cid) REFERENCES users(id) ON DELETE CASCADE
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
    PRIMARY KEY (id),
    FOREIGN KEY (cid) REFERENCES users(id) ON DELETE CASCADE
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
    FOREIGN KEY (mid) REFERENCES `module`(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (uid) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;


CREATE TABLE feed (
    uid INT NOT NULL,
    mid INT NOT NULL,
    lid INT NOT NULL,
    PRIMARY KEY (uid, mid),
    FOREIGN KEY (uid) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (mid) REFERENCES module(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
   FOREIGN KEY (lid) REFERENCES log(id)
       ON DELETE CASCADE
       ON UPDATE CASCADE
) ENGINE=InnoDB;


-- holds the videos for modules --
CREATE TABLE module_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    video_url VARCHAR(500) NOT NULL,
    lesson_number INT NOT NULL,
    FOREIGN KEY (module_id) REFERENCES module(id) ON DELETE CASCADE
);


-- MODULE STAGE TABLE ADDED TO HELP DEVELOPMENT OF MODULES, KEEP SEPARATE STAGES
CREATE TABLE module_stage (
id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
mid INT NOT NULL,
stage_num INT NOT NULL DEFAULT 1,
title varchar(100),
UNIQUE (mid, stage_num),
FOREIGN KEY (mid) REFERENCES module(id)
) ENGINE=InnoDB;

-- MODULE STAGE QUESTIONS (linked via module_stage id (msid))
CREATE TABLE module_stage_questions (
id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
msid INT NOT NULL,
question_text varchar(255),
is_correct INT NOT NULL DEFAULT 0,
order_num INT NOT NULL,
FOREIGN KEY (msid) REFERENCES module_stage(id)
) ENGINE=InnoDB;



-- MODULE STAGE PROGRESS TABLE TO KEEP TRACK OF USER PROGRESS.
CREATE TABLE module_stage_progress (
id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
uid INT NOT NULL,
mid INT NOT NULL,
msid INT NOT NULL,
FOREIGN KEY (uid) REFERENCES users(id),
FOREIGN KEY (mid) REFERENCES module(id), 
FOREIGN KEY (msid) REFERENCES module_stage(id)

) ENGINE=InnoDB;






