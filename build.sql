CREATE TABLE users (
 id INT NOT NULL AUTO_INCREMENT,
 username VARCHAR(32) NOT NULL,
 password VARCHAR(32) NOT NULL,
 age INT NOT NULL,
 email VARCHAR(255) NOT NULL,
 phone VARCHAR(20) NOT NULL,
 type TINYINT(1) NOT NULL DEFAULT 0,
 PRIMARY KEY (id) ) 
ENGINE=InnoDB;


CREATE TABLE tag (
 id INT NOT NULL AUTO_INCREMENT,
 name VARCHAR(40) NOT NULL,
 cid INT NOT NULL,
 PRIMARY KEY (id),
 FOREIGN KEY (cid) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;


CREATE TABLE `module` (
    id INT NOT NULL AUTO_INCREMENT,
    cid INT NOT NULL,
    tid INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(500),
    rating INT DEFAULT 0,
    exp_level VARCHAR(20) NOT NULL,
    num_lessons INT NOT NULL,
    est_comp_time INT NOT NULL CHECK (est_comp_time % 15 = 0),	   
    PRIMARY KEY (id),
    FOREIGN KEY (cid) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
  FOREIGN KEY (tid) REFERENCES tag(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
        CHECK (exp_level IN ('beginner', 'intermediate', 'expert'))
        ) ENGINE=InnoDB;

CREATE TABLE circle (
    circle_id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(40) NOT NULL,
    uid INT NOT NULL,
    PRIMARY KEY (circle_id),
    FOREIGN KEY (uid) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE log (
    mid INT NOT NULL,
    uid INT NOT NULL,
    last_visited DATE,
    times_visited INT NOT NULL DEFAULT 0,
    feedback VARCHAR(500),
    PRIMARY KEY (mid, uid),
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
    PRIMARY KEY (uid, mid),
    FOREIGN KEY (uid) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (mid) REFERENCES `module`(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;





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