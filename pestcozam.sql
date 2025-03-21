-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 10, 2025 at 06:31 PM
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
(15, 2, 8, '', '', '', '', 'Tumaga', '2025-03-15', '11:00:00', 'Pending', '2025-03-08 08:23:14', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-10 16:10:31'),
(16, 2, 6, '', '', '', '', '', '2025-03-20', '03:00:00', 'Pending', '2025-03-08 08:32:49', 0, 'Hannah', 'Alvarez', 'hannah1@gmail.com', '09759500345', NULL, NULL, NULL, '2025-03-10 16:10:31'),
(22, 5, 6, 'Region IX', 'Zamboanga Del Sur', 'Zamboanga City', 'Calarian', 'Ruby Drive', '2025-03-21', '01:00:00', 'Pending', '2025-03-10 14:51:10', 1, NULL, NULL, NULL, NULL, NULL, 6.928694, 122.025373, '2025-03-10 16:10:31'),
(23, 5, 3, 'Region IX', 'Zamboanga Del Sur', 'Zamboanga City', 'San Roque', 'Carmen Drive', '2025-03-29', '11:00:00', 'Pending', '2025-03-10 14:51:59', 1, NULL, NULL, NULL, NULL, NULL, 6.927831, 122.044193, '2025-03-10 16:10:31'),
(24, 5, 8, '', '', '', '', NULL, '0000-00-00', '00:00:00', 'Pending', '2025-03-10 15:00:01', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-10 16:10:31');

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
(8, 'Extraction', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '2-4 hours', 3500.00, 'Extraction.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
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

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `mobile_number`, `password`, `created_at`, `role`, `status`, `dob`) VALUES
(1, 'Hedrian Dunn', 'Alvarez', 'dunnlvrz13@gmail.com', '09759500123', '$2y$10$jn8m3Y/NnSIVBQmWSZdNtuAjpc2lIjj4oJPwqO2e4crXxoQkraRw2', '2025-03-01 07:13:17', 'technician', 'verified', NULL),
(2, 'Hedrian', 'Alvarez', 'hedrianlvrz13@gmail.com', '09925028930', '$2y$10$llagzPkGmqSRnmdaqysXA.Wma1Ra3r7E0OkE86NGCmG0yJ.7krvXS', '2025-03-04 14:59:55', 'user', 'verified', NULL),
(5, 'Aldwin', 'Suarez', 'aldwinsuarez@gmail.com', '09929508778', '$2y$10$rtquceequxvXMIEIVquu5OElwdj3R663mROq8hc31WnRIxTioX7Ii', '2025-03-10 04:49:04', 'admin', 'verified', NULL),
(6, 'John', 'Bue', 'Johnsage@gmail.com', '09292213467', '$2y$10$6BxP6fxlCMdPZwIEb5l8luMXc2AeVRb.oDAh2ok3N0SQLUHlQAgwa', '2025-03-10 05:28:23', 'technician', 'verified', '2025-03-13'),
(8, 'Andrew', 'Tate', 'AndrewTate@gmail.com', '09776537811', '$2y$10$ZL6SEzVCeeND9wAOr3XvFuF/vG6E0vIUHq9jjggO4o2D1V/ty7ehW', '2025-03-10 05:30:18', 'technician', 'verified', '2025-03-15'),
(23, 'Diz', 'Nuts', 'DizNutz@gmail.com', '09359472304', '$2y$10$wuGNS/gSlT.dE3K1/no/WeeJbtOA/DxMgNikntsIztDHEihZBLfZq', '2025-03-10 13:07:29', 'supervisor', 'verified', '2025-03-21');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
