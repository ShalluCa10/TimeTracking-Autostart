-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost:3306
-- Thời gian đã tạo: Th8 10, 2026 lúc 10:54 PM
-- Phiên bản máy phục vụ: 5.7.24
-- Phiên bản PHP: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `timetracking`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(80) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'admin', '$2y$10$rYZmutKCCrRRSHMoh8tDm.kailq7qDx.uvsB8G/NBL39UZnHADN7m', '2026-05-27 00:18:35');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `games`
--

CREATE TABLE `games` (
  `game_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `game_events`
--

CREATE TABLE `game_events` (
  `id` int(11) NOT NULL,
  `version_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `game_events`
--

INSERT INTO `game_events` (`id`, `version_id`, `name`, `image`, `sort_order`) VALUES
(15, 4, 'BAHRAIN', '/assets/uploads/game_events_15_1785604177.png', 1),
(16, 4, 'SAUDI ARABIA', '/assets/uploads/game_events_16_1785604320.png', 2),
(17, 4, 'AUSTRALIA', '/assets/uploads/game_events_17_1785604472.png', 3),
(18, 4, 'JAPAN', '/assets/uploads/game_events_18_1785604812.png', 4),
(19, 4, 'CHINA', '/assets/uploads/game_events_19_1785605038.jpg', 5),
(20, 4, 'MIAMI', '/assets/uploads/game_events_20_1785605136.png', 6),
(21, 4, 'IMOLA', '/assets/uploads/game_events_21_1785711712.jpg', 7),
(22, 4, 'MONACO', NULL, 8),
(23, 4, 'CANADA', NULL, 9),
(24, 4, 'SPAIN', NULL, 10),
(25, 4, 'AUSTEIA', NULL, 11),
(26, 4, 'GREAT BRITAIN', NULL, 12),
(27, 4, 'HUNGARY', NULL, 13),
(28, 4, 'BELGIUM', NULL, 14),
(29, 4, 'NETHERLANDS', NULL, 15),
(30, 4, 'MONZQ', NULL, 16),
(31, 4, 'AZERBAIJAN', NULL, 17),
(32, 4, 'SINGAPORE', NULL, 18),
(33, 4, 'TEXAS', NULL, 19),
(34, 4, 'MEXICO', NULL, 20),
(35, 4, 'BRAZIL', NULL, 21),
(36, 4, 'LAS VEGAS', NULL, 22),
(37, 4, 'QATAR', NULL, 23),
(38, 4, 'ABU DHABI', NULL, 24),
(39, 4, 'PORTUGAL', NULL, 25),
(40, 5, 'Henry', NULL, 1),
(41, 5, 'Tom', NULL, 2);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `game_teams`
--

CREATE TABLE `game_teams` (
  `id` int(11) NOT NULL,
  `version_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `game_teams`
--

INSERT INTO `game_teams` (`id`, `version_id`, `name`, `image`, `sort_order`) VALUES
(15, 4, 'ORACLE RED BULL RACING', '/assets/uploads/game_teams_15_1785603732.jpg', 0),
(16, 4, 'MERCEDES-AMG PETRONAS FORMULA ONE TEAM', '/assets/uploads/game_teams_16_1785603737.jpg', 1),
(17, 4, 'SCUDERIA FERRARI HP', '/assets/uploads/game_teams_17_1785603740.jpg', 2),
(18, 4, 'MCLAREN FORMULA 1 TEAM', '/assets/uploads/game_teams_18_1785603746.jpg', 3),
(19, 4, 'ASTON MARTIN ARAMCO FORMULA ONE TEAM', '/assets/uploads/game_teams_19_1785603750.jpg', 4),
(20, 4, 'BWT ALPINE F1 TEAM', '/assets/uploads/game_teams_20_1785603753.jpg', 5),
(21, 4, 'WILLIAMS RACING', '/assets/uploads/game_teams_21_1785603757.jpg', 6),
(22, 4, 'VISA CASH APP RB F1 TEAM', '/assets/uploads/game_teams_22_1785603760.jpg', 7),
(23, 4, 'KICK SAUBER F1 TEAM', '/assets/uploads/game_teams_23_1785603764.jpg', 8),
(24, 4, 'MONEYGRAM HAAS F1 TEAM', '/assets/uploads/game_teams_24_1785603768.jpg', 9),
(25, 4, 'F1® WORLD CAR', '/assets/uploads/game_teams_25_1785603773.jpg', 10),
(26, 5, 'Ferrari', '/assets/uploads/game_teams_26_1786025470.jpg', 1),
(27, 5, 'Mercedes', NULL, 2);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `game_versions`
--

CREATE TABLE `game_versions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `game_versions`
--

INSERT INTO `game_versions` (`id`, `name`) VALUES
(5, 'F1 25'),
(4, 'F1® 24');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `laps`
--

CREATE TABLE `laps` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `lap_number` int(11) NOT NULL,
  `lap_time_ms` int(11) NOT NULL,
  `lap_time` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `laps`
--

INSERT INTO `laps` (`id`, `session_id`, `lap_number`, `lap_time_ms`, `lap_time`, `created_at`) VALUES
(1, 2, 1, 7860, '00:07', '2026-08-03 03:46:04'),
(2, 2, 2, 4912, '00:04', '2026-08-03 03:46:04'),
(3, 3, 1, 7828, '00:07', '2026-08-06 14:09:37'),
(4, 3, 2, 3962, '00:03', '2026-08-06 14:09:37'),
(5, 3, 3, 3335, '00:03', '2026-08-06 14:09:37');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `results`
--

CREATE TABLE `results` (
  `result_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `position` int(11) NOT NULL DEFAULT '0',
  `best_lap_time` varchar(20) NOT NULL DEFAULT '',
  `total_time` varchar(50) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `schedule_name` varchar(150) NOT NULL,
  `schedule_date` date NOT NULL,
  `location` varchar(150) NOT NULL DEFAULT '',
  `version_id` int(11) DEFAULT NULL,
  `team` varchar(100) DEFAULT NULL,
  `event` varchar(100) DEFAULT NULL,
  `racer` varchar(100) DEFAULT NULL,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('auto','live','canceled','completed') NOT NULL DEFAULT 'auto',
  `timer_minutes` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `schedule_name`, `schedule_date`, `location`, `version_id`, `team`, `event`, `racer`, `notes`, `created_at`, `status`, `timer_minutes`) VALUES
(1, 'Testing 1', '2026-08-03', 'Toronto, ON', 4, 'SCUDERIA FERRARI HP', 'MEXICO', '', '', '2026-08-02 22:48:06', 'live', 5),
(2, 'Test 2', '2026-08-06', '', 4, 'KICK SAUBER F1 TEAM', 'AZERBAIJAN', '', '', '2026-08-06 10:08:49', 'auto', 5);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sessions`
--

CREATE TABLE `sessions` (
  `session_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `f1_version` varchar(50) DEFAULT NULL,
  `participant_name` varchar(120) NOT NULL,
  `team` varchar(100) NOT NULL DEFAULT '',
  `event` varchar(100) NOT NULL DEFAULT '',
  `best_lap_time` varchar(20) NOT NULL DEFAULT '',
  `timer_minutes` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Đang đổ dữ liệu cho bảng `sessions`
--

INSERT INTO `sessions` (`session_id`, `schedule_id`, `f1_version`, `participant_name`, `team`, `event`, `best_lap_time`, `timer_minutes`, `created_at`) VALUES
(1, 1, 'F1® 24', 'Le', 'SCUDERIA FERRARI HP', 'MEXICO', '', 5, '2026-08-02 22:52:31'),
(2, 1, 'F1® 24', 'Paisley', 'SCUDERIA FERRARI HP', 'MEXICO', '00:04', 5, '2026-08-02 23:36:09'),
(3, 1, 'F1® 24', '', 'SCUDERIA FERRARI HP', 'MEXICO', '00:03', 5, '2026-08-06 10:09:06'),
(4, 1, 'F1® 24', '', 'SCUDERIA FERRARI HP', 'MEXICO', '', 5, '2026-08-06 10:13:58');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `teams`
--

CREATE TABLE `teams` (
  `team_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `uq_username` (`username`);

--
-- Chỉ mục cho bảng `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`game_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Chỉ mục cho bảng `game_events`
--
ALTER TABLE `game_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `version_id` (`version_id`);

--
-- Chỉ mục cho bảng `game_teams`
--
ALTER TABLE `game_teams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `version_id` (`version_id`);

--
-- Chỉ mục cho bảng `game_versions`
--
ALTER TABLE `game_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Chỉ mục cho bảng `laps`
--
ALTER TABLE `laps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`);

--
-- Chỉ mục cho bảng `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Chỉ mục cho bảng `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `fk_events_version` (`version_id`);

--
-- Chỉ mục cho bảng `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Chỉ mục cho bảng `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`team_id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `code` (`code`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `games`
--
ALTER TABLE `games`
  MODIFY `game_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `game_events`
--
ALTER TABLE `game_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT cho bảng `game_teams`
--
ALTER TABLE `game_teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT cho bảng `game_versions`
--
ALTER TABLE `game_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `laps`
--
ALTER TABLE `laps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `results`
--
ALTER TABLE `results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `teams`
--
ALTER TABLE `teams`
  MODIFY `team_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `game_events`
--
ALTER TABLE `game_events`
  ADD CONSTRAINT `game_events_ibfk_1` FOREIGN KEY (`version_id`) REFERENCES `game_versions` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `game_teams`
--
ALTER TABLE `game_teams`
  ADD CONSTRAINT `game_teams_ibfk_1` FOREIGN KEY (`version_id`) REFERENCES `game_versions` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `laps`
--
ALTER TABLE `laps`
  ADD CONSTRAINT `laps_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `fk_schedules_version` FOREIGN KEY (`version_id`) REFERENCES `game_versions` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
