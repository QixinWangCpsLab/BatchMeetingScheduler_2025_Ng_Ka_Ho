USE capstone_project;

DROP TABLE IF EXISTS `exam`;
DROP TABLE IF EXISTS `job_queue`;
DROP TABLE IF EXISTS `preference`;
DROP TABLE IF EXISTS `studentexammatch`;  
DROP TABLE IF EXISTS `meetingtimeslots`;
DROP TABLE IF EXISTS `MeetingDate`;
DROP TABLE IF EXISTS `result`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ----------------------------------------------------------------------
CREATE TABLE `exam` (
  `examid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacher` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration` int(11) NOT NULL,
  `deadline` datetime NOT NULL,
  `datechoicenum` int(11) NOT NULL,
  `slotchoicenum` int(11) NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roundindex` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `exam`
  ADD PRIMARY KEY (`examid`);
  
-- ----------------------------------------------------------------------
CREATE TABLE `preference` (
  `id` int(11) NOT NULL,
  `examid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `studentid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timeslotid` int(11) NOT NULL,
  `priority` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `preference`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_preference_exam_student_priority` (`examid`, `studentid`, `priority`);

ALTER TABLE `preference`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
  
-- ----------------------------------------------------------------------
CREATE TABLE `result` (
  `id` int(11) NOT NULL,
  `examid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `studentid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timeslotid` int(11) NOT NULL,
  `roundindex` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `result`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_result_slot_round` (`examid`, `timeslotid`, `roundindex`),
  ADD UNIQUE KEY `uniq_result_student_round` (`examid`, `studentid`, `roundindex`);

ALTER TABLE `result`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ----------------------------------------------------------------------
CREATE TABLE `studentexammatch` (
  `examid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `studentid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduled` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `studentexammatch` 
  ADD PRIMARY KEY(`examid`, `studentid`),
  ADD KEY `idx_studentexammatch_exam_scheduled` (`examid`, `scheduled`);

-- ----------------------------------------------------------------------
CREATE TABLE `meetingtimeslots` (
  `timeslotid` int(11) NOT NULL,
  `examid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timeslot` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateid` int(11) NOT NULL,
  `scheduled` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `meetingtimeslots`
  ADD PRIMARY KEY (`timeslotid`),
  ADD KEY `idx_meetingtimeslots_exam_scheduled` (`examid`, `scheduled`);

ALTER TABLE `meetingtimeslots`
  MODIFY `timeslotid` int(11) NOT NULL AUTO_INCREMENT;

-- ----------------------------------------------------------------------
CREATE TABLE `MeetingDate` (
  `dateid` int(11) NOT NULL,
  `examid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `MeetingDate`
  ADD PRIMARY KEY (`dateid`);

ALTER TABLE `MeetingDate`
  MODIFY `dateid` int(11) NOT NULL AUTO_INCREMENT;

-- ----------------------------------------------------------------------
-- Lightweight background job queue for async tasks (emails, parsing, etc.)
CREATE TABLE `job_queue` (
  `id` int(11) NOT NULL,
  `type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json NOT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending', -- pending | in_progress | done | failed
  `available_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_error` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `job_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_job_queue_status_available` (`status`,`available_at`);

ALTER TABLE `job_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
