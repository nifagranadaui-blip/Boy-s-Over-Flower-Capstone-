-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 29, 2026 at 02:14 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `secureshops`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password_hash`, `created_at`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$wDLvHYBuf9oLVZuQXdriO.ObWxUsEaeKiNgLwHu4QX8v.TwWItTja', '2026-03-14 04:34:20'),
(2, 'admin_nino', 'adminnino@gmail.com', '$2y$12$HhN4wXtzmoBunuVBAGDdZ.iVgsd.j.mSqliMbELolIe3pUVzG/rsq', '2026-03-22 03:12:41');

-- --------------------------------------------------------

--
-- Table structure for table `batch_materials`
--

CREATE TABLE `batch_materials` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `material_name` varchar(255) NOT NULL,
  `original_kg` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batch_materials`
--

INSERT INTO `batch_materials` (`id`, `batch_id`, `material_name`, `original_kg`) VALUES
(16, 18, 'turmeric', 10.00),
(17, 18, 'lemongrass', 5.00),
(18, 19, 'Turmeric', 20.00),
(19, 19, 'Ginger', 15.00),
(20, 19, 'Sugar', 10.00),
(21, 20, 'Turmeric', 50.00),
(22, 20, 'Ginger', 25.00),
(23, 20, 'Calamansi', 15.00),
(24, 20, 'Sugar', 5.00),
(25, 21, 'ginger', 50.00),
(26, 21, 'calamansi', 20.00),
(27, 21, 'sugar', 10.00);

-- --------------------------------------------------------

--
-- Table structure for table `daily_analytics_snapshots`
--

CREATE TABLE `daily_analytics_snapshots` (
  `id` int(11) NOT NULL,
  `snapshot_date` date NOT NULL,
  `total_input_kg` decimal(10,2) DEFAULT 0.00,
  `total_output_kg` decimal(10,2) DEFAULT 0.00,
  `total_packs_produced` int(11) DEFAULT 0,
  `total_loss_kg` decimal(10,2) DEFAULT 0.00,
  `efficiency_percentage` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_analytics_snapshots`
--

INSERT INTO `daily_analytics_snapshots` (`id`, `snapshot_date`, `total_input_kg`, `total_output_kg`, `total_packs_produced`, `total_loss_kg`, `efficiency_percentage`, `created_at`, `updated_at`) VALUES
(1, '2026-03-27', 15.00, 62.00, 355, -47.00, 413.33, '2026-03-27 11:07:35', '2026-03-27 11:07:35'),
(3, '2026-03-28', 140.00, 73.00, 19, 67.00, 52.14, '2026-03-28 00:24:03', '2026-03-28 14:31:09'),
(22, '2026-03-29', 80.00, 35.00, 29, 45.00, 43.75, '2026-03-29 11:40:47', '2026-03-29 11:46:35');

-- --------------------------------------------------------

--
-- Table structure for table `material_extractions`
--

CREATE TABLE `material_extractions` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `extracted_by` varchar(100) DEFAULT NULL,
  `extraction_notes` text DEFAULT NULL,
  `material_name` varchar(255) DEFAULT NULL,
  `kg_extracted` decimal(10,2) DEFAULT 0.00,
  `extraction_datetime` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `material_extractions`
--

INSERT INTO `material_extractions` (`id`, `batch_id`, `extracted_by`, `extraction_notes`, `material_name`, `kg_extracted`, `extraction_datetime`) VALUES
(1, 10, 'beef', '200kg of juice\r\n', NULL, 0.00, '2026-03-26 07:24:16'),
(2, 11, 'beef', '60kg', NULL, 0.00, '2026-03-26 07:27:08'),
(3, 11, 'beef', 'finished juice', 'ginger', 23.00, '2026-03-26 07:59:06'),
(4, 11, 'beef', 'finished juice', 'turmeric', 35.00, '2026-03-26 07:59:06'),
(5, 11, 'beef', 'finished juice', 'garlic', 49.00, '2026-03-26 07:59:06'),
(6, 5, 'beef', '', 'TURMERIC', 33.00, '2026-03-26 07:59:37'),
(7, 5, 'beef', '', 'GINGER', 44.00, '2026-03-26 07:59:37'),
(8, 5, 'beef', '', 'SUGAR', 66.00, '2026-03-26 07:59:37'),
(9, 5, 'beef', '', 'LEMONGRASS', 77.00, '2026-03-26 07:59:37'),
(10, 12, 'beef', '', 'ginger', 8.00, '2026-03-26 08:13:31'),
(11, 12, 'beef', '', 'sugar', 7.00, '2026-03-26 08:13:31'),
(12, 12, 'beef', '', 'honey', 5.00, '2026-03-26 08:13:31'),
(13, 12, 'beef', '', 'ginger', 8.00, '2026-03-26 08:16:21'),
(14, 12, 'beef', '', 'sugar', 7.00, '2026-03-26 08:16:21'),
(15, 12, 'beef', '', 'honey', 5.00, '2026-03-26 08:16:21'),
(16, 12, 'beef', '', 'ginger', 5.00, '2026-03-26 08:16:43'),
(17, 12, 'beef', '', 'sugar', 5.00, '2026-03-26 08:16:43'),
(18, 12, 'beef', '', 'honey', 5.00, '2026-03-26 08:16:43'),
(19, 12, 'beef', '', 'ginger', 55.00, '2026-03-26 08:21:35'),
(20, 12, 'beef', '', 'sugar', 55.00, '2026-03-26 08:21:35'),
(21, 12, 'beef', '', 'honey', 55.00, '2026-03-26 08:21:35'),
(22, 12, 'beef', '', 'honey', 55.00, '2026-03-26 08:21:35'),
(23, 14, 'user_nino', '', 'Turmeric', 20.00, '2026-03-26 10:41:07'),
(24, 14, 'user_nino', '', 'Ginger', 15.00, '2026-03-26 10:41:07'),
(25, 14, 'user_nino', '', 'Lemongrass', 15.00, '2026-03-26 10:41:07'),
(26, 15, 'beef', '', 'TURMERIC', 3.00, '2026-03-27 06:49:40'),
(27, 15, 'beef', '', 'GINGER', 4.00, '2026-03-27 06:49:40'),
(28, 15, 'beef', '', 'LEMON GRASS', 2.00, '2026-03-27 06:49:40'),
(29, 15, 'beef', '', 'SUGAR', 6.00, '2026-03-27 06:49:40'),
(30, 17, 'beef', '', 'turmeric', 12.00, '2026-03-27 10:38:58'),
(31, 17, 'beef', '', 'ginger', 12.00, '2026-03-27 10:38:58'),
(32, 17, 'beef', '', 'sugar', 8.00, '2026-03-27 10:38:58'),
(33, 17, 'beef', '', 'honey', 5.00, '2026-03-27 10:38:58'),
(34, 18, 'beef', '', 'turmeric', 5.00, '2026-03-27 10:49:29'),
(35, 18, 'beef', '', 'lemongrass', 3.00, '2026-03-27 10:49:29'),
(36, 18, 'beef', '', 'sugar', 2.00, '2026-03-27 10:49:29'),
(37, 19, 'user_nino25', '', 'Turmeric', 15.00, '2026-03-28 00:29:29'),
(38, 19, 'user_nino25', '', 'Ginger', 10.00, '2026-03-28 00:29:29'),
(39, 19, 'user_nino25', '', 'Sugar', 5.00, '2026-03-28 00:29:29'),
(40, 20, 'user_nino25', '', 'Turmeric', 25.00, '2026-03-28 14:30:14'),
(41, 20, 'user_nino25', '', 'Ginger', 10.00, '2026-03-28 14:30:14'),
(42, 20, 'user_nino25', '', 'Calamansi', 5.00, '2026-03-28 14:30:14'),
(43, 20, 'user_nino25', '', 'Sugar', 3.00, '2026-03-28 14:30:14'),
(44, 21, 'user_nino25', '', 'ginger', 20.00, '2026-03-29 11:45:35'),
(45, 21, 'user_nino25', '', 'calamansi', 10.00, '2026-03-29 11:45:35'),
(46, 21, 'user_nino25', '', 'sugar', 5.00, '2026-03-29 11:45:35');

-- --------------------------------------------------------

--
-- Table structure for table `pack_yields`
--

CREATE TABLE `pack_yields` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `yield_total_kg` decimal(10,2) NOT NULL,
  `actual_grams` decimal(10,2) NOT NULL,
  `total_packs_produced` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pack_yields`
--

INSERT INTO `pack_yields` (`id`, `batch_id`, `user_id`, `yield_total_kg`, `actual_grams`, `total_packs_produced`, `created_at`) VALUES
(1, 20, 13, 80.00, 180.00, 4, '2026-03-29 11:22:10'),
(2, 20, 13, 80.00, 360.00, 2, '2026-03-29 11:22:10'),
(3, 20, 13, 100.00, 560.00, 10, '2026-03-29 11:35:40'),
(4, 20, 13, 100.00, 180.00, 5, '2026-03-29 11:35:40'),
(5, 21, 13, 35.00, 180.00, 5, '2026-03-29 11:46:18'),
(6, 21, 13, 35.00, 240.00, 3, '2026-03-29 11:46:18');

-- --------------------------------------------------------

--
-- Table structure for table `production_batches`
--

CREATE TABLE `production_batches` (
  `id` int(11) NOT NULL,
  `batch_number` varchar(50) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `production_datetime` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_batches`
--

INSERT INTO `production_batches` (`id`, `batch_number`, `product_name`, `created_by`, `production_datetime`) VALUES
(1, '40', 'turmeric', NULL, '2026-03-26 06:59:01'),
(3, '1', 'ginger tea', NULL, '2026-03-26 07:01:16'),
(5, '2', 'TURMERIC', 'beef', '2026-03-26 07:06:11'),
(7, ' 4', 'TURMERIC', NULL, '2026-03-26 07:09:21'),
(9, '4', 'TURMERIC', 'beef', '2026-03-26 07:16:00'),
(10, '112', 'TURMERIC', 'beef', '2026-03-26 07:18:03'),
(11, '09', 'ginger brew', 'beef', '2026-03-26 07:26:33'),
(12, '30', 'Ginger juice', 'beef', '2026-03-26 08:11:47'),
(13, '65', 'termmm', 'beef', '2026-03-26 08:23:30'),
(14, '3', 'Turmeric Ginger Lemongrass', 'user_nino', '2026-03-26 10:40:22'),
(15, '14', 'TURMERIC BREW', 'beef', '2026-03-27 06:48:26'),
(16, '100', 'GINGER JUICE DRINK', 'beef', '2026-03-27 08:19:24'),
(17, '20', 'turmeric ginger juice', 'beef', '2026-03-27 10:37:57'),
(18, '99', 'turmeric lemongrass tea', 'beef', '2026-03-27 10:47:44'),
(19, '5', 'Turmeric Ginger', 'user_nino25', '2026-03-28 00:29:00'),
(20, '6', 'Turmeric Ginger with Calamansi', 'user_nino25', '2026-03-28 14:29:47'),
(21, '88', 'Ginger Calamansi', 'user_nino25', '2026-03-29 11:45:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` text DEFAULT NULL,
  `phone` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `phone`, `address`, `otp_code`, `otp_expires_at`, `created_at`, `updated_at`) VALUES
(11, 'user_nino', '+M8x438zic4qtGFCydSzODo6ZGhRK0QvRGp0K0FsV2lnNTk4T09zS1JOUjJ4QWIwWk91NldEbFA5TzN2OD0=', '$2y$12$SPoVH2mdfwGyWYkPjn1CZ.s2tHe6x1VhOQPkrPFfb8PWrhj/.B2e2', '/jDq/eNCAHFzKK4gaZfGwTo6MGdQRkhLeStSSmJNN1lRL3FFbVY2Zz09', 'eQcfqbQIybrKcFzFmAiAfjo6azhoZ2tBRkFIZ3g1TnEvWUJwM2s3Zz09', 'amKMWx788Ha5uPvMNbsPOTo6N1F4S1R2Qm9yT1RKdGd1SFZDVndqMXZ5Vk16cW5tVWIrbHJOQ05uN3hicz0=', NULL, NULL, '2026-03-23 22:47:00', '2026-03-28 02:50:02'),
(12, 'beef', 'beef@gmail.com', '$2y$10$129PgcwflFm3PsymvU9pwuUAYrFrQ0etd8aDp.0ki21.iVgjLmTJa', 'beef', NULL, NULL, NULL, NULL, '2026-03-26 06:15:17', '2026-03-26 06:15:50'),
(13, 'user_nino25', 'nifa.granada.ui@phinmaed.com', '$2y$10$Ns2ZoaBEUeZT6NdviG0TeOQKavWRY1L6WneA.IWxYD/GdhWTDCxGO', 'Nino John Granada', NULL, NULL, NULL, NULL, '2026-03-28 00:26:21', '2026-03-28 00:26:21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `batch_materials`
--
ALTER TABLE `batch_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `daily_analytics_snapshots`
--
ALTER TABLE `daily_analytics_snapshots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `snapshot_date` (`snapshot_date`);

--
-- Indexes for table `material_extractions`
--
ALTER TABLE `material_extractions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `pack_yields`
--
ALTER TABLE `pack_yields`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `production_batches`
--
ALTER TABLE `production_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `batch_number` (`batch_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `batch_materials`
--
ALTER TABLE `batch_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `daily_analytics_snapshots`
--
ALTER TABLE `daily_analytics_snapshots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `material_extractions`
--
ALTER TABLE `material_extractions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `pack_yields`
--
ALTER TABLE `pack_yields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `production_batches`
--
ALTER TABLE `production_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `batch_materials`
--
ALTER TABLE `batch_materials`
  ADD CONSTRAINT `batch_materials_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `production_batches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `material_extractions`
--
ALTER TABLE `material_extractions`
  ADD CONSTRAINT `material_extractions_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `production_batches` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
