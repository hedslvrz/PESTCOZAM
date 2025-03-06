-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 04, 2025 at 07:34 PM
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
  `street_address` text NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `status` enum('Pending','Confirmed','Completed','Canceled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_for_self` tinyint(1) NOT NULL DEFAULT 1,
  `firstname` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- --------------------------------------------------------

--
-- Table structure for table `appointment_technicians`
--

CREATE TABLE `appointment_technicians` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `technician_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Drop existing services table if it exists
DROP TABLE IF EXISTS services;

-- Create new services table
CREATE TABLE `services` (
  `service_id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `service_name` varchar(100) NOT NULL,
  `description` TEXT NOT NULL,
  `estimated_time` varchar(50) NOT NULL,
  `starting_price` DECIMAL(10,2) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert the new service data
INSERT INTO services (service_name, description, estimated_time, starting_price, image_path) VALUES
('Soil Poisoning', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '2-4 hours', 3000.00, 'card 1 offer.jpg'),
('Mound Demolition', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '3-5 hours', 4500.00, 'mound-demolition.jpg'),
('Termite Control', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '2-3 hours', 2500.00, 'termite control.jpg'),
('General Pest Control', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '1-3 hours', 2000.00, 'general pest control.jpg'),
('Mosquito Control', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '1-2 hours', 1500.00, 'Mosquito control.jpg'),
('Rat Control', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '2-3 hours', 2000.00, 'rat control.jpg'),
('Other Flying and Crawling Insects', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '1-3 hours', 1800.00, 'Other-flying-insects.jpg'),
('Extraction', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '2-4 hours', 3500.00, 'Extraction.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `technicians`
--

CREATE TABLE `technicians` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('Available','Busy','On Leave') DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `is_booked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `role` enum('user','admin','employee') NOT NULL DEFAULT 'user',
  `status` enum('verified','unverified') NOT NULL DEFAULT 'unverified'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `mobile_number`, `password`, `created_at`, `role`, `status`) VALUES
(1, 'Hedrian Dunn', 'Alvarez', 'dunnlvrz13@gmail.com', '09759500123', '$2y$10$jn8m3Y/NnSIVBQmWSZdNtuAjpc2lIjj4oJPwqO2e4crXxoQkraRw2', '2025-03-01 07:13:17', 'admin', 'verified'),
(2, 'Hedrian', 'Alvarez', 'hedrianlvrz13@gmail.com', '09925028930', '$2y$10$llagzPkGmqSRnmdaqysXA.Wma1Ra3r7E0OkE86NGCmG0yJ.7krvXS', '2025-03-04 14:59:55', 'user', 'verified');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `appointment_technicians`
--
ALTER TABLE `appointment_technicians`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `technician_id` (`technician_id`);

--
-- Indexes for table `technicians`
--
ALTER TABLE `technicians`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointment_technicians`
--
ALTER TABLE `appointment_technicians`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `technicians`
--
ALTER TABLE `technicians`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

--
-- Constraints for table `appointment_technicians`
--
ALTER TABLE `appointment_technicians`
  ADD CONSTRAINT `appointment_technicians_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointment_technicians_ibfk_2` FOREIGN KEY (`technician_id`) REFERENCES `technicians` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD CONSTRAINT `time_slots_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
