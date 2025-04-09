-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2025 at 11:42 AM
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
-- Database: `psp_ubelt`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `lock_until` datetime DEFAULT NULL,
  `lockout_count` int(11) DEFAULT 0,
  `recovery_code` varchar(10) DEFAULT NULL,
  `recovery_code_expires` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `email`, `password`, `login_attempts`, `lock_until`, `lockout_count`, `recovery_code`, `recovery_code_expires`, `reset_token`, `reset_expires`) VALUES
(13, 'admin1@example.com', '$2y$10$rdF8/ibGP6wKYkjpUbQuV.o3Z.Bk/KNvE0y/DbjqvjHkqujPLj1nO', 0, NULL, 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` datetime NOT NULL,
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_login_attempts`
--

CREATE TABLE `admin_login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(255) NOT NULL,
  `success` tinyint(1) DEFAULT 0,
  `attempt_time` datetime NOT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `lockout_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_login_attempts`
--

INSERT INTO `admin_login_attempts` (`id`, `ip_address`, `email`, `success`, `attempt_time`, `is_locked`, `lockout_time`) VALUES
(1, '::1', 'luiszara@gmail.com', 0, '2025-04-08 22:58:43', 0, NULL),
(2, '::1', 'luiszara@gmail.com', 0, '2025-04-08 22:58:44', 0, NULL),
(3, '::1', 'luiszara@gmail.com', 0, '2025-04-08 22:59:01', 0, NULL),
(4, '::1', 'admin123@example.com', 0, '2025-04-08 22:59:12', 0, NULL),
(5, '::1', 'admin123@example.com', 0, '2025-04-08 23:02:11', 0, NULL),
(6, '::1', 'admin123@example.com', 0, '0000-00-00 00:00:00', 1, '2025-04-08 23:17:11');

-- --------------------------------------------------------

--
-- Table structure for table `advertisements`
--

CREATE TABLE `advertisements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `description` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Status flag: 1=active, 0=inactive'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `advertisements`
--

INSERT INTO `advertisements` (`id`, `title`, `image`, `created_at`, `description`, `is_active`) VALUES
(2, 'New4', 'uploads/boracay-philippines.jpg', '2025-03-18 07:24:43', 'test', 0),
(5, 'Ut ut maiores ut dol', 'uploads/1_y6C4nSvy2Woe0m7bWEn4BA.png', '2025-03-23 21:50:39', 'Hic ducimus ullamco', 0),
(17, 'New3', 'uploads/67e440e2435d7_472976138_1259992965110466_6504442411776558840_n.png', '2025-03-27 02:01:06', '123', 0),
(18, 'ewq321', 'uploads/67f537cbe143f_Giaz75IWUAAuWx0.jpg', '2025-04-08 16:50:51', '123', 1),
(19, '123', 'uploads/67f55b3c0acd0_Giaz75IWUAAuWx0.jpg', '2025-04-08 19:22:04', '123', 0),
(20, '123', 'uploads/67f62a977a8f4_Giaz75IWUAAuWx0.jpg', '2025-04-09 10:06:47', '123', 1);

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `image_path`, `created_at`) VALUES
(1, '123123', 'uploads/anon.jpg', '2025-03-27 15:51:53'),
(2, 'adadadasdsadas', 'uploads/willbarrios.com.png', '2025-03-04 01:44:16'),
(3, 'adasdasd', 'uploads/Web development service (2).png', '2025-03-04 02:29:55'),
(4, 'test', 'uploads/images (3).png', NULL),
(7, 'New2', 'uploads/cisco-certification-roadmap-2020-large.png', '2025-03-27 15:52:06');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `description` text DEFAULT NULL,
  `trainer` varchar(100) DEFAULT NULL,
  `status` enum('Pending','On-going','Done','Cancelled') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `balance_additions`
--

CREATE TABLE `balance_additions` (
  `balance_addition_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `balance_amount` decimal(10,2) NOT NULL,
  `balance_date` date NOT NULL,
  `balance_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `balance_additions`
--

INSERT INTO `balance_additions` (`balance_addition_id`, `user_id`, `balance_amount`, `balance_date`, `balance_note`, `created_at`) VALUES
(7, 35, 10000.00, '2025-04-09', '', '2025-04-09 03:52:02');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_due` date NOT NULL,
  `money_paid` decimal(10,2) NOT NULL,
  `promo_applied` varchar(255) DEFAULT NULL,
  `balance_adjustment` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `user_id`, `payment_date`, `payment_due`, `money_paid`, `promo_applied`, `balance_adjustment`) VALUES
(37, 35, '2025-04-09', '2025-04-18', 10000.00, '', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL,
  `schedule_picture` varchar(255) DEFAULT NULL,
  `day` varchar(20) NOT NULL,
  `personnel_name` varchar(50) DEFAULT NULL,
  `activity_description` varchar(100) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `time` time DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`id`, `schedule_picture`, `day`, `personnel_name`, `activity_description`, `start_time`, `end_time`, `time`, `created_at`) VALUES
(1, 'cisco-certification-roadmap-2020-large.png', 'Monday', 'COACH PAU', 'YOGA', '17:00:00', '18:00:00', '17:00:00', '2025-03-27 15:30:56'),
(2, 'image2.jpg', 'Tuesday', 'Coach Tags', 'H.I.I.T', '16:00:00', '17:00:00', '16:00:00', '2025-03-27 15:30:23'),
(3, 'image3.jpg', 'Wednesday', 'Coach Jeromasde', 'MixedFit', '20:00:00', '21:00:00', '20:00:00', '2025-03-18 12:13:45'),
(4, 'image4.jpg', 'Thursday', 'Teacher Wany', 'Yoga', '17:00:00', '18:00:00', '17:00:00', '2025-03-19 12:13:45'),
(5, 'image5.jpg', 'Friday', 'Coach Antony', 'Tabata', '19:00:00', '20:00:00', '19:00:00', '2025-03-20 12:13:45'),
(6, 'talking.png', 'Saturday', 's', 'Rest', '08:04:00', '09:04:00', '08:04:00', '2025-03-21 10:13:45'),
(7, 'talking.png', 'Sunday', 's', 'Closed', '08:04:00', '09:04:00', '08:04:00', '2025-03-21 10:13:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullName` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `emailAddress` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phoneNumber` varchar(20) NOT NULL,
  `membership_status` enum('active','inactive','frozen') NOT NULL DEFAULT 'inactive',
  `date_started` date NOT NULL,
  `next_payment` date NOT NULL,
  `birthday` date DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `profile_picture` varchar(255) DEFAULT 'default.png',
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `frozen_at` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `lock_until` datetime DEFAULT NULL,
  `account_balance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullName`, `first_name`, `last_name`, `emailAddress`, `password`, `phoneNumber`, `membership_status`, `date_started`, `next_payment`, `birthday`, `username`, `profile_picture`, `reset_token`, `reset_expires`, `email_verified`, `verification_token`, `frozen_at`, `login_attempts`, `lock_until`, `account_balance`) VALUES
(35, 'Luis Zara', 'Luis', 'Zara', 'luiszara321@gmail.com', '$2y$10$pzIhr2TNsFwxjGfBmnJHf.GhZALKFtcr43mVI/R4EWP5C2gf3Mf4O', '639052588348', 'inactive', '0000-00-00', '0000-00-00', '2000-03-02', 'luisz', 'default.png', '24bdd34bb02426340e309cdc7d3582bbc1a4e289974dcac2ad525d56e4f812260ca422c3ab4ae275a8ffef7ac7f1cbc65d90', '2025-04-09 20:07:56', 1, NULL, NULL, 0, NULL, 0.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_login_attempts`
--
ALTER TABLE `admin_login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `advertisements`
--
ALTER TABLE `advertisements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `balance_additions`
--
ALTER TABLE `balance_additions`
  ADD PRIMARY KEY (`balance_addition_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `emailAddress` (`emailAddress`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_login_attempts`
--
ALTER TABLE `admin_login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `advertisements`
--
ALTER TABLE `advertisements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `balance_additions`
--
ALTER TABLE `balance_additions`
  MODIFY `balance_addition_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `balance_additions`
--
ALTER TABLE `balance_additions`
  ADD CONSTRAINT `balance_additions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
