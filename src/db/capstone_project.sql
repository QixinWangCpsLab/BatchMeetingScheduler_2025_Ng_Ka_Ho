DROP Table `exam`;
DROP Table `preference`;
DROP Table `StudentExamMatch`;
DROP Table `MeetingTimeslots`;
DROP Table `MeetingDate`;
DROP Table `result`;

CREATE DATABASE testwsqlnew;
USE testwsqlnew;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
----------------------------------------------------------------------
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
----------------------------------------------------------------------------------------------------------
CREATE TABLE `preference` (
  `id` int(11) NOT NULL,
  `examid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `studentid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timeslotid` int(11) NOT NULL,
  `priority` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `preference`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `preference`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
----------------------------------------------------------------------------------------------------------------
CREATE TABLE `result` (
  `id` int(11) NOT NULL,
  `examid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `studentid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timeslotid` int(11) NOT NULL,
  `roundindex` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `result`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `result`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-------------------------------------------------------------------------------------------------------
CREATE TABLE `StudentExamMatch` (
  `examid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `studentid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `studentexammatch` ADD PRIMARY KEY(`examid`, `studentid`);
ALTER TABLE `studentexammatch`
  ADD `scheduled` int(1) NOT NULL;

----------------------------------------------------------------------------------------------------------

CREATE TABLE `MeetingTimeslots` (
  `timeslotid` int(11) NOT NULL,
  `examid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timeslot` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `MeetingTimeslots`
  ADD PRIMARY KEY (`timeslotid`);

ALTER TABLE `MeetingTimeslots`
  MODIFY `timeslotid` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `MeetingTimeslots`
  ADD `scheduled` int(1) NOT NULL;

------------------------------------------------------------------------------------------------------

CREATE TABLE `MeetingDate` (
  `dateid` int(11) NOT NULL,
  `examid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `MeetingDate`
  ADD PRIMARY KEY (`dateid`);

ALTER TABLE `MeetingDate`
  MODIFY `dateid` int(11) NOT NULL AUTO_INCREMENT;

--------------------------------------------------------------------------------------------------------
COMMIT;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
