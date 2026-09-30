-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 30, 2026 at 02:20 PM
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
-- Database: `evaluation`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `actor_name` varchar(150) NOT NULL,
  `actor_type` enum('admin','system') NOT NULL DEFAULT 'admin',
  `action_text` varchar(255) NOT NULL,
  `icon` varchar(40) DEFAULT 'fa-circle-info',
  `color` varchar(20) DEFAULT '#60a5fa',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `actor_name`, `actor_type`, `action_text`, `icon`, `color`, `created_at`) VALUES
(1, 'Flowen Nina Anecito', 'admin', 'Archived evaluation data for 2026-2027 (Archive #1)', 'fa-box-archive', '#2563EB', '2026-09-05 08:30:11'),
(2, 'Flowen Nina Anecito', 'admin', 'Archived evaluation data for 2026-2027 (Archive #2)', 'fa-box-archive', '#2563EB', '2026-09-05 09:22:35'),
(3, 'Flowen Nina Anecito', 'admin', 'Archived evaluation data for 2026-2027 (Archive #3)', 'fa-box-archive', '#2563EB', '2026-09-05 09:23:08'),
(4, 'Flowen Nina Anecito', 'admin', 'Restored evaluation archive #3 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-05 11:32:25'),
(5, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #2 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-10 14:34:28'),
(6, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #2)', 'fa-box-archive', '#2563EB', '2026-09-10 15:24:33'),
(7, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #3)', 'fa-box-archive', '#2563EB', '2026-09-10 15:24:40'),
(8, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #3 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-10 15:51:50'),
(9, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #3)', 'fa-box-archive', '#2563EB', '2026-09-11 12:26:21'),
(10, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #3 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-11 12:26:46'),
(11, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #2 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-11 12:27:02'),
(12, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #2)', 'fa-box-archive', '#2563EB', '2026-09-11 12:27:09'),
(13, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #3)', 'fa-box-archive', '#2563EB', '2026-09-11 12:27:12'),
(14, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #3 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-11 15:40:49'),
(15, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #2 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-11 15:45:45'),
(16, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #1 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-11 15:45:45'),
(17, 'Flowen Nina Anecito', 'admin', 'Archived evaluation data for 2026-2027 (Archive #1)', 'fa-box-archive', '#2563EB', '2026-09-13 08:59:00'),
(18, 'Flowen Nina Anecito', 'admin', 'Archived evaluation data for 2026-2027 (Archive #2)', 'fa-box-archive', '#2563EB', '2026-09-13 08:59:03'),
(19, 'Flowen Nina Anecito', 'admin', 'Archived evaluation data for 2026-2027 (Archive #3)', 'fa-box-archive', '#2563EB', '2026-09-13 08:59:07'),
(20, 'Flowen Nina Anecito', 'admin', 'Restored evaluation archive #3 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-13 08:59:29'),
(21, 'Flowen Nina Anecito', 'admin', 'Restored evaluation archive #2 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-13 08:59:29'),
(22, 'Flowen Nina Anecito', 'admin', 'Restored evaluation archive #1 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-13 08:59:29'),
(23, 'Flowen Nina Anecito', 'admin', 'Archived evaluation data for 2026-2027 (Archive #1)', 'fa-box-archive', '#2563EB', '2026-09-15 13:01:54'),
(24, 'Flowen Nina Anecito', 'admin', 'Restored evaluation archive #1 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-15 14:25:44'),
(25, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #1)', 'fa-box-archive', '#2563EB', '2026-09-18 17:03:46'),
(26, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #2)', 'fa-box-archive', '#2563EB', '2026-09-18 17:03:50'),
(27, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #3)', 'fa-box-archive', '#2563EB', '2026-09-18 17:03:54'),
(28, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #3 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-18 17:05:09'),
(29, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #2 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-18 17:05:09'),
(30, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #1 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-18 17:05:09'),
(31, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #1)', 'fa-box-archive', '#2563EB', '2026-09-19 11:05:57'),
(32, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #1 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-19 12:44:36'),
(33, 'Lorraine R. Sabay', 'admin', 'Deleted evaluation archive #1 for 2026-2027 (2026-2027 — 1st Semester)', 'fa-trash', '#D6455D', '2026-09-20 15:57:18'),
(34, 'Flowen Nina Anecito', 'admin', 'Archived evaluation data for 2026-2027 (Archive #4)', 'fa-box-archive', '#2563EB', '2026-09-21 10:37:21'),
(35, 'Flowen Nina Anecito', 'admin', 'Archived evaluation data for 2026-2027 (Archive #5)', 'fa-box-archive', '#2563EB', '2026-09-21 10:37:26'),
(36, 'Flowen Nina Anecito', 'admin', 'Archived evaluation data for 2026-2027 (Archive #2)', 'fa-box-archive', '#2563EB', '2026-09-21 10:37:31'),
(37, 'Flowen Nina Anecito', 'admin', 'Archived evaluation data for 2026-2027 (Archive #3)', 'fa-box-archive', '#2563EB', '2026-09-21 10:37:34'),
(38, 'Flowen Nina Anecito', 'admin', 'Deleted evaluation archive #3 for 2026-2027 (2026-2027 — School Year)', 'fa-trash', '#D6455D', '2026-09-21 13:53:43'),
(39, 'Flowen Nina Anecito', 'admin', 'Restored evaluation archive #2 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-21 13:53:47'),
(40, 'Flowen Nina Anecito', 'admin', 'Archived evaluation data for 2026-2027 (Archive #2)', 'fa-box-archive', '#2563EB', '2026-09-23 14:48:33'),
(41, 'Flowen Nina Anecito', 'admin', 'Archived evaluation data for 2026-2027 (Archive #6)', 'fa-box-archive', '#2563EB', '2026-09-23 14:48:36'),
(42, 'Flowen Nina Anecito', 'admin', 'Restored evaluation archive #6 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-23 14:49:19'),
(43, 'Flowen Nina Anecito', 'admin', 'Restored evaluation archive #2 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-23 14:49:19'),
(44, 'Flowen Nina Anecito', 'admin', 'Restored evaluation archive #5 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-23 14:49:19'),
(45, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #5)', 'fa-box-archive', '#2563EB', '2026-09-24 08:49:39'),
(46, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #2)', 'fa-box-archive', '#2563EB', '2026-09-24 08:49:42'),
(47, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #2 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-24 08:49:44'),
(48, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #5 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-24 08:49:47'),
(49, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #2)', 'fa-box-archive', '#2563EB', '2026-09-27 11:31:43'),
(50, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #5)', 'fa-box-archive', '#2563EB', '2026-09-27 11:31:50'),
(51, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #6)', 'fa-box-archive', '#2563EB', '2026-09-27 11:31:54'),
(52, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #6 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-27 15:52:15'),
(53, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #5 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-27 15:52:15'),
(54, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #2 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-27 15:52:15'),
(55, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #5)', 'fa-box-archive', '#2563EB', '2026-09-28 22:21:11'),
(56, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #6)', 'fa-box-archive', '#2563EB', '2026-09-28 22:21:14'),
(57, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #2)', 'fa-box-archive', '#2563EB', '2026-09-28 22:21:16'),
(58, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #2 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-28 22:25:56'),
(59, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #6 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-28 22:25:56'),
(60, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #5 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-28 22:25:56');

-- --------------------------------------------------------

--
-- Table structure for table `admin_permissions`
--

CREATE TABLE `admin_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_user_id` int(10) UNSIGNED NOT NULL,
  `feature_key` varchar(50) NOT NULL,
  `admin_can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `feature_label` varchar(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_permissions`
--

INSERT INTO `admin_permissions` (`id`, `admin_user_id`, `feature_key`, `admin_can_edit`, `notes`, `updated_at`, `feature_label`) VALUES
(2, 105, 'questionnaire', 1, NULL, '2026-07-24 15:08:49', 'Questionnaire'),
(3, 105, 'personnel_registry', 0, NULL, '2026-07-24 14:22:24', 'Personnel Registry'),
(4, 105, 'documents', 0, NULL, '2026-07-24 14:22:24', 'Documents'),
(5, 105, 'reports_analytics', 0, NULL, '2026-07-24 14:33:36', 'Reports & Analytics'),
(6, 105, 'eval_periods', 0, NULL, '2026-07-24 14:22:24', 'Evaluation Periods'),
(8, 138, 'questionnaire', 0, NULL, '2026-07-31 09:28:49', ''),
(9, 138, 'personnel_registry', 0, NULL, '2026-07-31 09:28:49', ''),
(10, 138, 'documents', 0, NULL, '2026-07-31 09:28:49', ''),
(11, 138, 'reports_analytics', 0, NULL, '2026-07-31 09:28:49', ''),
(12, 138, 'eval_periods', 0, NULL, '2026-07-31 09:28:49', ''),
(14, 140, 'questionnaire', 0, NULL, '2026-07-31 09:52:35', ''),
(15, 140, 'personnel_registry', 0, NULL, '2026-07-31 09:52:35', ''),
(16, 140, 'documents', 0, NULL, '2026-07-31 09:52:35', ''),
(17, 140, 'reports_analytics', 0, NULL, '2026-07-31 09:52:35', ''),
(18, 140, 'eval_periods', 0, NULL, '2026-07-31 09:52:35', ''),
(19, 155, 'user_management', 0, NULL, '2026-08-04 14:22:51', ''),
(20, 155, 'questionnaire', 0, NULL, '2026-08-04 14:22:51', ''),
(21, 155, 'personnel_registry', 0, NULL, '2026-08-04 14:22:51', ''),
(22, 155, 'documents', 0, NULL, '2026-08-04 14:22:51', ''),
(23, 155, 'reports_analytics', 0, NULL, '2026-08-04 14:22:51', ''),
(24, 155, 'eval_periods', 0, NULL, '2026-08-04 14:22:51', '');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `analytics_archive`
--

CREATE TABLE `analytics_archive` (
  `id` int(10) UNSIGNED NOT NULL,
  `target_user_id` int(10) UNSIGNED NOT NULL,
  `archived_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `analytics_archive`
--

INSERT INTO `analytics_archive` (`id`, `target_user_id`, `archived_at`) VALUES
(16, 158, '2026-08-29 12:54:06'),
(18, 157, '2026-09-18 07:47:12'),
(19, 209, '2026-09-21 13:53:12'),
(20, 144, '2026-09-21 13:53:15');

-- --------------------------------------------------------

--
-- Table structure for table `analytics_reports`
--

CREATE TABLE `analytics_reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `report_name` varchar(200) NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `period` varchar(50) DEFAULT NULL,
  `sector` enum('Faculty','Staff','Student','All') NOT NULL DEFAULT 'All',
  `generated_by` int(10) UNSIGNED DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `summary_json` longtext DEFAULT NULL,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(10) UNSIGNED NOT NULL,
  `reference_code` varchar(30) NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `service_id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled','no_show') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auth_attempts`
--

CREATE TABLE `auth_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kind` varchar(16) NOT NULL,
  `identifier` varchar(100) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `attempted_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auth_attempts`
--

INSERT INTO `auth_attempts` (`id`, `kind`, `identifier`, `ip`, `success`, `attempted_at`) VALUES
(29, 'login', 'jeo', '::1', 1, '2026-09-23 05:15:31'),
(30, 'login', 'jeo', '::1', 1, '2026-09-23 05:18:27'),
(31, 'login', 'j', '::1', 0, '2026-09-23 08:08:52'),
(32, 'login', 'jeo', '::1', 1, '2026-09-23 08:08:56'),
(33, 'login', 'jeo', '::1', 1, '2026-09-23 08:09:18'),
(36, 'login', 'jeo', '::1', 1, '2026-09-23 09:41:50'),
(37, 'login', 'john', '::1', 1, '2026-09-23 10:06:37'),
(40, 'login', 'josesantos', '::1', 1, '2026-09-24 03:33:39'),
(43, 'login', 'john', '::1', 1, '2026-09-24 03:35:43'),
(44, 'login', 'dim', '::1', 1, '2026-09-24 03:38:17'),
(45, 'login', 'dim', '::1', 1, '2026-09-24 04:46:23'),
(46, 'login', 'jeo', '::1', 1, '2026-09-24 04:46:38'),
(47, 'login', 'dim', '::1', 1, '2026-09-24 04:56:55'),
(50, 'login', 'dim', '::1', 1, '2026-09-24 10:05:51'),
(51, 'login', 'jeo', '::1', 1, '2026-09-24 10:06:10'),
(52, 'login', 'dim', '::1', 1, '2026-09-24 10:12:51'),
(53, 'login', 'dimmy', '::1', 1, '2026-09-24 10:23:26'),
(54, 'login', 'dim', '::1', 1, '2026-09-24 10:39:02'),
(55, 'login', 'dim', '::1', 1, '2026-09-26 15:04:47'),
(56, 'login', 'dim', '::1', 1, '2026-09-26 15:48:28'),
(57, 'login', 'dim', '::1', 1, '2026-09-27 05:23:42'),
(59, 'login', 'jeo', '::1', 1, '2026-09-27 05:25:55'),
(60, 'login', 'dim', '::1', 1, '2026-09-27 09:16:11'),
(62, 'login', 'jeo', '::1', 1, '2026-09-28 10:28:14'),
(63, 'login', 'dimmy', '::1', 0, '2026-09-28 15:57:32'),
(64, 'login', 'dimmy', '::1', 0, '2026-09-28 15:57:38'),
(65, 'login', 'michel', '::1', 1, '2026-09-28 15:57:57'),
(66, 'login', 'neil', '::1', 0, '2026-09-29 04:08:55'),
(67, 'login', 'neil', '::1', 0, '2026-09-29 04:09:05'),
(68, 'login', 'dim', '::1', 1, '2026-09-29 04:09:43'),
(69, 'login', 'dim', '::1', 1, '2026-09-29 09:39:42'),
(71, 'login', 'dim', '::1', 1, '2026-09-29 09:47:54'),
(72, 'login', 'jeo', '::1', 1, '2026-09-29 09:49:31');

-- --------------------------------------------------------

--
-- Table structure for table `business_hours`
--

CREATE TABLE `business_hours` (
  `id` int(10) UNSIGNED NOT NULL,
  `day_of_week` tinyint(3) UNSIGNED NOT NULL,
  `open_time` time DEFAULT NULL,
  `close_time` time DEFAULT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Dumping data for table `business_hours`
--

INSERT INTO `business_hours` (`id`, `day_of_week`, `open_time`, `close_time`, `is_open`) VALUES
(1, 0, NULL, NULL, 0),
(2, 1, '09:00:00', '17:00:00', 1),
(3, 2, '09:00:00', '17:00:00', 1),
(4, 3, '09:00:00', '17:00:00', 1),
(5, 4, '09:00:00', '17:00:00', 1),
(6, 5, '09:00:00', '17:00:00', 1),
(7, 6, '09:00:00', '15:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size_kb` int(10) UNSIGNED DEFAULT NULL,
  `sector` enum('Faculty','Staff','Student','All') NOT NULL DEFAULT 'All',
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_answers`
--

CREATE TABLE `evaluation_answers` (
  `id` int(11) NOT NULL,
  `tracker_id` int(10) UNSIGNED NOT NULL,
  `category` varchar(100) NOT NULL,
  `question` varchar(255) NOT NULL,
  `score` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation_answers`
--

INSERT INTO `evaluation_answers` (`id`, `tracker_id`, `category`, `question`, `score`) VALUES
(6, 73, 'Professionalism', 'Explains lessons clearly and effectively.', 5),
(7, 73, 'Professionalism', 'Handles classroom concerns appropriately.', 4),
(8, 73, 'Teaching Effectiveness', 'Presents learning objectives clearly.', 5),
(9, 73, 'Teaching Effectiveness', 'Uses appropriate teaching strategies and methods.', 5),
(10, 73, 'Teaching Effectiveness', 'Provides clear instructions for activities and assignments.', 5),
(11, 73, 'Teaching Effectiveness', 'Encourages active participation during class discussions.', 4),
(12, 73, 'Teaching Effectiveness', 'Maintains proper classroom discipline.', 4),
(13, 73, 'Teaching Effectiveness', 'Creates a positive and respectful learning environment.', 5),
(14, 73, 'Teaching Effectiveness', 'Manages classroom activities effectively.', 5),
(15, 73, 'Teaching Effectiveness', 'Treats students fairly and respectfully.', 5),
(16, 73, 'Teaching Effectiveness', 'testing', 4);

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_periods`
--

CREATE TABLE `evaluation_periods` (
  `id` int(10) UNSIGNED NOT NULL,
  `period_label` varchar(100) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `semester` enum('1st Semester','2nd Semester','Summer','School Year') NOT NULL,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `tracking_enabled` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluation_periods`
--

INSERT INTO `evaluation_periods` (`id`, `period_label`, `school_year`, `semester`, `date_start`, `date_end`, `is_active`, `created_at`, `tracking_enabled`) VALUES
(1, '2024-2025 1st Semester', '2024-2025', '1st Semester', NULL, NULL, 0, '2026-06-08 22:14:26', 0),
(2, '2026-2027 — School Year', '2026-2027', 'School Year', '2026-09-28', '2026-09-28', 0, '2026-08-05 18:15:42', 0),
(3, '2026-2027 — 1st Semester', '2026-2027', '1st Semester', '2026-09-19', '2026-08-26', 0, '2026-08-05 18:16:56', 0),
(4, '2025-2-26 — School Year', '2025-2-26', 'School Year', '2026-08-06', '2026-08-06', 0, '2026-08-06 11:03:33', 0),
(5, '2026-2027 — Summer', '2026-2027', 'Summer', '2026-08-26', '2026-08-26', 0, '2026-08-06 13:21:02', 0),
(6, '2026-2027 — 1st Semester', '2026-2027', '1st Semester', '2026-09-28', '2026-09-28', 1, '2026-08-21 12:39:24', 0),
(7, '2026-2027 — 2nd Semester', '2026-2027', '2nd Semester', '2026-08-07', '2026-08-07', 0, '2026-08-24 09:52:56', 0),
(8, '2027-2028 — Summer', '2027-2028', 'Summer', '2026-08-07', '2026-08-07', 0, '2026-08-25 08:39:06', 0),
(9, '2027-2028 — 1st Semester', '2027-2028', '1st Semester', '2026-09-28', '2026-09-28', 0, '2026-09-28 22:22:47', 0);

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_questions`
--

CREATE TABLE `evaluation_questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `target_type` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'General',
  `question_text` text NOT NULL,
  `eval_type` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `evaluator_role` varchar(10) NOT NULL DEFAULT 'shared'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluation_questions`
--

INSERT INTO `evaluation_questions` (`id`, `target_type`, `category`, `question_text`, `eval_type`, `created_at`, `is_active`, `date_added`, `evaluator_role`) VALUES
(193, 'Multi-Role', 'General Performance', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 'student', '2026-08-18 15:08:56', 1, '2026-08-18 07:08:56', 'shared'),
(203, 'Teacher', 'General', 'Collaborates effectively with colleagues in academic activities.', 'peer', '2026-08-26 11:35:35', 1, '2026-08-26 03:35:35', 'shared'),
(204, 'Teacher', 'General', 'Shares knowledge, ideas, and teaching resources with other teachers.', 'peer', '2026-08-26 11:36:06', 1, '2026-08-26 03:36:06', 'shared'),
(205, 'Teacher', 'General', 'Shows willingness to support colleagues in addressing teaching-related concerns.', 'peer', '2026-08-26 11:36:29', 1, '2026-08-26 03:36:29', 'shared'),
(206, 'Teacher', 'Collaboration', 'Contributes positively to a collaborative and supportive school environment.', 'peer', '2026-08-26 11:36:53', 1, '2026-08-26 03:36:53', 'shared'),
(207, 'Teacher', 'Professionalism', 'Demonstrates professionalism and respect toward colleagues.', 'peer', '2026-08-26 11:38:45', 1, '2026-08-26 03:38:45', 'shared'),
(208, 'Teacher', 'Professionalism', 'Accepts feedback and suggestions from fellow teachers constructively.', 'peer', '2026-08-26 11:39:15', 1, '2026-08-26 03:39:15', 'shared'),
(209, 'Teacher', 'Professionalism', 'Explains lessons clearly and effectively.', 'student', '2026-08-26 12:02:45', 1, '2026-08-26 04:02:45', 'shared'),
(210, 'Teacher', 'Teaching Effectiveness', 'Presents learning objectives clearly.', 'student', '2026-08-26 12:02:57', 1, '2026-08-26 04:02:57', 'shared'),
(211, 'Teacher', 'Teaching Effectiveness', 'Uses appropriate teaching strategies and methods.', 'student', '2026-08-26 12:03:05', 1, '2026-08-26 04:03:05', 'shared'),
(212, 'Teacher', 'Teaching Effectiveness', 'Provides clear instructions for activities and assignments.', 'student', '2026-08-26 12:03:13', 1, '2026-08-26 04:03:13', 'shared'),
(213, 'Teacher', 'Teaching Effectiveness', 'Encourages active participation during class discussions.', 'student', '2026-08-26 12:03:26', 1, '2026-08-26 04:03:26', 'shared'),
(214, 'Teacher', 'Teaching Effectiveness', 'Maintains proper classroom discipline.', 'student', '2026-08-26 12:03:44', 1, '2026-08-26 04:03:44', 'shared'),
(215, 'Teacher', 'Teaching Effectiveness', 'Creates a positive and respectful learning environment.', 'student', '2026-08-26 12:03:50', 1, '2026-08-26 04:03:50', 'shared'),
(216, 'Teacher', 'Teaching Effectiveness', 'Manages classroom activities effectively.', 'student', '2026-08-26 12:03:58', 1, '2026-08-26 04:03:58', 'shared'),
(217, 'Teacher', 'Teaching Effectiveness', 'Treats students fairly and respectfully.', 'student', '2026-08-26 12:04:05', 1, '2026-08-26 04:04:05', 'shared'),
(218, 'Teacher', 'Professionalism', 'Handles classroom concerns appropriately.', 'student', '2026-08-26 12:04:25', 1, '2026-08-26 04:04:25', 'shared'),
(219, 'Teacher', 'Teaching Effectiveness', 'testing', 'student', '2026-08-26 14:32:18', 1, '2026-08-26 06:32:18', 'shared'),
(224, 'Faculty', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 'school_head', '2026-09-12 14:41:01', 1, '2026-09-12 06:41:01', 'dean'),
(225, 'Faculty', 'General', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 'school_head', '2026-09-12 14:41:01', 1, '2026-09-12 06:41:01', 'principal'),
(226, 'Faculty', 'Professionalism', 'Demonstrates punctuality, excellent attendance *', 'school_head', '2026-09-12 14:41:01', 1, '2026-09-12 06:41:01', 'dean'),
(227, 'Faculty', 'General', 'Demonstrates punctuality, excellent attendance *', 'school_head', '2026-09-12 14:41:01', 1, '2026-09-12 06:41:01', 'principal'),
(229, 'Faculty', 'General', 'dvfdxv', 'school_head', '2026-09-12 14:41:01', 1, '2026-09-12 06:41:01', 'principal'),
(230, 'Faculty', 'Teaching Effectiveness', 'Clearly explains lessons and course-related concepts.', 'school_head', '2026-09-13 17:09:59', 1, '2026-09-13 09:09:59', 'dean'),
(231, 'Dean', 'Communication', 'Clearly explains lessons and course-related concepts.', 'staff', '2026-09-13 17:16:21', 1, '2026-09-13 09:16:21', 'shared'),
(232, 'Dean', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 'staff', '2026-09-13 17:16:34', 1, '2026-09-13 09:16:34', 'shared'),
(233, 'Principal', 'Leadership & Governance', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 'staff', '2026-09-13 17:23:31', 1, '2026-09-13 09:23:31', 'shared'),
(234, 'EA', 'Administrative Support', 'Demonstrates effective teaching strategies and methods.', 'staff', '2026-09-13 17:23:54', 1, '2026-09-13 09:23:54', 'shared'),
(235, 'Principal', 'Communication', 'Clearly explains lessons and course-related concepts.', 'staff', '2026-09-13 21:57:26', 1, '2026-09-13 13:57:26', 'shared'),
(236, 'Principal', 'Professionalism', 'Demonstrates punctuality, excellent attendance.', 'staff', '2026-09-13 21:59:45', 1, '2026-09-13 13:59:45', 'shared'),
(237, 'EA', 'Leadership & Coordination', 'Demonstrates punctuality, excellent attendance', 'school_head', '2026-09-15 07:48:50', 1, '2026-09-14 23:48:50', 'dean'),
(238, 'EA', 'Leadership & Coordination', 'Demonstrates effective teaching strategies and methods.', 'school_head', '2026-09-15 07:49:12', 1, '2026-09-14 23:49:12', 'dean'),
(239, 'EA', 'Leadership & Coordination', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 'school_head', '2026-09-15 07:49:27', 1, '2026-09-14 23:49:27', 'dean'),
(240, 'EA', 'Professionalism', 'Demonstrates punctuality, excellent attendance', 'staff', '2026-09-15 14:26:16', 1, '2026-09-15 06:26:16', 'shared'),
(241, 'EA', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 'staff', '2026-09-15 14:26:40', 1, '2026-09-15 06:26:40', 'shared'),
(242, 'EA', 'Administrative Support', 'Clearly explains lessons and course-related concepts.', 'staff', '2026-09-18 15:23:42', 1, '2026-09-18 07:23:42', 'shared'),
(243, 'EA', 'Service & Coordination', 'Demonstrates effective teaching strategies and methods.', 'staff', '2026-09-18 15:23:47', 1, '2026-09-18 07:23:47', 'shared'),
(244, 'Faculty', 'General', 'Collaborates effectively with colleagues in academic activities.', 'general', '2026-09-19 17:02:41', 0, '2026-09-19 09:02:41', 'shared'),
(245, 'Faculty', 'General', 'Shares knowledge, ideas, and teaching resources with other teachers.', 'general', '2026-09-19 17:02:41', 0, '2026-09-19 09:02:41', 'shared'),
(246, 'Faculty', 'General', 'Shows willingness to support colleagues in addressing teaching-related concerns.', 'general', '2026-09-19 17:02:41', 0, '2026-09-19 09:02:41', 'shared'),
(247, 'Faculty', 'General', 'Contributes positively to a collaborative and supportive school environment.', 'general', '2026-09-19 17:02:41', 0, '2026-09-19 09:02:41', 'shared'),
(248, 'Faculty', 'General', 'Demonstrates professionalism and respect toward colleagues.', 'general', '2026-09-19 17:02:41', 0, '2026-09-19 09:02:41', 'shared'),
(249, 'Faculty', 'General', 'Accepts feedback and suggestions from fellow teachers constructively.', 'general', '2026-09-19 17:02:41', 0, '2026-09-19 09:02:41', 'shared'),
(250, 'Faculty', 'General', 'Explains lessons clearly and effectively.', 'general', '2026-09-19 17:02:41', 0, '2026-09-19 09:02:41', 'shared'),
(251, 'Faculty', 'Teaching & Learning', 'Presents learning objectives clearly.', 'general', '2026-09-19 17:02:41', 1, '2026-09-19 09:02:41', 'shared'),
(252, 'Faculty', 'Teaching & Learning', 'Uses appropriate teaching strategies and methods.', 'general', '2026-09-19 17:02:41', 1, '2026-09-19 09:02:41', 'shared'),
(253, 'Faculty', 'Teaching & Learning', 'Provides clear instructions for activities and assignments.', 'general', '2026-09-19 17:02:41', 1, '2026-09-19 09:02:41', 'shared'),
(254, 'Faculty', 'Teaching & Learning', 'Encourages active participation during class discussions.', 'general', '2026-09-19 17:02:41', 1, '2026-09-19 09:02:41', 'shared'),
(255, 'Faculty', 'Teaching & Learning', 'Maintains proper classroom discipline.', 'general', '2026-09-19 17:02:41', 1, '2026-09-19 09:02:41', 'shared'),
(256, 'Faculty', 'Teaching & Learning', 'Creates a positive and respectful learning environment.', 'general', '2026-09-19 17:02:41', 1, '2026-09-19 09:02:41', 'shared'),
(257, 'Faculty', 'Teaching & Learning', 'Manages classroom activities effectively.', 'general', '2026-09-19 17:02:41', 1, '2026-09-19 09:02:41', 'shared'),
(258, 'Faculty', 'Teaching & Learning', 'Treats students fairly and respectfully.', 'general', '2026-09-19 17:02:41', 1, '2026-09-19 09:02:41', 'shared'),
(259, 'Faculty', 'General', 'Handles classroom concerns appropriately.', 'general', '2026-09-19 17:02:41', 0, '2026-09-19 09:02:41', 'shared'),
(260, 'Faculty', 'Teaching & Learning', 'testing', 'general', '2026-09-19 17:02:41', 1, '2026-09-19 09:02:41', 'shared'),
(261, 'Faculty', 'General', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 'general', '2026-09-19 17:02:41', 0, '2026-09-19 09:02:41', 'shared'),
(262, 'Faculty', 'General', 'Demonstrates punctuality, excellent attendance *', 'general', '2026-09-19 17:02:41', 1, '2026-09-19 09:02:41', 'shared'),
(263, 'Faculty', 'General', 'dvfdxv', 'general', '2026-09-19 17:02:41', 0, '2026-09-19 09:02:41', 'shared'),
(264, 'Faculty', 'Teaching & Learning', 'Clearly explains lessons and course-related concepts.', 'general', '2026-09-19 17:02:41', 0, '2026-09-19 09:02:41', 'shared'),
(265, 'Faculty', 'General', 'dsfsdsdfsd', 'general', '2026-09-19 20:37:30', 0, '2026-09-19 12:37:30', 'shared'),
(266, 'Faculty', 'Professionalism & Student Support', 'Attends In every Class', 'general', '2026-09-22 10:46:49', 1, '2026-09-22 02:46:49', 'shared'),
(267, 'Faculty', 'Teaching & Learning', 'Effective teaching?', 'general', '2026-09-22 12:10:59', 1, '2026-09-22 04:10:59', 'shared'),
(268, 'Faculty', 'Professionalism & Student Support', 'Demonstrates punctuality and preparedness for classes', 'general', '2026-09-23 12:29:52', 1, '2026-09-23 04:29:52', 'shared'),
(269, 'Faculty', 'Professionalism & Student Support', 'Treats students fairly, respectfully, and professionally.', 'general', '2026-09-23 12:29:58', 1, '2026-09-23 04:29:58', 'shared'),
(270, 'Faculty', 'Professionalism & Student Support', 'Maintains a positive and conducive learning environment.', 'general', '2026-09-23 12:30:03', 1, '2026-09-23 04:30:03', 'shared'),
(271, 'Faculty', 'Professionalism & Student Support', 'Communicates effectively with students and fellow faculty members.', 'general', '2026-09-23 12:30:08', 1, '2026-09-23 04:30:08', 'shared'),
(272, 'Faculty', 'Professionalism & Student Support', 'Demonstrates responsibility in performing teaching and academic duties.', 'general', '2026-09-23 12:30:12', 1, '2026-09-23 04:30:12', 'shared');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_question_categories`
--

CREATE TABLE `evaluation_question_categories` (
  `question_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluation_question_categories`
--

INSERT INTO `evaluation_question_categories` (`question_id`, `category_id`) VALUES
(193, 75),
(206, 92),
(207, 110),
(208, 110),
(209, 106),
(209, 111),
(210, 111),
(211, 111),
(212, 111),
(213, 111),
(214, 111),
(214, 113),
(215, 111),
(215, 113),
(216, 111),
(216, 113),
(217, 111),
(217, 113),
(218, 106),
(218, 113),
(219, 111),
(219, 113),
(224, 4364),
(224, 4374),
(226, 4364),
(226, 4374),
(230, 4363),
(230, 4373),
(231, 4349),
(232, 4350),
(233, 4353),
(234, 4358),
(235, 4354),
(236, 4355),
(237, 4368),
(237, 4378),
(238, 4368),
(238, 4378),
(239, 4368),
(239, 4378),
(240, 4360),
(241, 4360),
(242, 4358),
(243, 4362),
(244, 4384),
(245, 4384),
(246, 4384),
(250, 4387),
(251, 4387),
(252, 4387),
(253, 4387),
(254, 4387),
(255, 4387),
(256, 4387),
(257, 4387),
(258, 4387),
(260, 4387),
(261, 4384),
(262, 4384),
(263, 4384),
(264, 4387),
(266, 4389),
(267, 4387),
(268, 4389),
(269, 4389),
(270, 4389),
(271, 4389),
(272, 4389);

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_reminders`
--

CREATE TABLE `evaluation_reminders` (
  `id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `eval_type` varchar(20) NOT NULL DEFAULT 'student',
  `level` varchar(20) NOT NULL DEFAULT 'college',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation_reminders`
--

INSERT INTO `evaluation_reminders` (`id`, `period_id`, `sender_id`, `recipient_id`, `eval_type`, `level`, `created_at`) VALUES
(2, 5, 157, 175, 'student', 'college', '2026-08-24 17:19:27');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_results`
--

CREATE TABLE `evaluation_results` (
  `id` int(10) UNSIGNED NOT NULL,
  `submission_id` int(10) UNSIGNED DEFAULT NULL,
  `tracker_id` int(10) UNSIGNED DEFAULT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `target_user_id` int(10) UNSIGNED DEFAULT NULL,
  `question_id` int(10) UNSIGNED DEFAULT NULL,
  `form_id` int(10) UNSIGNED DEFAULT NULL,
  `rating` decimal(5,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `period_id` int(10) UNSIGNED DEFAULT NULL,
  `evaluator_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_submissions`
--

CREATE TABLE `evaluation_submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `period_id` int(10) UNSIGNED DEFAULT NULL,
  `form_id` int(10) UNSIGNED DEFAULT NULL,
  `evaluator_id` int(10) UNSIGNED DEFAULT NULL,
  `target_user_id` int(10) UNSIGNED DEFAULT NULL,
  `eval_type` enum('student_to_faculty','student_to_staff','faculty_peer','staff_peer') NOT NULL,
  `evaluator_year_level` varchar(50) DEFAULT NULL,
  `evaluator_department` varchar(50) DEFAULT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `status` enum('draft','submitted') NOT NULL DEFAULT 'submitted',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_tracker`
--

CREATE TABLE `evaluation_tracker` (
  `id` int(10) UNSIGNED NOT NULL,
  `legacy_submission_id` int(10) UNSIGNED DEFAULT NULL,
  `evaluator_id` int(10) UNSIGNED NOT NULL,
  `target_user_id` int(10) UNSIGNED NOT NULL,
  `eval_bucket` varchar(20) NOT NULL DEFAULT 'Faculty',
  `level` enum('junior_high','senior_high','college') DEFAULT NULL,
  `form_type` varchar(100) NOT NULL DEFAULT '',
  `form_id` int(10) UNSIGNED DEFAULT NULL,
  `source_module` varchar(255) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `period_id` int(10) UNSIGNED DEFAULT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `eval_type` varchar(30) NOT NULL DEFAULT 'student',
  `peer_group` varchar(20) DEFAULT NULL,
  `status` enum('draft','submitted','approved','archived') NOT NULL DEFAULT 'submitted',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `evaluator_year_level` varchar(50) DEFAULT NULL,
  `evaluator_department` varchar(50) DEFAULT NULL,
  `evaluation_context` varchar(30) NOT NULL DEFAULT 'teacher'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluation_tracker`
--

INSERT INTO `evaluation_tracker` (`id`, `legacy_submission_id`, `evaluator_id`, `target_user_id`, `eval_bucket`, `level`, `form_type`, `form_id`, `source_module`, `period`, `period_id`, `score`, `remarks`, `eval_type`, `peer_group`, `status`, `submitted_at`, `updated_at`, `evaluator_year_level`, `evaluator_department`, `evaluation_context`) VALUES
(58, NULL, 236, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 5, NULL, '', 'ea', NULL, 'submitted', '2026-08-24 14:57:55', '2026-09-24 14:36:32', NULL, NULL, 'teacher'),
(72, NULL, 236, 157, 'Faculty', NULL, '', NULL, NULL, NULL, 5, NULL, 'N/A', 'ea', NULL, 'submitted', '2026-08-27 16:55:58', '2026-09-24 14:36:32', NULL, NULL, 'teacher'),
(110, NULL, 157, 236, 'EA', 'college', 'school_head_dean_ea', NULL, NULL, NULL, 3, 4.67, 'N/A', 'school_head', NULL, 'submitted', '2026-09-15 07:49:43', '2026-09-24 14:36:32', NULL, NULL, 'teacher'),
(116, NULL, 236, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'N/A', 'ea', NULL, 'submitted', '2026-09-15 16:41:50', '2026-09-24 14:36:32', NULL, NULL, 'teacher'),
(121, NULL, 175, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-18 07:47:29', '2026-09-18 07:47:29', NULL, NULL, 'school_head'),
(138, NULL, 236, 218, 'Faculty', NULL, '', NULL, NULL, NULL, 6, NULL, 'N/A', 'ea', NULL, 'submitted', '2026-09-22 10:49:47', '2026-09-24 14:36:32', NULL, NULL, 'teacher'),
(139, NULL, 157, 236, 'EA', 'college', 'school_head_dean_ea', NULL, NULL, NULL, 6, 4.75, 'N/A', 'school_head', NULL, 'submitted', '2026-09-22 10:51:05', '2026-09-24 14:36:32', NULL, NULL, 'teacher'),
(140, NULL, 158, 217, 'Faculty', '', 'Principal Evaluation — Faculty', NULL, NULL, NULL, 2, 4.91, 'N/A', 'school_head', NULL, 'submitted', '2026-09-22 10:58:50', '2026-09-22 10:58:50', NULL, NULL, 'teacher'),
(141, NULL, 171, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-22 11:00:39', '2026-09-22 11:00:39', NULL, NULL, 'school_head'),
(142, NULL, 171, 218, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-22 11:01:58', '2026-09-22 11:01:58', NULL, NULL, 'staff'),
(143, NULL, 175, 218, 'Faculty', NULL, '', NULL, NULL, NULL, 6, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-22 11:05:51', '2026-09-22 11:05:51', NULL, NULL, 'staff'),
(144, NULL, 218, 236, 'Faculty', NULL, '', 7, NULL, NULL, 6, 5.00, 'N/A', 'staff', 'Staff Evaluation', 'submitted', '2026-09-22 11:09:59', '2026-09-24 14:36:32', NULL, NULL, 'teacher'),
(145, NULL, 217, 216, 'Faculty', NULL, '', 3, NULL, NULL, 6, 5.00, 'N/A', 'faculty_peer', 'Faculty', 'submitted', '2026-09-22 11:14:00', '2026-09-22 11:14:00', NULL, NULL, 'teacher'),
(146, NULL, 175, 215, 'Faculty', NULL, '', NULL, NULL, NULL, 6, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-22 11:19:31', '2026-09-22 11:19:31', NULL, NULL, 'staff'),
(147, NULL, 175, 157, 'Faculty', NULL, '', NULL, NULL, NULL, 6, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-22 11:20:04', '2026-09-22 11:20:04', NULL, NULL, 'school_head'),
(148, NULL, 219, 220, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'Palaging Late', 'student', NULL, 'submitted', '2026-09-22 11:45:15', '2026-09-22 11:45:15', NULL, NULL, 'teacher'),
(149, NULL, 171, 220, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'Sleeping', 'student', NULL, 'submitted', '2026-09-22 11:52:17', '2026-09-22 11:52:17', NULL, NULL, 'teacher'),
(150, NULL, 220, 217, 'Faculty', NULL, '', 3, NULL, NULL, 2, 3.45, '', 'faculty_peer', 'Faculty', 'submitted', '2026-09-22 11:58:19', '2026-09-22 11:58:19', NULL, NULL, 'teacher'),
(151, NULL, 217, 158, 'Faculty', NULL, '', 3, NULL, NULL, 2, 4.75, 'N/A', 'faculty_peer', 'Principal', 'submitted', '2026-09-23 10:29:32', '2026-09-23 10:29:32', NULL, NULL, 'teacher'),
(152, NULL, 236, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 6, NULL, '', 'ea', NULL, 'submitted', '2026-09-23 14:22:44', '2026-09-24 14:36:32', NULL, NULL, 'teacher'),
(153, NULL, 222, 216, 'Faculty', NULL, '', 3, NULL, NULL, 6, 4.53, '', 'faculty_peer', 'Faculty', 'submitted', '2026-09-23 14:35:12', '2026-09-23 14:35:12', NULL, NULL, 'teacher'),
(154, NULL, 227, 216, 'Faculty', NULL, '', 3, NULL, NULL, 6, 4.06, 'N/A', 'faculty_peer', 'Faculty', 'submitted', '2026-09-23 14:38:24', '2026-09-23 14:38:24', NULL, NULL, 'teacher'),
(155, NULL, 171, 217, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-23 15:51:42', '2026-09-23 15:51:42', NULL, NULL, 'teacher'),
(156, NULL, 219, 217, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-24 09:35:00', '2026-09-24 09:35:00', NULL, NULL, 'teacher'),
(157, NULL, 235, 217, 'Faculty', NULL, '', NULL, NULL, NULL, 6, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-24 09:38:06', '2026-09-24 09:38:06', NULL, NULL, 'teacher'),
(158, NULL, 175, 217, 'Faculty', NULL, '', NULL, NULL, NULL, 6, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-24 09:38:47', '2026-09-24 09:38:47', NULL, NULL, 'teacher'),
(159, NULL, 175, 225, 'Faculty', NULL, '', NULL, NULL, NULL, 6, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-24 16:39:57', '2026-09-24 16:39:57', NULL, NULL, 'teacher'),
(160, NULL, 233, 236, 'Faculty', NULL, '', 7, NULL, NULL, 6, 4.30, 'N/A', 'staff', 'Staff Evaluation', 'submitted', '2026-09-28 15:58:31', '2026-09-28 15:58:31', NULL, NULL, 'teacher'),
(161, NULL, 233, 236, 'Faculty', NULL, '', 7, NULL, NULL, 2, 4.40, 'N/A', 'staff', 'Staff Evaluation', 'submitted', '2026-09-28 16:00:48', '2026-09-28 16:00:48', NULL, NULL, 'teacher'),
(162, NULL, 237, 217, 'Faculty', NULL, '', NULL, NULL, NULL, 6, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-28 22:00:21', '2026-09-28 22:00:21', NULL, NULL, 'teacher');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_levels`
--

CREATE TABLE `faculty_levels` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `level` enum('junior_high','senior_high','college') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `username` varchar(100) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempt` datetime NOT NULL,
  `locked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`username`, `attempts`, `last_attempt`, `locked_until`) VALUES
('dfg', 1, '2026-07-25 22:00:44', NULL),
('dsfsd', 1, '2026-07-25 22:00:48', NULL),
('fdg', 1, '2026-07-25 22:00:41', NULL),
('flowen', 2, '2026-07-26 20:39:39', '2026-07-26 20:54:39'),
('flowen nina', 6, '2026-07-26 19:40:29', '2026-07-26 19:55:29'),
('john', 1, '2026-07-25 22:29:15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `login_confirmations`
--

CREATE TABLE `login_confirmations` (
  `id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'designation_update',
  `user_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `extra_data` text DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `user_id`, `message`, `extra_data`, `is_read`, `created_at`) VALUES
(1, 'designation_update', 22, 'Arevalo Raffy E. updated their designation from \"Registrar\" to \"Teacher\".', '{\"user_id\":22,\"full_name\":\"Arevalo Raffy E.\",\"role\":\"faculty\",\"old_desig\":\"Registrar\",\"new_desig\":\"Teacher\"}', 0, '2026-06-19 12:02:34'),
(2, 'designation_update', 22, 'Arevalo Raffy E. updated their designation from \"Teacher\" to \"Campus Ministry\".', '{\"user_id\":22,\"full_name\":\"Arevalo Raffy E.\",\"role\":\"faculty\",\"old_desig\":\"Teacher\",\"new_desig\":\"Campus Ministry\"}', 0, '2026-06-19 12:02:47'),
(3, 'designation_update', 22, 'Arevalo Raffy E. updated their designation from \"Campus Ministry\" to \"Campus Ministry/ Registrar\".', '{\"user_id\":22,\"full_name\":\"Arevalo Raffy E.\",\"role\":\"faculty\",\"old_desig\":\"Campus Ministry\",\"new_desig\":\"Campus Ministry\\/ Registrar\"}', 0, '2026-06-19 12:10:30'),
(4, 'designation_update', 46, 'Flowen Nina M. Anecito updated their designation from \"Teacher\" to \"ICT Coordinator/ HS TEACHER\".', '{\"user_id\":46,\"full_name\":\"Flowen Nina M. Anecito\",\"role\":\"faculty\",\"old_desig\":\"Teacher\",\"new_desig\":\"ICT Coordinator\\/ HS TEACHER\"}', 0, '2026-06-19 12:14:17'),
(5, 'designation_update', 22, 'Arevalo Raffy E. updated their designation from \"Campus Ministry/ Registrar\" to \"Registrar/ Admin Coordinator\".', '{\"user_id\":22,\"full_name\":\"Arevalo Raffy E.\",\"role\":\"faculty\",\"old_desig\":\"Campus Ministry\\/ Registrar\",\"new_desig\":\"Registrar\\/ Admin Coordinator\"}', 0, '2026-06-19 19:39:27'),
(6, 'designation_update', 22, 'Arevalo Raffy E. updated their designation from \"Teacher\" to \"Teacher/ Registrar/ Admin Coordinator\".', '{\"user_id\":22,\"full_name\":\"Arevalo Raffy E.\",\"role\":\"faculty\",\"old_desig\":\"Teacher\",\"new_desig\":\"Teacher\\/ Registrar\\/ Admin Coordinator\"}', 0, '2026-06-21 16:23:43'),
(7, 'designation_update', 22, 'Arevalo Raffy E. updated their designation from \"Teacher\" to \"Teacher/ Registrar/ Admin Coordinator\".', '{\"user_id\":22,\"full_name\":\"Arevalo Raffy E.\",\"role\":\"faculty\",\"old_desig\":\"Teacher\",\"new_desig\":\"Teacher\\/ Registrar\\/ Admin Coordinator\"}', 0, '2026-06-21 16:52:42'),
(8, 'designation_update', 46, 'Flowen Nina M. Anecito updated their designation from \"Teacher\" to \"HS-TEACHER/ ICT COORDINATOR\".', '{\"user_id\":46,\"full_name\":\"Flowen Nina M. Anecito\",\"role\":\"faculty\",\"old_desig\":\"Teacher\",\"new_desig\":\"HS-TEACHER\\/ ICT COORDINATOR\"}', 0, '2026-06-21 16:57:08'),
(9, 'designation_update', 22, 'Arevalo Raffy E. updated their designation from \"Teacher\" to \"School Regsistrar/ ADMIN COORDINATOR\".', '{\"user_id\":22,\"full_name\":\"Arevalo Raffy E.\",\"role\":\"faculty\",\"old_desig\":\"Teacher\",\"new_desig\":\"School Regsistrar\\/ ADMIN COORDINATOR\"}', 0, '2026-06-24 07:51:16'),
(10, 'designation_update', 79, 'GERLIE C. VELASCO updated their designation from \"Teacher\" to \"BSIT – II ADVISER\".', '{\"user_id\":79,\"full_name\":\"GERLIE C. VELASCO\",\"role\":\"faculty\",\"old_desig\":\"Teacher\",\"new_desig\":\"BSIT \\u2013 II ADVISER\"}', 0, '2026-06-24 08:26:32'),
(11, 'designation_update', 78, 'CATHERINE MAY R. BAUTISTA updated their designation from \"Teacher\" to \"BSTIT - IV Adviser\".', '{\"user_id\":78,\"full_name\":\"CATHERINE MAY R. BAUTISTA\",\"role\":\"faculty\",\"old_desig\":\"Teacher\",\"new_desig\":\"BSTIT - IV Adviser\"}', 0, '2026-06-24 08:27:22'),
(12, 'designation_update', 83, 'GERALD V. DELOS SANTOS updated their designation from \"Personnel\" to \"GUIDANCE STAFF/ SPORTS PROGRAM MANAGER/ SDRRM OFFICER\".', '{\"user_id\":83,\"full_name\":\"GERALD V. DELOS SANTOS\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"GUIDANCE STAFF\\/ SPORTS PROGRAM MANAGER\\/ SDRRM OFFICER\"}', 0, '2026-06-24 09:11:17'),
(13, 'designation_update', 84, 'JENNIFER A. BIADORA updated their designation from \"Personnel\" to \"ESC/ SHSVP/ TSS Encoder YEARBOOK IN CHARGE\".', '{\"user_id\":84,\"full_name\":\"JENNIFER A. BIADORA\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"ESC\\/ SHSVP\\/ TSS Encoder YEARBOOK IN CHARGE\"}', 0, '2026-06-24 09:11:45'),
(14, 'designation_update', 82, 'JESSIE A. AQUILLO, updated their designation from \"Personnel\" to \"Campus Ministry Officer  Formation Services Coordinator\".', '{\"user_id\":82,\"full_name\":\"JESSIE A. AQUILLO,\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"Campus Ministry Officer  Formation Services Coordinator\"}', 0, '2026-06-24 09:12:27'),
(15, 'designation_update', 75, 'RAFFY E. AREVALO updated their designation from \"Personnel\" to \"School Registrar/ ADMIN COORDINATOR\".', '{\"user_id\":75,\"full_name\":\"RAFFY E. AREVALO\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"School Registrar\\/ ADMIN COORDINATOR\"}', 0, '2026-06-24 09:13:09'),
(16, 'designation_update', 74, 'JOHN KENNETH M. ANECITO updated their designation from \"Personnel\" to \"Physical Plant Coordinator/ Computer Lab Custodian\".', '{\"user_id\":74,\"full_name\":\"JOHN KENNETH M. ANECITO\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"Physical Plant Coordinator\\/ Computer Lab Custodian\"}', 0, '2026-06-24 09:15:06'),
(17, 'designation_update', 86, 'STEPHANIE M. PUNTAL updated their designation from \"Teacher\" to \"steph12345\".', '{\"user_id\":86,\"full_name\":\"STEPHANIE M. PUNTAL\",\"role\":\"faculty\",\"old_desig\":\"Teacher\",\"new_desig\":\"steph12345\"}', 0, '2026-06-24 09:19:34'),
(18, 'designation_update', 86, 'STEPHANIE M. PUNTAL updated their designation from \"steph12345\" to \"ADMISSION AND SCHOLARSHIP STAFF Institutional Student Program Services Coordinator\".', '{\"user_id\":86,\"full_name\":\"STEPHANIE M. PUNTAL\",\"role\":\"faculty\",\"old_desig\":\"steph12345\",\"new_desig\":\"ADMISSION AND SCHOLARSHIP STAFF Institutional Student Program Services Coordinator\"}', 0, '2026-06-24 09:19:40'),
(19, 'designation_update', 79, 'GERLIE C. VELASCO updated their designation from \"BSIT – II ADVISER\" to \"BSIT – II ADVISER/ cashier/ bookkeeper\".', '{\"user_id\":79,\"full_name\":\"GERLIE C. VELASCO\",\"role\":\"faculty\",\"old_desig\":\"BSIT \\u2013 II ADVISER\",\"new_desig\":\"BSIT \\u2013 II ADVISER\\/ cashier\\/ bookkeeper\"}', 0, '2026-07-13 17:36:19'),
(20, 'designation_update', 79, 'GERLIE C. VELASCO updated their designation from \"BSIT – II ADVISER/ cashier/ bookkeeper\" to \"BSIT – II ADVISER/\".', '{\"user_id\":79,\"full_name\":\"GERLIE C. VELASCO\",\"role\":\"faculty\",\"old_desig\":\"BSIT \\u2013 II ADVISER\\/ cashier\\/ bookkeeper\",\"new_desig\":\"BSIT \\u2013 II ADVISER\\/\"}', 0, '2026-07-13 18:28:02'),
(21, 'designation_update', 118, 'Catherine May updated their designation from \"Teacher\" to \"Department Head\".', '{\"user_id\":118,\"full_name\":\"Catherine May\",\"role\":\"teacher\",\"old_desig\":\"Teacher\",\"new_desig\":\"Department Head\"}', 0, '2026-07-26 14:57:01'),
(22, 'designation_update', 118, 'Catherine May updated their designation from \"Department Head\" to \"Teacher/Department Head\".', '{\"user_id\":118,\"full_name\":\"Catherine May\",\"role\":\"teacher\",\"old_desig\":\"Department Head\",\"new_desig\":\"Teacher\\/Department Head\"}', 0, '2026-07-26 14:57:16'),
(23, 'designation_update', 131, 'joy updated their designation from \"Teacher\" to \"Teacher/cashier/bookkeeper\".', '{\"user_id\":131,\"full_name\":\"joy\",\"role\":\"teacher\",\"old_desig\":\"Teacher\",\"new_desig\":\"Teacher\\/cashier\\/bookkeeper\"}', 0, '2026-07-31 09:00:55'),
(24, 'designation_update', 136, 'John Kenneth M. Annecito updated their designation from \"Personnel\" to \"Personnel/ Physical Plant Coordinator/ Computer Lab Custodian\".', '{\"user_id\":136,\"full_name\":\"John Kenneth M. Annecito\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"Personnel\\/ Physical Plant Coordinator\\/ Computer Lab Custodian\"}', 0, '2026-07-31 16:00:18'),
(25, 'designation_update', 136, 'John Kenneth M. Annecito updated their designation from \"Staff\" to \"Staff/Physical Plant Coordinator/ Computer Lab Custodian\".', '{\"user_id\":136,\"full_name\":\"John Kenneth M. Annecito\",\"role\":\"staff\",\"old_desig\":\"Staff\",\"new_desig\":\"Staff\\/Physical Plant Coordinator\\/ Computer Lab Custodian\"}', 0, '2026-08-14 11:17:13'),
(26, 'evaluation_received', 165, 'You have received a new peer evaluation.', NULL, 0, '2026-08-14 18:52:31'),
(27, 'evaluation_received', 163, 'You have received a new peer evaluation.', NULL, 0, '2026-08-14 20:20:58'),
(28, 'designation_update', 152, 'Sheramay Dawn S. Pamay updated their designation from \"Staff\" to \"Staff/Cashier\".', '{\"user_id\":152,\"full_name\":\"Sheramay Dawn S. Pamay\",\"role\":\"staff\",\"old_desig\":\"Staff\",\"new_desig\":\"Staff\\/Cashier\"}', 0, '2026-08-17 18:00:10'),
(29, 'designation_update', 146, 'Gerald Delos Santos updated their designation from \"Staff\" to \"Staff/ GUIDANCE STAFF/ SPORTS PROGRAM MANAGER/ SDRRM OFFICER\".', '{\"user_id\":146,\"full_name\":\"Gerald Delos Santos\",\"role\":\"staff\",\"old_desig\":\"Staff\",\"new_desig\":\"Staff\\/ GUIDANCE STAFF\\/ SPORTS PROGRAM MANAGER\\/ SDRRM OFFICER\"}', 0, '2026-08-18 11:45:01'),
(30, 'designation_update', 160, 'Jennifer A. Biadora updated their designation from \"Teacher\" to \"Teacher/ CC   102 - Computer Programming 1** IPT   101 - Integrative Programming and Technologies 1\".', '{\"user_id\":160,\"full_name\":\"Jennifer A. Biadora\",\"role\":\"teacher\",\"old_desig\":\"Teacher\",\"new_desig\":\"Teacher\\/ CC   102 - Computer Programming 1** IPT   101 - Integrative Programming and Technologies 1\"}', 0, '2026-08-18 12:25:16'),
(31, 'designation_update', 176, 'Raffy E. Arevalo updated their designation from \"Personnel\" to \"Personnel/ Registrar\".', '{\"user_id\":176,\"full_name\":\"Raffy E. Arevalo\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"Personnel\\/ Registrar\"}', 0, '2026-08-20 17:07:59'),
(32, 'designation_update', 179, 'testing updated their designation from \"Teacher\" to \"Cashier\".', '{\"user_id\":179,\"full_name\":\"testing\",\"role\":\"teacher\",\"old_desig\":\"Teacher\",\"new_desig\":\"Cashier\"}', 0, '2026-08-22 10:14:59'),
(33, 'evaluation_received', 160, 'You have received a new peer evaluation.', NULL, 0, '2026-08-24 14:22:02'),
(34, 'evaluation_received', 183, 'You have received a new evaluation.', NULL, 0, '2026-08-26 11:42:05'),
(35, 'evaluation_received', 136, 'You have received a new evaluation.', NULL, 0, '2026-08-26 12:08:51'),
(36, 'designation_update', 185, 'Kim updated their designation from \"Personnel\" to \"Personnel/ Department Head\".', '{\"user_id\":185,\"full_name\":\"Kim\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"Personnel\\/ Department Head\"}', 0, '2026-08-26 15:24:13'),
(37, 'evaluation_received', 139, 'You have received a new evaluation.', NULL, 0, '2026-08-27 16:02:21'),
(38, 'designation_update', 170, 'Jingle R. Ausan updated their designation from \"Teacher\" to \"Teacher/ librarian\".', '{\"user_id\":170,\"full_name\":\"Jingle R. Ausan\",\"role\":\"teacher\",\"old_desig\":\"Teacher\",\"new_desig\":\"Teacher\\/ librarian\"}', 0, '2026-08-29 12:46:12'),
(39, 'designation_update', 197, 'LOLITO ANGUSTO updated their designation from \"Teacher\" to \"Personnel\".', '{\"user_id\":197,\"full_name\":\"LOLITO ANGUSTO\",\"role\":\"teacher\",\"old_desig\":\"Teacher\",\"new_desig\":\"Personnel\"}', 0, '2026-08-29 13:21:36'),
(40, 'designation_update', 199, 'Madilyn updated their designation from \"Teacher\" to \"Teacher/ BSIT-3 Adviser\".', '{\"user_id\":199,\"full_name\":\"Madilyn\",\"role\":\"teacher\",\"old_desig\":\"Teacher\",\"new_desig\":\"Teacher\\/ BSIT-3 Adviser\"}', 0, '2026-08-29 13:42:23'),
(41, 'evaluation_received', 172, 'You have received a new evaluation.', NULL, 0, '2026-09-13 18:52:36'),
(42, 'evaluation_received', 139, 'You have received a new evaluation.', NULL, 0, '2026-09-13 18:54:47'),
(43, 'evaluation_received', 158, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"Principal\",\"target_label\":\"Principal\"}', 0, '2026-09-13 22:05:54'),
(45, 'evaluation_received', 172, 'You have received a new evaluation.', NULL, 0, '2026-09-14 08:37:55'),
(46, 'evaluation_received', 157, 'You have received a new Dean / Principal evaluation.', NULL, 0, '2026-09-14 08:40:12'),
(47, 'evaluation_received', 158, 'You have received a new Dean / Principal evaluation.', NULL, 0, '2026-09-14 08:40:32'),
(48, 'evaluation_received', 157, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"Dean\",\"target_label\":\"Dean\"}', 0, '2026-09-15 14:30:22'),
(49, 'evaluation_received', 157, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"Dean\",\"target_label\":\"Dean\"}', 0, '2026-09-15 16:11:34'),
(50, 'evaluation_received', 236, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"EA\",\"target_label\":\"Executive Assistant\"}', 0, '2026-09-15 16:22:04'),
(51, 'designation_update', 209, 'Jessie A. Aquillo updated their designation from \"Personnel\" to \"Formation Services\".', '{\"user_id\":209,\"full_name\":\"Jessie A. Aquillo\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"Formation Services\"}', 0, '2026-09-18 15:48:41'),
(52, 'designation_update', 209, 'Jessie A. Aquillo updated their designation from \"Formation Services\" to \"Formation Services Coordinator/ CMO\".', '{\"user_id\":209,\"full_name\":\"Jessie A. Aquillo\",\"role\":\"staff\",\"old_desig\":\"Formation Services\",\"new_desig\":\"Formation Services Coordinator\\/ CMO\"}', 0, '2026-09-18 15:48:54'),
(53, 'designation_update', 209, 'Jessie A. Aquillo updated their designation from \"Formation Services Coordinator/ CMO\" to \"Bookkeeper\".', '{\"user_id\":209,\"full_name\":\"Jessie A. Aquillo\",\"role\":\"staff\",\"old_desig\":\"Formation Services Coordinator\\/ CMO\",\"new_desig\":\"Bookkeeper\"}', 0, '2026-09-21 13:59:12'),
(54, 'evaluation_received', 194, 'You have received a new peer evaluation.', NULL, 0, '2026-09-21 22:34:07'),
(56, 'designation_update', 215, 'Valerie Jane S. Bendijo updated their designation from \"Personnel\" to \"BS Nursing\".', '{\"user_id\":215,\"full_name\":\"Valerie Jane S. Bendijo\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"BS Nursing\"}', 0, '2026-09-22 10:09:01'),
(57, 'designation_update', 170, 'Jingle R. Ausan updated their designation from \"Teacher/ librarian\" to \"BEED - General Education\".', '{\"user_id\":170,\"full_name\":\"Jingle R. Ausan\",\"role\":\"teacher\",\"old_desig\":\"Teacher\\/ librarian\",\"new_desig\":\"BEED - General Education\"}', 0, '2026-09-22 10:09:45'),
(58, 'designation_update', 216, 'Stephanie M. Puntal updated their designation from \"Teacher\" to \"BS Information Technology  with Certificate in Teaching – Social Studies\".', '{\"user_id\":216,\"full_name\":\"Stephanie M. Puntal\",\"role\":\"teacher\",\"old_desig\":\"Teacher\",\"new_desig\":\"BS Information Technology  with Certificate in Teaching \\u2013 Social Studies\"}', 0, '2026-09-22 10:22:24'),
(59, 'designation_update', 217, 'Cedrick Dante Espillo updated their designation from \"Teacher\" to \"BSED - English\".', '{\"user_id\":217,\"full_name\":\"Cedrick Dante Espillo\",\"role\":\"teacher\",\"old_desig\":\"Teacher\",\"new_desig\":\"BSED - English\"}', 0, '2026-09-22 10:22:59'),
(61, 'designation_update', 218, 'Malou De la Torre updated their designation from \"Personnel\" to \"Master in Library and Information Science BSBA – Management\".', '{\"user_id\":218,\"full_name\":\"Malou De la Torre\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"Master in Library and Information Science BSBA \\u2013 Management\"}', 0, '2026-09-22 10:38:12'),
(62, 'designation_update', 218, 'Malou De la Torre updated their designation from \"Master in Library and Information Science BSBA – Management\" to \"Librarian\".', '{\"user_id\":218,\"full_name\":\"Malou De la Torre\",\"role\":\"staff\",\"old_desig\":\"Master in Library and Information Science BSBA \\u2013 Management\",\"new_desig\":\"Librarian\"}', 0, '2026-09-22 11:08:19'),
(63, 'evaluation_received', 236, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"EA\",\"target_label\":\"Executive Assistant\"}', 0, '2026-09-22 11:09:59'),
(64, 'designation_update', 217, 'Cedrick Dante Espillo updated their designation from \"BSED - English\" to \"Teacher/ Coordinator\".', '{\"user_id\":217,\"full_name\":\"Cedrick Dante Espillo\",\"role\":\"teacher\",\"old_desig\":\"BSED - English\",\"new_desig\":\"Teacher\\/ Coordinator\"}', 0, '2026-09-22 11:11:49'),
(65, 'evaluation_received', 216, 'You have received a new evaluation.', NULL, 0, '2026-09-22 11:14:00'),
(66, 'designation_update', 220, 'Barcibal Emily R. updated their designation from \"Teacher\" to \"Teacher/ Cashier\".', '{\"user_id\":220,\"full_name\":\"Barcibal Emily R.\",\"role\":\"teacher\",\"old_desig\":\"Teacher\",\"new_desig\":\"Teacher\\/ Cashier\"}', 0, '2026-09-22 11:54:31'),
(67, 'evaluation_received', 217, 'You have received a new evaluation.', NULL, 0, '2026-09-22 11:58:19'),
(68, 'evaluation_received', 158, 'You have received a new Principal evaluation.', NULL, 0, '2026-09-23 10:29:32'),
(69, 'designation_update', 221, 'Anecito John Kenneth M. updated their designation from \"Personnel\" to \"Physical Plant Coordinator/ Computer Lab Custodian\".', '{\"user_id\":221,\"full_name\":\"Anecito John Kenneth M.\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"Physical Plant Coordinator\\/ Computer Lab Custodian\"}', 0, '2026-09-23 10:59:19'),
(70, 'designation_update', 222, 'Biadora Jennifer A. updated their designation from \"Teacher\" to \"ESC/ SHSVP/ TSS Encoder YEARBOOK IN CHARGE\".', '{\"user_id\":222,\"full_name\":\"Biadora Jennifer A.\",\"role\":\"teacher\",\"old_desig\":\"Teacher\",\"new_desig\":\"ESC\\/ SHSVP\\/ TSS Encoder YEARBOOK IN CHARGE\"}', 0, '2026-09-23 11:01:14'),
(71, 'designation_update', 222, 'Biadora Jennifer A. updated their designation from \"ESC/ SHSVP/ TSS Encoder YEARBOOK IN CHARGE\" to \"Teacher/ ESC/ SHSVP/ TSS Encoder YEARBOOK IN CHARGE\".', '{\"user_id\":222,\"full_name\":\"Biadora Jennifer A.\",\"role\":\"teacher\",\"old_desig\":\"ESC\\/ SHSVP\\/ TSS Encoder YEARBOOK IN CHARGE\",\"new_desig\":\"Teacher\\/ ESC\\/ SHSVP\\/ TSS Encoder YEARBOOK IN CHARGE\"}', 0, '2026-09-23 11:01:22'),
(72, 'designation_update', 225, 'Anecito John Kenneth M. updated their designation from \"Personnel\" to \"Physical Plant Coordinator/ Computer Lab Custodian\".', '{\"user_id\":225,\"full_name\":\"Anecito John Kenneth M.\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"Physical Plant Coordinator\\/ Computer Lab Custodian\"}', 0, '2026-09-23 11:23:34'),
(73, 'designation_update', 226, 'Delos Santos Johnny E. updated their designation from \"Personnel\" to \"MAINTENANCE OFFICER\".', '{\"user_id\":226,\"full_name\":\"Delos Santos Johnny E.\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"MAINTENANCE OFFICER\"}', 0, '2026-09-23 11:25:48'),
(74, 'designation_update', 230, 'Delos Santos Gerald V. updated their designation from \"Personnel\" to \"GUIDANCE STAFF/ SPORTS PROGRAM MANAGER/ SDRRM OFFICER\".', '{\"user_id\":230,\"full_name\":\"Delos Santos Gerald V.\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"GUIDANCE STAFF\\/ SPORTS PROGRAM MANAGER\\/ SDRRM OFFICER\"}', 0, '2026-09-23 11:32:31'),
(75, 'designation_update', 232, 'Arevalo Raffy E. updated their designation from \"Personnel\" to \"School Registrar/ ADMIN COORDINATOR\".', '{\"user_id\":232,\"full_name\":\"Arevalo Raffy E.\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"School Registrar\\/ ADMIN COORDINATOR\"}', 0, '2026-09-23 11:36:38'),
(76, 'designation_update', 227, 'Sardina Joselle C. updated their designation from \"Teacher\" to \"Ang Kingke Adviser/ Grade 7 - St. Albert Adviser/ HS TEACHER\".', '{\"user_id\":227,\"full_name\":\"Sardina Joselle C.\",\"role\":\"teacher\",\"old_desig\":\"Teacher\",\"new_desig\":\"Ang Kingke Adviser\\/ Grade 7 - St. Albert Adviser\\/ HS TEACHER\"}', 0, '2026-09-23 11:42:39'),
(77, 'designation_update', 233, 'Candolita Amelia C. updated their designation from \"Personnel\" to \"Bookkeeper\".', '{\"user_id\":233,\"full_name\":\"Candolita Amelia C.\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"Bookkeeper\"}', 0, '2026-09-23 11:50:55'),
(78, 'designation_update', 234, 'Aquillo Jessie A. updated their designation from \"Personnel\" to \"VE/CLE COORDINATOR/ HS TEACHER\".', '{\"user_id\":234,\"full_name\":\"Aquillo Jessie A.\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"VE\\/CLE COORDINATOR\\/ HS TEACHER\"}', 0, '2026-09-23 11:52:55'),
(79, 'evaluation_received', 216, 'You have received a new evaluation.', NULL, 0, '2026-09-23 14:35:12'),
(80, 'evaluation_received', 216, 'You have received a new evaluation.', NULL, 0, '2026-09-23 14:38:24'),
(81, 'evaluation_received', 236, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"EA\",\"target_label\":\"Executive Assistant\"}', 0, '2026-09-28 15:58:31'),
(82, 'evaluation_received', 236, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"EA\",\"target_label\":\"Executive Assistant\"}', 0, '2026-09-28 16:00:48');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `peer_evaluation_results`
--

CREATE TABLE `peer_evaluation_results` (
  `id` int(10) UNSIGNED NOT NULL,
  `submission_id` int(10) UNSIGNED NOT NULL,
  `evaluator_id` int(10) UNSIGNED NOT NULL,
  `target_user_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `period_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `peer_evaluation_submissions`
--

CREATE TABLE `peer_evaluation_submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `period_id` int(10) UNSIGNED NOT NULL,
  `evaluator_id` int(10) UNSIGNED NOT NULL,
  `target_user_id` int(10) UNSIGNED NOT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_answers`
--

CREATE TABLE `questionnaire_answers` (
  `id` int(10) UNSIGNED NOT NULL,
  `tracker_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED DEFAULT NULL,
  `question_source` enum('evaluation','user') NOT NULL DEFAULT 'evaluation',
  `user_question_id` int(10) DEFAULT NULL,
  `answer_text` text DEFAULT NULL,
  `answer_score` decimal(5,2) DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `comments` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questionnaire_answers`
--

INSERT INTO `questionnaire_answers` (`id`, `tracker_id`, `question_id`, `question_source`, `user_question_id`, `answer_text`, `answer_score`, `submitted_at`, `comments`) VALUES
(315, 58, 153, 'evaluation', NULL, NULL, 4.00, '2026-08-24 14:57:55', NULL),
(316, 58, 79, 'evaluation', NULL, NULL, 3.00, '2026-08-24 14:57:55', NULL),
(381, 72, 80, 'evaluation', NULL, NULL, 5.00, '2026-08-27 16:55:58', NULL),
(382, 72, 82, 'evaluation', NULL, NULL, 4.00, '2026-08-27 16:55:58', NULL),
(383, 72, 83, 'evaluation', NULL, NULL, 5.00, '2026-08-27 16:55:58', NULL),
(557, 110, 237, 'evaluation', NULL, NULL, 5.00, '2026-09-15 07:49:43', NULL),
(558, 110, 238, 'evaluation', NULL, NULL, 4.00, '2026-09-15 07:49:43', NULL),
(559, 110, 239, 'evaluation', NULL, NULL, 5.00, '2026-09-15 07:49:43', NULL),
(572, 116, 315, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:41:50', NULL),
(573, 116, 157, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:41:50', NULL),
(574, 116, 314, 'evaluation', NULL, NULL, 4.00, '2026-09-15 16:41:50', NULL),
(575, 116, 154, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:41:50', NULL),
(576, 116, 155, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:41:50', NULL),
(577, 116, 158, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:41:50', NULL),
(578, 116, 159, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:41:50', NULL),
(579, 116, 156, 'evaluation', NULL, NULL, 4.00, '2026-09-15 16:41:50', NULL),
(605, 121, NULL, 'user', 232, NULL, 5.00, '2026-09-18 07:47:29', NULL),
(606, 121, NULL, 'user', 244, NULL, 4.00, '2026-09-18 07:47:29', NULL),
(607, 121, NULL, 'user', 245, NULL, 5.00, '2026-09-18 07:47:29', NULL),
(852, 140, 266, 'evaluation', NULL, NULL, 5.00, '2026-09-22 10:58:50', NULL),
(853, 140, 262, 'evaluation', NULL, NULL, 4.00, '2026-09-22 10:58:50', NULL),
(854, 140, 251, 'evaluation', NULL, NULL, 5.00, '2026-09-22 10:58:50', NULL),
(855, 140, 252, 'evaluation', NULL, NULL, 5.00, '2026-09-22 10:58:50', NULL),
(856, 140, 253, 'evaluation', NULL, NULL, 5.00, '2026-09-22 10:58:50', NULL),
(857, 140, 254, 'evaluation', NULL, NULL, 5.00, '2026-09-22 10:58:50', NULL),
(858, 140, 255, 'evaluation', NULL, NULL, 5.00, '2026-09-22 10:58:50', NULL),
(859, 140, 256, 'evaluation', NULL, NULL, 5.00, '2026-09-22 10:58:50', NULL),
(860, 140, 257, 'evaluation', NULL, NULL, 5.00, '2026-09-22 10:58:50', NULL),
(861, 140, 258, 'evaluation', NULL, NULL, 5.00, '2026-09-22 10:58:50', NULL),
(862, 140, 260, 'evaluation', NULL, NULL, 5.00, '2026-09-22 10:58:50', NULL),
(881, 145, 266, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:14:00', NULL),
(882, 145, 262, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:14:00', NULL),
(883, 145, 251, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:14:00', NULL),
(884, 145, 252, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:14:00', NULL),
(885, 145, 253, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:14:00', NULL),
(886, 145, 254, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:14:00', NULL),
(887, 145, 255, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:14:00', NULL),
(888, 145, 256, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:14:00', NULL),
(889, 145, 257, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:14:00', NULL),
(890, 145, 258, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:14:00', NULL),
(891, 145, 260, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:14:00', NULL),
(906, 148, 266, 'evaluation', NULL, NULL, 3.00, '2026-09-22 11:45:15', NULL),
(907, 148, 247, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:45:15', NULL),
(908, 148, 265, 'evaluation', NULL, NULL, 4.00, '2026-09-22 11:45:15', NULL),
(909, 148, 244, 'evaluation', NULL, NULL, 2.00, '2026-09-22 11:45:15', NULL),
(910, 148, 245, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:45:15', NULL),
(911, 148, 246, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:45:15', NULL),
(912, 148, 263, 'evaluation', NULL, NULL, 2.00, '2026-09-22 11:45:15', NULL),
(913, 148, 248, 'evaluation', NULL, NULL, 4.00, '2026-09-22 11:45:15', NULL),
(914, 148, 249, 'evaluation', NULL, NULL, 3.00, '2026-09-22 11:45:15', NULL),
(915, 148, 250, 'evaluation', NULL, NULL, 3.00, '2026-09-22 11:45:15', NULL),
(916, 148, 259, 'evaluation', NULL, NULL, 4.00, '2026-09-22 11:45:15', NULL),
(917, 148, 261, 'evaluation', NULL, NULL, 2.00, '2026-09-22 11:45:15', NULL),
(918, 148, 262, 'evaluation', NULL, NULL, 1.00, '2026-09-22 11:45:15', NULL),
(919, 148, 251, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:45:15', NULL),
(920, 148, 252, 'evaluation', NULL, NULL, 4.00, '2026-09-22 11:45:15', NULL),
(921, 148, 253, 'evaluation', NULL, NULL, 4.00, '2026-09-22 11:45:15', NULL),
(922, 148, 254, 'evaluation', NULL, NULL, 3.00, '2026-09-22 11:45:15', NULL),
(923, 148, 255, 'evaluation', NULL, NULL, 3.00, '2026-09-22 11:45:15', NULL),
(924, 148, 256, 'evaluation', NULL, NULL, 2.00, '2026-09-22 11:45:15', NULL),
(925, 148, 257, 'evaluation', NULL, NULL, 1.00, '2026-09-22 11:45:15', NULL),
(926, 148, 258, 'evaluation', NULL, NULL, 4.00, '2026-09-22 11:45:15', NULL),
(927, 148, 260, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:45:15', NULL),
(928, 148, 264, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:45:15', NULL),
(929, 149, 266, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:52:17', NULL),
(930, 149, 247, 'evaluation', NULL, NULL, 4.00, '2026-09-22 11:52:17', NULL),
(931, 149, 265, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:52:17', NULL),
(932, 149, 244, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:52:17', NULL),
(933, 149, 245, 'evaluation', NULL, NULL, 4.00, '2026-09-22 11:52:17', NULL),
(934, 149, 246, 'evaluation', NULL, NULL, 3.00, '2026-09-22 11:52:17', NULL),
(935, 149, 263, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:52:17', NULL),
(936, 149, 248, 'evaluation', NULL, NULL, 2.00, '2026-09-22 11:52:17', NULL),
(937, 149, 249, 'evaluation', NULL, NULL, 4.00, '2026-09-22 11:52:17', NULL),
(938, 149, 250, 'evaluation', NULL, NULL, 3.00, '2026-09-22 11:52:17', NULL),
(939, 149, 259, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:52:17', NULL),
(940, 149, 261, 'evaluation', NULL, NULL, 3.00, '2026-09-22 11:52:17', NULL),
(941, 149, 262, 'evaluation', NULL, NULL, 4.00, '2026-09-22 11:52:17', NULL),
(942, 149, 251, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:52:17', NULL),
(943, 149, 252, 'evaluation', NULL, NULL, 1.00, '2026-09-22 11:52:17', NULL),
(944, 149, 253, 'evaluation', NULL, NULL, 4.00, '2026-09-22 11:52:17', NULL),
(945, 149, 254, 'evaluation', NULL, NULL, 3.00, '2026-09-22 11:52:17', NULL),
(946, 149, 255, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:52:17', NULL),
(947, 149, 256, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:52:17', NULL),
(948, 149, 257, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:52:17', NULL),
(949, 149, 258, 'evaluation', NULL, NULL, 4.00, '2026-09-22 11:52:17', NULL),
(950, 149, 260, 'evaluation', NULL, NULL, 3.00, '2026-09-22 11:52:17', NULL),
(951, 149, 264, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:52:17', NULL),
(952, 150, 266, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:58:19', NULL),
(953, 150, 262, 'evaluation', NULL, NULL, 4.00, '2026-09-22 11:58:19', NULL),
(954, 150, 251, 'evaluation', NULL, NULL, 3.00, '2026-09-22 11:58:19', NULL),
(955, 150, 252, 'evaluation', NULL, NULL, 2.00, '2026-09-22 11:58:19', NULL),
(956, 150, 253, 'evaluation', NULL, NULL, 1.00, '2026-09-22 11:58:19', NULL),
(957, 150, 254, 'evaluation', NULL, NULL, 2.00, '2026-09-22 11:58:19', NULL),
(958, 150, 255, 'evaluation', NULL, NULL, 3.00, '2026-09-22 11:58:19', NULL),
(959, 150, 256, 'evaluation', NULL, NULL, 4.00, '2026-09-22 11:58:19', NULL),
(960, 150, 257, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:58:19', NULL),
(961, 150, 258, 'evaluation', NULL, NULL, 5.00, '2026-09-22 11:58:19', NULL),
(962, 150, 260, 'evaluation', NULL, NULL, 4.00, '2026-09-22 11:58:19', NULL),
(975, 152, NULL, 'user', 529, NULL, 3.00, '2026-09-23 14:22:44', NULL),
(976, 152, NULL, 'user', 530, NULL, 4.00, '2026-09-23 14:22:44', NULL),
(977, 152, NULL, 'user', 531, NULL, 4.00, '2026-09-23 14:22:44', NULL),
(978, 152, NULL, 'user', 532, NULL, 3.00, '2026-09-23 14:22:44', NULL),
(979, 152, NULL, 'user', 533, NULL, 5.00, '2026-09-23 14:22:44', NULL),
(980, 152, NULL, 'user', 534, NULL, 5.00, '2026-09-23 14:22:44', NULL),
(981, 152, NULL, 'user', 535, NULL, 4.00, '2026-09-23 14:22:44', NULL),
(982, 152, NULL, 'user', 536, NULL, 4.00, '2026-09-23 14:22:44', NULL),
(983, 152, NULL, 'user', 537, NULL, 3.00, '2026-09-23 14:22:44', NULL),
(984, 152, NULL, 'user', 538, NULL, 4.00, '2026-09-23 14:22:44', NULL),
(985, 153, 262, 'evaluation', NULL, NULL, 5.00, '2026-09-23 14:35:12', NULL),
(986, 153, 266, 'evaluation', NULL, NULL, 4.00, '2026-09-23 14:35:12', NULL),
(987, 153, 268, 'evaluation', NULL, NULL, 4.00, '2026-09-23 14:35:12', NULL),
(988, 153, 269, 'evaluation', NULL, NULL, 5.00, '2026-09-23 14:35:12', NULL),
(989, 153, 270, 'evaluation', NULL, NULL, 5.00, '2026-09-23 14:35:12', NULL),
(990, 153, 271, 'evaluation', NULL, NULL, 5.00, '2026-09-23 14:35:12', NULL),
(991, 153, 272, 'evaluation', NULL, NULL, 5.00, '2026-09-23 14:35:12', NULL),
(992, 153, 251, 'evaluation', NULL, NULL, 5.00, '2026-09-23 14:35:12', NULL),
(993, 153, 252, 'evaluation', NULL, NULL, 5.00, '2026-09-23 14:35:12', NULL),
(994, 153, 253, 'evaluation', NULL, NULL, 4.00, '2026-09-23 14:35:12', NULL),
(995, 153, 254, 'evaluation', NULL, NULL, 5.00, '2026-09-23 14:35:12', NULL),
(996, 153, 255, 'evaluation', NULL, NULL, 4.00, '2026-09-23 14:35:12', NULL),
(997, 153, 256, 'evaluation', NULL, NULL, 5.00, '2026-09-23 14:35:12', NULL),
(998, 153, 257, 'evaluation', NULL, NULL, 4.00, '2026-09-23 14:35:12', NULL),
(999, 153, 258, 'evaluation', NULL, NULL, 4.00, '2026-09-23 14:35:12', NULL),
(1000, 153, 260, 'evaluation', NULL, NULL, 4.00, '2026-09-23 14:35:12', NULL),
(1001, 153, 267, 'evaluation', NULL, NULL, 4.00, '2026-09-23 14:35:12', NULL),
(1002, 154, 262, 'evaluation', NULL, NULL, 5.00, '2026-09-23 14:38:24', NULL),
(1003, 154, 266, 'evaluation', NULL, NULL, 4.00, '2026-09-23 14:38:24', NULL),
(1004, 154, 268, 'evaluation', NULL, NULL, 3.00, '2026-09-23 14:38:24', NULL),
(1005, 154, 269, 'evaluation', NULL, NULL, 4.00, '2026-09-23 14:38:24', NULL),
(1006, 154, 270, 'evaluation', NULL, NULL, 2.00, '2026-09-23 14:38:24', NULL),
(1007, 154, 271, 'evaluation', NULL, NULL, 5.00, '2026-09-23 14:38:24', NULL),
(1008, 154, 272, 'evaluation', NULL, NULL, 4.00, '2026-09-23 14:38:24', NULL),
(1009, 154, 251, 'evaluation', NULL, NULL, 5.00, '2026-09-23 14:38:24', NULL),
(1010, 154, 252, 'evaluation', NULL, NULL, 4.00, '2026-09-23 14:38:24', NULL),
(1011, 154, 253, 'evaluation', NULL, NULL, 4.00, '2026-09-23 14:38:24', NULL),
(1012, 154, 254, 'evaluation', NULL, NULL, 5.00, '2026-09-23 14:38:24', NULL),
(1013, 154, 255, 'evaluation', NULL, NULL, 5.00, '2026-09-23 14:38:24', NULL),
(1014, 154, 256, 'evaluation', NULL, NULL, 1.00, '2026-09-23 14:38:24', NULL),
(1015, 154, 257, 'evaluation', NULL, NULL, 4.00, '2026-09-23 14:38:24', NULL),
(1016, 154, 258, 'evaluation', NULL, NULL, 5.00, '2026-09-23 14:38:24', NULL),
(1017, 154, 260, 'evaluation', NULL, NULL, 4.00, '2026-09-23 14:38:24', NULL),
(1018, 154, 267, 'evaluation', NULL, NULL, 5.00, '2026-09-23 14:38:24', NULL),
(1019, 155, 244, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1020, 155, 245, 'evaluation', NULL, NULL, 4.00, '2026-09-23 15:51:42', NULL),
(1021, 155, 246, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1022, 155, 247, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1023, 155, 248, 'evaluation', NULL, NULL, 3.00, '2026-09-23 15:51:42', NULL),
(1024, 155, 249, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1025, 155, 250, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1026, 155, 259, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1027, 155, 261, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1028, 155, 262, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1029, 155, 263, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1030, 155, 265, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1031, 155, 266, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1032, 155, 268, 'evaluation', NULL, NULL, 4.00, '2026-09-23 15:51:42', NULL),
(1033, 155, 269, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1034, 155, 270, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1035, 155, 271, 'evaluation', NULL, NULL, 4.00, '2026-09-23 15:51:42', NULL),
(1036, 155, 272, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1037, 155, 251, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1038, 155, 252, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1039, 155, 253, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1040, 155, 254, 'evaluation', NULL, NULL, 4.00, '2026-09-23 15:51:42', NULL),
(1041, 155, 255, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1042, 155, 256, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1043, 155, 257, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1044, 155, 258, 'evaluation', NULL, NULL, 4.00, '2026-09-23 15:51:42', NULL),
(1045, 155, 260, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1046, 155, 264, 'evaluation', NULL, NULL, 5.00, '2026-09-23 15:51:42', NULL),
(1047, 155, 267, 'evaluation', NULL, NULL, 4.00, '2026-09-23 15:51:42', NULL),
(1048, 156, 244, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:35:00', NULL),
(1049, 156, 245, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:35:00', NULL),
(1050, 156, 246, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:35:00', NULL),
(1051, 156, 247, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:35:00', NULL),
(1052, 156, 248, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:35:00', NULL),
(1053, 156, 249, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:35:00', NULL),
(1054, 156, 250, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:35:00', NULL),
(1055, 156, 259, 'evaluation', NULL, NULL, 3.00, '2026-09-24 09:35:00', NULL),
(1056, 156, 261, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:35:00', NULL),
(1057, 156, 262, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:35:00', NULL),
(1058, 156, 263, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:35:00', NULL),
(1059, 156, 265, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:35:00', NULL),
(1060, 156, 266, 'evaluation', NULL, NULL, 3.00, '2026-09-24 09:35:00', NULL),
(1061, 156, 268, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:35:00', NULL),
(1062, 156, 269, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:35:00', NULL),
(1063, 156, 270, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:35:00', NULL),
(1064, 156, 271, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:35:00', NULL),
(1065, 156, 272, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:35:00', NULL),
(1066, 156, 251, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:35:00', NULL),
(1067, 156, 252, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:35:00', NULL),
(1068, 156, 253, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:35:00', NULL),
(1069, 156, 254, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:35:00', NULL),
(1070, 156, 255, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:35:00', NULL),
(1071, 156, 256, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:35:00', NULL),
(1072, 156, 257, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:35:00', NULL),
(1073, 156, 258, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:35:00', NULL),
(1074, 156, 260, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:35:00', NULL),
(1075, 156, 264, 'evaluation', NULL, NULL, 3.00, '2026-09-24 09:35:00', NULL),
(1076, 156, 267, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:35:00', NULL),
(1077, 157, 244, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1078, 157, 245, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:38:06', NULL),
(1079, 157, 246, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1080, 157, 247, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1081, 157, 248, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:38:06', NULL),
(1082, 157, 249, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1083, 157, 250, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1084, 157, 259, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:38:06', NULL),
(1085, 157, 261, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1086, 157, 262, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1087, 157, 263, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:38:06', NULL),
(1088, 157, 265, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1089, 157, 266, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1090, 157, 268, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:38:06', NULL),
(1091, 157, 269, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1092, 157, 270, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1093, 157, 271, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:38:06', NULL),
(1094, 157, 272, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1095, 157, 251, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1096, 157, 252, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:38:06', NULL),
(1097, 157, 253, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1098, 157, 254, 'evaluation', NULL, NULL, 3.00, '2026-09-24 09:38:06', NULL),
(1099, 157, 255, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1100, 157, 256, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1101, 157, 257, 'evaluation', NULL, NULL, 3.00, '2026-09-24 09:38:06', NULL),
(1102, 157, 258, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1103, 157, 260, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:38:06', NULL),
(1104, 157, 264, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1105, 157, 267, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:06', NULL),
(1106, 158, 244, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:47', NULL),
(1107, 158, 245, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:38:47', NULL),
(1108, 158, 246, 'evaluation', NULL, NULL, 3.00, '2026-09-24 09:38:47', NULL),
(1109, 158, 247, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:47', NULL),
(1110, 158, 248, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:47', NULL),
(1111, 158, 249, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:38:47', NULL),
(1112, 158, 250, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:47', NULL),
(1113, 158, 259, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:47', NULL),
(1114, 158, 261, 'evaluation', NULL, NULL, 3.00, '2026-09-24 09:38:47', NULL),
(1115, 158, 262, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:47', NULL),
(1116, 158, 263, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:38:47', NULL),
(1117, 158, 265, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:47', NULL),
(1118, 158, 266, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:47', NULL),
(1119, 158, 268, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:38:47', NULL),
(1120, 158, 269, 'evaluation', NULL, NULL, 3.00, '2026-09-24 09:38:47', NULL),
(1121, 158, 270, 'evaluation', NULL, NULL, 3.00, '2026-09-24 09:38:47', NULL),
(1122, 158, 271, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:47', NULL),
(1123, 158, 272, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:38:47', NULL),
(1124, 158, 251, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:47', NULL),
(1125, 158, 252, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:38:47', NULL),
(1126, 158, 253, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:47', NULL),
(1127, 158, 254, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:38:47', NULL),
(1128, 158, 255, 'evaluation', NULL, NULL, 3.00, '2026-09-24 09:38:47', NULL),
(1129, 158, 256, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:47', NULL),
(1130, 158, 257, 'evaluation', NULL, NULL, 4.00, '2026-09-24 09:38:47', NULL),
(1131, 158, 258, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:47', NULL),
(1132, 158, 260, 'evaluation', NULL, NULL, 3.00, '2026-09-24 09:38:47', NULL),
(1133, 158, 264, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:47', NULL),
(1134, 158, 267, 'evaluation', NULL, NULL, 5.00, '2026-09-24 09:38:47', NULL),
(1135, 159, 244, 'evaluation', NULL, NULL, 5.00, '2026-09-24 16:39:57', NULL),
(1136, 159, 245, 'evaluation', NULL, NULL, 4.00, '2026-09-24 16:39:57', NULL),
(1137, 159, 246, 'evaluation', NULL, NULL, 5.00, '2026-09-24 16:39:57', NULL),
(1138, 159, 247, 'evaluation', NULL, NULL, 3.00, '2026-09-24 16:39:57', NULL),
(1139, 159, 248, 'evaluation', NULL, NULL, 5.00, '2026-09-24 16:39:57', NULL),
(1140, 159, 249, 'evaluation', NULL, NULL, 4.00, '2026-09-24 16:39:57', NULL),
(1141, 159, 250, 'evaluation', NULL, NULL, 5.00, '2026-09-24 16:39:57', NULL),
(1142, 159, 259, 'evaluation', NULL, NULL, 4.00, '2026-09-24 16:39:57', NULL),
(1143, 159, 261, 'evaluation', NULL, NULL, 5.00, '2026-09-24 16:39:57', NULL),
(1144, 159, 262, 'evaluation', NULL, NULL, 2.00, '2026-09-24 16:39:57', NULL),
(1145, 159, 263, 'evaluation', NULL, NULL, 5.00, '2026-09-24 16:39:57', NULL),
(1146, 159, 265, 'evaluation', NULL, NULL, 4.00, '2026-09-24 16:39:57', NULL),
(1147, 159, 266, 'evaluation', NULL, NULL, 5.00, '2026-09-24 16:39:57', NULL),
(1148, 159, 268, 'evaluation', NULL, NULL, 4.00, '2026-09-24 16:39:57', NULL),
(1149, 159, 269, 'evaluation', NULL, NULL, 5.00, '2026-09-24 16:39:57', NULL),
(1150, 159, 270, 'evaluation', NULL, NULL, 4.00, '2026-09-24 16:39:57', NULL),
(1151, 159, 271, 'evaluation', NULL, NULL, 5.00, '2026-09-24 16:39:57', NULL),
(1152, 159, 272, 'evaluation', NULL, NULL, 3.00, '2026-09-24 16:39:57', NULL),
(1153, 159, 251, 'evaluation', NULL, NULL, 5.00, '2026-09-24 16:39:57', NULL),
(1154, 159, 252, 'evaluation', NULL, NULL, 4.00, '2026-09-24 16:39:57', NULL),
(1155, 159, 253, 'evaluation', NULL, NULL, 5.00, '2026-09-24 16:39:57', NULL),
(1156, 159, 254, 'evaluation', NULL, NULL, 5.00, '2026-09-24 16:39:57', NULL),
(1157, 159, 255, 'evaluation', NULL, NULL, 4.00, '2026-09-24 16:39:57', NULL),
(1158, 159, 256, 'evaluation', NULL, NULL, 5.00, '2026-09-24 16:39:57', NULL),
(1159, 159, 257, 'evaluation', NULL, NULL, 5.00, '2026-09-24 16:39:57', NULL),
(1160, 159, 258, 'evaluation', NULL, NULL, 4.00, '2026-09-24 16:39:57', NULL),
(1161, 159, 260, 'evaluation', NULL, NULL, 5.00, '2026-09-24 16:39:57', NULL),
(1162, 159, 264, 'evaluation', NULL, NULL, 5.00, '2026-09-24 16:39:57', NULL),
(1163, 159, 267, 'evaluation', NULL, NULL, 4.00, '2026-09-24 16:39:57', NULL),
(1164, 160, NULL, 'user', 549, NULL, 5.00, '2026-09-28 15:58:31', NULL),
(1165, 160, NULL, 'user', 550, NULL, 4.00, '2026-09-28 15:58:31', NULL),
(1166, 160, NULL, 'user', 551, NULL, 5.00, '2026-09-28 15:58:31', NULL),
(1167, 160, NULL, 'user', 552, NULL, 4.00, '2026-09-28 15:58:31', NULL),
(1168, 160, NULL, 'user', 553, NULL, 5.00, '2026-09-28 15:58:31', NULL),
(1169, 160, NULL, 'user', 554, NULL, 4.00, '2026-09-28 15:58:31', NULL),
(1170, 160, NULL, 'user', 555, NULL, 4.00, '2026-09-28 15:58:31', NULL),
(1171, 160, NULL, 'user', 556, NULL, 5.00, '2026-09-28 15:58:31', NULL),
(1172, 160, NULL, 'user', 557, NULL, 3.00, '2026-09-28 15:58:31', NULL),
(1173, 160, NULL, 'user', 558, NULL, 4.00, '2026-09-28 15:58:31', NULL),
(1174, 161, NULL, 'user', 549, NULL, 5.00, '2026-09-28 16:00:48', NULL),
(1175, 161, NULL, 'user', 550, NULL, 4.00, '2026-09-28 16:00:48', NULL),
(1176, 161, NULL, 'user', 551, NULL, 4.00, '2026-09-28 16:00:48', NULL),
(1177, 161, NULL, 'user', 552, NULL, 5.00, '2026-09-28 16:00:48', NULL),
(1178, 161, NULL, 'user', 553, NULL, 3.00, '2026-09-28 16:00:48', NULL),
(1179, 161, NULL, 'user', 554, NULL, 5.00, '2026-09-28 16:00:48', NULL),
(1180, 161, NULL, 'user', 555, NULL, 5.00, '2026-09-28 16:00:48', NULL),
(1181, 161, NULL, 'user', 556, NULL, 4.00, '2026-09-28 16:00:48', NULL),
(1182, 161, NULL, 'user', 557, NULL, 5.00, '2026-09-28 16:00:48', NULL),
(1183, 161, NULL, 'user', 558, NULL, 4.00, '2026-09-28 16:00:48', NULL),
(1184, 162, 262, 'evaluation', NULL, NULL, 5.00, '2026-09-28 22:00:21', NULL),
(1185, 162, 266, 'evaluation', NULL, NULL, 4.00, '2026-09-28 22:00:21', NULL),
(1186, 162, 268, 'evaluation', NULL, NULL, 4.00, '2026-09-28 22:00:21', NULL),
(1187, 162, 269, 'evaluation', NULL, NULL, 5.00, '2026-09-28 22:00:21', NULL),
(1188, 162, 270, 'evaluation', NULL, NULL, 3.00, '2026-09-28 22:00:21', NULL),
(1189, 162, 271, 'evaluation', NULL, NULL, 4.00, '2026-09-28 22:00:21', NULL),
(1190, 162, 272, 'evaluation', NULL, NULL, 5.00, '2026-09-28 22:00:21', NULL),
(1191, 162, 251, 'evaluation', NULL, NULL, 5.00, '2026-09-28 22:00:21', NULL),
(1192, 162, 252, 'evaluation', NULL, NULL, 3.00, '2026-09-28 22:00:21', NULL),
(1193, 162, 253, 'evaluation', NULL, NULL, 5.00, '2026-09-28 22:00:21', NULL),
(1194, 162, 254, 'evaluation', NULL, NULL, 5.00, '2026-09-28 22:00:21', NULL),
(1195, 162, 255, 'evaluation', NULL, NULL, 4.00, '2026-09-28 22:00:21', NULL),
(1196, 162, 256, 'evaluation', NULL, NULL, 5.00, '2026-09-28 22:00:21', NULL),
(1197, 162, 257, 'evaluation', NULL, NULL, 5.00, '2026-09-28 22:00:21', NULL),
(1198, 162, 258, 'evaluation', NULL, NULL, 4.00, '2026-09-28 22:00:21', NULL),
(1199, 162, 260, 'evaluation', NULL, NULL, 5.00, '2026-09-28 22:00:21', NULL),
(1200, 162, 267, 'evaluation', NULL, NULL, 5.00, '2026-09-28 22:00:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_forms`
--

CREATE TABLE `questionnaire_forms` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `sector` enum('Faculty','Staff','Student','All','Teacher','Executive Assistant') NOT NULL DEFAULT 'All',
  `period` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `eval_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questionnaire_forms`
--

INSERT INTO `questionnaire_forms` (`id`, `title`, `description`, `sector`, `period`, `is_active`, `created_by`, `created_at`, `updated_at`, `eval_type`) VALUES
(1, 'Faculty Performance Evaluation', NULL, 'All', NULL, 1, NULL, '2026-06-10 07:54:30', '2026-06-10 07:54:30', 'student_to_faculty'),
(2, 'Staff Performance Evaluation', NULL, 'All', NULL, 1, NULL, '2026-06-10 07:54:52', '2026-06-10 07:54:52', 'student_to_staff'),
(3, 'Faculty Peer Evaluation', NULL, 'All', NULL, 1, NULL, '2026-06-10 07:54:52', '2026-06-10 07:54:52', 'faculty_peer'),
(4, 'Staff Peer Evaluation', NULL, 'All', NULL, 1, NULL, '2026-06-10 07:54:52', '2026-06-10 07:54:52', 'staff_peer'),
(5, 'Teacher Performance Evaluation (Supervisor)', NULL, 'Teacher', NULL, 1, NULL, '2026-08-06 19:32:37', '2026-08-06 19:32:37', 'supervisor_to_teacher'),
(6, 'Staff Performance Evaluation (Supervisor)', NULL, 'Staff', NULL, 1, NULL, '2026-08-06 19:32:37', '2026-08-06 19:32:37', 'supervisor_to_staff'),
(7, 'Executive Assistant Performance Evaluation (Supervisor)', NULL, 'Executive Assistant', NULL, 1, NULL, '2026-08-07 14:56:19', '2026-08-24 08:12:15', 'upward_to_ea');

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_migrations`
--

CREATE TABLE `questionnaire_migrations` (
  `migration_key` varchar(120) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questionnaire_migrations`
--

INSERT INTO `questionnaire_migrations` (`migration_key`, `applied_at`) VALUES
('generalized_questionnaire_v1', '2026-09-19 17:02:41');

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_questions`
--

CREATE TABLE `questionnaire_questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `form_id` int(10) UNSIGNED NOT NULL,
  `question_no` smallint(6) NOT NULL DEFAULT 1,
  `question` text NOT NULL,
  `type` enum('rating','text','yes_no','multiple_choice') NOT NULL DEFAULT 'rating',
  `max_score` decimal(5,2) DEFAULT 5.00,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `question_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questionnaire_questions`
--

INSERT INTO `questionnaire_questions` (`id`, `form_id`, `question_no`, `question`, `type`, `max_score`, `is_required`, `question_id`) VALUES
(4, 5, 4, 'Demonstrates professionalism and reliability (punctuality, conduct, dependability).', 'rating', 5.00, 1, NULL),
(5, 5, 5, 'Manages the classroom effectively and maintains student engagement.', 'rating', 5.00, 1, NULL),
(6, 5, 6, 'Collaborates well with school leadership and fellow teachers.', 'rating', 5.00, 1, NULL),
(7, 6, 1, 'Completes assigned tasks accurately and on time.', 'rating', 5.00, 1, NULL),
(8, 6, 2, 'Demonstrates professionalism in interactions with students, parents, and colleagues.', 'rating', 5.00, 1, NULL),
(9, 6, 3, 'Communicates clearly and responds promptly to requests.', 'rating', 5.00, 1, NULL),
(10, 6, 4, 'Shows initiative and sound judgment in daily responsibilities.', 'rating', 5.00, 1, NULL),
(11, 6, 5, 'Maintains a positive, cooperative attitude in the workplace.', 'rating', 5.00, 1, NULL),
(12, 6, 6, 'Reliable attendance and adherence to work schedules.', 'rating', 5.00, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `question_categories`
--

CREATE TABLE `question_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `target_type` varchar(100) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `eval_type` varchar(50) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `evaluator_role` varchar(10) NOT NULL DEFAULT 'shared'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `question_categories`
--

INSERT INTO `question_categories` (`id`, `target_type`, `category_name`, `eval_type`, `sort_order`, `created_at`, `evaluator_role`) VALUES
(6, 'Registrar', 'Service Quality', 'student', 0, '2026-06-15 07:39:21', 'shared'),
(7, 'Registrar', 'Accuracy', 'student', 1, '2026-06-15 07:39:21', 'shared'),
(8, 'Registrar', 'Professionalism', 'student', 2, '2026-06-15 07:39:21', 'shared'),
(9, 'Registrar', 'Responsiveness', 'student', 3, '2026-06-15 07:39:21', 'shared'),
(13, 'Cashier', 'Courtesy', 'student', 3, '2026-06-15 07:39:21', 'shared'),
(14, 'Bookkeeper', 'Accuracy', 'student', 0, '2026-06-15 07:39:21', 'shared'),
(15, 'Bookkeeper', 'Timeliness', 'student', 1, '2026-06-15 07:39:21', 'shared'),
(16, 'Bookkeeper', 'Professionalism', 'student', 2, '2026-06-15 07:39:21', 'shared'),
(17, 'Bookkeeper', 'Financial Reporting', 'student', 3, '2026-06-15 07:39:21', 'shared'),
(18, 'Librarian', 'Service Quality', 'student', 0, '2026-06-15 07:39:21', 'shared'),
(19, 'Librarian', 'Resource Management', 'student', 1, '2026-06-15 07:39:21', 'shared'),
(20, 'Librarian', 'Professionalism', 'student', 2, '2026-06-15 07:39:21', 'shared'),
(21, 'Librarian', 'Assistance', 'student', 3, '2026-06-15 07:39:21', 'shared'),
(22, 'Guidance', 'Counseling Quality', 'student', 0, '2026-06-15 07:39:21', 'shared'),
(23, 'Guidance', 'Approachability', 'student', 1, '2026-06-15 07:39:21', 'shared'),
(24, 'Guidance', 'Professionalism', 'student', 2, '2026-06-15 07:39:21', 'shared'),
(25, 'Guidance', 'Student Support', 'student', 3, '2026-06-15 07:39:21', 'shared'),
(26, 'Nurse', 'Medical Service', 'student', 0, '2026-06-15 07:39:21', 'shared'),
(27, 'Nurse', 'Responsiveness', 'student', 1, '2026-06-15 07:39:21', 'shared'),
(28, 'Nurse', 'Professionalism', 'student', 2, '2026-06-15 07:39:21', 'shared'),
(30, 'Personnel', 'Work Performance', 'student', 0, '2026-06-15 07:39:21', 'shared'),
(31, 'Personnel', 'Professionalism', 'student', 1, '2026-06-15 07:39:21', 'shared'),
(32, 'Personnel', 'Communication', 'student', 2, '2026-06-15 07:39:21', 'shared'),
(33, 'Personnel', 'Responsiveness', 'student', 3, '2026-06-15 07:39:21', 'shared'),
(34, 'Bookkeeper', 'Approachable', 'student', 4, '2026-06-15 07:40:00', 'shared'),
(36, 'Registrar', 'category', 'student', 4, '2026-06-15 16:04:07', 'shared'),
(42, 'Faculty', 'Collaboration', 'peer', 0, '2026-06-21 14:44:05', 'shared'),
(43, 'Faculty', 'Professionalism', 'peer', 1, '2026-06-21 14:44:05', 'shared'),
(44, 'Faculty', 'Communication', 'peer', 2, '2026-06-21 14:44:05', 'shared'),
(45, 'Faculty', 'Initiative', 'peer', 3, '2026-06-21 14:44:05', 'shared'),
(46, 'Faculty', 'Dependability', 'peer', 4, '2026-06-21 14:44:05', 'shared'),
(47, 'Registrar', 'Cooperation', 'peer', 0, '2026-06-21 14:44:05', 'shared'),
(48, 'Registrar', 'Accuracy', 'peer', 1, '2026-06-21 14:44:05', 'shared'),
(49, 'Registrar', 'Professionalism', 'peer', 2, '2026-06-21 14:44:05', 'shared'),
(50, 'Registrar', 'Responsiveness', 'peer', 3, '2026-06-21 14:44:05', 'shared'),
(51, 'Cashier', 'Teamwork', 'peer', 0, '2026-06-21 14:44:05', 'shared'),
(52, 'Cashier', 'Accuracy', 'peer', 1, '2026-06-21 14:44:05', 'shared'),
(53, 'Cashier', 'Professionalism', 'peer', 2, '2026-06-21 14:44:05', 'shared'),
(54, 'Cashier', 'Reliability', 'peer', 3, '2026-06-21 14:44:05', 'shared'),
(55, 'Bookkeeper', 'Accuracy', 'peer', 0, '2026-06-21 14:44:05', 'shared'),
(56, 'Bookkeeper', 'Timeliness', 'peer', 1, '2026-06-21 14:44:05', 'shared'),
(57, 'Bookkeeper', 'Professionalism', 'peer', 2, '2026-06-21 14:44:05', 'shared'),
(58, 'Bookkeeper', 'Transparency', 'peer', 3, '2026-06-21 14:44:05', 'shared'),
(59, 'Librarian', 'Teamwork', 'peer', 0, '2026-06-21 14:44:05', 'shared'),
(60, 'Librarian', 'Resource Management', 'peer', 1, '2026-06-21 14:44:05', 'shared'),
(61, 'Librarian', 'Professionalism', 'peer', 2, '2026-06-21 14:44:05', 'shared'),
(62, 'Librarian', 'Helpfulness', 'peer', 3, '2026-06-21 14:44:05', 'shared'),
(63, 'Guidance', 'Collaboration', 'peer', 0, '2026-06-21 14:44:05', 'shared'),
(64, 'Guidance', 'Approachability', 'peer', 1, '2026-06-21 14:44:05', 'shared'),
(65, 'Guidance', 'Professionalism', 'peer', 2, '2026-06-21 14:44:05', 'shared'),
(66, 'Guidance', 'Empathy', 'peer', 3, '2026-06-21 14:44:05', 'shared'),
(67, 'Nurse', 'Teamwork', 'peer', 0, '2026-06-21 14:44:05', 'shared'),
(68, 'Nurse', 'Responsiveness', 'peer', 1, '2026-06-21 14:44:05', 'shared'),
(69, 'Nurse', 'Professionalism', 'peer', 2, '2026-06-21 14:44:05', 'shared'),
(70, 'Nurse', 'Care Quality', 'peer', 3, '2026-06-21 14:44:05', 'shared'),
(71, 'Personnel', 'Work Performance', 'peer', 0, '2026-06-21 14:44:05', 'shared'),
(72, 'Personnel', 'Professionalism', 'peer', 1, '2026-06-21 14:44:05', 'shared'),
(73, 'Personnel', 'Communication', 'peer', 2, '2026-06-21 14:44:05', 'shared'),
(74, 'Personnel', 'Team Spirit', 'peer', 3, '2026-06-21 14:44:05', 'shared'),
(75, 'Multi-Role', 'General Performance', 'student', 0, '2026-06-23 11:41:01', 'shared'),
(76, 'Multi-Role', 'Cross-Role Responsibilities', 'student', 1, '2026-06-23 11:41:01', 'shared'),
(77, 'Multi-Role', 'Professionalism', 'student', 2, '2026-06-23 11:41:01', 'shared'),
(78, 'Multi-Role', 'Adaptability', 'student', 3, '2026-06-23 11:41:01', 'shared'),
(79, 'Multi-Role', 'Communication', 'student', 4, '2026-06-23 11:41:01', 'shared'),
(80, 'Multi-Role', 'Cross-Role Collaboration', 'peer', 0, '2026-06-23 11:41:01', 'shared'),
(81, 'Multi-Role', 'Professionalism', 'peer', 1, '2026-06-23 11:41:01', 'shared'),
(82, 'Multi-Role', 'Adaptability', 'peer', 2, '2026-06-23 11:41:01', 'shared'),
(83, 'Multi-Role', 'Communication', 'peer', 3, '2026-06-23 11:41:01', 'shared'),
(84, 'Multi-Role', 'Initiative', 'peer', 4, '2026-06-23 11:41:01', 'shared'),
(85, 'Staff', 'Cooperaton', 'student', 1, '2026-06-24 09:43:30', 'shared'),
(86, 'Staff', 'Attendance', 'student', 2, '2026-06-24 09:44:27', 'shared'),
(87, 'Staff', 'Relationship', 'student', 3, '2026-06-24 09:45:22', 'shared'),
(88, 'Staff', 'QUALITY OF WORK', 'student', 4, '2026-06-24 09:46:14', 'shared'),
(90, 'Faculty', 'Professionalism', 'student', 1, '2026-06-25 10:41:59', 'shared'),
(92, 'Teacher', 'Collaboration', 'peer', 0, '2026-07-25 17:33:30', 'shared'),
(94, 'Teacher', 'Communication', 'peer', 2, '2026-07-25 17:33:30', 'shared'),
(97, 'Staff', 'Teamwork', 'peer', 0, '2026-07-25 17:33:30', 'shared'),
(98, 'Staff', 'Professionalism', 'peer', 1, '2026-07-25 17:33:30', 'shared'),
(99, 'Staff', 'Communication', 'peer', 2, '2026-07-25 17:33:30', 'shared'),
(100, 'Staff', 'Reliability', 'peer', 3, '2026-07-25 17:33:30', 'shared'),
(101, 'Staff', 'Cooperation', 'peer', 4, '2026-07-25 17:33:30', 'shared'),
(105, 'Teacher', 'Cooperaton', 'student', 3, '2026-08-20 10:43:42', 'shared'),
(106, 'Teacher', 'Professionalism', 'student', 4, '2026-08-20 10:45:16', 'shared'),
(109, 'Teacher', 'Initiative', 'peer', 3, '2026-08-26 11:37:36', 'shared'),
(110, 'Teacher', 'Professionalism', 'peer', 4, '2026-08-26 11:38:03', 'shared'),
(111, 'Teacher', 'Teaching Effectiveness', 'student', 5, '2026-08-26 11:47:14', 'shared'),
(113, 'Teacher', 'Classroom Management', 'student', 6, '2026-08-26 12:03:38', 'shared'),
(115, 'Faculty', 'Professionalism', '', 1, '2026-08-28 15:45:56', 'shared'),
(127, 'Faculty', 'Initiative', '', 1, '2026-08-28 16:11:39', 'shared'),
(129, 'Faculty', 'Staff Effectiveness', '', 1, '2026-08-28 18:39:59', 'shared'),
(130, 'Faculty', 'ghdfg', '', 1, '2026-09-01 08:38:29', 'shared'),
(131, 'EA', 'Leadership & Coordination', '', 0, '2026-09-12 09:20:41', 'shared'),
(132, 'EA', 'Administrative Management', '', 1, '2026-09-12 09:20:41', 'shared'),
(133, 'EA', 'Communication', '', 2, '2026-09-12 09:20:41', 'shared'),
(134, 'EA', 'Professionalism', '', 3, '2026-09-12 09:20:41', 'shared'),
(135, 'EA', 'Responsiveness', '', 4, '2026-09-12 09:20:41', 'shared'),
(361, 'Faculty', 'Teaching Effectiveness', '', 0, '2026-09-12 10:16:00', 'shared'),
(363, 'Faculty', 'Communication', '', 2, '2026-09-12 10:16:00', 'shared'),
(364, 'Faculty', 'Classroom Management', '', 3, '2026-09-12 10:16:00', 'shared'),
(365, 'Faculty', 'Dependability', '', 4, '2026-09-12 10:16:00', 'shared'),
(366, 'Staff', 'Work Performance', '', 0, '2026-09-12 10:16:00', 'shared'),
(367, 'Staff', 'Service Quality', '', 1, '2026-09-12 10:16:00', 'shared'),
(368, 'Staff', 'Professionalism', '', 2, '2026-09-12 10:16:00', 'shared'),
(369, 'Staff', 'Communication', '', 3, '2026-09-12 10:16:00', 'shared'),
(370, 'Staff', 'Responsiveness', '', 4, '2026-09-12 10:16:00', 'shared'),
(1061, 'Faculty', 'Teaching Effectiveness', '', 0, '2026-09-12 14:41:01', 'dean'),
(1062, 'Faculty', 'Professionalism', '', 1, '2026-09-12 14:41:01', 'dean'),
(1063, 'Faculty', 'Communication', '', 2, '2026-09-12 14:41:01', 'dean'),
(1064, 'Faculty', 'Classroom Management', '', 3, '2026-09-12 14:41:01', 'dean'),
(1065, 'Faculty', 'Dependability', '', 4, '2026-09-12 14:41:01', 'dean'),
(1066, 'Staff', 'Work Performance', '', 0, '2026-09-12 14:41:01', 'dean'),
(1067, 'Staff', 'Service Quality', '', 1, '2026-09-12 14:41:01', 'dean'),
(1068, 'Staff', 'Professionalism', '', 2, '2026-09-12 14:41:01', 'dean'),
(1069, 'Staff', 'Communication', '', 3, '2026-09-12 14:41:01', 'dean'),
(1070, 'Staff', 'Responsiveness', '', 4, '2026-09-12 14:41:01', 'dean'),
(1071, 'EA', 'Leadership & Coordination', '', 0, '2026-09-12 14:41:01', 'dean'),
(1072, 'EA', 'Administrative Management', '', 1, '2026-09-12 14:41:01', 'dean'),
(1073, 'EA', 'Communication', '', 2, '2026-09-12 14:41:01', 'dean'),
(1074, 'EA', 'Professionalism', '', 3, '2026-09-12 14:41:01', 'dean'),
(1075, 'EA', 'Responsiveness', '', 4, '2026-09-12 14:41:01', 'dean'),
(1076, 'Faculty', 'Teaching Effectiveness', '', 0, '2026-09-12 14:41:01', 'principal'),
(1077, 'Faculty', 'Professionalism', '', 1, '2026-09-12 14:41:01', 'principal'),
(1078, 'Faculty', 'Communication', '', 2, '2026-09-12 14:41:01', 'principal'),
(1079, 'Faculty', 'Classroom Management', '', 3, '2026-09-12 14:41:01', 'principal'),
(1080, 'Faculty', 'Dependability', '', 4, '2026-09-12 14:41:01', 'principal'),
(1081, 'Staff', 'Work Performance', '', 0, '2026-09-12 14:41:01', 'principal'),
(1082, 'Staff', 'Service Quality', '', 1, '2026-09-12 14:41:01', 'principal'),
(1083, 'Staff', 'Professionalism', '', 2, '2026-09-12 14:41:01', 'principal'),
(1084, 'Staff', 'Communication', '', 3, '2026-09-12 14:41:01', 'principal'),
(1085, 'Staff', 'Responsiveness', '', 4, '2026-09-12 14:41:01', 'principal'),
(1086, 'EA', 'Leadership & Coordination', '', 0, '2026-09-12 14:41:01', 'principal'),
(1087, 'EA', 'Administrative Management', '', 1, '2026-09-12 14:41:01', 'principal'),
(1088, 'EA', 'Communication', '', 2, '2026-09-12 14:41:01', 'principal'),
(1089, 'EA', 'Professionalism', '', 3, '2026-09-12 14:41:01', 'principal'),
(1090, 'EA', 'Responsiveness', '', 4, '2026-09-12 14:41:01', 'principal'),
(3691, 'EA', 'Cooperaton', '', 1, '2026-09-12 17:03:01', 'dean'),
(4343, 'EA', 'Leadership & Coordination', 'ea', 0, '2026-09-13 17:05:28', 'shared'),
(4344, 'EA', 'Administrative Management', 'ea', 1, '2026-09-13 17:05:28', 'shared'),
(4345, 'EA', 'Communication', 'ea', 2, '2026-09-13 17:05:28', 'shared'),
(4346, 'EA', 'Professionalism', 'ea', 3, '2026-09-13 17:05:28', 'shared'),
(4347, 'EA', 'Responsiveness', 'ea', 4, '2026-09-13 17:05:28', 'shared'),
(4349, 'Dean', 'Communication', 'staff', 1, '2026-09-13 17:05:28', 'shared'),
(4350, 'Dean', 'Professionalism', 'staff', 2, '2026-09-13 17:05:28', 'shared'),
(4351, 'Dean', 'Responsiveness', 'staff', 3, '2026-09-13 17:05:28', 'shared'),
(4352, 'Dean', 'Support & Decision-Making', 'staff', 4, '2026-09-13 17:05:28', 'shared'),
(4353, 'Principal', 'Leadership & Governance', 'staff', 0, '2026-09-13 17:05:28', 'shared'),
(4354, 'Principal', 'Communication', 'staff', 1, '2026-09-13 17:05:28', 'shared'),
(4355, 'Principal', 'Professionalism', 'staff', 2, '2026-09-13 17:05:28', 'shared'),
(4356, 'Principal', 'Responsiveness', 'staff', 3, '2026-09-13 17:05:28', 'shared'),
(4357, 'Principal', 'Support & Decision-Making', 'staff', 4, '2026-09-13 17:05:28', 'shared'),
(4358, 'EA', 'Administrative Support', 'staff', 0, '2026-09-13 17:05:28', 'shared'),
(4359, 'EA', 'Communication', 'staff', 1, '2026-09-13 17:05:28', 'shared'),
(4360, 'EA', 'Professionalism', 'staff', 2, '2026-09-13 17:05:28', 'shared'),
(4361, 'EA', 'Responsiveness', 'staff', 3, '2026-09-13 17:05:28', 'shared'),
(4362, 'EA', 'Service & Coordination', 'staff', 4, '2026-09-13 17:05:28', 'shared'),
(4363, 'Faculty', 'Teaching Effectiveness', 'school_head', 0, '2026-09-13 17:05:28', 'dean'),
(4364, 'Faculty', 'Professionalism', 'school_head', 1, '2026-09-13 17:05:28', 'dean'),
(4365, 'Faculty', 'Communication', 'school_head', 2, '2026-09-13 17:05:28', 'dean'),
(4366, 'Faculty', 'Classroom Management', 'school_head', 3, '2026-09-13 17:05:28', 'dean'),
(4367, 'Faculty', 'Dependability', 'school_head', 4, '2026-09-13 17:05:28', 'dean'),
(4368, 'EA', 'Leadership & Coordination', 'school_head', 0, '2026-09-13 17:05:28', 'dean'),
(4369, 'EA', 'Administrative Management', 'school_head', 1, '2026-09-13 17:05:28', 'dean'),
(4370, 'EA', 'Communication', 'school_head', 2, '2026-09-13 17:05:28', 'dean'),
(4371, 'EA', 'Professionalism', 'school_head', 3, '2026-09-13 17:05:28', 'dean'),
(4372, 'EA', 'Responsiveness', 'school_head', 4, '2026-09-13 17:05:28', 'dean'),
(4373, 'Faculty', 'Teaching Effectiveness', 'school_head', 0, '2026-09-13 17:05:28', 'principal'),
(4374, 'Faculty', 'Professionalism', 'school_head', 1, '2026-09-13 17:05:28', 'principal'),
(4375, 'Faculty', 'Communication', 'school_head', 2, '2026-09-13 17:05:28', 'principal'),
(4376, 'Faculty', 'Classroom Management', 'school_head', 3, '2026-09-13 17:05:28', 'principal'),
(4377, 'Faculty', 'Dependability', 'school_head', 4, '2026-09-13 17:05:28', 'principal'),
(4378, 'EA', 'Leadership & Coordination', 'school_head', 0, '2026-09-13 17:05:28', 'principal'),
(4379, 'EA', 'Administrative Management', 'school_head', 1, '2026-09-13 17:05:28', 'principal'),
(4380, 'EA', 'Communication', 'school_head', 2, '2026-09-13 17:05:28', 'principal'),
(4381, 'EA', 'Professionalism', 'school_head', 3, '2026-09-13 17:05:28', 'principal'),
(4382, 'EA', 'Responsiveness', 'school_head', 4, '2026-09-13 17:05:28', 'principal'),
(4384, 'Faculty', 'General', 'general', 0, '2026-09-19 17:02:41', 'shared'),
(4387, 'Faculty', 'Teaching & Learning', 'general', 5, '2026-09-19 17:02:41', 'shared'),
(4389, 'Faculty', 'Professionalism & Student Support', 'general', 0, '2026-09-22 10:46:18', 'shared');

-- --------------------------------------------------------

--
-- Table structure for table `rating_certifications`
--

CREATE TABLE `rating_certifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `rated_user_id` int(10) UNSIGNED NOT NULL,
  `period_id` int(10) UNSIGNED NOT NULL,
  `issued_by` int(10) UNSIGNED NOT NULL,
  `student_avg` decimal(4,2) DEFAULT NULL,
  `peer_avg` decimal(4,2) DEFAULT NULL,
  `final_rating` decimal(4,2) NOT NULL,
  `adjectival_rating` varchar(20) NOT NULL,
  `status` enum('issued','revoked') NOT NULL DEFAULT 'issued',
  `issued_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_change_log`
--

CREATE TABLE `role_change_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `performed_by_id` int(10) UNSIGNED DEFAULT NULL,
  `source_module` varchar(255) DEFAULT NULL,
  `old_role` varchar(60) NOT NULL DEFAULT '',
  `new_role` varchar(60) NOT NULL DEFAULT '',
  `old_designation` varchar(120) DEFAULT NULL,
  `new_designation` varchar(120) DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_change_log`
--

INSERT INTO `role_change_log` (`id`, `user_id`, `performed_by_id`, `source_module`, `old_role`, `new_role`, `old_designation`, `new_designation`, `changed_at`) VALUES
(1, 79, NULL, NULL, 'faculty', 'faculty', 'BSIT – II ADVISER', 'BSIT – II ADVISER/ cashier/ bookkeeper', '2026-07-13 17:36:19'),
(2, 79, NULL, NULL, 'faculty', 'faculty', 'BSIT – II ADVISER/ cashier/ bookkeeper', 'BSIT – II ADVISER/', '2026-07-13 18:28:02'),
(3, 118, NULL, NULL, 'faculty', 'faculty', 'Teacher', 'Department Head', '2026-07-26 14:57:01'),
(4, 118, NULL, NULL, 'faculty', 'faculty', 'Department Head', 'Teacher/Department Head', '2026-07-26 14:57:16'),
(5, 131, NULL, NULL, 'faculty', 'faculty', 'Teacher', 'Teacher/cashier/bookkeeper', '2026-07-31 09:00:55'),
(6, 136, NULL, NULL, 'staff', 'staff', 'Personnel', 'Personnel/ Physical Plant Coordinator/ Computer Lab Custodian', '2026-07-31 16:00:18'),
(7, 136, NULL, NULL, 'staff', 'staff', 'Staff', 'Staff/Physical Plant Coordinator/ Computer Lab Custodian', '2026-08-14 11:17:13'),
(8, 152, NULL, NULL, 'staff', 'staff', 'Staff', 'Staff/Cashier', '2026-08-17 18:00:10'),
(9, 146, NULL, NULL, 'staff', 'staff', 'Staff', 'Staff/ GUIDANCE STAFF/ SPORTS PROGRAM MANAGER/ SDRRM OFFICER', '2026-08-18 11:45:01'),
(10, 160, NULL, NULL, 'faculty', 'faculty', 'Teacher', 'Teacher/ CC   102 - Computer Programming 1** IPT   101 - Integrative Programming and Technologies 1', '2026-08-18 12:25:16'),
(11, 176, NULL, NULL, 'staff', 'staff', 'Personnel', 'Personnel/ Registrar', '2026-08-20 17:07:59'),
(12, 179, NULL, NULL, 'faculty', 'faculty', 'Teacher', 'Cashier', '2026-08-22 10:14:59'),
(13, 185, NULL, NULL, 'staff', 'staff', 'Personnel', 'Personnel/ Department Head', '2026-08-26 15:24:13'),
(14, 170, NULL, NULL, 'faculty', 'faculty', 'Teacher', 'Teacher/ librarian', '2026-08-29 12:46:12'),
(15, 197, NULL, NULL, 'faculty', 'faculty', 'Teacher', 'Personnel', '2026-08-29 13:21:36'),
(16, 199, NULL, NULL, 'faculty', 'faculty', 'Teacher', 'Teacher/ BSIT-3 Adviser', '2026-08-29 13:42:23'),
(17, 209, NULL, NULL, 'staff', 'staff', 'Personnel', 'Formation Services', '2026-09-18 15:48:41'),
(18, 209, NULL, NULL, 'staff', 'staff', 'Formation Services', 'Formation Services Coordinator/ CMO', '2026-09-18 15:48:54'),
(19, 209, NULL, NULL, 'staff', 'staff', 'Formation Services Coordinator/ CMO', 'Bookkeeper', '2026-09-21 13:59:12'),
(21, 215, NULL, NULL, 'staff', 'staff', 'Personnel', 'BS Nursing', '2026-09-22 10:09:01'),
(22, 170, NULL, NULL, 'faculty', 'faculty', 'Teacher/ librarian', 'BEED - General Education', '2026-09-22 10:09:45'),
(23, 216, NULL, NULL, 'faculty', 'faculty', 'Teacher', 'BS Information Technology  with Certificate in Teaching – Social Studies', '2026-09-22 10:22:24'),
(24, 217, NULL, NULL, 'faculty', 'faculty', 'Teacher', 'BSED - English', '2026-09-22 10:22:59'),
(25, 218, NULL, NULL, 'staff', 'staff', 'Personnel', 'Master in Library and Information Science BSBA – Management', '2026-09-22 10:38:12'),
(26, 218, NULL, NULL, 'staff', 'staff', 'Master in Library and Information Science BSBA – Management', 'Librarian', '2026-09-22 11:08:19'),
(27, 217, NULL, NULL, 'faculty', 'faculty', 'BSED - English', 'Teacher/ Coordinator', '2026-09-22 11:11:49'),
(28, 220, NULL, NULL, 'faculty', 'faculty', 'Teacher', 'Teacher/ Cashier', '2026-09-22 11:54:31'),
(29, 221, NULL, NULL, 'staff', 'staff', 'Personnel', 'Physical Plant Coordinator/ Computer Lab Custodian', '2026-09-23 10:59:19'),
(30, 222, NULL, NULL, 'faculty', 'faculty', 'Teacher', 'ESC/ SHSVP/ TSS Encoder YEARBOOK IN CHARGE', '2026-09-23 11:01:14'),
(31, 222, NULL, NULL, 'faculty', 'faculty', 'ESC/ SHSVP/ TSS Encoder YEARBOOK IN CHARGE', 'Teacher/ ESC/ SHSVP/ TSS Encoder YEARBOOK IN CHARGE', '2026-09-23 11:01:22'),
(32, 225, NULL, NULL, 'staff', 'staff', 'Personnel', 'Physical Plant Coordinator/ Computer Lab Custodian', '2026-09-23 11:23:34'),
(33, 226, NULL, NULL, 'staff', 'staff', 'Personnel', 'MAINTENANCE OFFICER', '2026-09-23 11:25:48'),
(34, 230, NULL, NULL, 'staff', 'staff', 'Personnel', 'GUIDANCE STAFF/ SPORTS PROGRAM MANAGER/ SDRRM OFFICER', '2026-09-23 11:32:31'),
(35, 232, NULL, NULL, 'staff', 'staff', 'Personnel', 'School Registrar/ ADMIN COORDINATOR', '2026-09-23 11:36:38'),
(36, 227, NULL, NULL, 'faculty', 'faculty', 'Teacher', 'Ang Kingke Adviser/ Grade 7 - St. Albert Adviser/ HS TEACHER', '2026-09-23 11:42:39'),
(37, 233, NULL, NULL, 'staff', 'staff', 'Personnel', 'Bookkeeper', '2026-09-23 11:50:55'),
(38, 234, NULL, NULL, 'staff', 'staff', 'Personnel', 'VE/CLE COORDINATOR/ HS TEACHER', '2026-09-23 11:52:55');

-- --------------------------------------------------------

--
-- Table structure for table `school_head_evaluation_assignments`
--

CREATE TABLE `school_head_evaluation_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `evaluator_id` int(10) UNSIGNED NOT NULL,
  `target_user_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_head_evaluation_assignments`
--

INSERT INTO `school_head_evaluation_assignments` (`id`, `evaluator_id`, `target_user_id`, `question_id`, `assigned_at`) VALUES
(4, 158, 183, 220, '2026-09-10 14:29:29'),
(5, 158, 183, 221, '2026-09-10 14:29:29'),
(6, 158, 183, 222, '2026-09-10 14:29:29');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `duration_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 30,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `price`, `duration_minutes`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Basic Service', 'A simple appointment-based service.', 350.00, 30, 1, '2026-09-05 01:46:51', '2026-09-05 01:46:51'),
(2, 'Premium Service', 'A longer service with additional features.', 650.00, 60, 1, '2026-09-05 01:46:51', '2026-09-05 01:46:51'),
(3, 'Consultation', 'Initial consultation and assessment.', 250.00, 30, 1, '2026-09-05 01:46:51', '2026-09-05 01:46:51'),
(4, 'Technical Support', 'Hands-on technical support and troubleshooting.', 500.00, 60, 1, '2026-09-05 01:46:51', '2026-09-05 01:46:51');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `staff_availability`
--

CREATE TABLE `staff_availability` (
  `id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED NOT NULL,
  `day_of_week` tinyint(3) UNSIGNED NOT NULL,
  `open_time` time DEFAULT NULL,
  `close_time` time DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1
) ;

-- --------------------------------------------------------

--
-- Table structure for table `staff_services`
--

CREATE TABLE `staff_services` (
  `staff_id` int(10) UNSIGNED NOT NULL,
  `service_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_security_answers`
--

CREATE TABLE `student_security_answers` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `slot` tinyint(3) UNSIGNED NOT NULL,
  `question_key` varchar(40) NOT NULL,
  `answer_hash` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_security_answers`
--

INSERT INTO `student_security_answers` (`id`, `user_id`, `slot`, `question_key`, `answer_hash`, `updated_at`) VALUES
(4, 175, 1, 'childhood_nick', '$2y$10$RSCnEjMmQ0w3Lkkgyx5zGOu.dhheBdUq1KxPti7XgLin6HOWSTK5u', '2026-09-21 03:45:49'),
(5, 175, 2, 'first_trip', '$2y$10$IAF6eIETa8Np7LMD6kcQ7ed7GcyDNd8TS8vhQMskm2N0viHaQ8R/S', '2026-09-21 03:45:49'),
(6, 175, 3, 'first_game', '$2y$10$kyhg70YCBeFlCS/w2NQI3eD1uEc3tHa9xWC2QctOuCF0EkrBSgU2.', '2026-09-21 03:45:49'),
(19, 171, 1, 'first_pet', '$2y$10$qwljCVmtTvoOWpKSZNAXJeesUsb2dqhfjgO05vSv3XgDJ7nohoKYW', '2026-09-22 01:59:34'),
(20, 171, 2, 'childhood_nick', '$2y$10$6uTUVL.Mg3tGFsG.Vn9uZuHyIwZctC5EFgPpR8Z6uhCfE1v.VVrEC', '2026-09-22 01:59:34'),
(21, 171, 3, 'grandparent_middle', '$2y$10$4rQHBsmRWxdkiOftzGDonuor3QnoGTMN9myd3egjaU6aofZ4Mms.a', '2026-09-22 01:59:34'),
(22, 219, 1, 'first_pet', '$2y$10$oEUI7hk/9ormI1MzYyhZtOtPI4jY91BWOvs4FB7yi/Wf6XBS8YBY2', '2026-09-22 03:33:12'),
(23, 219, 2, 'childhood_nick', '$2y$10$gWimup9QQMfp9zMzebD5hehgHnQM.ssHIOKAHctTiXtgZ7bXiqCw.', '2026-09-22 03:33:12'),
(24, 219, 3, 'parents_met', '$2y$10$v0KlPIZMvwThmUcnsyKUze4U/A4zLqjc8O.g4Hpp2EpqN06p23ulq', '2026-09-22 03:33:12'),
(25, 235, 1, 'first_pet', '$2y$10$u/e2ddCpyas2e0SfCVr/.OLtRyb7EZkqBMKgCjJl51dGraFL3tSj2', '2026-09-23 08:05:55'),
(26, 235, 2, 'childhood_nick', '$2y$10$lRkWVjhdrJcWPGFsaeosae2hLinCBYhQ5wQtz0ubFIk5goaZTQ2im', '2026-09-23 08:05:55'),
(27, 235, 3, 'grandparent_middle', '$2y$10$HExrPQ.pBgj2NKWqA8X1ReUg4AGzEuS6d3B4fWlhFaptck7C3Jbvi', '2026-09-23 08:05:55'),
(28, 237, 1, 'first_pet', '$2y$10$Ka1Cff0XMZCItSW1CKzOIObylrdptP45WV1DaNlvWtpZn4KwlS/4O', '2026-09-24 03:22:29'),
(29, 237, 2, 'childhood_nick', '$2y$10$gi7ZH/CZzlV52OfW5wr/q.19R8Ep4BMbnzXb5jHYHIPF60iymYs0e', '2026-09-24 03:22:29'),
(30, 237, 3, 'first_phone', '$2y$10$kzjzLV.0neH8Z35bRVcKe.QdGtU.1gd/jo5VQNC6zIinRUU7UhnZK', '2026-09-24 03:22:29'),
(31, 238, 1, 'first_pet', '$2y$10$6fjld65uUdhbcddE.b/1FuzxlcCphrAODKIHJMOubWupEXYZyyZ5a', '2026-09-24 03:27:31'),
(32, 238, 2, 'childhood_nick', '$2y$10$gDx0ILtGjuJ8KOhoKJZIwOECsHLBfUL9l3Ryg23.fNUVch0vzae8S', '2026-09-24 03:27:31'),
(33, 238, 3, 'grandparent_middle', '$2y$10$WtLcNEQk31YfeGxWr8FGQeTC1C9xrW5wIESrAAZopMGxao3qJdazy', '2026-09-24 03:27:31'),
(34, 240, 1, 'first_pet', '$2y$10$EZEKzqdPQ1ScRk51EG10der/Kwf3AUDiCWERpOX1vhUrAq8wDrnkG', '2026-09-24 08:22:50'),
(35, 240, 2, 'oldest_cousin', '$2y$10$zRR3J.4Bq911oX5I9dXPQeUkWKYSaWYYzGS1sCDM22xQWVwHsR3q6', '2026-09-24 08:22:50'),
(36, 240, 3, 'parents_met', '$2y$10$w.U9DzXZoYgq.pdFVfFi9uNzUXBsuQ2RWSXf.D6G8Pf.KuhmbPnly', '2026-09-24 08:22:50');

-- --------------------------------------------------------

--
-- Table structure for table `system_archives`
--

CREATE TABLE `system_archives` (
  `id` int(10) UNSIGNED NOT NULL,
  `period_id` int(10) UNSIGNED NOT NULL,
  `period_label` varchar(150) NOT NULL,
  `school_year` varchar(30) NOT NULL,
  `archived_by` int(10) UNSIGNED DEFAULT NULL,
  `archived_by_name` varchar(150) DEFAULT NULL,
  `archived_at` datetime NOT NULL DEFAULT current_timestamp(),
  `restored_at` datetime DEFAULT NULL,
  `restored_by` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('archived','restored') NOT NULL DEFAULT 'archived',
  `record_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `summary_json` longtext DEFAULT NULL,
  `payload_json` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_archives`
--

INSERT INTO `system_archives` (`id`, `period_id`, `period_label`, `school_year`, `archived_by`, `archived_by_name`, `archived_at`, `restored_at`, `restored_by`, `status`, `record_count`, `summary_json`, `payload_json`) VALUES
(2, 5, '2026-2027 — Summer', '2026-2027', 236, 'Lorraine R. Sabay', '2026-09-28 22:21:16', '2026-09-28 22:25:56', 236, 'restored', 8, '{\"evaluation_tracker\":2,\"questionnaire_answers\":5,\"evaluation_answers\":0,\"evaluation_submissions\":0,\"evaluation_results\":0,\"peer_evaluation_submissions\":0,\"peer_evaluation_results\":0,\"evaluation_reminders\":1,\"analytics_reports\":0,\"notifications\":0,\"activity_log_snapshot\":0}', '{\"evaluation_tracker\":[{\"id\":\"58\",\"legacy_submission_id\":null,\"evaluator_id\":\"236\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":null,\"remarks\":\"\",\"eval_type\":\"ea\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-24 14:57:55\",\"updated_at\":\"2026-09-24 14:36:32\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"72\",\"legacy_submission_id\":null,\"evaluator_id\":\"236\",\"target_user_id\":\"157\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"ea\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-27 16:55:58\",\"updated_at\":\"2026-09-24 14:36:32\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"}],\"questionnaire_answers\":[{\"id\":\"315\",\"tracker_id\":\"58\",\"question_id\":\"153\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-24 14:57:55\",\"comments\":null},{\"id\":\"316\",\"tracker_id\":\"58\",\"question_id\":\"79\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-08-24 14:57:55\",\"comments\":null},{\"id\":\"381\",\"tracker_id\":\"72\",\"question_id\":\"80\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-27 16:55:58\",\"comments\":null},{\"id\":\"382\",\"tracker_id\":\"72\",\"question_id\":\"82\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-27 16:55:58\",\"comments\":null},{\"id\":\"383\",\"tracker_id\":\"72\",\"question_id\":\"83\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-27 16:55:58\",\"comments\":null}],\"evaluation_answers\":[],\"evaluation_submissions\":[],\"evaluation_results\":[],\"peer_evaluation_submissions\":[],\"peer_evaluation_results\":[],\"evaluation_reminders\":[{\"id\":\"2\",\"period_id\":\"5\",\"sender_id\":\"157\",\"recipient_id\":\"175\",\"eval_type\":\"student\",\"level\":\"college\",\"created_at\":\"2026-08-24 17:19:27\"}],\"analytics_reports\":[],\"notifications\":[],\"activity_log_snapshot\":[]}'),
(4, 6, '2026-2027 — 1st Semester', '2026-2027', 125, 'Flowen Nina Anecito', '2026-09-21 10:37:21', NULL, NULL, 'archived', 69, '{\"evaluation_tracker\":3,\"questionnaire_answers\":66,\"evaluation_answers\":0,\"evaluation_submissions\":0,\"evaluation_results\":0,\"peer_evaluation_submissions\":0,\"peer_evaluation_results\":0,\"evaluation_reminders\":0,\"analytics_reports\":0,\"notifications\":0,\"activity_log_snapshot\":1}', '{\"evaluation_tracker\":[{\"id\":\"128\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"144\",\"eval_bucket\":\"Faculty\",\"level\":\"college\",\"form_type\":\"school_head_dean_faculty\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":\"4.68\",\"remarks\":\"Overall Goods naman po!\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-19 22:10:23\",\"updated_at\":\"2026-09-19 22:10:23\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"129\",\"legacy_submission_id\":null,\"evaluator_id\":\"161\",\"target_user_id\":\"144\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-20 15:56:25\",\"updated_at\":\"2026-09-20 15:56:25\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"130\",\"legacy_submission_id\":null,\"evaluator_id\":\"161\",\"target_user_id\":\"194\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-20 16:09:50\",\"updated_at\":\"2026-09-20 16:09:50\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"}],\"questionnaire_answers\":[{\"id\":\"648\",\"tracker_id\":\"128\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"649\",\"tracker_id\":\"128\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"650\",\"tracker_id\":\"128\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"651\",\"tracker_id\":\"128\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"652\",\"tracker_id\":\"128\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"653\",\"tracker_id\":\"128\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"654\",\"tracker_id\":\"128\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"655\",\"tracker_id\":\"128\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"656\",\"tracker_id\":\"128\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"657\",\"tracker_id\":\"128\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"658\",\"tracker_id\":\"128\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"659\",\"tracker_id\":\"128\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"660\",\"tracker_id\":\"128\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"661\",\"tracker_id\":\"128\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"662\",\"tracker_id\":\"128\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"663\",\"tracker_id\":\"128\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"664\",\"tracker_id\":\"128\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"665\",\"tracker_id\":\"128\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"666\",\"tracker_id\":\"128\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"667\",\"tracker_id\":\"128\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"668\",\"tracker_id\":\"128\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"669\",\"tracker_id\":\"128\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-19 22:10:23\",\"comments\":null},{\"id\":\"670\",\"tracker_id\":\"129\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"671\",\"tracker_id\":\"129\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"672\",\"tracker_id\":\"129\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"673\",\"tracker_id\":\"129\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"674\",\"tracker_id\":\"129\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"675\",\"tracker_id\":\"129\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"676\",\"tracker_id\":\"129\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"677\",\"tracker_id\":\"129\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"678\",\"tracker_id\":\"129\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"679\",\"tracker_id\":\"129\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"680\",\"tracker_id\":\"129\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"681\",\"tracker_id\":\"129\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"682\",\"tracker_id\":\"129\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"683\",\"tracker_id\":\"129\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"684\",\"tracker_id\":\"129\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"685\",\"tracker_id\":\"129\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"686\",\"tracker_id\":\"129\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"687\",\"tracker_id\":\"129\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"688\",\"tracker_id\":\"129\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"689\",\"tracker_id\":\"129\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"690\",\"tracker_id\":\"129\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"691\",\"tracker_id\":\"129\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 15:56:25\",\"comments\":null},{\"id\":\"692\",\"tracker_id\":\"130\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"693\",\"tracker_id\":\"130\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"694\",\"tracker_id\":\"130\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"695\",\"tracker_id\":\"130\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"696\",\"tracker_id\":\"130\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"697\",\"tracker_id\":\"130\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"698\",\"tracker_id\":\"130\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"699\",\"tracker_id\":\"130\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"700\",\"tracker_id\":\"130\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"701\",\"tracker_id\":\"130\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"702\",\"tracker_id\":\"130\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"703\",\"tracker_id\":\"130\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"704\",\"tracker_id\":\"130\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"705\",\"tracker_id\":\"130\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"706\",\"tracker_id\":\"130\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"707\",\"tracker_id\":\"130\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"708\",\"tracker_id\":\"130\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"709\",\"tracker_id\":\"130\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"710\",\"tracker_id\":\"130\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"711\",\"tracker_id\":\"130\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"712\",\"tracker_id\":\"130\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null},{\"id\":\"713\",\"tracker_id\":\"130\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-20 16:09:50\",\"comments\":null}],\"evaluation_answers\":[],\"evaluation_submissions\":[],\"evaluation_results\":[],\"peer_evaluation_submissions\":[],\"peer_evaluation_results\":[],\"evaluation_reminders\":[],\"analytics_reports\":[],\"notifications\":[],\"activity_log_snapshot\":[{\"id\":\"33\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Deleted evaluation archive #1 for 2026-2027 (2026-2027 — 1st Semester)\",\"icon\":\"fa-trash\",\"color\":\"#D6455D\",\"created_at\":\"2026-09-20 15:57:18\"}]}'),
(5, 3, '2026-2027 — 1st Semester', '2026-2027', 236, 'Lorraine R. Sabay', '2026-09-28 22:21:11', '2026-09-28 22:25:56', 236, 'restored', 17, '{\"evaluation_tracker\":3,\"questionnaire_answers\":14,\"evaluation_answers\":0,\"evaluation_submissions\":0,\"evaluation_results\":0,\"peer_evaluation_submissions\":0,\"peer_evaluation_results\":0,\"evaluation_reminders\":0,\"analytics_reports\":0,\"notifications\":0,\"activity_log_snapshot\":0}', '{\"evaluation_tracker\":[{\"id\":\"110\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"236\",\"eval_bucket\":\"EA\",\"level\":\"college\",\"form_type\":\"school_head_dean_ea\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":\"4.67\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-15 07:49:43\",\"updated_at\":\"2026-09-24 14:36:32\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"116\",\"legacy_submission_id\":null,\"evaluator_id\":\"236\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"ea\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-15 16:41:50\",\"updated_at\":\"2026-09-24 14:36:32\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"121\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-18 07:47:29\",\"updated_at\":\"2026-09-18 07:47:29\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"}],\"questionnaire_answers\":[{\"id\":\"557\",\"tracker_id\":\"110\",\"question_id\":\"237\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 07:49:43\",\"comments\":null},{\"id\":\"558\",\"tracker_id\":\"110\",\"question_id\":\"238\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-15 07:49:43\",\"comments\":null},{\"id\":\"559\",\"tracker_id\":\"110\",\"question_id\":\"239\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 07:49:43\",\"comments\":null},{\"id\":\"572\",\"tracker_id\":\"116\",\"question_id\":\"315\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:41:50\",\"comments\":null},{\"id\":\"573\",\"tracker_id\":\"116\",\"question_id\":\"157\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:41:50\",\"comments\":null},{\"id\":\"574\",\"tracker_id\":\"116\",\"question_id\":\"314\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-15 16:41:50\",\"comments\":null},{\"id\":\"575\",\"tracker_id\":\"116\",\"question_id\":\"154\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:41:50\",\"comments\":null},{\"id\":\"576\",\"tracker_id\":\"116\",\"question_id\":\"155\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:41:50\",\"comments\":null},{\"id\":\"577\",\"tracker_id\":\"116\",\"question_id\":\"158\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:41:50\",\"comments\":null},{\"id\":\"578\",\"tracker_id\":\"116\",\"question_id\":\"159\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:41:50\",\"comments\":null},{\"id\":\"579\",\"tracker_id\":\"116\",\"question_id\":\"156\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-15 16:41:50\",\"comments\":null},{\"id\":\"605\",\"tracker_id\":\"121\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"232\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:47:29\",\"comments\":null},{\"id\":\"606\",\"tracker_id\":\"121\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"244\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 07:47:29\",\"comments\":null},{\"id\":\"607\",\"tracker_id\":\"121\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"245\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:47:29\",\"comments\":null}],\"evaluation_answers\":[],\"evaluation_submissions\":[],\"evaluation_results\":[],\"peer_evaluation_submissions\":[],\"peer_evaluation_results\":[],\"evaluation_reminders\":[],\"analytics_reports\":[],\"notifications\":[],\"activity_log_snapshot\":[]}');
INSERT INTO `system_archives` (`id`, `period_id`, `period_label`, `school_year`, `archived_by`, `archived_by_name`, `archived_at`, `restored_at`, `restored_by`, `status`, `record_count`, `summary_json`, `payload_json`) VALUES
(6, 2, '2026-2027 — School Year', '2026-2027', 236, 'Lorraine R. Sabay', '2026-09-28 22:21:14', '2026-09-28 22:25:56', 236, 'restored', 146, '{\"evaluation_tracker\":10,\"questionnaire_answers\":136,\"evaluation_answers\":0,\"evaluation_submissions\":0,\"evaluation_results\":0,\"peer_evaluation_submissions\":0,\"peer_evaluation_results\":0,\"evaluation_reminders\":0,\"analytics_reports\":0,\"notifications\":0,\"activity_log_snapshot\":1}', '{\"evaluation_tracker\":[{\"id\":\"140\",\"legacy_submission_id\":null,\"evaluator_id\":\"158\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":\"\",\"form_type\":\"Principal Evaluation — Faculty\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"4.91\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 10:58:50\",\"updated_at\":\"2026-09-22 10:58:50\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"141\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:00:39\",\"updated_at\":\"2026-09-22 11:00:39\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"142\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"218\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:01:58\",\"updated_at\":\"2026-09-22 11:01:58\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"148\",\"legacy_submission_id\":null,\"evaluator_id\":\"219\",\"target_user_id\":\"220\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"Palaging Late\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:45:15\",\"updated_at\":\"2026-09-22 11:45:15\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"149\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"220\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"Sleeping\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:52:17\",\"updated_at\":\"2026-09-22 11:52:17\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"150\",\"legacy_submission_id\":null,\"evaluator_id\":\"220\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"3.45\",\"remarks\":\"\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Faculty\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:58:19\",\"updated_at\":\"2026-09-22 11:58:19\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"151\",\"legacy_submission_id\":null,\"evaluator_id\":\"217\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"4.75\",\"remarks\":\"N/A\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Principal\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-23 10:29:32\",\"updated_at\":\"2026-09-23 10:29:32\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"155\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-23 15:51:42\",\"updated_at\":\"2026-09-23 15:51:42\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"156\",\"legacy_submission_id\":null,\"evaluator_id\":\"219\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-24 09:35:00\",\"updated_at\":\"2026-09-24 09:35:00\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"161\",\"legacy_submission_id\":null,\"evaluator_id\":\"233\",\"target_user_id\":\"236\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"7\",\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"4.40\",\"remarks\":\"N/A\",\"eval_type\":\"staff\",\"peer_group\":\"Staff Evaluation\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-28 16:00:48\",\"updated_at\":\"2026-09-28 16:00:48\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"}],\"questionnaire_answers\":[{\"id\":\"852\",\"tracker_id\":\"140\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"853\",\"tracker_id\":\"140\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"854\",\"tracker_id\":\"140\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"855\",\"tracker_id\":\"140\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"856\",\"tracker_id\":\"140\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"857\",\"tracker_id\":\"140\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"858\",\"tracker_id\":\"140\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"859\",\"tracker_id\":\"140\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"860\",\"tracker_id\":\"140\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"861\",\"tracker_id\":\"140\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"862\",\"tracker_id\":\"140\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"906\",\"tracker_id\":\"148\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"907\",\"tracker_id\":\"148\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"908\",\"tracker_id\":\"148\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"909\",\"tracker_id\":\"148\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"910\",\"tracker_id\":\"148\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"911\",\"tracker_id\":\"148\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"912\",\"tracker_id\":\"148\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"913\",\"tracker_id\":\"148\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"914\",\"tracker_id\":\"148\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"915\",\"tracker_id\":\"148\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"916\",\"tracker_id\":\"148\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"917\",\"tracker_id\":\"148\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"918\",\"tracker_id\":\"148\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"919\",\"tracker_id\":\"148\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"920\",\"tracker_id\":\"148\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"921\",\"tracker_id\":\"148\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"922\",\"tracker_id\":\"148\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"923\",\"tracker_id\":\"148\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"924\",\"tracker_id\":\"148\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"925\",\"tracker_id\":\"148\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"926\",\"tracker_id\":\"148\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"927\",\"tracker_id\":\"148\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"928\",\"tracker_id\":\"148\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"929\",\"tracker_id\":\"149\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"930\",\"tracker_id\":\"149\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"931\",\"tracker_id\":\"149\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"932\",\"tracker_id\":\"149\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"933\",\"tracker_id\":\"149\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"934\",\"tracker_id\":\"149\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"935\",\"tracker_id\":\"149\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"936\",\"tracker_id\":\"149\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"937\",\"tracker_id\":\"149\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"938\",\"tracker_id\":\"149\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"939\",\"tracker_id\":\"149\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"940\",\"tracker_id\":\"149\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"941\",\"tracker_id\":\"149\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"942\",\"tracker_id\":\"149\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"943\",\"tracker_id\":\"149\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"944\",\"tracker_id\":\"149\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"945\",\"tracker_id\":\"149\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"946\",\"tracker_id\":\"149\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"947\",\"tracker_id\":\"149\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"948\",\"tracker_id\":\"149\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"949\",\"tracker_id\":\"149\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"950\",\"tracker_id\":\"149\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"951\",\"tracker_id\":\"149\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"952\",\"tracker_id\":\"150\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"953\",\"tracker_id\":\"150\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"954\",\"tracker_id\":\"150\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"955\",\"tracker_id\":\"150\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"956\",\"tracker_id\":\"150\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"957\",\"tracker_id\":\"150\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"958\",\"tracker_id\":\"150\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"959\",\"tracker_id\":\"150\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"960\",\"tracker_id\":\"150\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"961\",\"tracker_id\":\"150\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"962\",\"tracker_id\":\"150\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"1019\",\"tracker_id\":\"155\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1020\",\"tracker_id\":\"155\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1021\",\"tracker_id\":\"155\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1022\",\"tracker_id\":\"155\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1023\",\"tracker_id\":\"155\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1024\",\"tracker_id\":\"155\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1025\",\"tracker_id\":\"155\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1026\",\"tracker_id\":\"155\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1027\",\"tracker_id\":\"155\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1028\",\"tracker_id\":\"155\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1029\",\"tracker_id\":\"155\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1030\",\"tracker_id\":\"155\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1031\",\"tracker_id\":\"155\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1032\",\"tracker_id\":\"155\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1033\",\"tracker_id\":\"155\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1034\",\"tracker_id\":\"155\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1035\",\"tracker_id\":\"155\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1036\",\"tracker_id\":\"155\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1037\",\"tracker_id\":\"155\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1038\",\"tracker_id\":\"155\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1039\",\"tracker_id\":\"155\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1040\",\"tracker_id\":\"155\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1041\",\"tracker_id\":\"155\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1042\",\"tracker_id\":\"155\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1043\",\"tracker_id\":\"155\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1044\",\"tracker_id\":\"155\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1045\",\"tracker_id\":\"155\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1046\",\"tracker_id\":\"155\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1047\",\"tracker_id\":\"155\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1048\",\"tracker_id\":\"156\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1049\",\"tracker_id\":\"156\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1050\",\"tracker_id\":\"156\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1051\",\"tracker_id\":\"156\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1052\",\"tracker_id\":\"156\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1053\",\"tracker_id\":\"156\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1054\",\"tracker_id\":\"156\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1055\",\"tracker_id\":\"156\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1056\",\"tracker_id\":\"156\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1057\",\"tracker_id\":\"156\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1058\",\"tracker_id\":\"156\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1059\",\"tracker_id\":\"156\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1060\",\"tracker_id\":\"156\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1061\",\"tracker_id\":\"156\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1062\",\"tracker_id\":\"156\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1063\",\"tracker_id\":\"156\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1064\",\"tracker_id\":\"156\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1065\",\"tracker_id\":\"156\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1066\",\"tracker_id\":\"156\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1067\",\"tracker_id\":\"156\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1068\",\"tracker_id\":\"156\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1069\",\"tracker_id\":\"156\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1070\",\"tracker_id\":\"156\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1071\",\"tracker_id\":\"156\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1072\",\"tracker_id\":\"156\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1073\",\"tracker_id\":\"156\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1074\",\"tracker_id\":\"156\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1075\",\"tracker_id\":\"156\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1076\",\"tracker_id\":\"156\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1174\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"549\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1175\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"550\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1176\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"551\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1177\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"552\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1178\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"553\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1179\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"554\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1180\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"555\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1181\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"556\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1182\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"557\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1183\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"558\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null}],\"evaluation_answers\":[],\"evaluation_submissions\":[],\"evaluation_results\":[],\"peer_evaluation_submissions\":[],\"peer_evaluation_results\":[],\"evaluation_reminders\":[],\"analytics_reports\":[],\"notifications\":[],\"activity_log_snapshot\":[{\"id\":\"55\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #5)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-09-28 22:21:11\"}]}');

-- --------------------------------------------------------

--
-- Table structure for table `system_documents`
--

CREATE TABLE `system_documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `storage_name` varchar(255) NOT NULL,
  `file_size` varchar(30) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'General',
  `visibility` enum('All','Faculty','Staff','Student','Admin') NOT NULL DEFAULT 'All',
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_documents`
--

INSERT INTO `system_documents` (`id`, `display_name`, `storage_name`, `file_size`, `category`, `visibility`, `uploaded_at`) VALUES
(2, 'SRMS', '1781876422_STUDENT_REGISTRATION_MANAGEMENT_SYSTEM_OF_PANDAN_BAY_INSTITUE_INC_COLLEGE_DEPARTMENT.docx', '7.3 MB', 'Memo', 'All', '2026-06-19 21:40:22'),
(3, 'cbxcbcxv', '1782282475_EVALUATION.docx', '902.7 KB', 'evaluation', 'Staff', '2026-06-24 14:27:55');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(64) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('acad_structure', 'college'),
('acad_term', '1st Semester'),
('acad_year', '2026-2027'),
('auto_schedule', '1'),
('control_mode', 'open'),
('eval_end', '2026-09-28T23:00'),
('eval_start', '2026-09-28T22:15'),
('maintenance', '0'),
('notify_eval_closing', '1'),
('notify_eval_open', '1'),
('notify_faculty_complete', '1'),
('notify_reminders', '0'),
('publish_state', 'published'),
('rule_auto_lock', '0'),
('rule_countdown', '0'),
('rule_edit_after_submit', '0'),
('rule_one_submission', '0'),
('rule_only_during_period', '0'),
('rule_prevent_late', '0'),
('rule_require_all', '0'),
('year_level_edit_mode', 'open');

-- --------------------------------------------------------

--
-- Table structure for table `teaching_assignments`
--

CREATE TABLE `teaching_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `education_level` varchar(20) NOT NULL,
  `year_level` varchar(20) NOT NULL,
  `section` varchar(50) DEFAULT NULL,
  `assigned_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL DEFAULT 'NOT NULL',
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `sector` enum('Teacher','Staff','Student') NOT NULL DEFAULT 'Student',
  `designation` varchar(100) DEFAULT NULL,
  `role` enum('superadmin','admin','executive_assistant','teacher','staff','student','principal','dean') NOT NULL DEFAULT 'student',
  `education_level` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `name` varchar(150) GENERATED ALWAYS AS (`full_name`) VIRTUAL,
  `department` varchar(255) DEFAULT NULL,
  `year_level` varchar(30) DEFAULT NULL,
  `registration_source` enum('self','admin','super_admin') DEFAULT 'self',
  `source` enum('self','admin','admin_nologin') NOT NULL DEFAULT 'self',
  `is_logged_in` tinyint(1) NOT NULL DEFAULT 0,
  `assigned_period` varchar(20) DEFAULT NULL,
  `account_status` varchar(10) NOT NULL DEFAULT 'pending',
  `employee_id` varchar(50) DEFAULT NULL,
  `academic_level` varchar(20) DEFAULT NULL,
  `grade_level` varchar(10) DEFAULT NULL,
  `secondary_role` varchar(20) DEFAULT NULL,
  `id_number` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `email`, `photo`, `sector`, `designation`, `role`, `education_level`, `is_active`, `created_at`, `updated_at`, `department`, `year_level`, `registration_source`, `source`, `is_logged_in`, `assigned_period`, `account_status`, `employee_id`, `academic_level`, `grade_level`, `secondary_role`, `id_number`) VALUES
(157, 'RINALITA', '$2y$10$Hwg1Afa273XUtZk2LUtmmOgUen.3TL.hlM1k4rV9ObyFxBPWMlpoq', 'Rinalita S. Tutor', 'rinalita@gmail.com', NULL, 'Student', 'DEAN', 'dean', 'college', 1, '2026-08-04 17:52:56', '2026-09-23 12:21:44', 'BSIT', NULL, 'self', 'self', 0, NULL, 'approved', '040506', NULL, NULL, NULL, NULL),
(158, 'RAY', '$2y$10$w4.0sOL2y99sh.GLmm.3Que0wRUt.ECQuR/hifUHHEf0ce5os2O7q', 'Evelyn M. Verano', 'ray@gmail.com', NULL, 'Student', NULL, 'principal', 'both', 1, '2026-08-05 15:38:01', '2026-09-19 10:51:11', '', NULL, 'self', 'self', 1, NULL, 'approved', '060708', NULL, NULL, NULL, NULL),
(171, 'jeo', '$2y$10$.qFtE1a2ywTVnQUKGiSWAO.tHmTrkmdYEB.xi2WsmGGUvwnLdsAvm', 'jeo', 'jeo@gmail.com', 'stu_6aac8ee4e9f6e8.04613904.jpg', 'Student', 'Student', 'student', 'senior_high', 1, '2026-08-12 23:45:19', '2026-09-22 09:33:49', 'SHS', 'Grade 11', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(175, 'Dim', '$2y$10$NoPujTO4svEjolJP03d3C.c6jlJG8ShC2B3K/0f7EAwVC57CNiGai', 'Dim Mark Damaso', 'dim@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 1, '2026-08-18 16:28:09', '2026-09-26 22:18:00', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(215, 'Valerie', '$2y$10$7aawtl2Ba53xdo5VBZNSt.Ee4WAs/IZqzB6vifBgP/2Y.GWnj00ge', 'Valerie Jane S. Bendijo', 'valerie@gmail.com', 'usr_6ab1df39eb6404.56845468.jpg', 'Staff', 'BS Nursing', 'staff', NULL, 1, '2026-09-22 09:51:54', '2026-09-22 10:09:01', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(216, 'Stephanie', '$2y$10$8iab0i7yVIqTleak05eDI.NHV5dSFLikKxb4t2wUKjqjng/m3NIde', 'Stephanie M. Puntal', 'stephanie@gmail.com', 'usr_6ab1e532bcc723.93036759.jpg', 'Teacher', 'BS Information Technology  with Certificate in Teaching – Social Studies', 'teacher', NULL, 1, '2026-09-22 10:17:22', '2026-09-22 10:22:24', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(217, 'Cedrick', '$2y$10$QZPaDcfro.BW8A3uSXFgu.o4MwRGMynAhzJFTQvYxTx70iueHoNMO', 'Cedrick Dante Espillo', 'cedrick@gmail.com', 'usr_6ab1e58b175c02.72807880.jpg', 'Teacher', 'Teacher/ Coordinator', 'teacher', NULL, 1, '2026-09-22 10:18:51', '2026-09-22 11:15:25', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(218, 'Maluo', '$2y$10$EGHjk/Jiqo.4hf9hb7xplemI2eCQM1.CbnTyV2AUpN7ibYtBAvvQG', 'Malou De la Torre', 'maluo@gmail.com', 'usr_6ab1e9f519e7f0.00650896.jpg', 'Staff', 'Librarian', 'staff', NULL, 1, '2026-09-22 10:37:41', '2026-09-22 11:08:19', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(219, 'josesantos', '$2y$10$GC8Y.eElesI4rxXGH83TOeJ3laR9J/sxJhDov5/yEn2kIuidZsS0q', 'Jose K. Santos', 'jose@gmail.com', NULL, 'Student', 'Student', 'student', 'junior_high', 1, '2026-09-22 11:33:12', '2026-09-22 11:33:51', 'JHS', 'Grade 7', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(220, 'Em26', '$2y$10$t4w5yiDb8iaUg0tksEMbnuNQ5mcQKQptJSVttiykaW7YAQ.903wre', 'Barcibal Emily R.', 'em@gmail.com', 'usr_6ab1f8a95ddf77.68369057.jpg', 'Teacher', 'Teacher/ Cashier', 'teacher', NULL, 1, '2026-09-22 11:40:25', '2026-09-22 11:54:31', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(222, 'Jen jen', '$2y$10$uMKlZHbQzjYKQq/4d8OLhe4/1fkjKntXxqxGaKpINgZ63fOGD3/hC', 'Biadora Jennifer A.', 'jen@gmail.com', 'usr_6ab340d75b1240.55091137.jpg', 'Teacher', 'Teacher/ ESC/ SHSVP/ TSS Encoder YEARBOOK IN CHARGE', 'teacher', NULL, 1, '2026-09-23 11:00:39', '2026-09-24 16:33:48', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(225, 'Jhong', '$2y$10$BJN/.a6HFx5OQ6T5.xKLFe1WCG80yIBic5fJb5Hkop9cn82fBdsfi', 'Anecito John Kenneth M.', 'jhong@gmail.com', 'usr_6ab34615511401.85637987.jpg', 'Staff', 'Physical Plant Coordinator/ Computer Lab Custodian', 'staff', NULL, 1, '2026-09-23 11:23:01', '2026-09-24 10:48:20', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(226, 'Johnny', '$2y$10$e8RUwdkMbrUh.jVeqyyMe.s3JLncs7O051OeyGs7I3NbxY8G8cr1m', 'Delos Santos Johnny E.', 'johnny@gmail.com', 'usr_6ab346a3ba25d3.72583291.jpg', 'Staff', 'MAINTENANCE OFFICER', 'staff', NULL, 1, '2026-09-23 11:25:23', '2026-09-23 11:25:48', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(227, 'Joselle', '$2y$10$Z5FBPjfYF///65dyfVXmVO/ox.qy5WEN1JpJYPnCOvHYVlCP5a/Ue', 'Sardina Joselle C.', 'joselle@gmail.com', 'usr_6ab3474341f3f6.96519829.jpg', 'Teacher', 'Ang Kingke Adviser/ Grade 7 - St. Albert Adviser/ HS TEACHER', 'teacher', NULL, 1, '2026-09-23 11:28:03', '2026-09-23 11:42:39', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(230, 'Gerald', '$2y$10$GRKf/z5Vy5DEm339bHgoMeXDY7LcxWkzjaYExiw1GwZx558MmPWZS', 'Delos Santos Gerald V.', 'gerald@gmail.com', 'usr_6ab3482a0ff224.35204052.jpg', 'Staff', 'GUIDANCE STAFF/ SPORTS PROGRAM MANAGER/ SDRRM OFFICER', 'staff', NULL, 1, '2026-09-23 11:31:54', '2026-09-24 10:48:38', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(232, 'Raffy', '$2y$10$E3E8KCvHAQeBq3AZbG/Tfuz5ABvL7kBSMvOE1bTnnCpYMzUvDH6s6', 'Arevalo Raffy E.', 'raffy@gmail.com', 'usr_6ab34934f240f1.26407181.jpg', 'Staff', 'School Registrar/ ADMIN COORDINATOR', 'staff', NULL, 1, '2026-09-23 11:36:21', '2026-09-23 11:36:38', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(233, 'Amelia', '$2y$10$B3ILoeABU7l6AIvjvHRfyOnaE.NJlnlmoJtINwJcNiGnPte8YM/7e', 'Candolita Amelia C.', 'amelia@gmail.com', 'usr_6ab34c5cea1ef0.90072910.jpg', 'Staff', 'Bookkeeper', 'staff', NULL, 1, '2026-09-23 11:49:49', '2026-09-23 11:50:55', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(234, 'Jessie', '$2y$10$vCT8odfTibas8JCtgrzNM.FUfmSMBmFSExV79im.0meZQ8AWq5BWG', 'Aquillo Jessie A.', 'jessie@gmail.com', 'usr_6ab34ceaaa0f73.47035302.jpg', 'Staff', 'VE/CLE COORDINATOR/ HS TEACHER', 'staff', NULL, 1, '2026-09-23 11:52:10', '2026-09-23 11:52:55', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(235, 'john', '$2y$10$IDejaBEkRwZY9uEti3fksOV3LWLtlgON7lXo2NLjVgyX/JOpKwM5C', 'Amelia C. Candolita', 'john@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 0, '2026-09-23 16:05:55', '2026-09-24 11:20:11', 'College', '4th Year College', 'self', 'self', 0, NULL, 'blocked', NULL, NULL, NULL, NULL, NULL),
(236, 'Lorraine', '$2y$10$Nk6Rv1yLiqnYF5S/T4fsbOyd6VLKR5Snog3HDp35fa7pkZSDebBsG', 'Lorraine R. Sabay', 'lorraine@gmail.com', 'adm_6ab46d4f554d89.08860627.jpg', 'Student', NULL, 'superadmin', NULL, 1, '2026-09-24 08:22:39', '2026-09-24 14:36:32', NULL, NULL, 'self', 'self', 1, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(237, 'Michel', '$2y$10$oyyMVA80ce7KnMFXg9uxi.xCTKIZMR8xWW2zH1sQvJsEoXnhtmp8W', 'John Michel Sardañas', 'michel@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 1, '2026-09-24 11:22:29', '2026-09-24 11:22:36', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(238, 'Hannah', '$2y$10$7G8dzT9rUZHQl1xvJlXTHubTMqjt5wd6iAWQChY3LQvTEBSz/7nDK', 'Hannah Mae Solangon', 'hannah@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 1, '2026-09-24 11:27:31', '2026-09-24 11:29:35', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(240, 'Dimmy', '$2y$10$RV3/dcsS7Z4SSIR65K7hrez1ybQrvrsn7E7nxErE39ckCp9U60l6y', 'Damaso Dim Mark', 'damasodimmark@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 1, '2026-09-24 16:22:50', '2026-09-24 16:23:13', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_management_log`
--

CREATE TABLE `user_management_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `target_id` int(10) UNSIGNED DEFAULT NULL,
  `action` enum('created','updated','deleted','activated','deactivated','password_reset') NOT NULL,
  `details` text DEFAULT NULL,
  `performed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `email_on_designation_update` tinyint(1) NOT NULL DEFAULT 1,
  `email_on_new_evaluation` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `show_result_details` tinyint(1) NOT NULL DEFAULT 1,
  `compact_dashboard` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`user_id`, `email_on_designation_update`, `email_on_new_evaluation`, `updated_at`, `show_result_details`, `compact_dashboard`) VALUES
(170, 1, 1, '2026-09-21 21:31:41', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_questions`
--

CREATE TABLE `user_questions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `eval_type` varchar(20) NOT NULL DEFAULT 'student',
  `category` varchar(100) NOT NULL DEFAULT 'General',
  `question_text` text NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_added` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_questions`
--

INSERT INTO `user_questions` (`id`, `user_id`, `target_type`, `eval_type`, `category`, `question_text`, `sort_order`, `created_at`, `date_added`) VALUES
(7, 80, 'Staff', 'peer', 'General', 'receives all collections of the school and acknowledges the same by issuing official receipts for such.  *', 1, '2026-06-24 09:14:07', '2026-08-15 04:35:11'),
(8, 80, 'Staff', 'peer', 'General', 'is cordial and accommodating to parents and students * 5', 2, '2026-06-24 09:14:22', '2026-08-15 04:35:11'),
(9, 80, 'Staff', 'peer', 'General', 'Is honest and truthful in every transactions *', 3, '2026-06-24 09:14:30', '2026-08-15 04:35:11'),
(10, 80, 'Staff', 'peer', 'General', 'attends to office duty regularly and avoids being absent *', 4, '2026-06-24 09:14:43', '2026-08-15 04:35:11'),
(11, 75, 'Staff', 'peer', 'General', 'receives, processes, releases on time  school records of students.  *', 1, '2026-06-24 09:15:11', '2026-08-15 04:35:11'),
(12, 75, 'Staff', 'peer', 'General', 'prepares and keeps all students’ records up-to-date such as Permanent Record (School Form 10), Transfer Credentials, Certifications, Diploma, Report on Promotion (School Form 5) and Enrolment Reports.      *', 2, '2026-06-24 09:15:31', '2026-08-15 04:35:11'),
(13, 75, 'Staff', 'peer', 'General', 'prepares and submits academic records of graduating students to the DepEd to secure Special Order for graduation.  *', 3, '2026-06-24 09:15:42', '2026-08-15 04:35:11'),
(14, 81, 'Staff', 'peer', 'General', 'performs records management *', 1, '2026-06-24 09:16:05', '2026-08-15 04:35:11'),
(15, 81, 'Staff', 'peer', 'General', 'conducts library orientation for students    *', 2, '2026-06-24 09:16:13', '2026-08-15 04:35:11'),
(16, 81, 'Staff', 'peer', 'General', 'assists students/staff in the proper use of the library particularly on research works *', 3, '2026-06-24 09:16:22', '2026-08-15 04:35:11'),
(22, 46, 'Faculty', 'student', 'General', 'ffuhvof', 2, '2026-06-24 14:06:40', '2026-08-15 04:35:11'),
(23, 57, 'Faculty', 'student', 'General', 'ffuhvof', 2, '2026-06-24 14:06:40', '2026-08-15 04:35:11'),
(24, 78, 'Faculty', 'student', 'General', 'ffuhvof', 2, '2026-06-24 14:06:40', '2026-08-15 04:35:11'),
(25, 79, 'Faculty', 'student', 'General', 'ffuhvof', 2, '2026-06-24 14:06:40', '2026-08-15 04:35:11'),
(26, 86, 'Faculty', 'student', 'General', 'ffuhvof', 2, '2026-06-24 14:06:40', '2026-08-15 04:35:11'),
(27, 46, 'Faculty', 'student', 'General', 'tyfu', 3, '2026-06-24 14:10:10', '2026-08-15 04:35:11'),
(28, 57, 'Faculty', 'student', 'General', 'tyfu', 3, '2026-06-24 14:10:10', '2026-08-15 04:35:11'),
(29, 78, 'Faculty', 'student', 'General', 'tyfu', 3, '2026-06-24 14:10:10', '2026-08-15 04:35:11'),
(30, 79, 'Faculty', 'student', 'General', 'tyfu', 3, '2026-06-24 14:10:10', '2026-08-15 04:35:11'),
(31, 86, 'Faculty', 'student', 'General', 'tyfu', 3, '2026-06-24 14:10:10', '2026-08-15 04:35:11'),
(32, 46, 'Faculty', 'student', 'General', 'yduf', 4, '2026-06-24 14:10:12', '2026-08-15 04:35:11'),
(33, 57, 'Faculty', 'student', 'General', 'yduf', 4, '2026-06-24 14:10:12', '2026-08-15 04:35:11'),
(34, 78, 'Faculty', 'student', 'General', 'yduf', 4, '2026-06-24 14:10:12', '2026-08-15 04:35:11'),
(35, 79, 'Faculty', 'student', 'General', 'yduf', 4, '2026-06-24 14:10:12', '2026-08-15 04:35:11'),
(36, 86, 'Faculty', 'student', 'General', 'yduf', 4, '2026-06-24 14:10:12', '2026-08-15 04:35:11'),
(37, 84, 'Staff', 'student', 'Professionalism', 'Attends school on time', 1, '2026-06-25 02:41:21', '2026-08-15 04:35:11'),
(38, 84, 'Staff', 'peer', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 1, '2026-06-25 02:43:28', '2026-08-15 04:35:11'),
(39, 84, 'Staff', 'student', 'Administrative Functions', 'Implements diocesan and DepEd policies and directives.', 2, '2026-07-15 13:11:04', '2026-08-15 04:35:11'),
(41, 84, 'Staff', 'student', 'Administrative Functions', 'Cooperates and works with the Director/Superintendent and ADCE.', 3, '2026-07-15 13:11:35', '2026-08-15 04:35:11'),
(42, 84, 'Staff', 'student', 'Administrative Functions', 'Reviews school objectives yearly in consultation with faculty, parents, and students.', 4, '2026-07-15 13:11:48', '2026-08-15 04:35:11'),
(43, 84, 'Staff', 'student', 'Administrative Functions', 'Prepares and submits a written annual report to the Board of Trustees.', 5, '2026-07-15 13:12:01', '2026-08-15 04:35:11'),
(44, 84, 'Staff', 'student', 'Administrative Functions', 'Carries out school objectives and policies within existing laws and regulations.', 6, '2026-07-15 13:12:11', '2026-08-15 04:35:11'),
(45, 84, 'Staff', 'student', 'Professionalism', 'Integrates faith with the learning process in line with the school mission.', 7, '2026-07-15 13:12:26', '2026-08-15 04:35:11'),
(46, 84, 'Staff', 'student', 'Professionalism', 'Ensures all religious, academic, and student programs reflect the Catholic mission and school identity.', 8, '2026-07-15 13:12:42', '2026-08-15 04:35:11'),
(47, 84, 'Staff', 'student', 'Professionalism', 'Implements religious instruction programs prescribed by the diocese.', 9, '2026-07-15 13:12:47', '2026-08-15 04:35:11'),
(48, 84, 'Staff', 'student', 'Professionalism', 'Implements spiritual life programs for faculty and staff.', 10, '2026-07-15 13:13:02', '2026-08-15 04:35:11'),
(49, 84, 'Staff', 'student', 'Professionalism', 'Implements spiritual programs for students including liturgy, prayer, recollections, retreats, service-learning, and parish relations.', 11, '2026-07-15 13:13:32', '2026-08-15 04:35:11'),
(51, 80, 'Staff', 'student', 'dsgfg', 'dfhgfdh', 1, '2026-07-20 09:17:07', '2026-08-15 04:35:11'),
(52, 80, 'Staff', 'student', 'dsgfg', 'dsgfdg', 2, '2026-07-20 09:17:12', '2026-08-15 04:35:11'),
(53, 80, 'Staff', 'student', 'dsgfg', 'fdgfdg', 3, '2026-07-20 09:17:18', '2026-08-15 04:35:11'),
(58, 96, 'Staff', 'student', 'Professionalism', 'Arrives at school on time', 1, '2026-07-24 05:29:45', '2026-08-15 04:35:11'),
(59, 101, 'Staff', 'student', 'Professionalism', 'Arrives at school on time', 1, '2026-07-24 05:33:00', '2026-08-15 04:35:11'),
(60, 101, 'Staff', 'student', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 2, '2026-07-24 05:33:22', '2026-08-15 04:35:11'),
(61, 101, 'Staff', 'student', 'Professionalism', 'Demonstrates punctuality, excellent attendance', 3, '2026-07-24 05:33:45', '2026-08-15 04:35:11'),
(62, 96, 'Staff', 'student', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 2, '2026-07-24 05:34:02', '2026-08-15 04:35:11'),
(63, 96, 'Staff', 'student', 'Professionalism', 'Demonstrates punctuality, excellent attendance', 3, '2026-07-24 05:34:09', '2026-08-15 04:35:11'),
(64, 96, 'Staff', 'student', 'fgdfg', 'fghfgh', 4, '2026-07-26 06:50:48', '2026-08-15 04:35:11'),
(65, 96, 'Staff', 'student', 'fgdfg', 'fghfgh', 5, '2026-07-26 06:50:51', '2026-08-15 04:35:11'),
(66, 96, 'Staff', 'student', 'fgdfg', 'fghfgh', 6, '2026-07-26 06:50:55', '2026-08-15 04:35:11'),
(67, 127, 'Staff', 'student', 'Professionalism', 'informs the class in advance of any schedule of the day or directives from the office *', 1, '2026-07-29 08:33:21', '2026-08-15 04:35:11'),
(68, 127, 'Staff', 'student', 'Professionalism', 'Arrives at class on time.', 2, '2026-07-29 08:34:05', '2026-08-15 04:35:11'),
(79, 158, 'Principal', 'school_head', 'General', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 2, '2026-08-16 05:06:49', '2026-08-16 05:06:49'),
(80, 157, 'Dean', 'school_head', 'Cooperaton', 'Demonstrates punctuality, excellent attendance *', 3, '2026-08-16 05:28:39', '2026-08-16 05:28:39'),
(82, 157, 'Dean', 'school_head', 'Cooperaton', 'Demonstrates punctuality, excellent attendance *', 4, '2026-08-16 08:30:46', '2026-08-16 08:30:46'),
(83, 157, 'Dean', 'school_head', 'Cooperaton', 'Implements diocesan and DepEd policies and directives.', 5, '2026-08-16 08:31:09', '2026-08-16 08:31:09'),
(92, 152, 'Multi-Role', 'student', 'Cashiering & Financial Service', 'Clearly explains lessons and course-related concepts.', 4, '2026-08-20 09:38:54', '2026-08-20 09:38:54'),
(93, 152, 'Multi-Role', 'student', 'Cashiering & Financial Service', 'Provides appropriate guidance to 4th Year College students.', 5, '2026-08-20 09:39:20', '2026-08-20 09:39:20'),
(94, 152, 'Multi-Role', 'student', 'Cashiering & Financial Service', 'Demonstrates adequate knowledge of the subject being taught.', 6, '2026-08-20 09:39:31', '2026-08-20 09:39:31'),
(95, 152, 'Multi-Role', 'student', 'Cashiering & Financial Service', 'Encourages students to participate in learning activities.', 7, '2026-08-20 09:39:41', '2026-08-20 09:39:41'),
(96, 152, 'Multi-Role', 'student', 'Cashiering & Financial Service', 'Provides helpful feedback regarding student work.', 8, '2026-08-20 09:39:49', '2026-08-20 09:39:49'),
(97, 152, 'Multi-Role', 'student', 'Cashiering & Financial Service', 'Communicates instructions clearly.', 9, '2026-08-20 09:39:55', '2026-08-20 09:39:55'),
(98, 152, 'Multi-Role', 'student', 'Cashiering & Financial Service', 'Responds appropriately to students\' academic concerns.', 10, '2026-08-20 09:40:04', '2026-08-20 09:40:04'),
(99, 152, 'Multi-Role', 'student', 'Cashiering & Financial Service', 'Uses appropriate activities to support student learning.', 11, '2026-08-20 09:40:13', '2026-08-20 09:40:13'),
(100, 152, 'Multi-Role', 'student', 'Cashiering & Financial Service', 'Treats students fairly and respectfully.', 12, '2026-08-20 09:40:19', '2026-08-20 09:40:19'),
(101, 152, 'Multi-Role', 'student', 'Cashiering & Financial Service', 'Performs her teaching assignment responsibly.', 13, '2026-08-20 09:40:25', '2026-08-20 09:40:25'),
(102, 146, 'Multi-Role', 'student', 'Professionalism & Responsibility', 'Performs his additional responsibilities in an organized and professional manner.', 2, '2026-08-20 09:40:53', '2026-08-20 09:40:53'),
(103, 146, 'Multi-Role', 'student', 'Professionalism & Responsibility', 'Responds appropriately to students who need guidance or assistance.', 3, '2026-08-20 09:41:06', '2026-08-20 09:41:06'),
(104, 146, 'Multi-Role', 'student', 'Professionalism & Responsibility', 'Provides appropriate support during sports-related activities and programs.', 4, '2026-08-20 09:41:11', '2026-08-20 09:41:11'),
(105, 146, 'Multi-Role', 'student', 'Professionalism & Responsibility', 'Helps ensure that sports activities are properly organized and conducted.', 5, '2026-08-20 09:41:21', '2026-08-20 09:41:21'),
(106, 146, 'Multi-Role', 'student', 'Professionalism & Responsibility', 'Demonstrates preparedness during school emergency and disaster-related activities.', 6, '2026-08-20 09:41:30', '2026-08-20 09:41:30'),
(107, 146, 'Multi-Role', 'student', 'Professionalism & Responsibility', 'Responds promptly and appropriately during emergency situations.', 7, '2026-08-20 09:41:48', '2026-08-20 09:41:48'),
(108, 146, 'Multi-Role', 'student', 'Professionalism & Responsibility', 'Promotes student safety during school activities and events.', 8, '2026-08-20 09:41:56', '2026-08-20 09:41:56'),
(109, 146, 'Multi-Role', 'student', 'Professionalism & Responsibility', 'Coordinates effectively with students, teachers, and school personnel.', 9, '2026-08-20 09:42:11', '2026-08-20 09:42:11'),
(110, 146, 'Multi-Role', 'student', 'Professionalism & Responsibility', 'Demonstrates fairness and respect when handling student concerns.', 10, '2026-08-20 09:42:17', '2026-08-20 09:42:17'),
(111, 146, 'Multi-Role', 'student', 'Professionalism & Responsibility', 'Effectively balances his regular duties with his additional responsibilities.', 11, '2026-08-20 09:42:25', '2026-08-20 09:42:25'),
(112, 160, 'Multi-Role', 'student', 'Teaching & Technical Support', 'Clearly explains programming concepts to students.', 2, '2026-08-20 09:43:07', '2026-08-20 09:43:07'),
(113, 160, 'Multi-Role', 'student', 'Teaching & Technical Support', 'Demonstrates sufficient knowledge of computer programming.', 3, '2026-08-20 09:43:15', '2026-08-20 09:43:15'),
(114, 160, 'Multi-Role', 'student', 'Teaching & Technical Support', 'Provides useful guidance during programming activities and exercises.', 4, '2026-08-20 09:43:22', '2026-08-20 09:43:22'),
(115, 160, 'Multi-Role', 'student', 'Teaching & Technical Support', 'Helps students understand and apply programming concepts in practical tasks.', 5, '2026-08-20 09:43:29', '2026-08-20 09:43:29'),
(116, 160, 'Multi-Role', 'student', 'Teaching & Technical Support', 'Encourages students to develop problem-solving skills through programming.', 6, '2026-08-20 09:43:38', '2026-08-20 09:43:38'),
(117, 160, 'Multi-Role', 'student', 'Teaching & Technical Support', 'Provides appropriate assistance when students encounter technical difficulties.', 7, '2026-08-20 09:43:45', '2026-08-20 09:43:45'),
(118, 160, 'Multi-Role', 'student', 'Teaching & Technical Support', 'Uses appropriate programming examples and activities to support learning.', 8, '2026-08-20 09:43:52', '2026-08-20 09:43:52'),
(119, 160, 'Multi-Role', 'student', 'Teaching & Technical Support', 'Communicates technical information clearly and understandably.', 9, '2026-08-20 09:43:59', '2026-08-20 09:43:59'),
(120, 160, 'Multi-Role', 'student', 'Teaching & Technical Support', 'Encourages students to apply programming knowledge to practical situations.', 10, '2026-08-20 09:44:08', '2026-08-20 09:44:08'),
(121, 160, 'Multi-Role', 'student', 'Teaching & Technical Support', 'Effectively performs her additional programming-related responsibilities.', 11, '2026-08-20 09:44:14', '2026-08-20 09:44:14'),
(122, 136, 'Multi-Role', 'student', 'Facilities & Laboratory Management', 'Helps maintain the cleanliness and orderliness of school facilities.', 4, '2026-08-20 09:44:53', '2026-08-20 09:44:53'),
(123, 136, 'Multi-Role', 'student', 'Facilities & Laboratory Management', 'Ensures that assigned physical facilities are properly maintained.', 5, '2026-08-20 09:45:01', '2026-08-20 09:45:01'),
(124, 136, 'Multi-Role', 'student', 'Facilities & Laboratory Management', 'Keeps the computer laboratory organized and ready for use.', 6, '2026-08-20 09:45:09', '2026-08-20 09:45:09'),
(126, 136, 'Multi-Role', 'student', 'Facilities & Laboratory Management', 'Handles computer laboratory equipment responsibly.', 8, '2026-08-20 09:45:24', '2026-08-20 09:45:24'),
(127, 136, 'Multi-Role', 'student', 'Facilities & Laboratory Management', 'Reports damaged or malfunctioning equipment appropriately.', 9, '2026-08-20 09:45:39', '2026-08-20 09:45:39'),
(128, 136, 'Multi-Role', 'student', 'Facilities & Laboratory Management', 'Helps ensure that laboratory equipment is used properly.', 10, '2026-08-20 09:45:54', '2026-08-20 09:45:54'),
(129, 136, 'Multi-Role', 'student', 'Facilities & Laboratory Management', 'Responds promptly to facility or laboratory concerns.', 11, '2026-08-20 09:46:10', '2026-08-20 09:46:10'),
(130, 136, 'Multi-Role', 'student', 'Facilities & Laboratory Management', 'Promotes safety and proper conduct within the computer laboratory.', 12, '2026-08-20 09:46:19', '2026-08-20 09:46:19'),
(131, 136, 'Multi-Role', 'student', 'Facilities & Laboratory Management', 'Coordinates effectively with students and school personnel regarding facility concerns.', 13, '2026-08-20 09:46:26', '2026-08-20 09:46:26'),
(132, 136, 'Multi-Role', 'student', 'Facilities & Laboratory Management', 'Performs his additional facility and laboratory responsibilities efficiently.', 14, '2026-08-20 09:46:34', '2026-08-20 09:46:34'),
(133, 176, 'Multi-Role', 'student', 'Personnel / Registrar', 'Handles student and personnel records accurately.', 1, '2026-08-20 09:47:39', '2026-08-20 09:47:39'),
(134, 176, 'Multi-Role', 'student', 'Personnel / Registrar', 'Processes registrar-related requests efficiently.', 2, '2026-08-20 09:47:48', '2026-08-20 09:47:48'),
(135, 176, 'Multi-Role', 'student', 'Personnel / Registrar', 'Provides clear information regarding records and documents.', 3, '2026-08-20 09:47:56', '2026-08-20 09:47:56'),
(136, 176, 'Multi-Role', 'student', 'Personnel / Registrar', 'Maintains confidentiality of student and personnel information.', 4, '2026-08-20 09:48:05', '2026-08-20 09:48:05'),
(137, 176, 'Multi-Role', 'student', 'Personnel / Registrar', 'Responds promptly to requests and inquiries.', 5, '2026-08-20 09:48:12', '2026-08-20 09:48:12'),
(138, 176, 'Multi-Role', 'student', 'Personnel / Registrar', 'Keeps records organized and properly maintained.', 6, '2026-08-20 09:48:31', '2026-08-20 09:48:31'),
(139, 176, 'Multi-Role', 'student', 'Personnel / Registrar', 'Demonstrates accuracy when preparing or releasing documents.', 7, '2026-08-20 09:48:49', '2026-08-20 09:48:49'),
(140, 176, 'Multi-Role', 'student', 'Personnel / Registrar', 'Treats students and personnel respectfully.', 8, '2026-08-20 09:49:03', '2026-08-20 09:49:03'),
(141, 176, 'Multi-Role', 'student', 'Personnel / Registrar', 'Follows appropriate procedures when handling official records.', 9, '2026-08-20 09:49:14', '2026-08-20 09:49:14'),
(142, 176, 'Multi-Role', 'student', 'Personnel / Registrar', 'Performs registrar and personnel-related responsibilities professionally.', 10, '2026-08-20 09:49:23', '2026-08-20 09:49:23'),
(153, 158, 'Principal', 'school_head', 'Administrative Functions', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 3, '2026-08-21 03:58:02', '2026-08-21 03:58:02'),
(154, 158, 'Principal', 'ea', 'Leadership & Direction', 'Provides clear direction and leadership for the school.', 1, '2026-08-23 02:07:58', '2026-08-23 02:07:58'),
(155, 158, 'Principal', 'ea', 'Leadership & Direction', 'Makes decisions that support the institution’s goals and policies.', 2, '2026-08-23 02:07:58', '2026-08-23 02:07:58'),
(156, 158, 'Principal', 'ea', 'Professionalism', 'Demonstrates professionalism, fairness, and accountability.', 3, '2026-08-23 02:07:58', '2026-08-23 02:07:58'),
(157, 158, 'Principal', 'ea', 'Communication', 'Communicates policies, expectations, and important information clearly.', 4, '2026-08-23 02:07:58', '2026-08-23 02:07:58'),
(158, 158, 'Principal', 'ea', 'People Management', 'Promotes a respectful, supportive, and productive workplace.', 5, '2026-08-23 02:07:58', '2026-08-23 02:07:58'),
(159, 158, 'Principal', 'ea', 'Performance', 'Responds appropriately to concerns and opportunities for improvement.', 6, '2026-08-23 02:07:58', '2026-08-23 02:07:58'),
(160, 157, 'Dean', 'ea', 'Academic Leadership', 'Provides effective academic leadership for the college.', 1, '2026-08-23 02:08:07', '2026-08-23 02:08:07'),
(161, 157, 'Dean', 'ea', 'Academic Leadership', 'Supports quality instruction and continuous academic improvement.', 2, '2026-08-23 02:08:07', '2026-08-23 02:08:07'),
(162, 157, 'Dean', 'ea', 'Professionalism', 'Demonstrates fairness, integrity, and accountability in decisions.', 3, '2026-08-23 02:08:07', '2026-08-23 02:08:07'),
(163, 157, 'Dean', 'ea', 'Communication', 'Communicates academic plans, policies, and expectations clearly.', 4, '2026-08-23 02:08:07', '2026-08-23 02:08:07'),
(164, 157, 'Dean', 'ea', 'People Management', 'Supports faculty and staff and addresses concerns constructively.', 5, '2026-08-23 02:08:07', '2026-08-23 02:08:07'),
(165, 157, 'Dean', 'ea', 'Performance', 'Uses resources and decisions responsibly to achieve institutional goals.', 6, '2026-08-23 02:08:07', '2026-08-23 02:08:07'),
(166, 172, 'Staff', 'ea', 'Work Performance', 'Performs assigned duties accurately and consistently.', 1, '2026-08-23 02:08:29', '2026-08-23 02:08:29'),
(167, 172, 'Staff', 'ea', 'Work Performance', 'Completes responsibilities efficiently and on time.', 2, '2026-08-23 02:08:29', '2026-08-23 02:08:29'),
(168, 172, 'Staff', 'ea', 'Service Quality', 'Provides courteous, helpful, and responsive service.', 3, '2026-08-23 02:08:29', '2026-08-23 02:08:29'),
(169, 172, 'Staff', 'ea', 'Professionalism', 'Demonstrates professionalism, reliability, and accountability.', 4, '2026-08-23 02:08:29', '2026-08-23 02:08:29'),
(170, 172, 'Staff', 'ea', 'Communication', 'Communicates clearly and respectfully with clients and coworkers.', 5, '2026-08-23 02:08:29', '2026-08-23 02:08:29'),
(171, 172, 'Staff', 'ea', 'Teamwork', 'Works cooperatively with other members of the institution.', 6, '2026-08-23 02:08:29', '2026-08-23 02:08:29'),
(172, 172, 'Staff', 'ea', 'Work Performance', 'Performs assigned duties accurately and consistently.', 1, '2026-08-23 02:09:00', '2026-08-23 02:09:00'),
(173, 172, 'Staff', 'ea', 'Work Performance', 'Completes responsibilities efficiently and on time.', 2, '2026-08-23 02:09:00', '2026-08-23 02:09:00'),
(174, 172, 'Staff', 'ea', 'Service Quality', 'Provides courteous, helpful, and responsive service.', 3, '2026-08-23 02:09:00', '2026-08-23 02:09:00'),
(175, 172, 'Staff', 'ea', 'Professionalism', 'Demonstrates professionalism, reliability, and accountability.', 4, '2026-08-23 02:09:00', '2026-08-23 02:09:00'),
(176, 172, 'Staff', 'ea', 'Communication', 'Communicates clearly and respectfully with clients and coworkers.', 5, '2026-08-23 02:09:01', '2026-08-23 02:09:01'),
(177, 172, 'Staff', 'ea', 'Teamwork', 'Works cooperatively with other members of the institution.', 6, '2026-08-23 02:09:01', '2026-08-23 02:09:01'),
(178, 172, 'Staff', 'ea', 'Work Performance', 'Performs assigned duties accurately and consistently.', 1, '2026-08-23 06:33:24', '2026-08-23 06:33:24'),
(179, 172, 'Staff', 'ea', 'Work Performance', 'Completes responsibilities efficiently and on time.', 2, '2026-08-23 06:33:24', '2026-08-23 06:33:24'),
(180, 172, 'Staff', 'ea', 'Service Quality', 'Provides courteous, helpful, and responsive service.', 3, '2026-08-23 06:33:24', '2026-08-23 06:33:24'),
(181, 172, 'Staff', 'ea', 'Professionalism', 'Demonstrates professionalism, reliability, and accountability.', 4, '2026-08-23 06:33:24', '2026-08-23 06:33:24'),
(182, 172, 'Staff', 'ea', 'Communication', 'Communicates clearly and respectfully with clients and coworkers.', 5, '2026-08-23 06:33:24', '2026-08-23 06:33:24'),
(183, 172, 'Staff', 'ea', 'Teamwork', 'Works cooperatively with other members of the institution.', 6, '2026-08-23 06:33:24', '2026-08-23 06:33:24'),
(184, 172, 'Staff', 'ea', 'Work Performance', 'Performs assigned duties accurately and consistently.', 1, '2026-08-23 06:34:19', '2026-08-23 06:34:19'),
(185, 172, 'Staff', 'ea', 'Work Performance', 'Completes responsibilities efficiently and on time.', 2, '2026-08-23 06:34:19', '2026-08-23 06:34:19'),
(186, 172, 'Staff', 'ea', 'Service Quality', 'Provides courteous, helpful, and responsive service.', 3, '2026-08-23 06:34:19', '2026-08-23 06:34:19'),
(187, 172, 'Staff', 'ea', 'Professionalism', 'Demonstrates professionalism, reliability, and accountability.', 4, '2026-08-23 06:34:19', '2026-08-23 06:34:19'),
(188, 172, 'Staff', 'ea', 'Communication', 'Communicates clearly and respectfully with clients and coworkers.', 5, '2026-08-23 06:34:19', '2026-08-23 06:34:19'),
(189, 172, 'Staff', 'ea', 'Teamwork', 'Works cooperatively with other members of the institution.', 6, '2026-08-23 06:34:19', '2026-08-23 06:34:19'),
(190, 172, 'Staff', 'ea', 'Work Performance', 'Performs assigned duties accurately and consistently.', 1, '2026-08-24 01:31:01', '2026-08-24 01:31:01'),
(191, 172, 'Staff', 'ea', 'Work Performance', 'Completes responsibilities efficiently and on time.', 2, '2026-08-24 01:31:01', '2026-08-24 01:31:01'),
(192, 172, 'Staff', 'ea', 'Service Quality', 'Provides courteous, helpful, and responsive service.', 3, '2026-08-24 01:31:01', '2026-08-24 01:31:01'),
(193, 172, 'Staff', 'ea', 'Professionalism', 'Demonstrates professionalism, reliability, and accountability.', 4, '2026-08-24 01:31:01', '2026-08-24 01:31:01'),
(194, 172, 'Staff', 'ea', 'Communication', 'Communicates clearly and respectfully with clients and coworkers.', 5, '2026-08-24 01:31:01', '2026-08-24 01:31:01'),
(195, 172, 'Staff', 'ea', 'Teamwork', 'Works cooperatively with other members of the institution.', 6, '2026-08-24 01:31:01', '2026-08-24 01:31:01'),
(199, 172, 'Staff', 'student', 'Staff Effectiveness', 'Performs assigned duties and responsibilities effectively.', 1, '2026-08-26 02:28:05', '2026-08-26 02:28:05'),
(200, 172, 'Staff', 'student', 'Staff Effectiveness', 'Completes assigned tasks accurately on time.', 2, '2026-08-26 02:28:29', '2026-08-26 02:28:29'),
(201, 172, 'Staff', 'student', 'Staff Effectiveness', 'Follows school policies, and procedures consistently.', 3, '2026-08-26 02:29:01', '2026-08-26 02:29:01'),
(202, 172, 'Staff', 'student', 'Staff Effectiveness', 'Demonstrates professionalism while performing assigned duties.', 4, '2026-08-26 02:29:42', '2026-08-26 02:29:42'),
(203, 172, 'Staff', 'student', 'Staff Effectiveness', 'Communicates clearly and effectively.', 5, '2026-08-26 02:30:05', '2026-08-26 02:30:05'),
(204, 146, 'Staff', 'student', 'Staff Effectiveness', 'Performs assigned duties and responsibilities effectively.', 12, '2026-08-26 02:30:23', '2026-08-26 02:30:23'),
(205, 146, 'Staff', 'student', 'Staff Effectiveness', 'Completes assigned tasks accurately on time.', 13, '2026-08-26 02:30:38', '2026-08-26 02:30:38'),
(206, 146, 'Staff', 'student', 'Staff Effectiveness', 'Follows school policies, and procedures consistently.', 14, '2026-08-26 02:30:49', '2026-08-26 02:30:49'),
(207, 146, 'Staff', 'student', 'Staff Effectiveness', 'Demonstrates professionalism while performing assigned duties.', 15, '2026-08-26 02:30:56', '2026-08-26 02:30:56'),
(208, 146, 'Staff', 'student', 'Staff Effectiveness', 'Communicates clearly and effectively.', 16, '2026-08-26 02:31:05', '2026-08-26 02:31:05'),
(209, 136, 'Staff', 'student', 'Staff Effectiveness', 'Performs assigned duties and responsibilities effectively.', 15, '2026-08-26 02:31:19', '2026-08-26 02:31:19'),
(210, 136, 'Staff', 'student', 'Staff Effectiveness', 'Completes assigned tasks accurately on time.', 16, '2026-08-26 02:31:27', '2026-08-26 02:31:27'),
(211, 136, 'Staff', 'student', 'Staff Effectiveness', 'Follows school policies, and procedures consistently.', 17, '2026-08-26 02:31:33', '2026-08-26 02:31:33'),
(212, 136, 'Staff', 'student', 'Staff Effectiveness', 'Demonstrates professionalism while performing assigned duties.', 18, '2026-08-26 02:31:39', '2026-08-26 02:31:39'),
(213, 136, 'Staff', 'student', 'Staff Effectiveness', 'Communicates clearly and effectively.', 19, '2026-08-26 02:31:44', '2026-08-26 02:31:44'),
(214, 152, 'Staff', 'student', 'Staff Effectiveness', 'Performs assigned duties and responsibilities effectively.', 14, '2026-08-26 02:31:58', '2026-08-26 02:31:58'),
(215, 152, 'Staff', 'student', 'Staff Effectiveness', 'Completes assigned tasks accurately on time.', 15, '2026-08-26 02:32:13', '2026-08-26 02:32:13'),
(216, 152, 'Staff', 'student', 'Staff Effectiveness', 'Follows school policies, and procedures consistently.', 16, '2026-08-26 02:32:20', '2026-08-26 02:32:20'),
(217, 152, 'Staff', 'student', 'Staff Effectiveness', 'Demonstrates professionalism while performing assigned duties.', 17, '2026-08-26 02:32:27', '2026-08-26 02:32:27'),
(218, 152, 'Staff', 'student', 'Staff Effectiveness', 'Communicates clearly and effectively.', 18, '2026-08-26 02:32:34', '2026-08-26 02:32:34'),
(219, 181, 'Staff', 'student', 'Staff Effectiveness', 'Performs assigned duties and responsibilities effectively.', 1, '2026-08-26 02:32:45', '2026-08-26 02:32:45'),
(220, 181, 'Staff', 'student', 'Staff Effectiveness', 'Completes assigned tasks accurately on time.', 2, '2026-08-26 02:32:52', '2026-08-26 02:32:52'),
(221, 181, 'Staff', 'student', 'Staff Effectiveness', 'Follows school policies, and procedures consistently.', 3, '2026-08-26 02:32:59', '2026-08-26 02:32:59'),
(222, 181, 'Staff', 'student', 'Staff Effectiveness', 'Demonstrates professionalism while performing assigned duties.', 4, '2026-08-26 02:33:09', '2026-08-26 02:33:09'),
(223, 181, 'Staff', 'student', 'Staff Effectiveness', 'Communicates clearly and effectively.', 5, '2026-08-26 02:33:17', '2026-08-26 02:33:17'),
(224, 136, 'Staff', 'peer', 'Work Performance', 'Performs assigned duties and responsibilities effectively.', 1, '2026-08-26 04:07:09', '2026-08-26 04:07:09'),
(225, 136, 'Staff', 'peer', 'Work Performance', 'Completes tasks accurately and on time.', 2, '2026-08-26 04:07:17', '2026-08-26 04:07:17'),
(226, 136, 'Staff', 'peer', 'Work Performance', 'Demonstrates knowledge of assigned responsibilities.', 3, '2026-08-26 04:07:34', '2026-08-26 04:07:34'),
(227, 136, 'Staff', 'peer', 'Work Performance', 'Maintains quality and consistency in work.', 4, '2026-08-26 04:07:45', '2026-08-26 04:07:45'),
(228, 136, 'Staff', 'peer', 'Work Performance', 'Handles work-related responsibilities efficiently.', 5, '2026-08-26 04:07:58', '2026-08-26 04:07:58'),
(229, 136, 'Staff', 'peer', 'Work Performance', 'Takes initiative in completing assigned tasks.', 6, '2026-08-26 04:08:08', '2026-08-26 04:08:08'),
(230, 136, 'Staff', 'peer', 'Work Performance', 'Follows established procedures and guidelines.', 7, '2026-08-26 04:08:14', '2026-08-26 04:08:14'),
(232, 158, 'Principal', 'student', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 1, '2026-08-28 08:10:24', '2026-08-28 08:10:24'),
(233, 158, 'School', 'peer', 'Cooperaton', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 1, '2026-08-28 08:11:19', '2026-08-28 08:11:19'),
(234, 172, 'Staff', 'school_head', 'Professionalism', 'Demonstrates punctuality, excellent attendance *', 1, '2026-09-12 08:53:23', '2026-09-12 08:53:23'),
(235, 172, 'Staff', 'school_head', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 2, '2026-09-12 08:53:31', '2026-09-12 08:53:31'),
(236, 198, 'Staff', 'student', 'Professionalism', 'Demonstrates effective teaching strategies and methods.', 1, '2026-09-13 10:13:22', '2026-09-13 10:13:22'),
(237, 172, 'Staff', 'peer', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 1, '2026-09-13 10:52:10', '2026-09-13 10:52:10'),
(238, 172, 'Staff', 'peer', 'Professionalism', 'Demonstrates punctuality, excellent attendance', 2, '2026-09-13 10:52:20', '2026-09-13 10:52:20'),
(239, 157, 'School', 'peer', 'Professionalism', 'Demonstrates punctuality, excellent attendance', 1, '2026-09-14 00:39:01', '2026-09-14 00:39:01'),
(240, 157, 'School', 'peer', 'Professionalism', 'Demonstrates punctuality, excellent attendance', 2, '2026-09-14 00:39:41', '2026-09-14 00:39:41'),
(241, 157, 'School', 'peer', 'Teaching Effectiveness', 'Clearly explains lessons and course-related concepts.', 3, '2026-09-14 00:39:52', '2026-09-14 00:39:52'),
(242, 157, 'School', 'peer', 'Teaching Effectiveness', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 4, '2026-09-14 00:40:00', '2026-09-14 00:40:00'),
(243, 157, 'Dean', 'student', 'Professionalism', 'Demonstrates effective teaching strategies and methods.', 1, '2026-09-14 00:42:14', '2026-09-14 00:42:14'),
(244, 158, 'Principal', 'student', 'Professionalism', 'Demonstrates punctuality, excellent attendance', 2, '2026-09-14 00:54:41', '2026-09-14 00:54:41'),
(245, 158, 'Principal', 'student', 'Professionalism', 'Demonstrates effective teaching strategies and methods.', 3, '2026-09-14 00:55:00', '2026-09-14 00:55:00'),
(246, 157, 'Dean', 'student', 'Cooperaton', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 2, '2026-09-14 00:55:37', '2026-09-14 00:55:37'),
(247, 157, 'Dean', 'student', 'Cooperaton', 'Clearly explains lessons and course-related concepts.', 3, '2026-09-14 00:55:43', '2026-09-14 00:55:43'),
(248, 84, 'Staff', 'ea', 'Professionalism', 'Attends school on time', 1, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(249, 84, 'Staff', 'ea', 'Administrative Functions', 'Implements diocesan and DepEd policies and directives.', 2, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(250, 84, 'Staff', 'ea', 'Administrative Functions', 'Cooperates and works with the Director/Superintendent and ADCE.', 3, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(251, 84, 'Staff', 'ea', 'Administrative Functions', 'Reviews school objectives yearly in consultation with faculty, parents, and students.', 4, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(252, 84, 'Staff', 'ea', 'Administrative Functions', 'Prepares and submits a written annual report to the Board of Trustees.', 5, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(253, 84, 'Staff', 'ea', 'Administrative Functions', 'Carries out school objectives and policies within existing laws and regulations.', 6, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(254, 84, 'Staff', 'ea', 'Professionalism', 'Integrates faith with the learning process in line with the school mission.', 7, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(255, 84, 'Staff', 'ea', 'Professionalism', 'Ensures all religious, academic, and student programs reflect the Catholic mission and school identity.', 8, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(256, 84, 'Staff', 'ea', 'Professionalism', 'Implements religious instruction programs prescribed by the diocese.', 9, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(257, 84, 'Staff', 'ea', 'Professionalism', 'Implements spiritual life programs for faculty and staff.', 10, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(258, 84, 'Staff', 'ea', 'Professionalism', 'Implements spiritual programs for students including liturgy, prayer, recollections, retreats, service-learning, and parish relations.', 11, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(259, 80, 'Staff', 'ea', 'dsgfg', 'dfhgfdh', 1, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(260, 80, 'Staff', 'ea', 'dsgfg', 'dsgfdg', 2, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(261, 80, 'Staff', 'ea', 'dsgfg', 'fdgfdg', 3, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(262, 96, 'Staff', 'ea', 'Professionalism', 'Arrives at school on time', 1, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(263, 101, 'Staff', 'ea', 'Professionalism', 'Arrives at school on time', 1, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(264, 101, 'Staff', 'ea', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 2, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(265, 101, 'Staff', 'ea', 'Professionalism', 'Demonstrates punctuality, excellent attendance', 3, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(266, 96, 'Staff', 'ea', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 2, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(267, 96, 'Staff', 'ea', 'Professionalism', 'Demonstrates punctuality, excellent attendance', 3, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(268, 96, 'Staff', 'ea', 'fgdfg', 'fghfgh', 4, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(269, 96, 'Staff', 'ea', 'fgdfg', 'fghfgh', 5, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(270, 96, 'Staff', 'ea', 'fgdfg', 'fghfgh', 6, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(271, 127, 'Staff', 'ea', 'Professionalism', 'informs the class in advance of any schedule of the day or directives from the office *', 1, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(272, 127, 'Staff', 'ea', 'Professionalism', 'Arrives at class on time.', 2, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(275, 172, 'Staff', 'ea', 'Staff Effectiveness', 'Performs assigned duties and responsibilities effectively.', 1, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(276, 172, 'Staff', 'ea', 'Staff Effectiveness', 'Completes assigned tasks accurately on time.', 2, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(277, 172, 'Staff', 'ea', 'Staff Effectiveness', 'Follows school policies, and procedures consistently.', 3, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(278, 172, 'Staff', 'ea', 'Staff Effectiveness', 'Demonstrates professionalism while performing assigned duties.', 4, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(279, 172, 'Staff', 'ea', 'Staff Effectiveness', 'Communicates clearly and effectively.', 5, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(280, 146, 'Staff', 'ea', 'Staff Effectiveness', 'Performs assigned duties and responsibilities effectively.', 12, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(281, 146, 'Staff', 'ea', 'Staff Effectiveness', 'Completes assigned tasks accurately on time.', 13, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(282, 146, 'Staff', 'ea', 'Staff Effectiveness', 'Follows school policies, and procedures consistently.', 14, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(283, 146, 'Staff', 'ea', 'Staff Effectiveness', 'Demonstrates professionalism while performing assigned duties.', 15, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(284, 146, 'Staff', 'ea', 'Staff Effectiveness', 'Communicates clearly and effectively.', 16, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(285, 136, 'Staff', 'ea', 'Staff Effectiveness', 'Performs assigned duties and responsibilities effectively.', 15, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(286, 136, 'Staff', 'ea', 'Staff Effectiveness', 'Completes assigned tasks accurately on time.', 16, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(287, 136, 'Staff', 'ea', 'Staff Effectiveness', 'Follows school policies, and procedures consistently.', 17, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(288, 136, 'Staff', 'ea', 'Staff Effectiveness', 'Demonstrates professionalism while performing assigned duties.', 18, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(289, 136, 'Staff', 'ea', 'Staff Effectiveness', 'Communicates clearly and effectively.', 19, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(290, 152, 'Staff', 'ea', 'Staff Effectiveness', 'Performs assigned duties and responsibilities effectively.', 14, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(291, 152, 'Staff', 'ea', 'Staff Effectiveness', 'Completes assigned tasks accurately on time.', 15, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(292, 152, 'Staff', 'ea', 'Staff Effectiveness', 'Follows school policies, and procedures consistently.', 16, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(293, 152, 'Staff', 'ea', 'Staff Effectiveness', 'Demonstrates professionalism while performing assigned duties.', 17, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(294, 152, 'Staff', 'ea', 'Staff Effectiveness', 'Communicates clearly and effectively.', 18, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(295, 181, 'Staff', 'ea', 'Staff Effectiveness', 'Performs assigned duties and responsibilities effectively.', 1, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(296, 181, 'Staff', 'ea', 'Staff Effectiveness', 'Completes assigned tasks accurately on time.', 2, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(297, 181, 'Staff', 'ea', 'Staff Effectiveness', 'Follows school policies, and procedures consistently.', 3, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(298, 181, 'Staff', 'ea', 'Staff Effectiveness', 'Demonstrates professionalism while performing assigned duties.', 4, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(299, 181, 'Staff', 'ea', 'Staff Effectiveness', 'Communicates clearly and effectively.', 5, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(300, 198, 'Staff', 'ea', 'Professionalism', 'Demonstrates effective teaching strategies and methods.', 1, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(311, 157, 'Dean', 'ea', 'Cooperaton', 'Demonstrates punctuality, excellent attendance *', 3, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(312, 157, 'Dean', 'ea', 'Cooperaton', 'Demonstrates punctuality, excellent attendance *', 4, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(313, 157, 'Dean', 'ea', 'Cooperaton', 'Implements diocesan and DepEd policies and directives.', 5, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(314, 158, 'Principal', 'ea', 'General', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 2, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(315, 158, 'Principal', 'ea', 'Administrative Functions', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 3, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(316, 207, 'Staff', 'ea', 'Professionalism', 'Clearly explains lessons and course-related concepts.', 1, '2026-09-15 08:23:16', '2026-09-15 08:23:16'),
(318, 207, 'Staff', 'ea', 'General', 'Demonstrates punctuality, excellent attendance *', 1, '2026-09-17 01:05:14', '2026-09-17 01:05:14'),
(319, 158, 'Principal', 'ea', 'Administrative Functions', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 7, '2026-09-18 07:14:46', '2026-09-18 07:14:46'),
(326, 84, 'Staff', 'general', 'Professionalism', 'Attends school on time', 1, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(327, 84, 'Staff', 'general', 'Administrative Functions', 'Implements diocesan and DepEd policies and directives.', 2, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(328, 84, 'Staff', 'general', 'Administrative Functions', 'Cooperates and works with the Director/Superintendent and ADCE.', 3, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(329, 84, 'Staff', 'general', 'Administrative Functions', 'Reviews school objectives yearly in consultation with faculty, parents, and students.', 4, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(330, 84, 'Staff', 'general', 'Administrative Functions', 'Prepares and submits a written annual report to the Board of Trustees.', 5, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(331, 84, 'Staff', 'general', 'Administrative Functions', 'Carries out school objectives and policies within existing laws and regulations.', 6, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(332, 84, 'Staff', 'general', 'Professionalism', 'Integrates faith with the learning process in line with the school mission.', 7, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(333, 84, 'Staff', 'general', 'Professionalism', 'Ensures all religious, academic, and student programs reflect the Catholic mission and school identity.', 8, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(334, 84, 'Staff', 'general', 'Professionalism', 'Implements religious instruction programs prescribed by the diocese.', 9, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(335, 84, 'Staff', 'general', 'Professionalism', 'Implements spiritual life programs for faculty and staff.', 10, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(336, 84, 'Staff', 'general', 'Professionalism', 'Implements spiritual programs for students including liturgy, prayer, recollections, retreats, service-learning, and parish relations.', 11, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(337, 80, 'Staff', 'general', 'dsgfg', 'dfhgfdh', 1, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(338, 80, 'Staff', 'general', 'dsgfg', 'dsgfdg', 2, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(339, 80, 'Staff', 'general', 'dsgfg', 'fdgfdg', 3, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(340, 96, 'Staff', 'general', 'Professionalism', 'Arrives at school on time', 1, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(341, 101, 'Staff', 'general', 'Professionalism', 'Arrives at school on time', 1, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(342, 101, 'Staff', 'general', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 2, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(343, 101, 'Staff', 'general', 'Professionalism', 'Demonstrates punctuality, excellent attendance', 3, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(344, 96, 'Staff', 'general', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 2, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(345, 96, 'Staff', 'general', 'Professionalism', 'Demonstrates punctuality, excellent attendance', 3, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(346, 96, 'Staff', 'general', 'fgdfg', 'fghfgh', 4, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(347, 127, 'Staff', 'general', 'Professionalism', 'informs the class in advance of any schedule of the day or directives from the office *', 1, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(348, 127, 'Staff', 'general', 'Professionalism', 'Arrives at class on time.', 2, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(356, 146, 'Staff', 'general', 'Staff Effectiveness', 'Performs assigned duties and responsibilities effectively.', 12, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(357, 146, 'Staff', 'general', 'Staff Effectiveness', 'Completes assigned tasks accurately on time.', 13, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(358, 146, 'Staff', 'general', 'Staff Effectiveness', 'Follows school policies, and procedures consistently.', 14, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(359, 146, 'Staff', 'general', 'Staff Effectiveness', 'Demonstrates professionalism while performing assigned duties.', 15, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(360, 146, 'Staff', 'general', 'Staff Effectiveness', 'Communicates clearly and effectively.', 16, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(361, 136, 'Staff', 'general', 'Staff Effectiveness', 'Performs assigned duties and responsibilities effectively.', 15, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(362, 136, 'Staff', 'general', 'Staff Effectiveness', 'Completes assigned tasks accurately on time.', 16, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(363, 136, 'Staff', 'general', 'Staff Effectiveness', 'Follows school policies, and procedures consistently.', 17, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(364, 136, 'Staff', 'general', 'Staff Effectiveness', 'Demonstrates professionalism while performing assigned duties.', 18, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(365, 136, 'Staff', 'general', 'Staff Effectiveness', 'Communicates clearly and effectively.', 19, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(366, 152, 'Staff', 'general', 'Staff Effectiveness', 'Performs assigned duties and responsibilities effectively.', 14, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(367, 152, 'Staff', 'general', 'Staff Effectiveness', 'Completes assigned tasks accurately on time.', 15, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(368, 152, 'Staff', 'general', 'Staff Effectiveness', 'Follows school policies, and procedures consistently.', 16, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(369, 152, 'Staff', 'general', 'Staff Effectiveness', 'Demonstrates professionalism while performing assigned duties.', 17, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(370, 152, 'Staff', 'general', 'Staff Effectiveness', 'Communicates clearly and effectively.', 18, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(371, 181, 'Staff', 'general', 'Staff Effectiveness', 'Performs assigned duties and responsibilities effectively.', 1, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(372, 181, 'Staff', 'general', 'Staff Effectiveness', 'Completes assigned tasks accurately on time.', 2, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(373, 181, 'Staff', 'general', 'Staff Effectiveness', 'Follows school policies, and procedures consistently.', 3, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(374, 181, 'Staff', 'general', 'Staff Effectiveness', 'Demonstrates professionalism while performing assigned duties.', 4, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(375, 181, 'Staff', 'general', 'Staff Effectiveness', 'Communicates clearly and effectively.', 5, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(376, 198, 'Staff', 'general', 'Professionalism', 'Demonstrates effective teaching strategies and methods.', 1, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(377, 207, 'Staff', 'general', 'Professionalism', 'Clearly explains lessons and course-related concepts.', 1, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(378, 207, 'Staff', 'general', 'General', 'Demonstrates punctuality, excellent attendance *', 1, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(379, 80, 'Staff', 'general', 'General', 'receives all collections of the school and acknowledges the same by issuing official receipts for such.  *', 1, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(380, 80, 'Staff', 'general', 'General', 'is cordial and accommodating to parents and students * 5', 2, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(381, 80, 'Staff', 'general', 'General', 'Is honest and truthful in every transactions *', 3, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(382, 80, 'Staff', 'general', 'General', 'attends to office duty regularly and avoids being absent *', 4, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(383, 75, 'Staff', 'general', 'General', 'receives, processes, releases on time  school records of students.  *', 1, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(384, 75, 'Staff', 'general', 'General', 'prepares and keeps all students’ records up-to-date such as Permanent Record (School Form 10), Transfer Credentials, Certifications, Diploma, Report on Promotion (School Form 5) and Enrolment Reports.      *', 2, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(385, 75, 'Staff', 'general', 'General', 'prepares and submits academic records of graduating students to the DepEd to secure Special Order for graduation.  *', 3, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(386, 81, 'Staff', 'general', 'General', 'performs records management *', 1, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(387, 81, 'Staff', 'general', 'General', 'conducts library orientation for students    *', 2, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(388, 81, 'Staff', 'general', 'General', 'assists students/staff in the proper use of the library particularly on research works *', 3, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(389, 84, 'Staff', 'general', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 1, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(390, 136, 'Staff', 'general', 'Work Performance', 'Completes tasks accurately and on time.', 2, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(391, 136, 'Staff', 'general', 'Work Performance', 'Demonstrates knowledge of assigned responsibilities.', 3, '2026-09-19 09:02:41', '2026-09-19 09:02:41');
INSERT INTO `user_questions` (`id`, `user_id`, `target_type`, `eval_type`, `category`, `question_text`, `sort_order`, `created_at`, `date_added`) VALUES
(392, 136, 'Staff', 'general', 'Work Performance', 'Maintains quality and consistency in work.', 4, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(393, 136, 'Staff', 'general', 'Work Performance', 'Handles work-related responsibilities efficiently.', 5, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(394, 136, 'Staff', 'general', 'Work Performance', 'Takes initiative in completing assigned tasks.', 6, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(395, 136, 'Staff', 'general', 'Work Performance', 'Follows established procedures and guidelines.', 7, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(422, 210, 'Principal', 'general', 'Leadership & Governance', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 0, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(424, 210, 'Principal', 'general', 'Communication', 'Clearly explains lessons and course-related concepts.', 0, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(426, 210, 'Principal', 'general', 'Professionalism', 'Demonstrates punctuality, excellent attendance.', 0, '2026-09-19 09:02:41', '2026-09-19 09:02:41'),
(439, 225, 'Staff', 'general', 'Job Performance & Service', 'Maintains the cleanliness and proper condition of assigned facilities.', 0, '2026-09-23 03:59:39', '2026-09-23 03:59:39'),
(440, 225, 'Staff', 'general', 'Job Performance & Service', 'Ensures that computer laboratory equipment is available and functional.', 0, '2026-09-23 03:59:50', '2026-09-23 03:59:50'),
(441, 225, 'Staff', 'general', 'Job Performance & Service', 'Responds promptly to maintenance or equipment concerns.', 0, '2026-09-23 03:59:57', '2026-09-23 03:59:57'),
(442, 225, 'Staff', 'general', 'Job Performance & Service', 'Monitors the proper use of laboratory facilities and equipment.', 0, '2026-09-23 04:00:02', '2026-09-23 04:00:02'),
(443, 225, 'Staff', 'general', 'Job Performance & Service', 'Performs assigned facility and laboratory tasks efficiently.', 0, '2026-09-23 04:00:10', '2026-09-23 04:00:10'),
(444, 225, 'Staff', 'general', 'Professionalism & Responsibility', 'Follows proper procedures when handling equipment and facilities.', 0, '2026-09-23 04:00:18', '2026-09-23 04:00:18'),
(445, 225, 'Staff', 'general', 'Professionalism & Responsibility', 'Keeps accurate records of equipment and maintenance activities.', 0, '2026-09-23 04:00:27', '2026-09-23 04:00:27'),
(446, 225, 'Staff', 'general', 'Professionalism & Responsibility', 'Coordinates effectively with school personnel regarding facility concerns.', 0, '2026-09-23 04:00:36', '2026-09-23 04:00:36'),
(447, 225, 'Staff', 'general', 'Professionalism & Responsibility', 'Demonstrates responsibility in maintaining a safe environment.', 0, '2026-09-23 04:00:46', '2026-09-23 04:00:46'),
(448, 225, 'Staff', 'general', 'Professionalism & Responsibility', 'Performs duties in a professional and dependable manner.', 0, '2026-09-23 04:00:52', '2026-09-23 04:00:52'),
(455, 234, 'Staff', 'general', 'Teaching Performance & Service', 'Explains lessons and instructions clearly.', 0, '2026-09-23 04:04:17', '2026-09-23 04:04:17'),
(456, 234, 'Staff', 'general', 'Teaching Performance & Service', 'Demonstrates adequate knowledge of the subject matter.', 0, '2026-09-23 04:04:24', '2026-09-23 04:04:24'),
(457, 234, 'Staff', 'general', 'Teaching Performance & Service', 'Uses appropriate teaching strategies and learning activities.', 0, '2026-09-23 04:04:30', '2026-09-23 04:04:30'),
(458, 234, 'Staff', 'general', 'Teaching Performance & Service', 'Provides useful feedback on student performance.', 0, '2026-09-23 04:04:37', '2026-09-23 04:04:37'),
(459, 234, 'Staff', 'general', 'Teaching Performance & Service', 'Encourages students to actively participate in learning.', 0, '2026-09-23 04:04:43', '2026-09-23 04:04:43'),
(460, 234, 'Staff', 'general', 'Professionalism & Responsibility', 'Treats students fairly and respectfully.', 0, '2026-09-23 04:04:50', '2026-09-23 04:04:50'),
(461, 234, 'Staff', 'general', 'Professionalism & Responsibility', 'Manages classroom activities effectively.', 0, '2026-09-23 04:04:56', '2026-09-23 04:04:56'),
(462, 234, 'Staff', 'general', 'Professionalism & Responsibility', 'Maintains professional relationships with students and colleagues.', 0, '2026-09-23 04:05:08', '2026-09-23 04:05:08'),
(463, 234, 'Staff', 'general', 'Professionalism & Responsibility', 'Performs teaching and coordination responsibilities responsibly.', 0, '2026-09-23 04:05:13', '2026-09-23 04:05:13'),
(464, 234, 'Staff', 'general', 'Professionalism & Responsibility', 'Demonstrates commitment to students\' learning and development.', 0, '2026-09-23 04:05:18', '2026-09-23 04:05:18'),
(465, 232, 'Staff', 'general', 'Service & Administrative Performance', 'Processes student records and documents accurately.', 0, '2026-09-23 04:05:42', '2026-09-23 04:05:42'),
(466, 232, 'Staff', 'general', 'Service & Administrative Performance', 'Provides timely assistance to students and personnel.', 0, '2026-09-23 04:05:47', '2026-09-23 04:05:47'),
(467, 232, 'Staff', 'general', 'Service & Administrative Performance', 'Communicates registration procedures and requirements clearly.', 0, '2026-09-23 04:05:53', '2026-09-23 04:05:53'),
(468, 232, 'Staff', 'general', 'Service & Administrative Performance', 'Maintains organized and updated records.', 0, '2026-09-23 04:05:58', '2026-09-23 04:05:58'),
(469, 232, 'Staff', 'general', 'Service & Administrative Performance', 'Responds effectively to registration-related concerns.', 0, '2026-09-23 04:06:04', '2026-09-23 04:06:04'),
(470, 232, 'Staff', 'general', 'Professionalism & Responsibility', 'Maintains confidentiality of student information.', 0, '2026-09-23 04:06:10', '2026-09-23 04:06:10'),
(471, 232, 'Staff', 'general', 'Professionalism & Responsibility', 'Handles documents carefully and responsibly.', 0, '2026-09-23 04:06:19', '2026-09-23 04:06:19'),
(472, 232, 'Staff', 'general', 'Professionalism & Responsibility', 'Follows established administrative procedures.', 0, '2026-09-23 04:06:25', '2026-09-23 04:06:25'),
(473, 232, 'Staff', 'general', 'Professionalism & Responsibility', 'Coordinates effectively with other school offices.', 0, '2026-09-23 04:06:29', '2026-09-23 04:06:29'),
(474, 232, 'Staff', 'general', 'Professionalism & Responsibility', 'Performs assigned responsibilities accurately and professionally.', 0, '2026-09-23 04:06:35', '2026-09-23 04:06:35'),
(475, 233, 'Staff', 'general', 'Financial Performance & Service', 'Records financial transactions accurately.', 0, '2026-09-23 04:07:00', '2026-09-23 04:07:00'),
(476, 233, 'Staff', 'general', 'Financial Performance & Service', 'Maintains organized financial records.', 0, '2026-09-23 04:07:05', '2026-09-23 04:07:05'),
(477, 233, 'Staff', 'general', 'Financial Performance & Service', 'Processes financial documents in a timely manner.', 0, '2026-09-23 04:07:12', '2026-09-23 04:07:12'),
(478, 233, 'Staff', 'general', 'Financial Performance & Service', 'Provides accurate financial information when needed.', 0, '2026-09-23 04:07:17', '2026-09-23 04:07:17'),
(479, 233, 'Staff', 'general', 'Financial Performance & Service', 'Properly manages receipts, vouchers, and supporting documents.', 0, '2026-09-23 04:07:24', '2026-09-23 04:07:24'),
(483, 233, 'Staff', 'general', 'Professionalism & Responsibility', 'Maintains confidentiality of financial information.', 0, '2026-09-23 04:08:22', '2026-09-23 04:08:22'),
(484, 233, 'Staff', 'general', 'Professionalism & Responsibility', 'Follows established accounting and financial procedures.', 0, '2026-09-23 04:08:29', '2026-09-23 04:08:29'),
(485, 233, 'Staff', 'general', 'Professionalism & Responsibility', 'Demonstrates attention to detail in financial tasks.', 0, '2026-09-23 04:08:58', '2026-09-23 04:08:58'),
(486, 233, 'Staff', 'general', 'Professionalism & Responsibility', 'Coordinates effectively with administrators and other personnel.', 0, '2026-09-23 04:09:04', '2026-09-23 04:09:04'),
(487, 233, 'Staff', 'general', 'Professionalism & Responsibility', 'Handles financial responsibilities honestly and professionally.', 0, '2026-09-23 04:09:09', '2026-09-23 04:09:09'),
(488, 230, 'Staff', 'general', 'Professionalism & Responsibility', 'Maintains confidentiality when handling sensitive concerns.', 0, '2026-09-23 04:09:37', '2026-09-23 04:09:37'),
(490, 230, 'Staff', 'general', 'Professionalism & Responsibility', 'Coordinates effectively with school personnel during programs and emergencies.', 0, '2026-09-23 04:09:50', '2026-09-23 04:09:50'),
(491, 230, 'Staff', 'general', 'Professionalism & Responsibility', 'Promotes safety and responsible participation in activities.', 0, '2026-09-23 04:10:21', '2026-09-23 04:10:21'),
(492, 230, 'Staff', 'general', 'Professionalism & Responsibility', 'Follows established school safety and program procedures.', 0, '2026-09-23 04:10:26', '2026-09-23 04:10:26'),
(493, 230, 'Staff', 'general', 'Professionalism & Responsibility', 'Performs assigned responsibilities reliably and professionally.', 0, '2026-09-23 04:10:31', '2026-09-23 04:10:31'),
(494, 230, 'Staff', 'general', 'Program Performance & Service', 'Provides appropriate assistance to students and school personnel.', 0, '2026-09-23 04:10:37', '2026-09-23 04:10:37'),
(495, 230, 'Staff', 'general', 'Program Performance & Service', 'Organizes assigned programs and activities effectively.', 0, '2026-09-23 04:10:44', '2026-09-23 04:10:44'),
(496, 230, 'Staff', 'general', 'Program Performance & Service', 'Responds appropriately to student or program-related concerns.', 0, '2026-09-23 04:10:48', '2026-09-23 04:10:48'),
(497, 230, 'Staff', 'general', 'Program Performance & Service', 'Coordinates activities, schedules, and resources efficiently.', 0, '2026-09-23 04:10:53', '2026-09-23 04:10:53'),
(498, 230, 'Staff', 'general', 'Program Performance & Service', 'Supports the successful implementation of school programs.', 0, '2026-09-23 04:10:59', '2026-09-23 04:10:59'),
(499, 226, 'Staff', 'general', 'Maintenance Performance & Service', 'Maintains school facilities in clean and functional condition.', 0, '2026-09-23 04:11:24', '2026-09-23 04:11:24'),
(500, 226, 'Staff', 'general', 'Maintenance Performance & Service', 'Responds promptly to maintenance requests.', 0, '2026-09-23 04:11:29', '2026-09-23 04:11:29'),
(501, 226, 'Staff', 'general', 'Maintenance Performance & Service', 'Performs repairs and maintenance tasks properly.', 0, '2026-09-23 04:11:36', '2026-09-23 04:11:36'),
(502, 226, 'Staff', 'general', 'Maintenance Performance & Service', 'Regularly checks facilities for possible problems or hazards.', 0, '2026-09-23 04:11:45', '2026-09-23 04:11:45'),
(503, 226, 'Staff', 'general', 'Maintenance Performance & Service', 'Completes assigned maintenance tasks efficiently.', 0, '2026-09-23 04:11:49', '2026-09-23 04:11:49'),
(504, 226, 'Staff', 'general', 'Professionalism & Responsibility', 'Uses tools and equipment properly and safely.', 0, '2026-09-23 04:12:01', '2026-09-23 04:12:01'),
(505, 226, 'Staff', 'general', 'Professionalism & Responsibility', 'Reports facility problems that require further assistance.', 0, '2026-09-23 04:12:04', '2026-09-23 04:12:04'),
(506, 226, 'Staff', 'general', 'Professionalism & Responsibility', 'Coordinates effectively with school personnel.', 0, '2026-09-23 04:12:10', '2026-09-23 04:12:10'),
(507, 226, 'Staff', 'general', 'Professionalism & Responsibility', 'Helps maintain a safe environment for students and staff.', 0, '2026-09-23 04:12:16', '2026-09-23 04:12:16'),
(508, 226, 'Staff', 'general', 'General', 'Performs maintenance duties responsibly and professionally.', 0, '2026-09-23 04:12:24', '2026-09-23 04:12:24'),
(509, 218, 'Staff', 'general', 'Library Service & Performance', 'Maintains an organized and orderly library.', 0, '2026-09-23 04:13:45', '2026-09-23 04:13:45'),
(510, 218, 'Staff', 'general', 'Library Service & Performance', 'Assists users in locating appropriate library resources.', 0, '2026-09-23 04:13:52', '2026-09-23 04:13:52'),
(511, 218, 'Staff', 'general', 'Library Service & Performance', 'Maintains accurate borrowing and return records.', 0, '2026-09-23 04:13:57', '2026-09-23 04:13:57'),
(512, 218, 'Staff', 'general', 'Library Service & Performance', 'Keeps library materials properly organized and maintained.', 0, '2026-09-23 04:14:02', '2026-09-23 04:14:02'),
(513, 218, 'Staff', 'general', 'Library Service & Performance', 'Provides helpful and timely assistance to library users.', 0, '2026-09-23 04:14:09', '2026-09-23 04:14:09'),
(514, 218, 'Staff', 'general', 'Professionalism & Responsibility', 'Enforces library rules fairly and consistently.', 0, '2026-09-23 04:14:14', '2026-09-23 04:14:14'),
(515, 218, 'Staff', 'general', 'Professionalism & Responsibility', 'Promotes proper care and use of library resources.', 0, '2026-09-23 04:14:20', '2026-09-23 04:14:20'),
(516, 218, 'Staff', 'general', 'Professionalism & Responsibility', 'Maintains a quiet, safe, and conducive library environment.', 0, '2026-09-23 04:14:26', '2026-09-23 04:14:26'),
(517, 218, 'Staff', 'general', 'Professionalism & Responsibility', 'Communicates respectfully with students and personnel.', 0, '2026-09-23 04:14:36', '2026-09-23 04:14:36'),
(518, 218, 'Staff', 'general', 'General', 'Performs library responsibilities efficiently and professionally.', 0, '2026-09-23 04:14:40', '2026-09-23 04:14:40'),
(519, 215, 'Staff', 'general', 'Academic Performance & Participation', 'Completes academic requirements responsibly.', 0, '2026-09-23 04:15:21', '2026-09-23 04:15:21'),
(520, 215, 'Staff', 'general', 'Academic Performance & Participation', 'Participates actively in classroom and learning activities.', 0, '2026-09-23 04:15:30', '2026-09-23 04:15:30'),
(521, 215, 'Staff', 'general', 'Academic Performance & Participation', 'Demonstrates willingness to learn new knowledge and skills.', 0, '2026-09-23 04:15:38', '2026-09-23 04:15:38'),
(522, 215, 'Staff', 'general', 'Academic Performance & Participation', 'Manages academic responsibilities effectively.', 0, '2026-09-23 04:15:43', '2026-09-23 04:15:43'),
(523, 215, 'Staff', 'general', 'Academic Performance & Participation', 'Applies learned knowledge appropriately during academic activities.', 0, '2026-09-23 04:15:47', '2026-09-23 04:15:47'),
(524, 215, 'Staff', 'general', 'Conduct & Professionalism', 'Demonstrates respect toward teachers, staff, and fellow students.', 0, '2026-09-23 04:15:53', '2026-09-23 04:15:53'),
(525, 215, 'Staff', 'general', 'Conduct & Professionalism', 'Follows school rules and established procedures.', 0, '2026-09-23 04:16:03', '2026-09-23 04:16:03'),
(526, 215, 'Staff', 'general', 'Conduct & Professionalism', 'Communicates appropriately with others.', 0, '2026-09-23 04:16:08', '2026-09-23 04:16:08'),
(527, 215, 'Staff', 'general', 'Conduct & Professionalism', 'Cooperates effectively during group activities.', 0, '2026-09-23 04:16:14', '2026-09-23 04:16:14'),
(528, 215, 'Staff', 'general', 'Conduct & Professionalism', 'Demonstrates responsible and professional behavior.', 0, '2026-09-23 04:16:20', '2026-09-23 04:16:20'),
(529, 158, 'Principal', 'general', 'Leadership & Supervision', 'Provides clear direction for the high school division.', 0, '2026-09-23 04:24:14', '2026-09-23 04:24:14'),
(530, 158, 'Principal', 'general', 'Leadership & Supervision', 'Monitors the implementation of high school academic programs.', 0, '2026-09-23 04:24:19', '2026-09-23 04:24:19'),
(531, 158, 'Principal', 'general', 'Leadership & Supervision', 'Ensures that high school policies and procedures are properly followed.', 0, '2026-09-23 04:24:23', '2026-09-23 04:24:23'),
(532, 158, 'Principal', 'general', 'Leadership & Supervision', 'Addresses concerns involving high school students, faculty, and staff appropriately.', 0, '2026-09-23 04:24:28', '2026-09-23 04:24:28'),
(533, 158, 'Principal', 'general', 'Leadership & Supervision', 'Oversees the effective implementation of high school activities and programs.', 0, '2026-09-23 04:24:33', '2026-09-23 04:24:33'),
(534, 158, 'Principal', 'general', 'School Management & Student Development', 'Promotes a positive and supportive environment for high school students.', 0, '2026-09-23 04:24:39', '2026-09-23 04:24:39'),
(535, 158, 'Principal', 'general', 'School Management & Student Development', 'Communicates high school policies and expectations clearly.', 0, '2026-09-23 04:24:44', '2026-09-23 04:24:44'),
(536, 158, 'Principal', 'general', 'School Management & Student Development', 'Coordinates effectively with high school faculty and staff.', 0, '2026-09-23 04:24:48', '2026-09-23 04:24:48'),
(537, 158, 'Principal', 'general', 'School Management & Student Development', 'Supports programs that promote student development and achievement.', 0, '2026-09-23 04:24:54', '2026-09-23 04:24:54'),
(538, 158, 'Principal', 'general', 'School Management & Student Development', 'Demonstrates accountability in managing high school operations.', 0, '2026-09-23 04:24:59', '2026-09-23 04:24:59'),
(539, 157, 'Dean', 'general', 'Academic Leadership', 'Provides clear direction for the college academic programs.', 0, '2026-09-23 04:25:57', '2026-09-23 04:25:57'),
(540, 157, 'Dean', 'general', 'Academic Leadership', 'Monitors the implementation of college-level curricula and academic requirements.', 0, '2026-09-23 04:26:02', '2026-09-23 04:26:02'),
(541, 157, 'Dean', 'general', 'Academic Leadership', 'Coordinates effectively with college faculty regarding academic matters.', 0, '2026-09-23 04:26:05', '2026-09-23 04:26:05'),
(542, 157, 'Dean', 'general', 'Academic Leadership', 'Addresses academic concerns of college students appropriately.', 0, '2026-09-23 04:26:09', '2026-09-23 04:26:09'),
(543, 157, 'Dean', 'general', 'Academic Leadership', 'Supports the continuous improvement of college teaching and learning.', 0, '2026-09-23 04:26:13', '2026-09-23 04:26:13'),
(544, 157, 'Dean', 'general', 'Faculty & College Management', 'Monitors faculty compliance with college academic responsibilities.', 0, '2026-09-23 04:26:56', '2026-09-23 04:26:56'),
(545, 157, 'Dean', 'general', 'Faculty & College Management', 'Communicates college policies and academic requirements clearly.', 0, '2026-09-23 04:27:01', '2026-09-23 04:27:01'),
(546, 157, 'Dean', 'general', 'Faculty & College Management', 'Coordinates college schedules, activities, and academic programs effectively.', 0, '2026-09-23 04:27:09', '2026-09-23 04:27:09'),
(547, 157, 'Dean', 'general', 'Faculty & College Management', 'Promotes a professional and supportive environment for college students and faculty.', 0, '2026-09-23 04:27:14', '2026-09-23 04:27:14'),
(548, 157, 'Dean', 'general', 'Faculty & College Management', 'Demonstrates accountability in managing college academic operations.', 0, '2026-09-23 04:27:19', '2026-09-23 04:27:19'),
(549, 236, 'EA', 'general', 'Administrative Support & Coordination', 'Provides timely and organized administrative support to school management.', 0, '2026-09-23 04:27:57', '2026-09-23 04:27:57'),
(550, 236, 'EA', 'general', 'Administrative Support & Coordination', 'Coordinates meetings, schedules, and official activities effectively.', 0, '2026-09-23 04:28:01', '2026-09-23 04:28:01'),
(551, 236, 'EA', 'general', 'Administrative Support & Coordination', 'Prepares and organizes documents and administrative records accurately.', 0, '2026-09-23 04:28:04', '2026-09-23 04:28:04'),
(552, 236, 'EA', 'general', 'Administrative Support & Coordination', 'Communicates information and instructions clearly to faculty, staff, and other stakeholders.', 0, '2026-09-23 04:28:09', '2026-09-23 04:28:09'),
(553, 236, 'EA', 'general', 'Administrative Support & Coordination', 'Responds promptly to administrative requests and concerns.', 0, '2026-09-23 04:28:13', '2026-09-23 04:28:13'),
(554, 236, 'EA', 'general', 'Professionalism & Office Management', 'Maintains confidentiality of official and sensitive information.', 0, '2026-09-23 04:28:21', '2026-09-23 04:28:21'),
(555, 236, 'EA', 'general', 'Professionalism & Office Management', 'Demonstrates accuracy and attention to detail in administrative tasks.', 0, '2026-09-23 04:28:26', '2026-09-23 04:28:26'),
(556, 236, 'EA', 'general', 'Professionalism & Office Management', 'Coordinates effectively with different school offices and personnel.', 0, '2026-09-23 04:28:32', '2026-09-23 04:28:32'),
(557, 236, 'EA', 'general', 'Professionalism & Office Management', 'Manages assigned responsibilities in an organized and dependable manner.', 0, '2026-09-23 04:28:39', '2026-09-23 04:28:39'),
(558, 236, 'EA', 'general', 'Professionalism & Office Management', 'Demonstrates professionalism and courtesy when dealing with students, faculty, staff, and administrators.', 0, '2026-09-23 04:28:48', '2026-09-23 04:28:48');

-- --------------------------------------------------------

--
-- Table structure for table `user_question_categories`
--

CREATE TABLE `user_question_categories` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `eval_type` varchar(20) NOT NULL DEFAULT 'student',
  `category_name` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_question_categories`
--

INSERT INTO `user_question_categories` (`id`, `user_id`, `target_type`, `eval_type`, `category_name`, `sort_order`) VALUES
(1, 83, 'Staff', 'student', 'professionalsm', 1),
(2, 84, 'Staff', 'student', 'Professionalism', 1),
(3, 84, 'Staff', 'peer', 'Professionalism', 1),
(4, 84, 'Staff', 'student', 'Administrative Functions', 2),
(5, 80, 'Staff', 'student', 'dsgfg', 1),
(7, 101, 'Staff', 'student', 'Professionalism', 1),
(8, 96, 'Staff', 'student', 'Professionalism', 1),
(9, 96, 'Staff', 'student', 'fgdfg', 2),
(10, 127, 'Staff', 'student', 'Professionalism', 1),
(12, 136, 'Staff', 'student', 'Professionalism', 1),
(13, 152, 'Staff', 'student', 'Professionalism', 1),
(20, 157, 'Dean', 'school_head', 'Cooperaton', 2),
(27, 157, 'Dean', 'school_head', 'Administrative Functions', 3),
(28, 158, 'Principal', 'school_head', 'Administrative Functions', 2),
(33, 157, 'Dean', 'school_head', 'Professionalism', 4),
(34, 160, 'Multi-Role', 'student', 'Professionalism', 1),
(35, 146, 'Multi-Role', 'student', 'Cooperaton', 1),
(36, 172, 'Staff', 'student', 'Professionalism', 1),
(37, 152, 'Multi-Role', 'student', 'Professionalism', 2),
(38, 152, 'Multi-Role', 'student', 'Cashiering & Financial Service', 3),
(39, 146, 'Multi-Role', 'student', 'Professionalism & Responsibility', 2),
(40, 160, 'Multi-Role', 'student', 'Teaching & Technical Support', 2),
(41, 136, 'Multi-Role', 'student', 'Facilities & Laboratory Management', 2),
(42, 176, 'Multi-Role', 'student', 'Personnel / Registrar', 1),
(44, 146, 'Staff', 'student', 'Professionalism', 3),
(45, 146, 'Staff', 'student', 'Cooperaton', 4),
(46, 172, 'Staff', 'student', 'Staff Effectiveness', 2),
(47, 146, 'Staff', 'student', 'Staff Effectiveness', 5),
(48, 136, 'Staff', 'student', 'Staff Effectiveness', 3),
(49, 152, 'Staff', 'student', 'Staff Effectiveness', 4),
(50, 181, 'Staff', 'student', 'Staff Effectiveness', 1),
(51, 136, 'Staff', 'peer', 'Work Performance', 1),
(52, 158, 'Principal', 'student', 'Professionalism', 1),
(53, 158, 'School', 'peer', 'Cooperaton', 1),
(54, 172, 'Staff', 'school_head', 'Professionalism', 1),
(55, 198, 'Staff', 'student', 'Professionalism', 1),
(56, 172, 'Staff', 'peer', 'Professionalism', 1),
(57, 157, 'School', 'peer', 'Professionalism', 1),
(59, 157, 'School', 'peer', 'Teaching Effectiveness', 2),
(60, 157, 'Dean', 'student', 'Professionalism', 1),
(61, 157, 'Dean', 'student', 'Cooperaton', 2),
(62, 83, 'Staff', 'ea', 'professionalsm', 1),
(63, 84, 'Staff', 'ea', 'Professionalism', 1),
(64, 84, 'Staff', 'ea', 'Administrative Functions', 2),
(65, 80, 'Staff', 'ea', 'dsgfg', 1),
(66, 101, 'Staff', 'ea', 'Professionalism', 1),
(67, 96, 'Staff', 'ea', 'Professionalism', 1),
(68, 96, 'Staff', 'ea', 'fgdfg', 2),
(69, 127, 'Staff', 'ea', 'Professionalism', 1),
(70, 136, 'Staff', 'ea', 'Professionalism', 1),
(71, 152, 'Staff', 'ea', 'Professionalism', 1),
(73, 172, 'Staff', 'ea', 'Professionalism', 1),
(74, 146, 'Staff', 'ea', 'Professionalism', 3),
(75, 146, 'Staff', 'ea', 'Cooperaton', 4),
(76, 172, 'Staff', 'ea', 'Staff Effectiveness', 2),
(77, 146, 'Staff', 'ea', 'Staff Effectiveness', 5),
(78, 136, 'Staff', 'ea', 'Staff Effectiveness', 3),
(79, 152, 'Staff', 'ea', 'Staff Effectiveness', 4),
(80, 181, 'Staff', 'ea', 'Staff Effectiveness', 1),
(81, 198, 'Staff', 'ea', 'Professionalism', 1),
(93, 157, 'Dean', 'ea', 'Cooperaton', 2),
(94, 157, 'Dean', 'ea', 'Administrative Functions', 3),
(95, 157, 'Dean', 'ea', 'Professionalism', 4),
(96, 158, 'Principal', 'ea', 'Administrative Functions', 2),
(97, 207, 'Staff', 'ea', 'Professionalism', 1),
(98, 172, 'Staff', 'general', 'Work Performance', 1),
(99, 172, 'Staff', 'general', 'Service Quality', 3),
(100, 172, 'Staff', 'general', 'Professionalism', 4),
(101, 172, 'Staff', 'general', 'Communication', 5),
(102, 172, 'Staff', 'general', 'Teamwork', 6),
(103, 84, 'Staff', 'general', 'Professionalism', 1),
(104, 84, 'Staff', 'general', 'Administrative Functions', 2),
(105, 80, 'Staff', 'general', 'dsgfg', 1),
(106, 96, 'Staff', 'general', 'Professionalism', 1),
(107, 101, 'Staff', 'general', 'Professionalism', 1),
(108, 96, 'Staff', 'general', 'fgdfg', 4),
(109, 127, 'Staff', 'general', 'Professionalism', 1),
(112, 172, 'Staff', 'general', 'Staff Effectiveness', 1),
(113, 146, 'Staff', 'general', 'Staff Effectiveness', 12),
(114, 136, 'Staff', 'general', 'Staff Effectiveness', 15),
(115, 152, 'Staff', 'general', 'Staff Effectiveness', 14),
(116, 181, 'Staff', 'general', 'Staff Effectiveness', 1),
(117, 198, 'Staff', 'general', 'Professionalism', 1),
(118, 207, 'Staff', 'general', 'Professionalism', 1),
(119, 207, 'Staff', 'general', 'General', 1),
(120, 80, 'Staff', 'general', 'General', 1),
(121, 75, 'Staff', 'general', 'General', 1),
(122, 81, 'Staff', 'general', 'General', 1),
(123, 136, 'Staff', 'general', 'Work Performance', 2),
(135, 158, 'Principal', 'general', 'General', 2),
(136, 210, 'Principal', 'general', 'Leadership & Governance', 0),
(137, 210, 'Principal', 'general', 'Communication', 0),
(138, 210, 'Principal', 'general', 'Professionalism', 0),
(147, 225, 'Staff', 'general', 'Job Performance & Service', 0),
(148, 225, 'Staff', 'general', 'Professionalism & Responsibility', 0),
(149, 234, 'Staff', 'general', 'Teaching Performance & Service', 0),
(150, 234, 'Staff', 'general', 'Professionalism & Responsibility', 0),
(151, 232, 'Staff', 'general', 'Service & Administrative Performance', 0),
(152, 232, 'Staff', 'general', 'Professionalism & Responsibility', 0),
(153, 233, 'Staff', 'general', 'Financial Performance & Service', 0),
(154, 233, 'Staff', 'general', 'Professionalism & Responsibility', 0),
(155, 230, 'Staff', 'general', 'Program Performance & Service', 0),
(157, 230, 'Staff', 'general', 'Professionalism & Responsibility', 0),
(159, 230, 'Staff', 'general', 'General', 0),
(160, 226, 'Staff', 'general', 'Maintenance Performance & Service', 0),
(161, 226, 'Staff', 'general', 'Professionalism & Responsibility', 0),
(162, 226, 'Staff', 'general', 'General', 0),
(165, 218, 'Staff', 'general', 'Library Service & Performance', 0),
(166, 218, 'Staff', 'general', 'Professionalism & Responsibility', 0),
(167, 218, 'Staff', 'general', 'General', 0),
(168, 215, 'Staff', 'general', 'Academic Performance & Participation', 0),
(169, 215, 'Staff', 'general', 'Conduct & Professionalism', 0),
(170, 215, 'Staff', 'general', 'General', 0),
(171, 158, 'Principal', 'general', 'Leadership & Supervision', 0),
(172, 158, 'Principal', 'general', 'School Management & Student Development', 0),
(176, 157, 'Dean', 'general', 'General', 0),
(177, 157, 'Dean', 'general', 'Academic Leadership', 0),
(178, 157, 'Dean', 'general', 'Faculty & College Management', 0),
(179, 236, 'EA', 'general', 'General', 0),
(180, 236, 'EA', 'general', 'Administrative Support & Coordination', 0),
(181, 236, 'EA', 'general', 'Professionalism & Office Management', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_year_levels`
--

CREATE TABLE `user_year_levels` (
  `id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `year_level` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_year_levels`
--

INSERT INTO `user_year_levels` (`id`, `user_id`, `year_level`) VALUES
(211, 216, '4th Year College'),
(210, 216, 'Grade 11'),
(221, 217, '4th Year College'),
(220, 217, 'Grade 11'),
(219, 217, 'Grade 7'),
(216, 220, 'Grade 11'),
(215, 220, 'Grade 7'),
(225, 222, '4th Year College'),
(229, 225, '4th Year College'),
(228, 225, 'Grade 12'),
(224, 230, '4th Year College');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_feature` (`admin_user_id`,`feature_key`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admin_users_username` (`username`);

--
-- Indexes for table `analytics_archive`
--
ALTER TABLE `analytics_archive`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_target` (`target_user_id`);

--
-- Indexes for table `analytics_reports`
--
ALTER TABLE `analytics_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`report_type`),
  ADD KEY `idx_period` (`period`),
  ADD KEY `idx_sector` (`sector`),
  ADD KEY `fk_ar_generator` (`generated_by`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_appointments_reference` (`reference_code`),
  ADD KEY `idx_appointments_date_time` (`appointment_date`,`appointment_time`),
  ADD KEY `idx_appointments_staff_date_time` (`staff_id`,`appointment_date`,`appointment_time`),
  ADD KEY `idx_appointments_service_date` (`service_id`,`appointment_date`),
  ADD KEY `idx_appointments_status` (`status`),
  ADD KEY `fk_appointments_customer` (`customer_id`);

--
-- Indexes for table `auth_attempts`
--
ALTER TABLE `auth_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ident` (`kind`,`identifier`,`attempted_at`),
  ADD KEY `idx_ip` (`kind`,`ip`,`attempted_at`);

--
-- Indexes for table `business_hours`
--
ALTER TABLE `business_hours`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_business_hours_day` (`day_of_week`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_customers_email` (`email`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_sector` (`sector`),
  ADD KEY `idx_archived` (`is_archived`),
  ADD KEY `fk_doc_uploader` (`uploaded_by`);

--
-- Indexes for table `evaluation_answers`
--
ALTER TABLE `evaluation_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tracker` (`tracker_id`);

--
-- Indexes for table `evaluation_periods`
--
ALTER TABLE `evaluation_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `evaluation_questions`
--
ALTER TABLE `evaluation_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_target` (`target_type`),
  ADD KEY `idx_eval_type` (`eval_type`);

--
-- Indexes for table `evaluation_question_categories`
--
ALTER TABLE `evaluation_question_categories`
  ADD PRIMARY KEY (`question_id`,`category_id`),
  ADD KEY `idx_eqc_category` (`category_id`);

--
-- Indexes for table `evaluation_reminders`
--
ALTER TABLE `evaluation_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipient_period` (`recipient_id`,`period_id`),
  ADD KEY `idx_period` (`period_id`);

--
-- Indexes for table `evaluation_results`
--
ALTER TABLE `evaluation_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_target` (`target_user_id`),
  ADD KEY `idx_evaluator` (`student_id`),
  ADD KEY `idx_form` (`form_id`),
  ADD KEY `idx_period` (`period`),
  ADD KEY `fk_er_tracker` (`tracker_id`),
  ADD KEY `idx_submission` (`submission_id`),
  ADD KEY `fk_er_question` (`question_id`);

--
-- Indexes for table `evaluation_submissions`
--
ALTER TABLE `evaluation_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_one_per_target` (`evaluator_id`,`target_user_id`,`period_id`,`form_id`),
  ADD KEY `idx_period` (`period_id`),
  ADD KEY `idx_form` (`form_id`),
  ADD KEY `idx_evaluator` (`evaluator_id`),
  ADD KEY `idx_target` (`target_user_id`),
  ADD KEY `idx_eval_type` (`eval_type`);

--
-- Indexes for table `evaluation_tracker`
--
ALTER TABLE `evaluation_tracker`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_eval_submission` (`evaluator_id`,`target_user_id`,`eval_type`,`period_id`),
  ADD KEY `idx_evaluator` (`evaluator_id`),
  ADD KEY `idx_evaluatee` (`target_user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_period` (`period`),
  ADD KEY `idx_eval_type` (`eval_type`),
  ADD KEY `fk_et_period` (`period_id`),
  ADD KEY `fk_et_form` (`form_id`);

--
-- Indexes for table `faculty_levels`
--
ALTER TABLE `faculty_levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_level` (`user_id`,`level`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `login_confirmations`
--
ALTER TABLE `login_confirmations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_unread` (`is_read`,`created_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `peer_evaluation_results`
--
ALTER TABLE `peer_evaluation_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_target` (`target_user_id`);

--
-- Indexes for table `peer_evaluation_submissions`
--
ALTER TABLE `peer_evaluation_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_peer` (`period_id`,`evaluator_id`,`target_user_id`);

--
-- Indexes for table `questionnaire_answers`
--
ALTER TABLE `questionnaire_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tracker` (`tracker_id`),
  ADD KEY `idx_question` (`question_id`),
  ADD KEY `fk_qa_user_question` (`user_question_id`);

--
-- Indexes for table `questionnaire_forms`
--
ALTER TABLE `questionnaire_forms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sector` (`sector`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `fk_qf_creator` (`created_by`);

--
-- Indexes for table `questionnaire_migrations`
--
ALTER TABLE `questionnaire_migrations`
  ADD PRIMARY KEY (`migration_key`);

--
-- Indexes for table `questionnaire_questions`
--
ALTER TABLE `questionnaire_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_form` (`form_id`);

--
-- Indexes for table `question_categories`
--
ALTER TABLE `question_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_category_scope` (`target_type`,`eval_type`,`evaluator_role`,`category_name`);

--
-- Indexes for table `rating_certifications`
--
ALTER TABLE `rating_certifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_person_period` (`rated_user_id`,`period_id`),
  ADD KEY `idx_period` (`period_id`);

--
-- Indexes for table `role_change_log`
--
ALTER TABLE `role_change_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_changed` (`changed_at`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_performed_by` (`performed_by_id`);

--
-- Indexes for table `school_head_evaluation_assignments`
--
ALTER TABLE `school_head_evaluation_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_sh_assignment` (`evaluator_id`,`target_user_id`,`question_id`),
  ADD KEY `idx_sh_evaluator_target` (`evaluator_id`,`target_user_id`),
  ADD KEY `idx_sh_target` (`target_user_id`),
  ADD KEY `idx_sh_question` (`question_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_services_active` (`is_active`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_staff_email` (`email`),
  ADD KEY `idx_staff_active` (`is_active`);

--
-- Indexes for table `staff_availability`
--
ALTER TABLE `staff_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_staff_availability_staff_day` (`staff_id`,`day_of_week`),
  ADD KEY `idx_staff_availability_day_staff` (`day_of_week`,`staff_id`);

--
-- Indexes for table `staff_services`
--
ALTER TABLE `staff_services`
  ADD PRIMARY KEY (`staff_id`,`service_id`),
  ADD KEY `idx_staff_services_service_staff` (`service_id`,`staff_id`);

--
-- Indexes for table `student_security_answers`
--
ALTER TABLE `student_security_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_slot` (`user_id`,`slot`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `system_archives`
--
ALTER TABLE `system_archives`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_system_archive_period` (`period_id`),
  ADD KEY `idx_system_archive_year` (`school_year`),
  ADD KEY `idx_system_archive_status` (`status`);

--
-- Indexes for table `system_documents`
--
ALTER TABLE `system_documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `teaching_assignments`
--
ALTER TABLE `teaching_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_assignment` (`user_id`,`education_level`,`year_level`,`section`),
  ADD KEY `fk_ta_assigned_by` (`assigned_by`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_level_year` (`education_level`,`year_level`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_sector` (`sector`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `user_management_log`
--
ALTER TABLE `user_management_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin` (`admin_id`),
  ADD KEY `idx_target` (`target_id`),
  ADD KEY `idx_action` (`action`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_questions`
--
ALTER TABLE `user_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_eval` (`user_id`,`eval_type`),
  ADD KEY `idx_target_eval` (`target_type`,`eval_type`);

--
-- Indexes for table `user_question_categories`
--
ALTER TABLE `user_question_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_cat` (`user_id`,`target_type`,`eval_type`,`category_name`),
  ADD KEY `idx_user_eval` (`user_id`,`eval_type`);

--
-- Indexes for table `user_year_levels`
--
ALTER TABLE `user_year_levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_year` (`user_id`,`year_level`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `analytics_archive`
--
ALTER TABLE `analytics_archive`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `analytics_reports`
--
ALTER TABLE `analytics_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auth_attempts`
--
ALTER TABLE `auth_attempts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `business_hours`
--
ALTER TABLE `business_hours`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluation_answers`
--
ALTER TABLE `evaluation_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `evaluation_periods`
--
ALTER TABLE `evaluation_periods`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `evaluation_questions`
--
ALTER TABLE `evaluation_questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=273;

--
-- AUTO_INCREMENT for table `evaluation_reminders`
--
ALTER TABLE `evaluation_reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `evaluation_results`
--
ALTER TABLE `evaluation_results`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `evaluation_submissions`
--
ALTER TABLE `evaluation_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `evaluation_tracker`
--
ALTER TABLE `evaluation_tracker`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT for table `faculty_levels`
--
ALTER TABLE `faculty_levels`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `login_confirmations`
--
ALTER TABLE `login_confirmations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `peer_evaluation_results`
--
ALTER TABLE `peer_evaluation_results`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `peer_evaluation_submissions`
--
ALTER TABLE `peer_evaluation_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questionnaire_answers`
--
ALTER TABLE `questionnaire_answers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1201;

--
-- AUTO_INCREMENT for table `questionnaire_forms`
--
ALTER TABLE `questionnaire_forms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `questionnaire_questions`
--
ALTER TABLE `questionnaire_questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `question_categories`
--
ALTER TABLE `question_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4391;

--
-- AUTO_INCREMENT for table `rating_certifications`
--
ALTER TABLE `rating_certifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_change_log`
--
ALTER TABLE `role_change_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `school_head_evaluation_assignments`
--
ALTER TABLE `school_head_evaluation_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_availability`
--
ALTER TABLE `staff_availability`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_security_answers`
--
ALTER TABLE `student_security_answers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `system_archives`
--
ALTER TABLE `system_archives`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `system_documents`
--
ALTER TABLE `system_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teaching_assignments`
--
ALTER TABLE `teaching_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=241;

--
-- AUTO_INCREMENT for table `user_management_log`
--
ALTER TABLE `user_management_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_questions`
--
ALTER TABLE `user_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=559;

--
-- AUTO_INCREMENT for table `user_question_categories`
--
ALTER TABLE `user_question_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=182;

--
-- AUTO_INCREMENT for table `user_year_levels`
--
ALTER TABLE `user_year_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=230;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `analytics_reports`
--
ALTER TABLE `analytics_reports`
  ADD CONSTRAINT `fk_ar_generator` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fk_appointments_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `fk_appointments_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `fk_doc_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `evaluation_question_categories`
--
ALTER TABLE `evaluation_question_categories`
  ADD CONSTRAINT `fk_eqc_category` FOREIGN KEY (`category_id`) REFERENCES `question_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_eqc_question` FOREIGN KEY (`question_id`) REFERENCES `evaluation_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluation_results`
--
ALTER TABLE `evaluation_results`
  ADD CONSTRAINT `fk_er_form` FOREIGN KEY (`form_id`) REFERENCES `questionnaire_forms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_er_question` FOREIGN KEY (`question_id`) REFERENCES `evaluation_questions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_er_target` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_er_tracker` FOREIGN KEY (`tracker_id`) REFERENCES `evaluation_tracker` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `evaluation_submissions`
--
ALTER TABLE `evaluation_submissions`
  ADD CONSTRAINT `fk_es_evaluator` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_es_form` FOREIGN KEY (`form_id`) REFERENCES `questionnaire_forms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_es_period` FOREIGN KEY (`period_id`) REFERENCES `evaluation_periods` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_es_target` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluation_tracker`
--
ALTER TABLE `evaluation_tracker`
  ADD CONSTRAINT `fk_et_evaluator` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_et_form` FOREIGN KEY (`form_id`) REFERENCES `questionnaire_forms` (`id`),
  ADD CONSTRAINT `fk_et_period` FOREIGN KEY (`period_id`) REFERENCES `evaluation_periods` (`id`),
  ADD CONSTRAINT `fk_et_target` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `faculty_levels`
--
ALTER TABLE `faculty_levels`
  ADD CONSTRAINT `fk_faculty_levels_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_confirmations`
--
ALTER TABLE `login_confirmations`
  ADD CONSTRAINT `login_confirmations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questionnaire_answers`
--
ALTER TABLE `questionnaire_answers`
  ADD CONSTRAINT `fk_qa_tracker` FOREIGN KEY (`tracker_id`) REFERENCES `evaluation_tracker` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_qa_user_question` FOREIGN KEY (`user_question_id`) REFERENCES `user_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questionnaire_forms`
--
ALTER TABLE `questionnaire_forms`
  ADD CONSTRAINT `fk_qf_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `questionnaire_questions`
--
ALTER TABLE `questionnaire_questions`
  ADD CONSTRAINT `fk_qq_form` FOREIGN KEY (`form_id`) REFERENCES `questionnaire_forms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_availability`
--
ALTER TABLE `staff_availability`
  ADD CONSTRAINT `fk_staff_availability_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_services`
--
ALTER TABLE `staff_services`
  ADD CONSTRAINT `fk_staff_services_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_staff_services_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teaching_assignments`
--
ALTER TABLE `teaching_assignments`
  ADD CONSTRAINT `fk_ta_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ta_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_management_log`
--
ALTER TABLE `user_management_log`
  ADD CONSTRAINT `fk_uml_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_uml_target` FOREIGN KEY (`target_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_year_levels`
--
ALTER TABLE `user_year_levels`
  ADD CONSTRAINT `fk_uyl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
