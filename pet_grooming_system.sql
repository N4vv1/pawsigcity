-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 18, 2025 at 08:03 AM
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
-- Database: `pet_grooming_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `appointment_date` datetime NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `groomer_name` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `payment_status` enum('unpaid','paid','refunded') DEFAULT 'unpaid',
  `payment_method` varchar(50) DEFAULT NULL,
  `appointment_end` datetime DEFAULT NULL,
  `no_show` tinyint(1) DEFAULT 0,
  `rating` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `sentiment` enum('positive','neutral','negative') DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `cancel_requested` tinyint(1) DEFAULT 0,
  `recommended_package` varchar(100) DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `cancel_approved` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `user_id`, `pet_id`, `package_id`, `appointment_date`, `status`, `groomer_name`, `notes`, `created_at`, `updated_at`, `payment_status`, `payment_method`, `appointment_end`, `no_show`, `rating`, `feedback`, `sentiment`, `is_approved`, `cancel_requested`, `recommended_package`, `cancel_reason`, `cancel_approved`) VALUES
(1, 1, 5, 1, '2025-07-16 01:20:00', 'completed', '', '', '2025-07-04 17:23:22', '2025-07-15 18:14:09', 'unpaid', NULL, NULL, 0, 5, 'Excellent service!', 'positive', 1, 0, NULL, NULL, NULL),
(35, 2, 6, 2, '2025-07-21 08:23:00', 'cancelled', '', '', '2025-07-15 18:21:39', '2025-07-16 06:10:38', 'unpaid', NULL, NULL, 0, NULL, NULL, NULL, 0, 0, NULL, 'asd', 1),
(43, 2, 6, 1, '2025-07-07 16:20:00', 'completed', '', 'ASDASD', '2025-07-16 06:18:12', '2025-07-16 06:30:05', 'unpaid', NULL, NULL, 0, 3, 'I loved how gentle the groomer was with my dog.', NULL, 1, 0, '', NULL, NULL),
(44, 2, 13, 3, '2025-07-25 17:44:00', 'completed', '', 'asdfsfd', '2025-07-16 06:41:14', '2025-07-17 03:47:20', 'unpaid', NULL, NULL, 0, NULL, NULL, NULL, 1, 0, 'Bath and Dry', NULL, NULL),
(45, 2, 13, 1, '2025-07-12 16:43:00', 'completed', '', 'aetrrewrtwqertewrtewrt', '2025-07-16 06:41:59', '2025-07-17 03:43:54', 'unpaid', NULL, NULL, 0, 5, 'I loved how gentle the groomer was with my dog.', NULL, 1, 0, 'Bath and Dry', NULL, NULL),
(46, 2, 13, 3, '2025-07-22 13:45:00', 'cancelled', '', 'anykind', '2025-07-17 03:43:30', '2025-07-17 03:46:40', 'unpaid', NULL, NULL, 0, NULL, NULL, NULL, 0, 0, 'Bath and Dry', 'none', 1);

-- --------------------------------------------------------

--
-- Table structure for table `behavior_preferences`
--

CREATE TABLE `behavior_preferences` (
  `preference_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `behavior_notes` text DEFAULT NULL,
  `nail_trimming` enum('Yes','No') DEFAULT NULL,
  `haircut_style` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `behavior_preferences`
--

INSERT INTO `behavior_preferences` (`preference_id`, `pet_id`, `behavior_notes`, `nail_trimming`, `haircut_style`) VALUES
(2, 6, '', 'Yes', ''),
(4, 8, '', 'Yes', ''),
(5, 15, 'asda', 'Yes', ''),
(6, 16, 'mabait', 'Yes', 'burst fade'),
(8, 18, 'mabait', 'Yes', 'burst fade');

-- --------------------------------------------------------

--
-- Table structure for table `breeds`
--

CREATE TABLE `breeds` (
  `breed_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `breeds`
--

INSERT INTO `breeds` (`breed_id`, `name`) VALUES
(1, 'Golden Retriever'),
(2, 'Poodle'),
(3, 'Shih Tzu'),
(4, 'Labrador'),
(5, 'Chihuahua'),
(6, 'Pomeranian'),
(7, 'German Shepherd'),
(8, 'Bulldog');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`id`, `image_path`, `uploaded_at`) VALUES
(2, 'gallery4.jpg', '2025-07-03 04:01:24'),
(3, 'gallery3.jpg', '2025-07-03 04:01:24'),
(4, 'gallery4.jpg', '2025-07-03 04:01:24'),
(6, 'gallery6.jpg', '2025-07-03 04:01:24'),
(14, 'gallery3.jpg', '2025-07-03 05:31:19'),
(16, 'gallery6.jpg', '2025-07-03 06:26:39'),
(20, 'gallery4.jpg', '2025-07-03 15:23:22'),
(22, 'gallery2.jpg', '2025-07-10 22:52:42'),
(23, 'gallery2.jpg', '2025-07-10 22:52:48'),
(24, 'gallery2.jpg', '2025-07-10 22:54:34'),
(25, 'gallery2.jpg', '2025-07-10 22:55:59');

-- --------------------------------------------------------

--
-- Table structure for table `grooming_history`
--

CREATE TABLE `grooming_history` (
  `history_id` int(11) NOT NULL,
  `pet_id` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `reaction_notes` text DEFAULT NULL,
  `tips_for_next_time` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `health_info`
--

CREATE TABLE `health_info` (
  `health_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `allergies` text DEFAULT NULL,
  `medications` text DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `health_info`
--

INSERT INTO `health_info` (`health_id`, `pet_id`, `allergies`, `medications`, `medical_conditions`) VALUES
(2, 6, '', '', ''),
(4, 8, 'hipon', 'allergy', 'pilay'),
(5, 15, 'asd', 'asd', 'asd'),
(6, 16, 'shrimp', 'biogesic', ''),
(8, 18, 'shrimp', 'biogesic', 'wala naman');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `name`, `price`, `description`, `is_active`) VALUES
(1, 'Full Grooming', 1000.00, 'Complete grooming service', 1),
(2, 'SPA BATH', 1000.00, 'Relaxing bath and spa treatment', 1),
(3, 'BATH AND DRY', 1000.00, 'Simple bath and drying service', 1);

-- --------------------------------------------------------

--
-- Table structure for table `package_features`
--

CREATE TABLE `package_features` (
  `id` int(11) NOT NULL,
  `feature_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_features`
--

INSERT INTO `package_features` (`id`, `feature_name`) VALUES
(1, 'WARM BATH'),
(2, 'BLOW DRY'),
(3, 'NAIL TRIM'),
(4, 'TOOTHBRUSHING'),
(5, 'EAR CLEANING'),
(6, 'STYLED HAIRCUT'),
(7, 'NANO BUBBLE BATH'),
(8, 'MASSAGE'),
(9, 'HAIRCUT WITH ODOR ELIMINATOR'),
(10, 'BATH AND DRY');

-- --------------------------------------------------------

--
-- Table structure for table `package_feature_map`
--

CREATE TABLE `package_feature_map` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `feature_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_feature_map`
--

INSERT INTO `package_feature_map` (`id`, `package_id`, `feature_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 1, 4),
(5, 1, 5),
(6, 1, 6),
(7, 2, 7),
(8, 2, 8),
(9, 2, 2),
(10, 2, 3),
(11, 2, 4),
(12, 2, 5),
(13, 2, 9),
(14, 3, 10);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `method` enum('gcash','cash') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','failed') DEFAULT 'pending',
  `reference_number` varchar(100) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `pet_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `age` varchar(50) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `photo_url` text DEFAULT NULL,
  `breed_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`pet_id`, `user_id`, `name`, `breed`, `gender`, `age`, `birthday`, `color`, `photo_url`, `breed_id`) VALUES
(5, 1, 'Brownie', 'Persian Cat', 'Female', '2', '2022-03-10', 'Brown', 'uploads/brownie.jpg', NULL),
(6, 2, 'dog', 'doberman', 'Male', '85', '2033-08-06', 'pink', 'uploads/1752054167_02d87aaedce7f6f6d605b71556642a8b.jpg', NULL),
(8, 3, 'Garfield', 'Husky', 'Male', '12', '2004-07-06', 'Red', 'uploads/1752057990_1751648624_blackie.jpg', NULL),
(13, 2, 'Brownie', 'American Bulldog', 'Male', '1', '2025-07-09', 'Black/White', 'uploads/1752073725_blackie.jpg', NULL),
(15, 4, 'asdasd', 'asdasd', 'Male', 'asda', '0000-00-00', 'asd', '', NULL),
(16, 6, 'Blackie', 'Brownie', 'Female', '12', '2004-08-06', 'White', 'uploads/1752722789_gallery3.jpg', NULL),
(18, 7, 'Blackie', 'Brownie', 'Male', '12', '2025-07-08', 'White', 'uploads/1752723242_gallery2.jpg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `reminder_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `reminder_date` datetime NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('customer','admin','staff') DEFAULT 'customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `phone`, `role`) VALUES
(1, 'Ivan Santos', 'ivan@example.com', 'testpass123', '09171234567', 'customer'),
(2, 'dyey', 'magnuscarlsen@gmail.com', '$2y$10$ghmj058sZmYh0GwRo4IYce33CbKmSf4MO84GR1dNl0M4b8TAfe3da', '09477188719', 'customer'),
(3, 'pogi', 'pogi@gmail.com', '$2y$10$2IZHc8LSfX1GAg0IwgqAQOAYbFBZ4NOUUIXbTxwS2PooIroFaF8Qq', '1231231', 'customer'),
(4, 'Hannah Jeyne Rosario Guanzon', 'hannah@gmail.com', '$2y$10$g7omW3Il9hkQ445r3BcLNuD6aEVVJ13aCBBBPgdgWpsEyjVNhskqi', '123', 'customer'),
(5, 'jaycee kartil clde', '123@gmail.com', '$2y$10$JhhduNaG.yuFVd7PRuEzQ.BE/m/9nWzvRT6Rf8XnvWset7f1UZIJK', '123', 'customer'),
(6, 'John Bernard De Guzman Mitra', 'jbmitra@gmail.com', '$2y$10$IRZ.1ViW0SHtsoNfIX9Y4.REO4MHH5U7XeIrTGP3K5mlLA.CUlPrq', '09477188719', 'customer'),
(8, 'John Bernard Mitra De Zguaman', 'johnebrnardmitra@gmail.com', '$2y$10$nx1VPh5Kdrn5/l5pwreLo.fBn98PaoNTNhsoy1/.qW.SKFSIsOhrq', '09577188719', 'customer'),
(9, 'John Bernard De Guzman DE GUZMAN', 'pogijb@gmail.com', '$2y$10$2En6jh9.h8XkSobwchgOhu8UFk5buyR6Q8qUMdaBci9f641vPQkrC', '123132', 'customer');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `pet_id` (`pet_id`),
  ADD KEY `fk_appointments_package` (`package_id`);

--
-- Indexes for table `behavior_preferences`
--
ALTER TABLE `behavior_preferences`
  ADD PRIMARY KEY (`preference_id`),
  ADD KEY `pet_id` (`pet_id`);

--
-- Indexes for table `breeds`
--
ALTER TABLE `breeds`
  ADD PRIMARY KEY (`breed_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `grooming_history`
--
ALTER TABLE `grooming_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `pet_id` (`pet_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `health_info`
--
ALTER TABLE `health_info`
  ADD PRIMARY KEY (`health_id`),
  ADD KEY `pet_id` (`pet_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_features`
--
ALTER TABLE `package_features`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_feature_map`
--
ALTER TABLE `package_feature_map`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `feature_id` (`feature_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`pet_id`),
  ADD KEY `owner_id` (`user_id`),
  ADD KEY `fk_breed_id` (`breed_id`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`reminder_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `pet_id` (`pet_id`);

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
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `behavior_preferences`
--
ALTER TABLE `behavior_preferences`
  MODIFY `preference_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `breeds`
--
ALTER TABLE `breeds`
  MODIFY `breed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `grooming_history`
--
ALTER TABLE `grooming_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `health_info`
--
ALTER TABLE `health_info`
  MODIFY `health_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `package_features`
--
ALTER TABLE `package_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `package_feature_map`
--
ALTER TABLE `package_feature_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `pet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `reminder_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appointments_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `behavior_preferences`
--
ALTER TABLE `behavior_preferences`
  ADD CONSTRAINT `behavior_preferences_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE;

--
-- Constraints for table `grooming_history`
--
ALTER TABLE `grooming_history`
  ADD CONSTRAINT `grooming_history_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grooming_history_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL;

--
-- Constraints for table `health_info`
--
ALTER TABLE `health_info`
  ADD CONSTRAINT `health_info_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE;

--
-- Constraints for table `package_feature_map`
--
ALTER TABLE `package_feature_map`
  ADD CONSTRAINT `package_feature_map_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_feature_map_ibfk_2` FOREIGN KEY (`feature_id`) REFERENCES `package_features` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
