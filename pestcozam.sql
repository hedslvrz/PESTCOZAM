-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 20, 2025 at 10:07 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `treatment_methods` text DEFAULT NULL,
  `chemicals` text DEFAULT NULL,
  `chemical_quantities` text DEFAULT NULL,
  `pct` varchar(255) DEFAULT NULL,
  `device_installation` text DEFAULT NULL,
  `chemical_consumables` text DEFAULT NULL,
  `visit_frequency` varchar(50) DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `user_id`, `service_id`, `region`, `province`, `city`, `barangay`, `street_address`, `appointment_date`, `appointment_time`, `status`, `created_at`, `is_for_self`, `firstname`, `lastname`, `email`, `mobile_number`, `technician_id`, `latitude`, `longitude`, `updated_at`, `treatment_methods`, `chemicals`, `chemical_quantities`, `pct`, `device_installation`, `chemical_consumables`, `visit_frequency`, `time_in`, `time_out`) VALUES
(15, 2, 8, '', '', '', '', 'Tumaga', '2025-03-15', '11:00:00', 'Confirmed', '2025-03-08 08:23:14', 1, NULL, NULL, NULL, NULL, 6, NULL, NULL, '2025-04-16 16:22:42', '[]', '[]', '[]', '', '', '', 'weekly', '00:00:00', '00:00:00'),
(16, 2, 6, '', '', '', '', '', '2025-03-20', '03:00:00', 'Confirmed', '2025-03-08 08:32:49', 0, 'Hannah', 'Alvarez', 'hannah1@gmail.com', '09759500345', 8, NULL, NULL, '2025-03-30 17:24:08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(22, 5, 6, 'Region IX', 'Zamboanga Del Sur', 'Zamboanga City', 'Calarian', 'Ruby Drive', '2025-03-21', '01:00:00', 'Confirmed', '2025-03-10 14:51:10', 1, NULL, NULL, NULL, NULL, 6, 6.928694, 122.025373, '2025-03-19 02:27:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, 5, 3, 'Region IX', 'Zamboanga Del Sur', 'Zamboanga City', 'San Roque', 'Carmen Drive', '2025-03-29', '11:00:00', 'Confirmed', '2025-03-10 14:51:59', 1, NULL, NULL, NULL, NULL, 6, 6.927831, 122.044193, '2025-04-16 14:28:17', '[]', '[\"Demand CS\",\"Fendona\"]', '[\"1\",\"2\"]', '', '', '', 'weekly', '00:00:00', '00:00:00'),
(70, 5, 3, 'Region IX', 'Zamboanga Del Sur', 'Zamboanga City', 'Calarian', 'Ruby Drive', '2025-04-23', '07:00:00', 'Confirmed', '2025-04-15 13:47:54', 1, NULL, NULL, NULL, NULL, 6, 6.928591, 122.025378, '2025-04-15 19:17:54', '[]', '[]', '[]', '', '', '', 'weekly', '00:00:00', '00:00:00'),
(83, 45, 6, 'Region IX', 'Zamboanga Del Sur', 'Zamboanga City', '', 'San Jose Street', '2025-04-30', '15:00:00', 'Pending', '2025-04-18 19:25:42', 0, 'Francine', 'Delos Reyes', 'francinemarchelle@gmail.com', '09759500123', NULL, 6.922505, 122.024724, '2025-04-18 19:27:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(85, 45, 4, 'Region IX', 'Zamboanga Del Sur', 'Zamboanga City', 'Pasonanca', 'Villa Rio Drive', '2025-04-30', '15:00:00', 'Pending', '2025-04-18 19:37:58', 1, NULL, NULL, NULL, NULL, NULL, 6.946032, 122.073967, '2025-04-18 19:38:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(86, 45, 5, 'Region IX', 'Zamboanga Del Sur', 'Zamboanga City', 'Divisoria', 'Divisoria Health center', '2025-04-30', '11:00:00', 'Pending', '2025-04-18 19:39:19', 0, 'Hedrian Dunn', 'Alvarez', 'dunnlvrz13@gmail.com', '09759500123', NULL, 6.945291, 122.100965, '2025-04-18 19:40:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(87, 2, 6, 'Region IX', 'Zamboanga Del Sur', 'Zamboanga City', 'Guiwan', 'Unity Drive', '2025-04-30', '07:00:00', 'Pending', '2025-04-19 13:17:12', 0, 'Francine', 'Delos Reyes', 'francinemarchelle@gmail.com', '09874563812', NULL, 6.922320, 122.088889, '2025-04-19 13:20:18', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appointment_technicians`
--

CREATE TABLE `appointment_technicians` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `technician_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment_technicians`
--

INSERT INTO `appointment_technicians` (`id`, `appointment_id`, `technician_id`, `created_at`) VALUES
(7, 70, 6, '2025-04-15 14:46:19'),
(9, 23, 6, '2025-04-16 14:28:17'),
(10, 23, 8, '2025-04-16 14:28:17'),
(12, 15, 6, '2025-04-16 16:22:42'),
(13, 15, 8, '2025-04-16 16:22:42');

-- --------------------------------------------------------

--
-- Table structure for table `followup_plan`
--

CREATE TABLE `followup_plan` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `plan_type` enum('weekly','monthly','quarterly','yearly') NOT NULL,
  `frequency` varchar(20) NOT NULL,
  `contract_duration` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `followup_visits`
--

CREATE TABLE `followup_visits` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `followup_date` date NOT NULL,
  `followup_time` time NOT NULL,
  `visit_number` int(11) NOT NULL,
  `status` enum('Scheduled','Completed','Canceled') NOT NULL DEFAULT 'Scheduled',
  `technician_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` between 1 and 5),
  `review_text` text NOT NULL,
  `service_rating` int(1) DEFAULT NULL,
  `technician_rating` int(1) DEFAULT NULL,
  `service_feedback` text DEFAULT NULL,
  `reported_issues` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `service_id`, `appointment_id`, `rating`, `review_text`, `service_rating`, `technician_rating`, `service_feedback`, `reported_issues`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 8, 15, 5, 'The extraction service was incredibly thorough! The technician was professional and removed the pest infestation completely. Would definitely recommend to anyone dealing with stubborn pests.', 5, 5, 'Excellent service, very thorough work.', NULL, 'approved', '2025-04-01 10:30:22', '2025-04-01 14:15:00'),
(2, 5, 6, 22, 4, 'Great rat control service. The team was prompt and efficient. My home has been rodent-free since their visit. Took off one star because they were a bit late, but the service itself was excellent.', 4, 5, 'The service was great, just wished they were on time.', 'The team was about 20 minutes late.', 'approved', '2025-04-02 15:45:10', '2025-04-03 09:22:15'),
(3, 44, 3, NULL, 5, 'The termite control service was outstanding. Not only did they eliminate the termites, but they also provided helpful tips on preventing future infestations. Very knowledgeable staff!', 5, 5, 'Very informative and helpful advice provided.', NULL, 'approved', '2025-04-05 08:12:30', '2025-04-05 13:40:00'),
(4, 2, 4, 16, 3, 'The general pest control was okay. It took care of most of the issues but I still noticed some insects after a week. Customer service was responsive when I called about this.', 3, 4, 'Good service but not fully effective.', 'Some insects still present after treatment.', 'pending', '2025-04-07 11:20:45', '2025-04-07 11:20:45'),
(5, 5, 3, 23, 2, 'I was not entirely satisfied with the termite treatment. The technicians were friendly, but the problem returned within two weeks. Currently working with them on a follow-up appointment.', 2, 4, 'Technicians were professional but service wasn\'t effective.', 'Termites returned within two weeks.', 'rejected', '2025-04-09 16:05:22', '2025-04-10 09:30:15');

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
(17, 'Ocular Inspection', 'Our professional pest control experts will conduct a thorough assessment of your property to identify pest problems and recommend the most effective treatment plan.', '30-60 minutes', 0.00, 'ocular-inspection.jpg'),
(18, 'wdqwdq', 'dawdwd', '3 hours', 4000.00, 'WINDOWS 11 WALLPAPER.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `service_reports`
--

CREATE TABLE `service_reports` (
  `report_id` int(11) NOT NULL,
  `technician_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `date_of_treatment` date NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  `treatment_type` varchar(100) NOT NULL,
  `treatment_method` text NOT NULL,
  `pest_count` varchar(50) DEFAULT NULL,
  `device_installation` varchar(255) DEFAULT NULL,
  `consumed_chemicals` text DEFAULT NULL,
  `frequency_of_visits` varchar(100) DEFAULT NULL,
  `photos` text DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `account_name` varchar(150) NOT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `slot_name` varchar(50) NOT NULL,
  `time_range` varchar(50) NOT NULL,
  `slot_limit` int(11) NOT NULL DEFAULT 3,
  `available_slots` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `date`, `slot_name`, `time_range`, `slot_limit`, `available_slots`, `created_at`, `updated_at`) VALUES
(40, '2025-04-16', 'morning_slot_1', '07:00 AM - 09:00 AM', 2, 2, '2025-04-15 19:10:25', '2025-04-15 19:10:25'),
(41, '2025-04-16', 'morning_slot_2', '09:00 AM - 11:00 AM', 3, 3, '2025-04-15 19:10:25', '2025-04-15 19:10:25'),
(42, '2025-04-16', 'afternoon_slot_1', '11:00 AM - 01:00 PM', 3, 3, '2025-04-15 19:10:25', '2025-04-15 19:10:25'),
(43, '2025-04-16', 'afternoon_slot_2', '01:00 PM - 03:00 PM', 3, 3, '2025-04-15 19:10:25', '2025-04-15 19:10:25'),
(44, '2025-04-16', 'evening_slot', '03:00 PM - 05:00 PM', 3, 3, '2025-04-15 19:10:25', '2025-04-15 19:10:25'),
(45, '2025-04-17', 'morning_slot_1', '07:00 AM - 09:00 AM', 2, 2, '2025-04-15 19:17:18', '2025-04-15 19:17:18'),
(46, '2025-04-17', 'morning_slot_2', '09:00 AM - 11:00 AM', 5, 5, '2025-04-15 19:17:18', '2025-04-15 19:17:18'),
(47, '2025-04-17', 'afternoon_slot_1', '11:00 AM - 01:00 PM', 1, 1, '2025-04-15 19:17:18', '2025-04-15 19:17:18'),
(48, '2025-04-17', 'afternoon_slot_2', '01:00 PM - 03:00 PM', 1, 1, '2025-04-15 19:17:18', '2025-04-15 19:17:18'),
(49, '2025-04-17', 'evening_slot', '03:00 PM - 05:00 PM', 4, 4, '2025-04-15 19:17:18', '2025-04-15 19:17:18'),
(50, '2025-04-30', 'morning_slot_1', '07:00 AM - 09:00 AM', 6, 6, '2025-04-18 05:49:41', '2025-04-18 05:50:13'),
(51, '2025-04-30', 'morning_slot_2', '09:00 AM - 11:00 AM', 5, 5, '2025-04-18 05:49:41', '2025-04-18 05:50:13'),
(52, '2025-04-30', 'afternoon_slot_1', '11:00 AM - 01:00 PM', 10, 10, '2025-04-18 05:49:41', '2025-04-18 05:50:13'),
(53, '2025-04-30', 'afternoon_slot_2', '01:00 PM - 03:00 PM', 3, 3, '2025-04-18 05:49:41', '2025-04-18 05:50:13'),
(54, '2025-04-30', 'evening_slot', '03:00 PM - 05:00 PM', 2, 2, '2025-04-18 05:49:41', '2025-04-18 05:50:13');

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
  `status` enum('active','inactive') NOT NULL DEFAULT 'inactive',
  `dob` date DEFAULT NULL,
  `employee_no` varchar(20) DEFAULT NULL,
  `sss_no` varchar(20) DEFAULT NULL,
  `pagibig_no` varchar(20) DEFAULT NULL,
  `philhealth_no` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `middlename`, `lastname`, `email`, `mobile_number`, `password`, `created_at`, `role`, `status`, `dob`, `employee_no`, `sss_no`, `pagibig_no`, `philhealth_no`) VALUES
(1, 'Hedrian Dunn', NULL, 'Alvarez', 'dunnlvrz13@gmail.com', '09759500123', '$2y$10$jn8m3Y/NnSIVBQmWSZdNtuAjpc2lIjj4oJPwqO2e4crXxoQkraRw2', '2025-03-01 07:13:17', 'admin', 'active', '2004-04-13', 'EMP-0001', '1234567890', '9876543210', '1122334455'),
(2, 'Hedrian', NULL, 'Alvarez', 'hedrianlvrz13@gmail.com', '09925028930', '$2y$10$llagzPkGmqSRnmdaqysXA.Wma1Ra3r7E0OkE86NGCmG0yJ.7krvXS', '2025-03-04 14:59:55', 'user', 'active', '2004-04-13', NULL, NULL, NULL, NULL),
(5, 'Aldwin', NULL, 'Suarez', 'aldwinsuarez@gmail.com', '09929508778', '$2y$10$rtquceequxvXMIEIVquu5OElwdj3R663mROq8hc31WnRIxTioX7Ii', '2025-03-10 04:49:04', 'admin', 'active', NULL, 'EMP-0002', '2233445566', '5544332211', '6677889900'),
(6, 'John', NULL, 'Bue', 'Johnsage@gmail.com', '09292213467', '$2y$10$6BxP6fxlCMdPZwIEb5l8luMXc2AeVRb.oDAh2ok3N0SQLUHlQAgwa', '2025-03-10 05:28:23', 'technician', 'active', '2025-03-13', 'EMP-0003', '3344556677', '7766554433', '1122334455'),
(8, 'Andrew', NULL, 'Tate', 'AndrewTate@gmail.com', '09776537811', '$2y$10$ZL6SEzVCeeND9wAOr3XvFuF/vG6E0vIUHq9jjggO4o2D1V/ty7ehW', '2025-03-10 05:30:18', 'technician', 'active', '2025-03-15', 'EMP-0004', '4455667788', '8877665544', '2233445566'),
(23, 'Diz', NULL, 'Nuts', 'DizNutz@gmail.com', '09359472304', '$2y$10$wuGNS/gSlT.dE3K1/no/WeeJbtOA/DxMgNikntsIztDHEihZBLfZq', '2025-03-10 13:07:29', 'supervisor', 'active', '2025-03-21', 'EMP-0005', '5566778899', '9988776655', '3344556677'),
(43, 'Francine', NULL, 'Delos Reyes', 'francine@gmail.com', '09726374892', '$2y$10$PA4m0oqFjStb/I//StISk.LdNlnqB.9fod0RWde38Nh4ZTz79VgIG', '2025-03-10 19:18:49', 'admin', 'active', '2004-03-22', 'EMP-0006', '6677889900', '0099887766', '4455667788'),
(44, 'Hannah', 'Marie', 'Alvarez', 'hannahlvrz13@gmail.com', '09827182739', '$2y$10$AsWSNGgeI06Tb.yXa6FfsuT2Nu9Q6XDX1w9L.grfPc505me0VHHui', '2025-03-29 04:01:32', 'user', 'active', '1994-06-26', NULL, NULL, NULL, NULL),
(45, 'Robert', NULL, 'Downey', 'robertdowney@gmail.com', '09827182837', '$2y$10$4H8vXmg0MfVYH6b4cAKSCOwRRIamEpeuNtLsLoVhFAQjiFZL4NhPe', '2025-03-29 04:04:42', 'supervisor', 'active', '2043-09-12', 'EMP-0007', '7788990011', '1100998877', '5566778899'),
(46, 'testing', '', 'testing', 'testing@gmail.com', '09831341345', '$2y$10$Dyy9BcO6tW3dF..9yTZAV.ZKgsfYVDDDBwdR7oJCD4F1VHkcwD2a6', '2025-04-13 13:44:08', 'supervisor', 'inactive', '1995-06-21', NULL, '', '', ''),
(47, 'dwqdq', NULL, 'qwdqw', 'dwqdwqd@gmail.com', '09382831921', '$2y$10$6XSEqVKEp403moWydxa5peyiKoPwFyFY2SfECtDLL2oRFfVXgNAFi', '2025-04-15 14:49:25', 'supervisor', 'active', '2025-04-15', 'EMP-0008', '2312312312', '231312312312', '123131231231');

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
-- Indexes for table `appointment_technicians`
--
ALTER TABLE `appointment_technicians`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`appointment_id`,`technician_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `technician_id` (`technician_id`);

--
-- Indexes for table `followup_plan`
--
ALTER TABLE `followup_plan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `followup_visits`
--
ALTER TABLE `followup_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `technician_id` (`technician_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `service_reports`
--
ALTER TABLE `service_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `technician_id` (`technician_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date_slot` (`date`,`slot_name`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `appointment_technicians`
--
ALTER TABLE `appointment_technicians`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `followup_plan`
--
ALTER TABLE `followup_plan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `followup_visits`
--
ALTER TABLE `followup_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `service_reports`
--
ALTER TABLE `service_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appointments_technician` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `appointment_technicians`
--
ALTER TABLE `appointment_technicians`
  ADD CONSTRAINT `fk_appoint_tech_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appoint_tech_technician` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `followup_plan`
--
ALTER TABLE `followup_plan`
  ADD CONSTRAINT `followup_plan_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `followup_plan_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `followup_visits`
--
ALTER TABLE `followup_visits`
  ADD CONSTRAINT `followup_visits_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `followup_plan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `followup_visits_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `followup_visits_ibfk_3` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `service_reports`
--
ALTER TABLE `service_reports`
  ADD CONSTRAINT `service_reports_ibfk_1` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_reports_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
