-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 06, 2025 at 11:14 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `petshops`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$vZh8tsHEvuj.rMpFT9sbMueoYdj9h4CHy4o1OCBvvxYIM6dH9L5/W', 'admin@petcarepro.com', '2025-06-01 16:47:40');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `service_id` int DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `message` text,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `first_name`, `last_name`, `email`, `phone`, `service_id`, `appointment_date`, `appointment_time`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 'John', 'Doe', 'john.doe@email.com', '(555) 123-4567', 1, '2025-06-15', '10:00:00', 'First time grooming for my golden retriever', 'pending', '2025-06-01 16:47:40', '2025-06-01 16:47:40'),
(2, 'Jane', 'Smith', 'jane.smith@email.com', '(555) 234-5678', 2, '2025-06-18', '14:30:00', 'Annual checkup for my cat', 'confirmed', '2025-06-01 16:47:40', '2025-06-01 16:47:40'),
(3, 'Bob', 'Wilson', 'bob.wilson@email.com', '(555) 345-6789', 3, '2025-06-20', '09:00:00', 'Need boarding for 3 days', 'pending', '2025-06-01 16:47:40', '2025-06-01 16:47:40'),
(4, 'Alice', 'Brown', 'alice.brown@email.com', '(555) 456-7890', 5, '2025-06-25', '11:00:00', 'Photo session for my two dogs', 'pending', '2025-06-01 16:47:40', '2025-06-01 16:47:40');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `price`, `icon`, `created_at`, `updated_at`) VALUES
(1, 'Pet Grooming', 'Professional grooming services including bathing, trimming, nail clipping, and styling for your beloved pets.', 6000.00, '✂️', '2025-06-01 16:47:40', '2025-06-01 19:27:07'),
(2, 'Veterinary Care', 'Comprehensive health checkups, vaccinations, and medical treatments by licensed veterinarians.', 120000.00, '🏥', '2025-06-01 16:47:40', '2025-06-01 18:30:54'),
(3, 'Pet Boarding', 'Safe and comfortable overnight care for your pets when you are away, with 24/7 supervision.', 450000.00, '🏠', '2025-06-01 16:47:40', '2025-06-01 18:30:58'),
(4, 'Nutrition Consultation', 'Personalized diet plans and nutrition advice to keep your pets healthy and happy.', 85000.00, '🥗', '2025-06-01 16:47:40', '2025-06-01 18:31:01'),
(5, 'Pet Photography', 'Professional photo sessions to capture beautiful memories of your furry friends.', 150000.00, '📸', '2025-06-01 16:47:40', '2025-06-01 18:31:04'),
(6, 'Dog Walking', 'Regular exercise and socialization for your dogs with our experienced pet walkers.', 250000.00, '🚶', '2025-06-01 16:47:40', '2025-06-01 18:31:08');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'site_title', 'DoCaRe', '2025-06-01 18:15:57'),
(2, 'site_tagline', 'Premium Pet Care Services You Can Trust', '2025-06-01 16:47:40'),
(3, 'contact_phone', '(555) 123-4567', '2025-06-01 16:47:40'),
(4, 'contact_email', 'info@petcarepro.com', '2025-06-01 16:47:40'),
(5, 'contact_address', '123 Pet Care Street, City, State 12345', '2025-06-01 16:47:40'),
(6, 'business_hours', 'Mon-Fri: 8AM-6PM, Sat-Sun: 9AM-4PM', '2025-06-01 16:47:40');

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int NOT NULL,
  `author` varchar(100) NOT NULL,
  `comments` text NOT NULL,
  `star` int NOT NULL,
  `pet_type` varchar(50) DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `author`, `comments`, `star`, `pet_type`, `is_approved`, `created_at`, `updated_at`) VALUES
(1, 'Sarah Johnson', 'Amazing service! My golden retriever Max looks absolutely fantastic after his grooming session. The staff is so caring and professional.', 5, 'dog', 1, '2025-06-01 16:47:40', '2025-06-01 16:47:40'),
(2, 'Michael Chen', 'Dr. Smith provided excellent veterinary care for my cat Luna. Very thorough examination and great advice on nutrition.', 5, 'cat', 1, '2025-06-01 16:47:40', '2025-06-01 16:47:40'),
(3, 'Emily Rodriguez', 'Left my two dogs here for a week while on vacation. They were so well taken care of and seemed happy when I picked them up!', 4, 'dog', 1, '2025-06-01 16:47:40', '2025-06-01 16:47:40'),
(4, 'David Thompson', 'The nutrition consultation was eye-opening. My rabbit Charlie is now on a much better diet and has more energy.', 5, 'rabbit', 1, '2025-06-01 16:47:40', '2025-06-01 16:47:40'),
(5, 'Jessica Williams', 'Beautiful photos of my French Bulldog! The photographer was patient and captured her personality perfectly.', 5, 'dog', 1, '2025-06-01 16:47:40', '2025-06-01 16:47:40'),
(6, 'Robert Brown', 'Great dog walking service. My beagle comes home tired and happy every time. Highly recommend!', 4, 'dog', 1, '2025-06-01 16:47:40', '2025-06-01 17:12:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
