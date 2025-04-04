-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 04, 2025 at 07:09 AM
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
-- Database: `pestcozam`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `region` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `street_address` text DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('Pending','Confirmed','Completed','Canceled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_for_self` tinyint(1) NOT NULL DEFAULT 1,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `technician_id` int(11) DEFAULT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `user_id`, `service_id`, `region`, `province`, `city`, `barangay`, `street_address`, `appointment_date`, `appointment_time`, `status`, `created_at`, `is_for_self`, `firstname`, `lastname`, `email`, `mobile_number`, `technician_id`, `latitude`, `longitude`, `updated_at`) VALUES
(15, 2, 8, '', '', '', '', 'Tumaga', '2025-03-15', '11:00:00', 'Confirmed', '2025-03-08 08:23:14', 1, NULL, NULL, NULL, NULL, 6, NULL, NULL, '2025-03-30 18:06:24'),
(16, 2, 6, '', '', '', '', '', '2025-03-20', '03:00:00', 'Confirmed', '2025-03-08 08:32:49', 0, 'Hannah', 'Alvarez', 'hannah1@gmail.com', '09759500345', 8, NULL, NULL, '2025-03-30 17:24:08'),
(22, 5, 6, 'Region IX', 'Zamboanga Del Sur', 'Zamboanga City', 'Calarian', 'Ruby Drive', '2025-03-21', '01:00:00', 'Confirmed', '2025-03-10 14:51:10', 1, NULL, NULL, NULL, NULL, 6, 6.928694, 122.025373, '2025-03-19 02:27:37'),
(23, 5, 3, 'Region IX', 'Zamboanga Del Sur', 'Zamboanga City', 'San Roque', 'Carmen Drive', '2025-03-29', '11:00:00', 'Confirmed', '2025-03-10 14:51:59', 1, NULL, NULL, NULL, NULL, 8, 6.927831, 122.044193, '2025-03-30 18:05:59');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `estimated_time` varchar(50) NOT NULL,
  `starting_price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `description`, `estimated_time`, `starting_price`, `image_path`) VALUES
(1, 'Soil Poisoning', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '2-4 hours', 3000.00, 'card 1 offer.jpg'),
(2, 'Mound Demolition', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '3-5 hours', 4500.00, 'mound-demolition.jpg'),
(3, 'Termite Control', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '2-3 hours', 2500.00, 'termite control.jpg'),
(4, 'General Pest Control', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '1-3 hours', 2000.00, 'general pest control.jpg'),
(5, 'Mosquito Control', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '1-2 hours', 1500.00, 'Mosquito control.jpg'),
(6, 'Rat Control', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '2-3 hours', 2000.00, 'rat control.jpg'),
(7, 'Other Flying and Crawling Insects', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '1-3 hours', 1800.00, 'Other-flying-insects.jpg'),
(8, 'Extraction', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '2-4 hours', 3500.00, 'Extraction.jpg'),
(17, 'Ocular Inspection', 'Our professional pest control experts will conduct a thorough assessment of your property to identify pest problems and recommend the most effective treatment plan.', '30-60 minutes', 0.00, 'ocular-inspection.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `service_reports`
--

CREATE TABLE `service_reports` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `contact_no` varchar(20) NOT NULL,
  `date_of_treatment` date NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  `treatment_type` varchar(255) NOT NULL,
  `treatment_method` varchar(255) NOT NULL,
  `pest_count` int(11) NOT NULL,
  `device_installation` text DEFAULT NULL,
  `consumed_chemicals` text DEFAULT NULL,
  `frequency_of_visits` varchar(255) DEFAULT NULL,
  `photos` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `middlename` varchar(255) DEFAULT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile_number` varchar(15) NOT NULL,
  `password` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('user','admin','supervisor','technician') NOT NULL DEFAULT 'user',
  `status` enum('verified','unverified') NOT NULL DEFAULT 'unverified',
  `dob` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `middlename`, `lastname`, `email`, `mobile_number`, `password`, `created_at`, `role`, `status`, `dob`) VALUES
(1, 'Hedrian Dunn', NULL, 'Alvarez', 'dunnlvrz13@gmail.com', '09759500123', '$2y$10$jn8m3Y/NnSIVBQmWSZdNtuAjpc2lIjj4oJPwqO2e4crXxoQkraRw2', '2025-03-01 07:13:17', 'admin', 'verified', '2004-04-13'),
(2, 'Hedrian', NULL, 'Alvarez', 'hedrianlvrz13@gmail.com', '09925028930', '$2y$10$llagzPkGmqSRnmdaqysXA.Wma1Ra3r7E0OkE86NGCmG0yJ.7krvXS', '2025-03-04 14:59:55', 'user', 'verified', NULL),
(5, 'Aldwin', NULL, 'Suarez', 'aldwinsuarez@gmail.com', '09929508778', '$2y$10$rtquceequxvXMIEIVquu5OElwdj3R663mROq8hc31WnRIxTioX7Ii', '2025-03-10 04:49:04', 'admin', 'verified', NULL),
(6, 'John', NULL, 'Bue', 'Johnsage@gmail.com', '09292213467', '$2y$10$6BxP6fxlCMdPZwIEb5l8luMXc2AeVRb.oDAh2ok3N0SQLUHlQAgwa', '2025-03-10 05:28:23', 'technician', 'verified', '2025-03-13'),
(8, 'Andrew', NULL, 'Tate', 'AndrewTate@gmail.com', '09776537811', '$2y$10$ZL6SEzVCeeND9wAOr3XvFuF/vG6E0vIUHq9jjggO4o2D1V/ty7ehW', '2025-03-10 05:30:18', 'technician', 'verified', '2025-03-15'),
(23, 'Diz', NULL, 'Nuts', 'DizNutz@gmail.com', '09359472304', '$2y$10$wuGNS/gSlT.dE3K1/no/WeeJbtOA/DxMgNikntsIztDHEihZBLfZq', '2025-03-10 13:07:29', 'supervisor', 'verified', '2025-03-21'),
(43, 'Francine', NULL, 'Delos Reyes', 'francine@gmail.com', '09726374892', '$2y$10$PA4m0oqFjStb/I//StISk.LdNlnqB.9fod0RWde38Nh4ZTz79VgIG', '2025-03-10 19:18:49', 'admin', 'verified', '2004-03-22'),
(44, 'Hannah', 'Marie', 'Alvarez', 'hannahlvrz13@gmail.com', '09827182739', '$2y$10$AsWSNGgeI06Tb.yXa6FfsuT2Nu9Q6XDX1w9L.grfPc505me0VHHui', '2025-03-29 04:01:32', 'user', 'verified', '1994-06-26'),
(45, 'Robert', NULL, 'Downey', 'robertdowney@gmail.com', '09827182837', '$2y$10$4H8vXmg0MfVYH6b4cAKSCOwRRIamEpeuNtLsLoVhFAQjiFZL4NhPe', '2025-03-29 04:04:42', 'supervisor', 'verified', '2043-09-12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `fk_appointments_technicians` (`technician_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `service_reports`
--
ALTER TABLE `service_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `service_reports`
--
ALTER TABLE `service_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appointments_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`),
  ADD CONSTRAINT `fk_appointments_technician` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `service_reports`
--
ALTER TABLE `service_reports`
  ADD CONSTRAINT `service_reports_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
