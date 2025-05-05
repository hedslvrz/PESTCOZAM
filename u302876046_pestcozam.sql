-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 03, 2025 at 12:38 PM
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
-- Database: `u302876046_pestcozam`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `service_type` enum('Ocular Inspection','Treatment') NOT NULL DEFAULT 'Ocular Inspection',
  `region` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `street_address` text DEFAULT NULL,
  `landmark` varchar(255) DEFAULT NULL,
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
  `time_out` time DEFAULT NULL,
  `property_type` varchar(20) DEFAULT 'residential',
  `establishment_name` varchar(255) DEFAULT NULL,
  `property_area` decimal(10,2) DEFAULT NULL,
  `pest_concern` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `user_id`, `service_id`, `service_type`, `region`, `province`, `city`, `barangay`, `street_address`, `landmark`, `appointment_date`, `appointment_time`, `status`, `created_at`, `is_for_self`, `firstname`, `lastname`, `email`, `mobile_number`, `technician_id`, `latitude`, `longitude`, `updated_at`, `treatment_methods`, `chemicals`, `chemical_quantities`, `pct`, `device_installation`, `chemical_consumables`, `visit_frequency`, `time_in`, `time_out`, `property_type`, `establishment_name`, `property_area`, `pest_concern`) VALUES
(95, 52, 3, 'Ocular Inspection', 'Region IX', 'Zamboanga Del Sur', 'Zamboanga City', 'Canelar', 'Wee Siu Tuy Road', NULL, '2025-04-30', '09:00:00', 'Completed', '2025-04-30 13:02:02', 1, NULL, NULL, NULL, NULL, 50, 6.913780, 122.074293, '2025-05-02 01:17:56', '[]', '[]', '[]', '', '', '', 'weekly', '00:00:00', '00:00:00', 'residential', '', 200.00, '');

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
(21, 95, 50, '2025-05-01 15:05:30');

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` (`id`, `slug`, `name`) VALUES
(1, 'rodent_bait_station', 'Rodent Bait Station'),
(2, 'cage_trap', 'Cage Trap'),
(3, 'glue_trap', 'Glue Trap'),
(4, 'insect_light_trap', 'Insect Light Trap'),
(5, 'fly_trap', 'Fly Trap'),
(6, 'bird_scare', 'Bird Scare');

-- --------------------------------------------------------

--
-- Table structure for table `followup_plan`
--

CREATE TABLE `followup_plan` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `plan_type` enum('weekly','monthly','quarterly','yearly') NOT NULL,
  `visit_frequency` int(11) NOT NULL DEFAULT 1,
  `contract_duration` int(11) NOT NULL DEFAULT 1,
  `duration_unit` enum('days','weeks','months','years') NOT NULL DEFAULT 'months',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `followup_visits`
--

CREATE TABLE `followup_visits` (
  `id` int(11) NOT NULL,
  `followup_plan_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time NOT NULL,
  `status` enum('Scheduled','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `method_devices`
--

CREATE TABLE `method_devices` (
  `id` int(11) NOT NULL,
  `method_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL
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
(7, 48, 2, NULL, 3, 'sadadsa', 3, 4, 'sdada', 'asdsadsa', 'pending', '2025-04-29 06:39:32', '2025-04-29 06:39:32');

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
  `image_path` varchar(255) NOT NULL,
  `image_data` longblob DEFAULT NULL,
  `image_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `description`, `estimated_time`, `starting_price`, `image_path`, `image_data`, `image_type`) VALUES
(1, 'Soil Poisoning', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '5 hours', 170000.00, 'services/soil-poisoning-1-1745896446.jpg', NULL, 'image/jpeg'),
(2, 'Mound Demolition', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '3-5 hours', 4500.00, 'mound-demolition.jpg', NULL, NULL),
(3, 'Termite Control', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '2-3 hours', 2500.00, 'termite control.jpg', NULL, NULL),
(4, 'General Pest Control', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '1-3 hours', 2000.00, 'general pest control.jpg', NULL, NULL),
(5, 'Mosquito Control', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '3-4 hours', 2000.00, 'Mosquito control.jpg', NULL, 'image/jpeg'),
(6, 'Rat Control', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '2-3 hours', 2000.00, 'rat control.jpg', NULL, NULL),
(7, 'Other Flying and Crawling Insects', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '1-3 hours', 1800.00, 'Other-flying-insects.jpg', NULL, NULL),
(8, 'Extraction', 'Accumsan iaculis dictumst montes eros nec tristique accumsan. Accumsan iaculis dictumst montes eros.', '2-4 hours', 3500.00, 'Extraction.jpg', NULL, NULL),
(17, 'Ocular Inspection', 'Our professional pest control experts will conduct a thorough assessment of your property to identify pest problems and recommend the most effective treatment plan.', '30-60 minutes', 0.00, 'ocular-inspection.jpg', NULL, NULL),
(18, 'wdqwdq', 'dawdwd', '3 hours', 4000.00, 'WINDOWS 11 WALLPAPER.jpg', NULL, NULL);

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

--
-- Dumping data for table `service_reports`
--

INSERT INTO `service_reports` (`report_id`, `technician_id`, `appointment_id`, `date_of_treatment`, `time_in`, `time_out`, `treatment_type`, `treatment_method`, `pest_count`, `device_installation`, `consumed_chemicals`, `frequency_of_visits`, `photos`, `location`, `account_name`, `contact_no`, `status`, `created_at`, `updated_at`) VALUES
(7, 50, NULL, '2025-04-29', '13:16:00', '15:16:00', 'Mound Demolition', 'kqml a', NULL, 'mklsamql', 'klzmqlq', NULL, NULL, 'Hector Suarez Avenue, Tumaga, Zamboanga City', 'Pestcozam Admin', '09234567431', 'approved', '2025-04-29 05:17:10', '2025-04-29 05:17:48'),
(8, 50, 95, '2025-05-01', '23:06:00', '01:06:00', 'Termite Control', 'asda', '20', 'sadasd', 'asda', NULL, NULL, 'Wee Siu Tuy Road, Canelar, Zamboanga City', 'Dunn Alvarez', '09759500123', 'rejected', '2025-05-01 15:06:20', '2025-05-02 01:18:05');

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
(55, '2025-04-30', 'morning_slot_1', '07:00 AM - 09:00 AM', 2, 2, '2025-04-29 07:29:24', '2025-04-29 07:29:24'),
(56, '2025-04-30', 'morning_slot_2', '09:00 AM - 11:00 AM', 3, 3, '2025-04-29 07:29:24', '2025-04-29 07:29:24'),
(57, '2025-04-30', 'afternoon_slot_1', '11:00 AM - 01:00 PM', 5, 5, '2025-04-29 07:29:24', '2025-04-29 07:29:24'),
(58, '2025-04-30', 'afternoon_slot_2', '01:00 PM - 03:00 PM', 3, 3, '2025-04-29 07:29:24', '2025-04-29 07:29:24'),
(59, '2025-04-30', 'evening_slot', '03:00 PM - 05:00 PM', 3, 3, '2025-04-29 07:29:24', '2025-04-29 07:29:24'),
(60, '2025-04-29', 'morning_slot_1', '07:00 AM - 09:00 AM', 2, 2, '2025-04-29 07:33:19', '2025-04-29 07:33:19'),
(61, '2025-04-29', 'morning_slot_2', '09:00 AM - 11:00 AM', 3, 3, '2025-04-29 07:33:19', '2025-04-29 07:33:19'),
(62, '2025-04-29', 'afternoon_slot_1', '11:00 AM - 01:00 PM', 4, 4, '2025-04-29 07:33:19', '2025-04-29 07:33:19'),
(63, '2025-04-29', 'afternoon_slot_2', '01:00 PM - 03:00 PM', 3, 3, '2025-04-29 07:33:19', '2025-04-29 07:33:19'),
(64, '2025-04-29', 'evening_slot', '03:00 PM - 05:00 PM', 3, 3, '2025-04-29 07:33:19', '2025-04-29 07:33:19');

-- --------------------------------------------------------

--
-- Table structure for table `treatment_chemicals`
--

CREATE TABLE `treatment_chemicals` (
  `id` int(11) NOT NULL,
  `method_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `treatment_chemicals`
--

INSERT INTO `treatment_chemicals` (`id`, `method_id`, `name`) VALUES
(1, 1, 'Cymflex'),
(2, 1, 'Permitor'),
(3, 1, 'Pervade'),
(4, 1, 'Cyflux'),
(5, 2, 'Demand CS'),
(6, 2, 'Fendona'),
(7, 2, 'Fipro'),
(8, 2, 'Cyflux'),
(9, 3, 'Bosny Rat Glue'),
(10, 4, 'Termidor'),
(11, 4, 'Optigard Termite Liquid'),
(12, 4, 'Revancha 10 EC'),
(13, 5, 'Termidor'),
(14, 5, 'Optigard Termite Liquid'),
(15, 5, 'Revancha 10 EC'),
(16, 6, 'Termipipes'),
(17, 6, 'PVC Pipes'),
(18, 8, 'Termite Box'),
(19, 9, 'Inground Bait'),
(20, 10, 'Fipronil'),
(21, 10, 'Exterminex Powder'),
(22, 11, '6 Mil Polyethylene Sheet'),
(23, 11, '8 Mil Polyethylene Sheet');

-- --------------------------------------------------------

--
-- Table structure for table `treatment_methods`
--

CREATE TABLE `treatment_methods` (
  `id` int(11) NOT NULL,
  `treatment_type_id` int(11) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `treatment_methods`
--

INSERT INTO `treatment_methods` (`id`, `treatment_type_id`, `slug`, `name`) VALUES
(1, 1, 'surface_spraying', 'Surface Spraying'),
(2, 1, 'space_spraying', 'Space Spraying'),
(3, 1, 'rodent_control', 'Rodent Control'),
(4, 2, 'post_construction', 'Post-Construction (Soil Injection Treatment)'),
(5, 2, 'pre_construction', 'Pre-Construction (Massive Spraying)'),
(6, 2, 'reticulation', 'Reticulation'),
(7, 2, 'termite_mound', 'Termite Mound'),
(8, 2, 'above_ground', 'Above-Ground Termite Treatment'),
(9, 2, 'in_ground', 'In-Ground Termite'),
(10, 2, 'dusting', 'Dusting'),
(11, 2, 'vapor_barrier', 'Vapor Barrier Installation');

-- --------------------------------------------------------

--
-- Table structure for table `treatment_types`
--

CREATE TABLE `treatment_types` (
  `id` int(11) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `treatment_types`
--

INSERT INTO `treatment_types` (`id`, `slug`, `name`) VALUES
(1, 'general_pest_control', 'General Pest Control'),
(2, 'termite_treatment', 'Termite Treatment');

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
  `philhealth_no` varchar(20) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `middlename`, `lastname`, `email`, `mobile_number`, `password`, `created_at`, `role`, `status`, `dob`, `employee_no`, `sss_no`, `pagibig_no`, `philhealth_no`, `profile_pic`, `deleted`, `deleted_reason`) VALUES
(48, 'Pestcozam', '', 'Admin', 'pestcozam2025@gmail.com', '09234567431', '$2y$10$BtHe06BusDhEpoLOk1V/LOAV0IEw/A8oJFuMOr2ect1E6o6a3YVLS', '2025-04-29 02:29:12', 'admin', 'active', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(50, 'Hedrian', NULL, 'Alvarez', 'hedrianlvrz13@gmail.com', '09759500123', '$2y$10$2vSdbX.kbClrLtifajS9vOlb3HlBMCzanV8Ksb5JAgIfc.HuMIn8K', '2025-04-29 04:45:18', 'technician', 'active', '2004-04-13', 'EMP-0001', '', '', '', NULL, 0, NULL),
(51, 'Aldwin', NULL, 'Suarez', 'aldwinsuarez@gmail.com', '09786758943', '$2y$10$gg1ZDPNqQDZ3bXzcI.YMM.DfkEqnviVfXUlV0vJwmCp.dUYUuCZby', '2025-04-29 05:13:19', 'supervisor', 'active', '2004-04-13', 'EMP-0002', '', '', '', NULL, 0, NULL),
(52, 'Dunn', 'Pastores', 'Alvarez', 'dunnlvrz13@gmail.com', '09759500123', '$2y$10$ynB4RQ830MyNS2JoqT/c5.RDjPLcMp2dkciJIGtCAgJ5ndP.lfzGa', '2025-04-30 12:59:37', 'user', 'active', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(54, 'Hannah', NULL, 'Alvarez', 'hannahlvrz26@gmail.com', '09758751934', '$2y$10$wLlQNjt3VZyV3XiPfTHH3eAzEC3c2D.bgDYsmsjULoTFja0aAS2ce', '2025-05-01 12:49:38', 'supervisor', 'active', '1998-06-26', 'EMP-0004', '', '', '', NULL, 0, 'FUCKER');

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
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

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
  ADD KEY `followup_plan_id` (`followup_plan_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `method_devices`
--
ALTER TABLE `method_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `method_device_pair` (`method_id`,`device_id`),
  ADD KEY `device_id` (`device_id`);

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
-- Indexes for table `treatment_chemicals`
--
ALTER TABLE `treatment_chemicals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `method_id` (`method_id`);

--
-- Indexes for table `treatment_methods`
--
ALTER TABLE `treatment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `treatment_type_id` (`treatment_type_id`);

--
-- Indexes for table `treatment_types`
--
ALTER TABLE `treatment_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `appointment_technicians`
--
ALTER TABLE `appointment_technicians`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- AUTO_INCREMENT for table `method_devices`
--
ALTER TABLE `method_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `service_reports`
--
ALTER TABLE `service_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `treatment_chemicals`
--
ALTER TABLE `treatment_chemicals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `treatment_methods`
--
ALTER TABLE `treatment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `treatment_types`
--
ALTER TABLE `treatment_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

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
  ADD CONSTRAINT `followup_visits_ibfk_1` FOREIGN KEY (`followup_plan_id`) REFERENCES `followup_plan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `followup_visits_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `method_devices`
--
ALTER TABLE `method_devices`
  ADD CONSTRAINT `method_devices_ibfk_1` FOREIGN KEY (`method_id`) REFERENCES `treatment_methods` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `method_devices_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `treatment_chemicals`
--
ALTER TABLE `treatment_chemicals`
  ADD CONSTRAINT `treatment_chemicals_ibfk_1` FOREIGN KEY (`method_id`) REFERENCES `treatment_methods` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `treatment_methods`
--
ALTER TABLE `treatment_methods`
  ADD CONSTRAINT `treatment_methods_ibfk_1` FOREIGN KEY (`treatment_type_id`) REFERENCES `treatment_types` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
