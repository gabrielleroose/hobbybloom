CREATE TABLE users (
id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
username VARCHAR(32) NOT NULL,
password VARCHAR(32) NOT NULL,
age INT NOT NULL,
email VARCHAR (255) NOT NULL,
phone VARCHAR(20) NOT NULL,
type tinyint(1) NOT NULL DEFAULT 0);

CREATE TABLE tag (
id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
name VARCHAR(40) NOT NULL,
cid INT NOT NULL,
FOREIGN KEY (cid) REFERENCES user (id) ON DELETE CASCADE ON UPDATE CASCADE);

CREATE TABLE module (
id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
name VARCHAR(100) NOT NULL,
rating INT DEFAULT 0,
feedback VARCHAR(500),
cid INT NOT NULL, 
tid INT NOT NULL,
FOREIGN KEY (cid) REFERENCES user (id) ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY (tid) REFERENCES tag (id) ON DELETE CASCADE ON UPDATE CASCADE);

CREATE TABLE circle (
circle_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
name VARCHAR(40) NOT NULL,
uid INT NOT NULL,
FOREIGN KEY (uid) REFERENCES user (id) ON DELETE CASCADE ON UPDATE CASCADE);


CREATE TABLE log (
    mid INT NOT NULL,
    uid INT NOT NULL,
    last_visited DATE,
    times_visited INT NOT NULL DEFAULT 0,
    PRIMARY KEY (mid),
    FOREIGN KEY (mid) REFERENCES module (id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (uid) REFERENCES user (id) ON DELETE CASCADE ON UPDATE CASCADE);

CREATE TABLE feed (
uid INT NOT NULL,
mid INT NOT NULL,
PRIMARY KEY (uid),
FOREIGN KEY (uid) REFERENCES user (id) ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY (mid) REFERENCES module (id) ON DELETE CASCADE ON UPDATE CASCADE);




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