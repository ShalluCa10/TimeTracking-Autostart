-- ============================================================
--  F1 AutoStart — Event Management Panel
--  Database Schema v1.0
--  Run this file once to set up the database.
-- ============================================================

CREATE DATABASE IF NOT EXISTS timetracking
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE timetracking;

-- -----------------------------------------------------------
-- Table: admins
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    admin_id      INT           NOT NULL AUTO_INCREMENT,
    username      VARCHAR(80)   NOT NULL,
    password_hash VARCHAR(255)  NOT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (admin_id),
    UNIQUE KEY uq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin: username = admin | password = Admin@1234
-- Change the password after first login via the DB or add a change-password page.
INSERT INTO admins (username, password_hash) VALUES (
    'admin',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'  -- password: password
);

-- -----------------------------------------------------------
-- Table: events
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
    event_id   INT           NOT NULL AUTO_INCREMENT,
    event_name VARCHAR(150)  NOT NULL,
    event_date DATE          NOT NULL,
    location   VARCHAR(200)  NULL,
    notes      TEXT          NULL,
    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME      NULL     ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Table: sessions
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions (
    session_id       INT                     NOT NULL AUTO_INCREMENT,
    event_id         INT                     NOT NULL,
    participant_name VARCHAR(100)            NOT NULL,
    car              VARCHAR(80)             NULL,
    track            VARCHAR(80)             NULL,
    best_lap_time    VARCHAR(12)             NOT NULL,
    source           ENUM('manual','api')    NOT NULL DEFAULT 'manual',
    recorded_at      DATETIME                NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (session_id),
    CONSTRAINT fk_sessions_event
        FOREIGN KEY (event_id) REFERENCES events(event_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data (optional — remove for production)
INSERT INTO events (event_name, event_date, location, notes) VALUES
    ('Rogers Centre F1 Expo', '2025-06-14', 'Toronto, ON', 'Annual simulator showcase'),
    ('Oshawa AutoShow Simulator', '2025-05-03', 'Oshawa, ON', NULL);

INSERT INTO sessions (event_id, participant_name, car, track, best_lap_time) VALUES
    (1, 'Jasdeep Singh', 'Red Bull RB20', 'Monza', '01:21.234'),
    (1, 'Mohamed Ali',   'Ferrari SF-24', 'Spa',   '01:22.891'),
    (1, 'Sarah Connor',  'Mercedes W15', 'Monza',  '01:24.100');
