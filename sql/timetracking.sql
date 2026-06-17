-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 17, 2026 at 03:12 PM
-- Server version: 5.7.24
-- PHP Version: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `timetracking`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(80) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'admin', '$2y$10$rYZmutKCCrRRSHMoh8tDm.kailq7qDx.uvsB8G/NBL39UZnHADN7m', '2026-05-27 00:18:35');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(150) NOT NULL,
  `event_date` date NOT NULL,
  `location` varchar(150) NOT NULL DEFAULT '',
  `version_id` int(11) DEFAULT NULL,
  `car` varchar(100) DEFAULT NULL,
  `track` varchar(100) DEFAULT NULL,
  `racer` varchar(100) DEFAULT NULL,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('auto','live','canceled','completed') NOT NULL DEFAULT 'auto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_name`, `event_date`, `location`, `version_id`, `car`, `track`, `racer`, `notes`, `created_at`, `status`) VALUES
(3, 'New Toronto', '2026-06-10', 'Toronto Hall', 1, 'AMR24', 'Bahrain International Circuit', 'Oscar Piastri (#81)', '', '2026-06-10 00:00:51', 'completed'),
(4, 'Car Club', '2026-07-10', 'Montreal, QC', 2, 'HAAS', 'Circuit de Spa-Francorchamps', 'Alexander Albon', '', '2026-06-10 04:42:06', 'live');

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `game_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `game_cars`
--

CREATE TABLE `game_cars` (
  `id` int(11) NOT NULL,
  `version_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `game_cars`
--

INSERT INTO `game_cars` (`id`, `version_id`, `name`, `image`, `sort_order`) VALUES
(5, 1, 'SF-24', NULL, 1),
(6, 1, 'AMR24', '/assets/uploads/game_cars_6_1781677359.jpg', 0),
(7, 1, 'A524', NULL, 2),
(8, 1, 'VF-24', NULL, 3),
(9, 1, 'C44', NULL, 4),
(10, 1, 'MCL38', NULL, 5),
(11, 2, 'Alpine 2025', NULL, 11),
(12, 2, 'HAAS', NULL, 12),
(13, 2, 'Aston Martin', NULL, 13),
(14, 2, 'Williams 2025', NULL, 14);

-- --------------------------------------------------------

--
-- Table structure for table `game_drivers`
--

CREATE TABLE `game_drivers` (
  `driver_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `game_racers`
--

CREATE TABLE `game_racers` (
  `id` int(11) NOT NULL,
  `version_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `game_racers`
--

INSERT INTO `game_racers` (`id`, `version_id`, `name`, `image`, `sort_order`) VALUES
(2, 1, 'LeLerc', NULL, 1),
(4, 1, 'Lando Norris (#4)', NULL, 2),
(5, 1, 'Oscar Piastri (#81)', NULL, 0),
(7, 2, 'Alexander Albon', NULL, 7),
(9, 2, 'Andrea Kimi Antonelli', NULL, 9),
(10, 2, 'Oliver Bearman', NULL, 10),
(11, 2, 'Gabriel Bortoleto', NULL, 11),
(12, 2, 'Jack Doohan', NULL, 12),
(13, 1, 'Valtteri Bottas (#77).', NULL, 3),
(14, 1, 'Guanyu Zhou (#24)', NULL, 4);

-- --------------------------------------------------------

--
-- Table structure for table `game_tracks`
--

CREATE TABLE `game_tracks` (
  `id` int(11) NOT NULL,
  `version_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `game_tracks`
--

INSERT INTO `game_tracks` (`id`, `version_id`, `name`, `image`, `sort_order`) VALUES
(3, 2, 'Spa', NULL, 3),
(4, 1, 'Bahrain International Circuit', '/assets/uploads/game_tracks_4_1781677650.png', 4),
(5, 1, 'Jeddah Corniche Circuit', NULL, 5),
(6, 1, 'Albert Park', NULL, 6),
(7, 1, 'Suzuka', NULL, 7),
(8, 1, 'Shanghai International Circuit', NULL, 8),
(9, 2, 'Circuit de Spa-Francorchamps', NULL, 9),
(10, 2, 'Hungaroring', NULL, 10),
(11, 2, 'Circuit Zandvoort', NULL, 11),
(12, 2, 'Baku City Circuit', NULL, 12),
(13, 2, 'Singapore Marina Bay', NULL, 13),
(14, 1, 'monza', NULL, 14);

-- --------------------------------------------------------

--
-- Table structure for table `game_versions`
--

CREATE TABLE `game_versions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `game_versions`
--

INSERT INTO `game_versions` (`id`, `name`) VALUES
(1, 'F1 2024'),
(2, 'F1 2025');

-- --------------------------------------------------------

--
-- Table structure for table `laps`
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
-- Dumping data for table `laps`
--

INSERT INTO `laps` (`id`, `session_id`, `lap_number`, `lap_time_ms`, `lap_time`, `created_at`) VALUES
(4, 3, 1, 4841, '00:04.841', '2026-06-10 04:01:17'),
(5, 3, 2, 11193, '00:11.193', '2026-06-10 04:01:28'),
(6, 3, 3, 6950, '00:06.950', '2026-06-10 04:01:35'),
(7, 3, 4, 2264, '00:02.264', '2026-06-10 04:01:38'),
(8, 5, 1, 664, '00:00', '2026-06-10 04:24:49'),
(9, 6, 1, 8788, '00:08', '2026-06-10 13:18:05'),
(10, 6, 2, 9818, '00:09', '2026-06-10 13:18:05'),
(11, 6, 3, 2292, '00:02', '2026-06-10 13:18:05'),
(12, 6, 4, 4643, '00:04', '2026-06-10 13:18:05'),
(13, 6, 5, 10479, '00:10', '2026-06-10 13:18:05'),
(14, 12, 1, 888, '00:00', '2026-06-16 06:59:58'),
(15, 12, 2, 904, '00:00', '2026-06-16 06:59:58'),
(16, 12, 3, 721, '00:00', '2026-06-16 06:59:58'),
(17, 17, 1, 4409, '00:04', '2026-06-17 06:08:12'),
(18, 18, 1, 4224, '00:04', '2026-06-17 06:08:25'),
(19, 18, 2, 1526, '00:01', '2026-06-17 06:08:25');

-- --------------------------------------------------------

--
-- Table structure for table `results`
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
-- Table structure for table `rigs`
--

CREATE TABLE `rigs` (
  `rig_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `session_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `f1_version` varchar(50) DEFAULT NULL,
  `participant_name` varchar(120) NOT NULL,
  `car` varchar(100) NOT NULL DEFAULT '',
  `track` varchar(100) NOT NULL DEFAULT '',
  `best_lap_time` varchar(20) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`session_id`, `event_id`, `f1_version`, `participant_name`, `car`, `track`, `best_lap_time`, `created_at`) VALUES
(3, 3, 'F1 2024', 'đ', 'Red Bull', 'Monza', '00:02.264', '2026-06-10 00:01:08'),
(4, 3, 'F1 2024', 'đ', 'Red Bull', 'Monza', '', '2026-06-10 00:04:28'),
(5, 3, 'F1 2024', 'đ', 'Red Bull', 'Monza', '', '2026-06-10 00:09:06'),
(6, 4, 'F1 2025', 'Fernando Alonso', 'HAAS', 'Circuit de Spa-Francorchamps', '', '2026-06-10 09:16:55'),
(7, 4, 'F1 2025', 'Fernando Alonso', 'HAAS', 'Circuit de Spa-Francorchamps', '', '2026-06-10 09:18:57'),
(8, 3, 'F1 2024', 'Oscar Piastri (#81)', 'AMR24', 'Bahrain International Circuit', '', '2026-06-10 09:19:21'),
(9, 4, 'F1 2025', 'Fernando Alonso', 'HAAS', 'Circuit de Spa-Francorchamps', '', '2026-06-10 09:19:27'),
(10, 4, 'F1 2025', 'Fernando Alonso', 'HAAS', 'Circuit de Spa-Francorchamps', '', '2026-06-16 00:59:11'),
(11, 4, 'F1 2025', 'Fernando Alonso', 'HAAS', 'Circuit de Spa-Francorchamps', '', '2026-06-16 02:58:53'),
(12, 3, 'F1 2024', 'Oscar Piastri (#81)', 'AMR24', 'Bahrain International Circuit', '', '2026-06-16 02:58:55'),
(13, 4, 'F1 2025', 'Fernando Alonso', 'HAAS', 'Circuit de Spa-Francorchamps', '', '2026-06-16 04:45:33'),
(14, 4, 'F1 2025', 'Fernando Alonso', 'HAAS', 'Circuit de Spa-Francorchamps', '', '2026-06-16 05:46:16'),
(15, 4, 'F1 2025', 'Alexander Albon', 'HAAS', 'Circuit de Spa-Francorchamps', '', '2026-06-16 08:25:19'),
(16, 4, 'F1 2025', 'Alexander Albon', 'HAAS', 'Circuit de Spa-Francorchamps', '', '2026-06-16 08:35:23'),
(17, 4, 'F1 2025', 'Alexander Albon', 'HAAS', 'Circuit de Spa-Francorchamps', '', '2026-06-17 02:08:01'),
(18, 4, 'F1 2025', 'Alexander Albon', 'HAAS', 'Circuit de Spa-Francorchamps', '', '2026-06-17 02:08:17'),
(21, 4, 'F1 2025', 'Alexander Albon', 'HAAS', 'Circuit de Spa-Francorchamps', '', '2026-06-17 03:40:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `uq_username` (`username`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `fk_events_version` (`version_id`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`game_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `game_cars`
--
ALTER TABLE `game_cars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `version_id` (`version_id`);

--
-- Indexes for table `game_drivers`
--
ALTER TABLE `game_drivers`
  ADD PRIMARY KEY (`driver_id`),
  ADD KEY `game_id` (`game_id`);

--
-- Indexes for table `game_racers`
--
ALTER TABLE `game_racers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `version_id` (`version_id`);

--
-- Indexes for table `game_tracks`
--
ALTER TABLE `game_tracks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `version_id` (`version_id`);

--
-- Indexes for table `game_versions`
--
ALTER TABLE `game_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `laps`
--
ALTER TABLE `laps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `rigs`
--
ALTER TABLE `rigs`
  ADD PRIMARY KEY (`rig_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `event_id` (`event_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `game_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `game_cars`
--
ALTER TABLE `game_cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `game_drivers`
--
ALTER TABLE `game_drivers`
  MODIFY `driver_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `game_racers`
--
ALTER TABLE `game_racers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `game_tracks`
--
ALTER TABLE `game_tracks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `game_versions`
--
ALTER TABLE `game_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `laps`
--
ALTER TABLE `laps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rigs`
--
ALTER TABLE `rigs`
  MODIFY `rig_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_events_version` FOREIGN KEY (`version_id`) REFERENCES `game_versions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `game_cars`
--
ALTER TABLE `game_cars`
  ADD CONSTRAINT `game_cars_ibfk_1` FOREIGN KEY (`version_id`) REFERENCES `game_versions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_drivers`
--
ALTER TABLE `game_drivers`
  ADD CONSTRAINT `game_drivers_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE;

--
-- Constraints for table `game_racers`
--
ALTER TABLE `game_racers`
  ADD CONSTRAINT `game_racers_ibfk_1` FOREIGN KEY (`version_id`) REFERENCES `game_versions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_tracks`
--
ALTER TABLE `game_tracks`
  ADD CONSTRAINT `game_tracks_ibfk_1` FOREIGN KEY (`version_id`) REFERENCES `game_versions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `laps`
--
ALTER TABLE `laps`
  ADD CONSTRAINT `laps_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE CASCADE;

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
