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


CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    start DATETIME NOT NULL,
    end DATETIME NOT NULL,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id)
);


/*  Drew Module tables - need to run by team
CREATE TABLE module (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    description TEXT,
    number_of_lessons INT,
    notes TEXT,
    xpLevel VARCHAR(50),
    compTime INT,
    created_by INT
);

CREATE TABLE module_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    video_url VARCHAR(500) NOT NULL,
    lesson_number INT NOT NULL,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
);

*/ -- also may need to create another table to track who is enrolled in the module