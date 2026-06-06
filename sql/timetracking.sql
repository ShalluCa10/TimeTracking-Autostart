CREATE DATABASE IF NOT EXISTS 
timetracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE timetracking;

CREATE TABLE IF NOT EXISTS admins (
    admin_id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (admin_id),
    UNIQUE KEY uq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS events (
    event_id INT NOT NULL AUTO_INCREMENT,
    event_name VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    location VARCHAR(150) NOT NULL DEFAULT '',
    notes TEXT,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sessions (
    session_id INT NOT NULL AUTO_INCREMENT,
    event_id INT NOT NULL,
    f1_version VARCHAR(50) NULL DEFAULT '',
    participant_name VARCHAR(120) NOT NULL,
    car VARCHAR(100) NOT NULL DEFAULT '',
    track VARCHAR(100) NOT NULL DEFAULT '',
    best_lap_time VARCHAR(20) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (session_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE laps (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    session_id  INT            NOT NULL,
    lap_number  INT            NOT NULL,
    lap_time_ms INT            NOT NULL,
    lap_time    VARCHAR(20)    NOT NULL,
    created_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (session_id) REFERENCES sessions(session_id) ON DELETE CASCADE
);

INSERT IGNORE INTO admins (username, password_hash) VALUES (
    'admin',
    '$2y$10$rYZmutKCCrRRSHMoh8tDm.kailq7qDx.uvsB8G/NBL39UZnHADN7m'
);