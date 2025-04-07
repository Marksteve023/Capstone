-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 09, 2025 at 11:13 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `attendance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `assigned_courses`
--

CREATE TABLE `assigned_courses` (
  `assigned_course_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assigned_courses`
--

INSERT INTO `assigned_courses` (`assigned_course_id`, `user_id`, `course_id`, `assigned_at`) VALUES
(4, 2, 8, '2025-03-07 17:58:38'),
(5, 2, 9, '2025-03-07 17:58:42'),
(6, 2, 10, '2025-03-07 17:58:48');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `status` enum('Present','Late','Absent','Excused') NOT NULL,
  `attendance_date` date NOT NULL,
  `attendance_time` time NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `section` varchar(50) NOT NULL,
  `semester` enum('1st Trimester','2nd Trimester','3rd Trimester') NOT NULL,
  `academic_year` varchar(50) NOT NULL,
  `full_course_name` varchar(255) GENERATED ALWAYS AS (concat(`course_name`,' ',`section`)) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_name`, `section`, `semester`, `academic_year`, `created_at`) VALUES
(8, 'CISCO01', 'LFCA12345', '1st Trimester', '2025-2026', '2025-03-07 17:57:43'),
(9, 'MATH01', 'LFCA12345', '1st Trimester', '2025-2026', '2025-03-07 17:58:04'),
(10, 'NSTP01', 'LFCA12345', '1st Trimester', '2025-2026', '2025-03-07 17:58:19');

-- --------------------------------------------------------

--
-- Table structure for table `rfid_logs`
--

CREATE TABLE `rfid_logs` (
  `log_id` int(11) NOT NULL,
  `rfid_tag` varchar(50) NOT NULL,
  `scan_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `school_student_id` varchar(50) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `rfid_tag` varchar(50) NOT NULL,
  `program` varchar(100) NOT NULL,
  `year_level` enum('1st Year','2nd Year','3rd Year','4th Year') NOT NULL,
  `password` varchar(255) NOT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `school_student_id`, `student_name`, `rfid_tag`, `program`, `year_level`, `password`, `picture`, `created_at`) VALUES
(1, 'CA123', 'James', '12345', 'BIST', '1st Year', '$2y$10$8Vm1Zb.HO28fb2SUyprrVuekB.RI62OGLDqWiAUH.QMLrsYBwcN/m', '1741147429_boy-student1.png', '2025-03-05 04:03:49'),
(2, 'CA1234', 'Mark', '123456', 'BSIT', '1st Year', '$2y$10$FYDPagfyUxtMOjEG/J85XugygA8Jk3C.JNxIUra1v4Vb0j/fpwXtm', '1741147468_boy-student3.png', '2025-03-05 04:04:28'),
(4, 'CA12345', 'STUDENT 1', 'ebab12345', 'BSIT', '1st Year', '$2y$10$wH4gGlVdOKrE5YpdbwkfbevhLGWII5.qT/vnpqszjGiIMUbxgC8ma', '', '2025-03-09 00:46:43'),
(5, 'CA1234567', 'STUDENT 2', 'EBA12356', 'BSIT', '1st Year', '$2y$10$DrlrGPupiR57NcN20ztc5OJG7X2XDpnlgvrYx2swscztQ1Wn8LHU.', '', '2025-03-09 00:47:27'),
(6, 'CA123425', 'STUDENT 3', 'AVVQ214112', 'BSIT', '1st Year', '$2y$10$0hhCwAedDYzofao3WcW47umtSnqdV1d6UMrnepCv4KQfC7wObQS8i', '', '2025-03-09 00:48:18'),
(7, 'CA213415', 'STUDENT 4', 'CASD1246', 'BSIT', '1st Year', '$2y$10$BNOC3lFouwbvnJmRB15JzeAD4LNavUmTYMWKM7myT0yD4crduL2fW', '', '2025-03-09 00:48:45'),
(8, 'CA1234561', 'STUDENT 5', 'CASD124', 'BSIT', '1st Year', '$2y$10$3pW5m.VlFz876af4Uddya.n0zNQAsxAbFE399XUTnV6ojQnR6IPfW', '', '2025-03-09 00:49:24'),
(9, 'CA1235124', 'STUDENT 6', 'CASD1124', 'BSIT', '1st Year', '$2y$10$NA1gzd4AGEl7yTv7B9zyxOlLc8jAtLQHRgYEm3qie8NZqIz/Lukim', '', '2025-03-09 00:50:04'),
(10, 'CA124512', 'STUDENT 7', 'VASFBI24', 'BSIT', '1st Year', '$2y$10$DxhGDgUujsOEeZlV4UCRxe1C0N5uJOQmYDYncKPpnaXLzmYVBzvw6', '', '2025-03-09 00:50:35'),
(11, 'CA123512', 'STUDENT 8', 'VQHWEQH35123', 'BSIT', '1st Year', '$2y$10$iSloIZ1AHTkBJw2SGSHEB.DXUwEr39dqNAlvAN88sqJ1eZrMKZjSi', '', '2025-03-09 00:51:05'),
(12, 'CA12341512', 'STUDENT 9', 'VAAFSF123456', 'BSIT', '1st Year', '$2y$10$uukQ3LFVqtkvPQ2/4OmlGODwjorlUyuB2xOLE8olW3qEzAq9BnTjq', '', '2025-03-09 00:52:14'),
(13, 'CA123124', 'STUDENT 10', 'FQQ1124147', 'BSIT', '1st Year', '$2y$10$lWiJ1iwWN91bl5M/R4joFunHPps3JrTiwLlbYoP/1y.Ec0qRk5DOy', '', '2025-03-09 00:53:50'),
(14, 'CA1232512', 'STUDENT 11', 'AFWF124', 'BS[T', '1st Year', '$2y$10$DPRHB62/aYsLgwtO5echbO64Rb2n6JbM.QfCIWh2HZMPXA0tBVlZ.', '', '2025-03-09 00:58:43');

-- --------------------------------------------------------

--
-- Table structure for table `student_courses`
--

CREATE TABLE `student_courses` (
  `student_course_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `set_group` enum('Set A','Set B') NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_courses`
--

INSERT INTO `student_courses` (`student_course_id`, `student_id`, `course_id`, `set_group`, `enrolled_at`) VALUES
(7, 1, 8, 'Set A', '2025-03-07 17:59:46'),
(8, 1, 9, 'Set A', '2025-03-07 17:59:46'),
(9, 1, 10, 'Set A', '2025-03-07 17:59:46'),
(10, 2, 8, 'Set A', '2025-03-07 18:00:02'),
(11, 2, 9, 'Set A', '2025-03-07 18:00:02'),
(12, 2, 10, 'Set A', '2025-03-07 18:00:02'),
(13, 4, 8, 'Set A', '2025-03-09 00:54:08'),
(14, 4, 9, 'Set A', '2025-03-09 00:54:08'),
(15, 4, 10, 'Set A', '2025-03-09 00:54:08'),
(16, 5, 8, 'Set A', '2025-03-09 00:55:08'),
(17, 5, 9, 'Set A', '2025-03-09 00:55:08'),
(18, 5, 10, 'Set A', '2025-03-09 00:55:08'),
(19, 6, 8, 'Set A', '2025-03-09 00:55:23'),
(20, 6, 9, 'Set A', '2025-03-09 00:55:23'),
(21, 6, 10, 'Set A', '2025-03-09 00:55:23'),
(22, 7, 8, 'Set A', '2025-03-09 00:55:35'),
(23, 7, 9, 'Set A', '2025-03-09 00:55:35'),
(24, 7, 10, 'Set A', '2025-03-09 00:55:35'),
(25, 8, 8, 'Set A', '2025-03-09 00:55:45'),
(26, 8, 9, 'Set A', '2025-03-09 00:55:45'),
(27, 8, 10, 'Set A', '2025-03-09 00:55:45'),
(28, 9, 8, 'Set A', '2025-03-09 00:56:00'),
(29, 9, 9, 'Set A', '2025-03-09 00:56:00'),
(30, 9, 10, 'Set A', '2025-03-09 00:56:00'),
(31, 10, 8, 'Set A', '2025-03-09 00:56:16'),
(32, 10, 9, 'Set A', '2025-03-09 00:56:16'),
(33, 10, 10, 'Set A', '2025-03-09 00:56:16'),
(34, 11, 8, 'Set A', '2025-03-09 00:56:26'),
(35, 11, 9, 'Set A', '2025-03-09 00:56:26'),
(36, 11, 10, 'Set A', '2025-03-09 00:56:26'),
(37, 12, 8, 'Set A', '2025-03-09 00:56:34'),
(38, 12, 9, 'Set A', '2025-03-09 00:56:34'),
(39, 12, 10, 'Set A', '2025-03-09 00:56:34'),
(40, 13, 8, 'Set A', '2025-03-09 00:56:44'),
(41, 13, 9, 'Set A', '2025-03-09 00:56:45'),
(42, 13, 10, 'Set A', '2025-03-09 00:56:45'),
(43, 14, 8, 'Set A', '2025-03-09 01:02:57'),
(44, 14, 9, 'Set A', '2025-03-09 01:02:57'),
(45, 14, 10, 'Set A', '2025-03-09 01:02:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `school_id` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher') NOT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `school_id`, `full_name`, `email`, `password`, `role`, `picture`, `created_at`) VALUES
(1, '1', 'Administrator', 'admin@gmail.com', '$2y$10$h7Pk/c.MtpyPmBYHSPxMu.xX7/FOjyQ443ZUSwJUI2JUzNNuIH.UO', 'admin', '1741146584_1740836378_teacher.png', '2025-03-05 03:49:44'),
(2, '12345', 'David', 'david@gmail.com', '$2y$10$WRPTL4FqISPRC5GqtLs8/eUSseTuFtEGR4O22pDr1Km6mFCRCf1BK', 'teacher', '1741147104_1741132487_perfil.png', '2025-03-05 03:58:24'),
(3, '123456', 'Jane Foster', 'jane@gmail.com', '$2y$10$VQ/dr2LELZ8pgwR4rNYb3uf/gYtfca1ZqwHUuVovCrpblbUNMafYC', 'teacher', '1741147148_Girl-student3.png', '2025-03-05 03:59:08'),
(4, '1234567', 'Jennelyn', 'jen@gmail.com', '$2y$10$o7jnaKbFtlWH/kclspvBG.cEEut2OtGp.XBSL6i1.h4vw3M1FTprS', 'teacher', '1741147198_Girl-student4.png', '2025-03-05 03:59:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assigned_courses`
--
ALTER TABLE `assigned_courses`
  ADD PRIMARY KEY (`assigned_course_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_assigned_courses` (`user_id`,`course_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_attendance` (`student_id`,`course_id`,`attendance_date`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `rfid_logs`
--
ALTER TABLE `rfid_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `rfid_tag` (`rfid_tag`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `school_student_id` (`school_student_id`),
  ADD UNIQUE KEY `rfid_tag` (`rfid_tag`);

--
-- Indexes for table `student_courses`
--
ALTER TABLE `student_courses`
  ADD PRIMARY KEY (`student_course_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_student_course` (`student_id`,`course_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assigned_courses`
--
ALTER TABLE `assigned_courses`
  MODIFY `assigned_course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `rfid_logs`
--
ALTER TABLE `rfid_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `student_courses`
--
ALTER TABLE `student_courses`
  MODIFY `student_course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assigned_courses`
--
ALTER TABLE `assigned_courses`
  ADD CONSTRAINT `assigned_courses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assigned_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `rfid_logs`
--
ALTER TABLE `rfid_logs`
  ADD CONSTRAINT `rfid_logs_ibfk_1` FOREIGN KEY (`rfid_tag`) REFERENCES `students` (`rfid_tag`) ON DELETE CASCADE;

--
-- Constraints for table `student_courses`
--
ALTER TABLE `student_courses`
  ADD CONSTRAINT `student_courses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
