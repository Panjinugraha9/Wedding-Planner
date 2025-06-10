-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 09, 2025 at 05:52 AM
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
-- Database: `rbplnewdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `budget_categories`
--

CREATE TABLE `budget_categories` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_kategori` varchar(255) NOT NULL,
  `anggaran` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 2, 'Profil Vendor Baru', 'Ada profil vendor baru yang perlu diverifikasi dari slebew company', 'vendor_submission', 0, '2025-06-06 13:41:56'),
(2, 3, 'Profil Disetujui', 'Selamat! Profil vendor Anda telah disetujui dan akan tampil di halaman vendor.', 'vendor_approved', 1, '2025-06-06 13:44:46'),
(3, 2, 'Profil Vendor Baru', 'Ada profil vendor baru yang perlu diverifikasi dari slebew company', 'vendor_submission', 0, '2025-06-07 10:19:30'),
(4, 2, 'Profil Vendor Baru', 'Ada profil vendor baru yang perlu diverifikasi dari slebew company', 'vendor_submission', 0, '2025-06-07 10:30:41'),
(5, 2, 'Profil Vendor Baru', 'Ada profil vendor baru yang perlu diverifikasi dari slebew company', 'vendor_submission', 0, '2025-06-07 10:36:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin','vendor') NOT NULL DEFAULT 'user',
  `status` varchar(50) DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `total_dana_terkumpul` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `status`, `created_at`, `last_login`, `total_dana_terkumpul`) VALUES
(1, 'Panji', 'user123@gmail.com', '$2y$10$.PTq/nRoDc30HvcvJuIS9udYMCLQe/lTYoDsY29QqRI4x9FGED5bK', 'user', 'active', '2025-06-07 19:36:36', '2025-06-07 22:00:10', 0.00),
(2, 'admin123', 'admin123@gmail.com', '$2y$10$5ER4Rp6HMFeSv28mzLBokeME91MwtfbOBvMBtNhkypLIJx0jlWqHG', 'admin', 'active', '2025-06-07 19:36:36', '2025-06-07 21:51:58', 0.00),
(3, 'vendor123', 'vendor123@gmail.com', '$2y$10$C/6kL0VkhJtpW6a1KHqjn.7SGnWV1bdyb9H1baDU12E1nr0IW0YRe', 'vendor', 'active', '2025-06-07 19:36:36', '2025-06-07 21:51:41', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `user_overall_funds`
--

CREATE TABLE `user_overall_funds` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_categories`
--

CREATE TABLE `vendor_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendor_categories`
--

INSERT INTO `vendor_categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Catering', 'Layanan katering dan makanan', '2025-06-06 13:22:31'),
(2, 'Fotografer', 'Layanan fotografi dan videografi', '2025-06-06 13:22:31'),
(3, 'Dekorasi', 'Layanan dekorasi dan penataan', '2025-06-06 13:22:31'),
(4, 'Entertainment', 'Layanan musik dan hiburan', '2025-06-06 13:22:31'),
(5, 'Wedding Organizer', 'Layanan perencana pernikahan', '2025-06-06 13:22:31'),
(6, 'Venue', 'Tempat dan lokasi acara', '2025-06-06 13:22:31'),
(7, 'Bridal', 'Layanan pengantin (makeup, gaun, dll)', '2025-06-06 13:22:31'),
(8, 'Undangan', 'Layanan undangan dan souvenir', '2025-06-06 13:22:31'),
(9, 'Bunga', 'Layanan bunga dan rangkaian', '2025-06-06 13:22:31'),
(10, 'Transportasi', 'Layanan transportasi', '2025-06-06 13:22:31'),
(11, 'MC', 'Master of Ceremony', '2025-06-06 13:22:31'),
(12, 'Sound System', 'Layanan sound system', '2025-06-06 13:22:31');

-- --------------------------------------------------------

--
-- Table structure for table `vendor_profiles`
--

CREATE TABLE `vendor_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text NOT NULL,
  `social_media` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `is_subscribed` tinyint(1) DEFAULT 0,
  `services` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendor_profiles`
--

INSERT INTO `vendor_profiles` (`id`, `user_id`, `company_name`, `description`, `address`, `social_media`, `whatsapp`, `contact_email`, `is_subscribed`, `services`, `status`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 3, 'slebew company', 'keunggulan compnay kami bisa sukses terus tanpa henti dan tiada kendala', 'jalan mana saja asalkan tetap bersama', '@siapajakenasslebew', '089222333111', 'slebew@companycoyyy.com', 1, '[\"Bunga\",\"Catering\",\"Dekorasi\",\"Entertainment\",\"Fotografer\",\"Sound System\",\"Undangan\",\"Venue\"]', 'approved', '', '2025-06-06 13:41:56', '2025-06-07 13:52:09');

-- --------------------------------------------------------

--
-- Table structure for table `vendor_ratings`
--

CREATE TABLE `vendor_ratings` (
  `id` int(11) NOT NULL,
  `vendor_profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendor_ratings`
--

INSERT INTO `vendor_ratings` (`id`, `vendor_profile_id`, `user_id`, `rating`, `review_text`, `created_at`) VALUES
(1, 1, 1, 5, 'slebew sekali', '2025-06-06 14:06:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budget_categories`
--
ALTER TABLE `budget_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_overall_funds`
--
ALTER TABLE `user_overall_funds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `vendor_categories`
--
ALTER TABLE `vendor_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vendor_profiles`
--
ALTER TABLE `vendor_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `vendor_ratings`
--
ALTER TABLE `vendor_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_profile_id` (`vendor_profile_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budget_categories`
--
ALTER TABLE `budget_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_overall_funds`
--
ALTER TABLE `user_overall_funds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_categories`
--
ALTER TABLE `vendor_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `vendor_profiles`
--
ALTER TABLE `vendor_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vendor_ratings`
--
ALTER TABLE `vendor_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budget_categories`
--
ALTER TABLE `budget_categories`
  ADD CONSTRAINT `budget_categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_overall_funds`
--
ALTER TABLE `user_overall_funds`
  ADD CONSTRAINT `user_overall_funds_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_profiles`
--
ALTER TABLE `vendor_profiles`
  ADD CONSTRAINT `vendor_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_ratings`
--
ALTER TABLE `vendor_ratings`
  ADD CONSTRAINT `vendor_ratings_ibfk_1` FOREIGN KEY (`vendor_profile_id`) REFERENCES `vendor_profiles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vendor_ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
