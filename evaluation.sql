-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 19, 2026 at 08:37 AM
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
(32, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #1 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-19 12:44:36');

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
(18, 157, '2026-09-18 07:47:12');

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
(1, 70, 'Job Performance', 'Performs assigned duties competently and reliably.', 5),
(2, 70, 'Communication', 'Communicates clearly with colleagues and stakeholders.', 4),
(3, 70, 'Professionalism', 'Demonstrates professionalism in the workplace.', 5),
(4, 70, 'Punctuality', 'Is punctual and dependable.', 5),
(5, 70, 'Overall Rating', 'Overall, meets expectations for this role.', 5),
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
(2, '2026-2027 — School Year', '2026-2027', 'School Year', '2026-09-19', '2026-09-19', 1, '2026-08-05 18:15:42', 0),
(3, '2026-2027 — 1st Semester', '2026-2027', '1st Semester', '2026-09-19', '2026-08-26', 0, '2026-08-05 18:16:56', 0),
(4, '2025-2-26 — School Year', '2025-2-26', 'School Year', '2026-08-06', '2026-08-06', 0, '2026-08-06 11:03:33', 0),
(5, '2026-2027 — Summer', '2026-2027', 'Summer', '2026-08-26', '2026-08-26', 0, '2026-08-06 13:21:02', 0),
(6, '1st semester 2026-2027', '2026-2027', '1st Semester', '2026-08-21', '2026-08-22', 0, '2026-08-21 12:39:24', 0),
(7, '2026-2027 — 2nd Semester', '2026-2027', '2nd Semester', '2026-08-07', '2026-08-07', 0, '2026-08-24 09:52:56', 0),
(8, '2027-2028 — Summer', '2027-2028', 'Summer', '2026-08-07', '2026-08-07', 0, '2026-08-25 08:39:06', 0);

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
(243, 'EA', 'Service & Coordination', 'Demonstrates effective teaching strategies and methods.', 'staff', '2026-09-18 15:23:47', 1, '2026-09-18 07:23:47', 'shared');

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
(243, 4362);

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
(1, 5, 157, 151, 'student', 'college', '2026-08-15 13:03:02'),
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
(26, NULL, 167, 170, 'Faculty', NULL, '', NULL, NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-13 07:57:36', '2026-08-13 07:57:36', NULL, NULL, 'teacher'),
(29, NULL, 171, 136, 'Faculty', NULL, '', NULL, NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-14 14:58:06', '2026-08-17 17:58:18', NULL, NULL, 'multi_role'),
(38, NULL, 166, 136, 'Faculty', NULL, '', NULL, NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-16 12:33:07', '2026-08-17 17:58:18', NULL, NULL, 'multi_role'),
(45, NULL, 151, 170, 'Faculty', NULL, '', NULL, NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-17 16:48:00', '2026-08-17 16:48:00', NULL, NULL, 'teacher'),
(47, NULL, 175, 172, 'Faculty', NULL, '', NULL, NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-19 13:42:27', '2026-08-19 13:42:27', NULL, NULL, 'staff'),
(55, NULL, 161, 144, 'Faculty', NULL, '', NULL, NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-24 14:03:23', '2026-08-24 14:03:23', NULL, NULL, 'staff'),
(58, NULL, 125, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 5, NULL, '', 'ea', NULL, 'submitted', '2026-08-24 14:57:55', '2026-08-24 14:57:55', NULL, NULL, 'teacher'),
(62, NULL, 167, 136, 'Faculty', NULL, '', NULL, NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-26 10:35:38', '2026-08-27 15:02:30', NULL, NULL, 'staff'),
(64, NULL, 167, 172, 'Faculty', NULL, '', NULL, NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-26 11:18:16', '2026-08-27 15:02:30', NULL, NULL, 'staff'),
(65, NULL, 167, 181, 'Faculty', NULL, '', NULL, NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-26 11:18:28', '2026-08-27 15:02:30', NULL, NULL, 'staff'),
(67, NULL, 170, 183, 'Faculty', NULL, '', 3, NULL, NULL, 5, 4.63, 'N/A', 'faculty_peer', 'Teacher', 'submitted', '2026-08-26 11:42:05', '2026-08-27 15:02:30', NULL, NULL, 'teacher'),
(68, NULL, 170, 136, 'Faculty', NULL, '', 3, NULL, NULL, 5, 4.57, 'N/A', 'faculty_peer', 'Staff', 'submitted', '2026-08-26 12:08:51', '2026-08-27 15:02:30', NULL, NULL, 'teacher'),
(69, NULL, 171, 170, 'Faculty', NULL, '', NULL, NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-27 15:03:41', '2026-08-27 15:03:41', NULL, NULL, 'teacher'),
(70, NULL, 157, 144, 'Staff', 'college', 'staff_dean', NULL, NULL, NULL, 5, 4.80, 'N/A', 'dean', NULL, 'submitted', '2026-08-27 15:59:01', '2026-08-27 15:59:01', NULL, NULL, 'teacher'),
(71, NULL, 170, 139, 'Faculty', NULL, '', 3, NULL, NULL, 5, 4.75, 'N/A', 'faculty_peer', 'Teacher', 'submitted', '2026-08-27 16:02:21', '2026-08-27 16:02:21', NULL, NULL, 'teacher'),
(72, NULL, 125, 157, 'Faculty', NULL, '', NULL, NULL, NULL, 5, NULL, 'N/A', 'ea', NULL, 'submitted', '2026-08-27 16:55:58', '2026-08-27 16:55:58', NULL, NULL, 'teacher'),
(73, NULL, 157, 136, 'Faculty', 'college', 'faculty_dean', NULL, NULL, NULL, 3, 4.64, 'N/A', 'dean', NULL, 'submitted', '2026-08-28 19:54:10', '2026-08-28 19:54:10', NULL, NULL, 'teacher'),
(74, NULL, 157, 136, 'Faculty', 'college', 'faculty_dean', NULL, NULL, NULL, 3, 4.55, 'N/A', 'school_head', NULL, 'submitted', '2026-08-28 19:55:39', '2026-08-28 19:55:39', NULL, NULL, 'teacher'),
(75, NULL, 158, 183, 'Teacher', '', 'Teacher Performance Evaluation (Supervisor)', 5, NULL, NULL, 2, 4.67, 'N/A', 'supervisor_to_teacher', NULL, 'submitted', '2026-08-28 20:01:30', '2026-08-28 20:01:30', NULL, NULL, 'teacher'),
(76, NULL, 171, 183, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-29 10:29:36', '2026-08-29 10:29:36', NULL, NULL, 'teacher'),
(77, NULL, 125, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'errtyydd', 'ea', NULL, 'submitted', '2026-08-29 11:13:11', '2026-08-29 11:13:11', NULL, NULL, 'teacher'),
(78, NULL, 161, 144, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'very good', 'student', NULL, 'submitted', '2026-08-29 12:56:21', '2026-08-29 12:56:21', NULL, NULL, 'teacher'),
(79, NULL, 161, 183, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'good', 'student', NULL, 'submitted', '2026-08-29 12:57:03', '2026-08-29 12:57:03', NULL, NULL, 'teacher'),
(80, NULL, 161, 172, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'very good', 'student', NULL, 'submitted', '2026-08-29 12:57:39', '2026-08-29 12:57:39', NULL, NULL, 'staff'),
(81, NULL, 161, 181, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, '', 'student', NULL, 'submitted', '2026-08-29 12:58:14', '2026-08-29 12:58:14', NULL, NULL, 'staff'),
(82, NULL, 192, 144, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, '', 'student', NULL, 'submitted', '2026-08-29 13:04:08', '2026-08-29 13:04:08', NULL, NULL, 'teacher'),
(83, NULL, 192, 136, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, '', 'student', NULL, 'submitted', '2026-08-29 13:04:53', '2026-08-29 13:04:53', NULL, NULL, 'teacher'),
(84, NULL, 192, 172, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, '', 'student', NULL, 'submitted', '2026-08-29 13:05:19', '2026-08-29 13:05:19', NULL, NULL, 'staff'),
(86, NULL, 192, 152, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, '', 'student', NULL, 'submitted', '2026-08-29 13:07:08', '2026-08-29 13:07:08', NULL, NULL, 'multi_role'),
(87, NULL, 192, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, '', 'student', NULL, 'submitted', '2026-08-29 13:07:30', '2026-08-29 13:07:30', NULL, NULL, 'school_head'),
(88, NULL, 170, 172, 'Faculty', NULL, '', 3, NULL, NULL, 2, 4.50, 'N/A', 'faculty_peer', 'Staff', 'submitted', '2026-09-13 18:52:36', '2026-09-13 18:52:36', NULL, NULL, 'teacher'),
(89, NULL, 164, 139, 'Faculty', NULL, '', 3, NULL, NULL, 2, 4.67, 'N/A', 'faculty_peer', 'Faculty', 'submitted', '2026-09-13 18:54:47', '2026-09-13 18:54:47', NULL, NULL, 'teacher'),
(90, NULL, 144, 158, 'Faculty', NULL, '', 7, NULL, NULL, 3, 4.67, 'N/A', 'staff', 'Staff Evaluation', 'submitted', '2026-09-13 22:05:54', '2026-09-15 16:19:26', NULL, NULL, 'teacher'),
(91, NULL, 161, 194, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-14 08:30:27', '2026-09-14 08:30:27', NULL, NULL, 'teacher'),
(92, NULL, 161, 198, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-14 08:30:58', '2026-09-14 08:30:58', NULL, NULL, 'staff'),
(93, NULL, 161, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-14 08:31:12', '2026-09-14 08:31:12', NULL, NULL, 'school_head'),
(94, NULL, 170, 144, 'Faculty', NULL, '', 3, NULL, NULL, 3, 4.67, 'N/A', 'faculty_peer', 'Faculty', 'submitted', '2026-09-14 08:37:19', '2026-09-14 08:37:19', NULL, NULL, 'teacher'),
(95, NULL, 170, 172, 'Faculty', NULL, '', 3, NULL, NULL, 3, 4.50, 'N/A', 'faculty_peer', 'Staff', 'submitted', '2026-09-14 08:37:55', '2026-09-14 08:37:55', NULL, NULL, 'teacher'),
(96, NULL, 170, 157, 'Faculty', NULL, '', 3, NULL, NULL, 3, 4.75, 'N/A', 'faculty_peer', 'Dean / Principal', 'submitted', '2026-09-14 08:40:12', '2026-09-14 08:40:12', NULL, NULL, 'teacher'),
(97, NULL, 170, 158, 'Faculty', NULL, '', 3, NULL, NULL, 3, 5.00, 'N/A', 'faculty_peer', 'Dean / Principal', 'submitted', '2026-09-14 08:40:32', '2026-09-14 08:40:32', NULL, NULL, 'teacher'),
(98, NULL, 161, 157, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-14 08:42:23', '2026-09-14 08:42:23', NULL, NULL, 'school_head'),
(99, NULL, 171, 157, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-14 08:43:00', '2026-09-14 08:43:00', NULL, NULL, 'school_head'),
(100, NULL, 171, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-14 08:43:05', '2026-09-14 08:43:05', NULL, NULL, 'school_head'),
(101, NULL, 167, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-14 08:55:14', '2026-09-14 08:55:14', NULL, NULL, 'school_head'),
(102, NULL, 167, 157, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-14 08:55:59', '2026-09-14 08:55:59', NULL, NULL, 'school_head'),
(103, NULL, 166, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-14 09:34:50', '2026-09-14 09:34:50', NULL, NULL, 'school_head'),
(104, NULL, 149, 139, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-14 16:28:49', '2026-09-14 16:28:49', NULL, NULL, 'teacher'),
(105, NULL, 149, 172, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-14 16:29:01', '2026-09-14 16:29:01', NULL, NULL, 'staff'),
(106, NULL, 149, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-14 16:29:08', '2026-09-14 16:29:08', NULL, NULL, 'school_head'),
(107, NULL, 149, 157, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-14 16:29:19', '2026-09-14 16:29:19', NULL, NULL, 'school_head'),
(108, NULL, 157, 183, 'Faculty', 'college', 'school_head_dean_faculty', NULL, NULL, NULL, 3, 4.67, 'N/A', 'school_head', NULL, 'submitted', '2026-09-14 21:41:32', '2026-09-14 21:41:32', NULL, NULL, 'teacher'),
(109, NULL, 157, 172, 'Staff', 'college', 'school_head_dean_staff', NULL, NULL, NULL, 3, 5.00, 'N/A', 'school_head', NULL, 'submitted', '2026-09-15 07:47:25', '2026-09-15 07:47:25', NULL, NULL, 'teacher'),
(110, NULL, 157, 125, 'EA', 'college', 'school_head_dean_ea', NULL, NULL, NULL, 3, 4.67, 'N/A', 'school_head', NULL, 'submitted', '2026-09-15 07:49:43', '2026-09-15 07:49:43', NULL, NULL, 'teacher'),
(111, NULL, 207, 157, 'Faculty', NULL, '', 7, NULL, NULL, 3, 4.50, 'N/A', 'staff', 'Staff Evaluation', 'submitted', '2026-09-15 14:30:22', '2026-09-15 16:19:26', NULL, NULL, 'teacher'),
(113, NULL, 144, 157, 'Faculty', NULL, '', 7, NULL, NULL, 3, 4.50, 'N/A', 'staff', 'Staff Evaluation', 'submitted', '2026-09-15 16:11:34', '2026-09-15 16:19:26', NULL, NULL, 'teacher'),
(114, NULL, 144, 125, 'Faculty', NULL, '', 7, NULL, NULL, 3, 4.67, 'N/A', 'staff', 'Staff Evaluation', 'submitted', '2026-09-15 16:22:04', '2026-09-15 16:22:04', NULL, NULL, 'teacher'),
(115, NULL, 125, 181, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'N/A', 'ea', NULL, 'submitted', '2026-09-15 16:40:12', '2026-09-15 16:40:12', NULL, NULL, 'teacher'),
(116, NULL, 125, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'N/A', 'ea', NULL, 'submitted', '2026-09-15 16:41:50', '2026-09-15 16:41:50', NULL, NULL, 'teacher'),
(117, NULL, 158, 172, 'Staff', '', 'Principal Evaluation — Non-Teaching Staff', NULL, NULL, NULL, 2, 5.00, 'N/A', 'school_head', NULL, 'submitted', '2026-09-16 10:15:15', '2026-09-16 10:15:15', NULL, NULL, 'teacher'),
(118, NULL, 161, 170, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'wala', 'student', NULL, 'submitted', '2026-09-17 13:51:08', '2026-09-17 13:51:08', NULL, NULL, 'teacher'),
(119, NULL, 208, 198, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'N/A', 'ea', NULL, 'submitted', '2026-09-17 14:02:33', '2026-09-17 14:02:33', NULL, NULL, 'teacher'),
(120, NULL, 175, 144, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-18 07:45:03', '2026-09-18 07:45:03', NULL, NULL, 'teacher'),
(121, NULL, 175, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-18 07:47:29', '2026-09-18 07:47:29', NULL, NULL, 'school_head'),
(122, NULL, 175, 194, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-18 07:52:42', '2026-09-18 07:52:42', NULL, NULL, 'teacher'),
(123, NULL, 186, 144, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-18 08:08:03', '2026-09-18 08:08:03', NULL, NULL, 'teacher'),
(124, NULL, 186, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 3, NULL, 'N/A', 'student', NULL, 'submitted', '2026-09-18 08:08:52', '2026-09-18 08:08:52', NULL, NULL, 'school_head'),
(125, NULL, 158, 170, 'Faculty', '', 'Principal Evaluation — Faculty', NULL, NULL, NULL, 2, 5.00, '', 'school_head', NULL, 'submitted', '2026-09-18 14:48:11', '2026-09-18 14:48:11', NULL, NULL, 'teacher'),
(126, NULL, 208, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'N/A', 'ea', NULL, 'submitted', '2026-09-18 15:15:38', '2026-09-18 15:15:38', NULL, NULL, 'teacher'),
(127, NULL, 158, 139, 'Faculty', '', 'Principal Evaluation — Faculty', NULL, NULL, NULL, 2, 4.67, 'n/a', 'school_head', NULL, 'submitted', '2026-09-19 10:56:15', '2026-09-19 10:56:15', NULL, NULL, 'teacher');

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

--
-- Dumping data for table `login_confirmations`
--

INSERT INTO `login_confirmations` (`id`, `user_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(1, 125, '67393592b4aa8980465dafb25e8d1622609d269a03a31fe736fd604403ee3d87', '2026-07-26 21:57:23', 0, '2026-07-26 21:42:23'),
(2, 125, 'a0c97a948841b33a299aefa8db4005900fa8fdcb5c40325f9e838ae37497a0af', '2026-07-27 11:33:35', 0, '2026-07-27 11:18:35');

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
(44, 'evaluation_received', 144, 'You have received a new evaluation.', NULL, 0, '2026-09-14 08:37:19'),
(45, 'evaluation_received', 172, 'You have received a new evaluation.', NULL, 0, '2026-09-14 08:37:55'),
(46, 'evaluation_received', 157, 'You have received a new Dean / Principal evaluation.', NULL, 0, '2026-09-14 08:40:12'),
(47, 'evaluation_received', 158, 'You have received a new Dean / Principal evaluation.', NULL, 0, '2026-09-14 08:40:32'),
(48, 'evaluation_received', 157, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"Dean\",\"target_label\":\"Dean\"}', 0, '2026-09-15 14:30:22'),
(49, 'evaluation_received', 157, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"Dean\",\"target_label\":\"Dean\"}', 0, '2026-09-15 16:11:34'),
(50, 'evaluation_received', 125, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"EA\",\"target_label\":\"Executive Assistant\"}', 0, '2026-09-15 16:22:04'),
(51, 'designation_update', 209, 'Jessie A. Aquillo updated their designation from \"Personnel\" to \"Formation Services\".', '{\"user_id\":209,\"full_name\":\"Jessie A. Aquillo\",\"role\":\"staff\",\"old_desig\":\"Personnel\",\"new_desig\":\"Formation Services\"}', 0, '2026-09-18 15:48:41'),
(52, 'designation_update', 209, 'Jessie A. Aquillo updated their designation from \"Formation Services\" to \"Formation Services Coordinator/ CMO\".', '{\"user_id\":209,\"full_name\":\"Jessie A. Aquillo\",\"role\":\"staff\",\"old_desig\":\"Formation Services\",\"new_desig\":\"Formation Services Coordinator\\/ CMO\"}', 0, '2026-09-18 15:48:54');

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

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`) VALUES
(45, 125, '7cf325b585a72f624e5ae498c6586515f9566ad6797bbc57a479d085d5ec1b68', '2026-08-28 09:14:05'),
(49, 136, '9b58af58056bebf33035ce1910c02389777b69c3f0b95719e202ffbbb50b8a83', '2026-09-18 11:59:53');

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
(244, 26, 184, 'evaluation', NULL, NULL, 5.00, '2026-08-13 07:57:36', NULL),
(245, 26, 185, 'evaluation', NULL, NULL, 2.00, '2026-08-13 07:57:36', NULL),
(246, 26, 188, 'evaluation', NULL, NULL, 5.00, '2026-08-13 07:57:36', NULL),
(247, 26, 189, 'evaluation', NULL, NULL, 4.00, '2026-08-13 07:57:36', NULL),
(256, 29, 192, 'evaluation', NULL, NULL, 5.00, '2026-08-14 14:58:06', NULL),
(265, 38, 192, 'evaluation', NULL, NULL, 4.00, '2026-08-16 12:33:07', NULL),
(290, 45, 184, 'evaluation', NULL, NULL, 5.00, '2026-08-17 16:48:00', NULL),
(291, 45, 185, 'evaluation', NULL, NULL, 4.00, '2026-08-17 16:48:00', NULL),
(292, 45, 188, 'evaluation', NULL, NULL, 5.00, '2026-08-17 16:48:00', NULL),
(293, 45, 189, 'evaluation', NULL, NULL, 5.00, '2026-08-17 16:48:00', NULL),
(307, 55, NULL, 'user', 75, NULL, 5.00, '2026-08-24 14:03:23', NULL),
(308, 55, NULL, 'user', 73, NULL, 4.00, '2026-08-24 14:03:23', NULL),
(315, 58, 153, 'evaluation', NULL, NULL, 4.00, '2026-08-24 14:57:55', NULL),
(316, 58, 79, 'evaluation', NULL, NULL, 3.00, '2026-08-24 14:57:55', NULL),
(327, 62, NULL, 'user', 209, NULL, 4.00, '2026-08-26 10:35:38', NULL),
(328, 62, NULL, 'user', 210, NULL, 5.00, '2026-08-26 10:35:38', NULL),
(329, 62, NULL, 'user', 211, NULL, 4.00, '2026-08-26 10:35:38', NULL),
(330, 62, NULL, 'user', 212, NULL, 5.00, '2026-08-26 10:35:38', NULL),
(331, 62, NULL, 'user', 213, NULL, 5.00, '2026-08-26 10:35:38', NULL),
(332, 64, NULL, 'user', 199, NULL, 5.00, '2026-08-26 11:18:16', NULL),
(333, 64, NULL, 'user', 200, NULL, 5.00, '2026-08-26 11:18:16', NULL),
(334, 64, NULL, 'user', 201, NULL, 4.00, '2026-08-26 11:18:16', NULL),
(335, 64, NULL, 'user', 202, NULL, 5.00, '2026-08-26 11:18:16', NULL),
(336, 64, NULL, 'user', 203, NULL, 4.00, '2026-08-26 11:18:16', NULL),
(337, 65, NULL, 'user', 219, NULL, 5.00, '2026-08-26 11:18:28', NULL),
(338, 65, NULL, 'user', 220, NULL, 5.00, '2026-08-26 11:18:28', NULL),
(339, 65, NULL, 'user', 221, NULL, 4.00, '2026-08-26 11:18:28', NULL),
(340, 65, NULL, 'user', 222, NULL, 5.00, '2026-08-26 11:18:28', NULL),
(341, 65, NULL, 'user', 223, NULL, 4.00, '2026-08-26 11:18:28', NULL),
(347, 67, 201, 'evaluation', NULL, NULL, 5.00, '2026-08-26 11:42:05', NULL),
(348, 67, 202, 'evaluation', NULL, NULL, 5.00, '2026-08-26 11:42:05', NULL),
(349, 67, 206, 'evaluation', NULL, NULL, 4.00, '2026-08-26 11:42:05', NULL),
(350, 67, 203, 'evaluation', NULL, NULL, 4.00, '2026-08-26 11:42:05', NULL),
(351, 67, 204, 'evaluation', NULL, NULL, 5.00, '2026-08-26 11:42:05', NULL),
(352, 67, 205, 'evaluation', NULL, NULL, 5.00, '2026-08-26 11:42:05', NULL),
(353, 67, 207, 'evaluation', NULL, NULL, 4.00, '2026-08-26 11:42:05', NULL),
(354, 67, 208, 'evaluation', NULL, NULL, 5.00, '2026-08-26 11:42:05', NULL),
(355, 68, NULL, 'user', 224, NULL, 5.00, '2026-08-26 12:08:51', NULL),
(356, 68, NULL, 'user', 225, NULL, 5.00, '2026-08-26 12:08:51', NULL),
(357, 68, NULL, 'user', 226, NULL, 4.00, '2026-08-26 12:08:51', NULL),
(358, 68, NULL, 'user', 227, NULL, 5.00, '2026-08-26 12:08:51', NULL),
(359, 68, NULL, 'user', 228, NULL, 4.00, '2026-08-26 12:08:51', NULL),
(360, 68, NULL, 'user', 229, NULL, 5.00, '2026-08-26 12:08:51', NULL),
(361, 68, NULL, 'user', 230, NULL, 4.00, '2026-08-26 12:08:51', NULL),
(362, 69, 209, 'evaluation', NULL, NULL, 5.00, '2026-08-27 15:03:41', NULL),
(363, 69, 218, 'evaluation', NULL, NULL, 4.00, '2026-08-27 15:03:41', NULL),
(364, 69, 210, 'evaluation', NULL, NULL, 5.00, '2026-08-27 15:03:41', NULL),
(365, 69, 211, 'evaluation', NULL, NULL, 4.00, '2026-08-27 15:03:41', NULL),
(366, 69, 212, 'evaluation', NULL, NULL, 5.00, '2026-08-27 15:03:41', NULL),
(367, 69, 213, 'evaluation', NULL, NULL, 4.00, '2026-08-27 15:03:41', NULL),
(368, 69, 214, 'evaluation', NULL, NULL, 5.00, '2026-08-27 15:03:41', NULL),
(369, 69, 215, 'evaluation', NULL, NULL, 4.00, '2026-08-27 15:03:41', NULL),
(370, 69, 216, 'evaluation', NULL, NULL, 5.00, '2026-08-27 15:03:41', NULL),
(371, 69, 217, 'evaluation', NULL, NULL, 4.00, '2026-08-27 15:03:41', NULL),
(372, 69, 219, 'evaluation', NULL, NULL, 5.00, '2026-08-27 15:03:41', NULL),
(373, 71, 201, 'evaluation', NULL, NULL, 5.00, '2026-08-27 16:02:21', NULL),
(374, 71, 202, 'evaluation', NULL, NULL, 5.00, '2026-08-27 16:02:21', NULL),
(375, 71, 206, 'evaluation', NULL, NULL, 4.00, '2026-08-27 16:02:21', NULL),
(376, 71, 203, 'evaluation', NULL, NULL, 5.00, '2026-08-27 16:02:21', NULL),
(377, 71, 204, 'evaluation', NULL, NULL, 4.00, '2026-08-27 16:02:21', NULL),
(378, 71, 205, 'evaluation', NULL, NULL, 5.00, '2026-08-27 16:02:21', NULL),
(379, 71, 207, 'evaluation', NULL, NULL, 5.00, '2026-08-27 16:02:21', NULL),
(380, 71, 208, 'evaluation', NULL, NULL, 5.00, '2026-08-27 16:02:21', NULL),
(381, 72, 80, 'evaluation', NULL, NULL, 5.00, '2026-08-27 16:55:58', NULL),
(382, 72, 82, 'evaluation', NULL, NULL, 4.00, '2026-08-27 16:55:58', NULL),
(383, 72, 83, 'evaluation', NULL, NULL, 5.00, '2026-08-27 16:55:58', NULL),
(384, 74, 209, 'evaluation', NULL, NULL, 5.00, '2026-08-28 19:55:39', NULL),
(385, 74, 218, 'evaluation', NULL, NULL, 4.00, '2026-08-28 19:55:39', NULL),
(386, 74, 210, 'evaluation', NULL, NULL, 4.00, '2026-08-28 19:55:39', NULL),
(387, 74, 211, 'evaluation', NULL, NULL, 5.00, '2026-08-28 19:55:39', NULL),
(388, 74, 212, 'evaluation', NULL, NULL, 4.00, '2026-08-28 19:55:39', NULL),
(389, 74, 213, 'evaluation', NULL, NULL, 5.00, '2026-08-28 19:55:39', NULL),
(390, 74, 214, 'evaluation', NULL, NULL, 4.00, '2026-08-28 19:55:39', NULL),
(391, 74, 215, 'evaluation', NULL, NULL, 5.00, '2026-08-28 19:55:39', NULL),
(392, 74, 216, 'evaluation', NULL, NULL, 5.00, '2026-08-28 19:55:39', NULL),
(393, 74, 217, 'evaluation', NULL, NULL, 5.00, '2026-08-28 19:55:39', NULL),
(394, 74, 219, 'evaluation', NULL, NULL, 4.00, '2026-08-28 19:55:39', NULL),
(395, 75, 4, 'evaluation', NULL, NULL, 5.00, '2026-08-28 20:01:30', NULL),
(396, 75, 5, 'evaluation', NULL, NULL, 4.00, '2026-08-28 20:01:30', NULL),
(397, 75, 6, 'evaluation', NULL, NULL, 5.00, '2026-08-28 20:01:30', NULL),
(398, 76, 209, 'evaluation', NULL, NULL, 5.00, '2026-08-29 10:29:36', NULL),
(399, 76, 218, 'evaluation', NULL, NULL, 4.00, '2026-08-29 10:29:36', NULL),
(400, 76, 210, 'evaluation', NULL, NULL, 5.00, '2026-08-29 10:29:36', NULL),
(401, 76, 211, 'evaluation', NULL, NULL, 5.00, '2026-08-29 10:29:36', NULL),
(402, 76, 212, 'evaluation', NULL, NULL, 4.00, '2026-08-29 10:29:36', NULL),
(403, 76, 213, 'evaluation', NULL, NULL, 5.00, '2026-08-29 10:29:36', NULL),
(404, 76, 214, 'evaluation', NULL, NULL, 4.00, '2026-08-29 10:29:36', NULL),
(405, 76, 215, 'evaluation', NULL, NULL, 4.00, '2026-08-29 10:29:36', NULL),
(406, 76, 216, 'evaluation', NULL, NULL, 5.00, '2026-08-29 10:29:36', NULL),
(407, 76, 217, 'evaluation', NULL, NULL, 5.00, '2026-08-29 10:29:36', NULL),
(408, 76, 219, 'evaluation', NULL, NULL, 3.00, '2026-08-29 10:29:36', NULL),
(409, 77, 153, 'evaluation', NULL, NULL, 5.00, '2026-08-29 11:13:11', NULL),
(410, 77, 79, 'evaluation', NULL, NULL, 5.00, '2026-08-29 11:13:11', NULL),
(411, 78, 209, 'evaluation', NULL, NULL, 5.00, '2026-08-29 12:56:21', NULL),
(412, 78, 218, 'evaluation', NULL, NULL, 5.00, '2026-08-29 12:56:21', NULL),
(413, 78, 210, 'evaluation', NULL, NULL, 5.00, '2026-08-29 12:56:21', NULL),
(414, 78, 211, 'evaluation', NULL, NULL, 5.00, '2026-08-29 12:56:21', NULL),
(415, 78, 212, 'evaluation', NULL, NULL, 5.00, '2026-08-29 12:56:21', NULL),
(416, 78, 213, 'evaluation', NULL, NULL, 5.00, '2026-08-29 12:56:21', NULL),
(417, 78, 214, 'evaluation', NULL, NULL, 5.00, '2026-08-29 12:56:21', NULL),
(418, 78, 215, 'evaluation', NULL, NULL, 5.00, '2026-08-29 12:56:21', NULL),
(419, 78, 216, 'evaluation', NULL, NULL, 5.00, '2026-08-29 12:56:21', NULL),
(420, 78, 217, 'evaluation', NULL, NULL, 5.00, '2026-08-29 12:56:21', NULL),
(421, 78, 219, 'evaluation', NULL, NULL, 5.00, '2026-08-29 12:56:21', NULL),
(422, 79, 209, 'evaluation', NULL, NULL, 4.00, '2026-08-29 12:57:03', NULL),
(423, 79, 218, 'evaluation', NULL, NULL, 4.00, '2026-08-29 12:57:03', NULL),
(424, 79, 210, 'evaluation', NULL, NULL, 4.00, '2026-08-29 12:57:03', NULL),
(425, 79, 211, 'evaluation', NULL, NULL, 4.00, '2026-08-29 12:57:03', NULL),
(426, 79, 212, 'evaluation', NULL, NULL, 4.00, '2026-08-29 12:57:03', NULL),
(427, 79, 213, 'evaluation', NULL, NULL, 4.00, '2026-08-29 12:57:03', NULL),
(428, 79, 214, 'evaluation', NULL, NULL, 4.00, '2026-08-29 12:57:03', NULL),
(429, 79, 215, 'evaluation', NULL, NULL, 4.00, '2026-08-29 12:57:03', NULL),
(430, 79, 216, 'evaluation', NULL, NULL, 4.00, '2026-08-29 12:57:03', NULL),
(431, 79, 217, 'evaluation', NULL, NULL, 4.00, '2026-08-29 12:57:03', NULL),
(432, 79, 219, 'evaluation', NULL, NULL, 4.00, '2026-08-29 12:57:03', NULL),
(433, 80, NULL, 'user', 199, NULL, 5.00, '2026-08-29 12:57:39', NULL),
(434, 80, NULL, 'user', 200, NULL, 5.00, '2026-08-29 12:57:39', NULL),
(435, 80, NULL, 'user', 201, NULL, 5.00, '2026-08-29 12:57:39', NULL),
(436, 80, NULL, 'user', 202, NULL, 5.00, '2026-08-29 12:57:39', NULL),
(437, 80, NULL, 'user', 203, NULL, 5.00, '2026-08-29 12:57:39', NULL),
(438, 81, NULL, 'user', 219, NULL, 5.00, '2026-08-29 12:58:14', NULL),
(439, 81, NULL, 'user', 220, NULL, 5.00, '2026-08-29 12:58:14', NULL),
(440, 81, NULL, 'user', 221, NULL, 5.00, '2026-08-29 12:58:14', NULL),
(441, 81, NULL, 'user', 222, NULL, 5.00, '2026-08-29 12:58:14', NULL),
(442, 81, NULL, 'user', 223, NULL, 5.00, '2026-08-29 12:58:14', NULL),
(443, 82, 209, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:08', NULL),
(444, 82, 218, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:08', NULL),
(445, 82, 210, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:08', NULL),
(446, 82, 211, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:08', NULL),
(447, 82, 212, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:08', NULL),
(448, 82, 213, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:08', NULL),
(449, 82, 214, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:08', NULL),
(450, 82, 215, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:08', NULL),
(451, 82, 216, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:08', NULL),
(452, 82, 217, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:08', NULL),
(453, 82, 219, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:08', NULL),
(454, 83, 209, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:53', NULL),
(455, 83, 218, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:53', NULL),
(456, 83, 210, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:53', NULL),
(457, 83, 211, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:53', NULL),
(458, 83, 212, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:53', NULL),
(459, 83, 213, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:53', NULL),
(460, 83, 214, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:53', NULL),
(461, 83, 215, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:53', NULL),
(462, 83, 216, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:53', NULL),
(463, 83, 217, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:53', NULL),
(464, 83, 219, 'evaluation', NULL, NULL, 5.00, '2026-08-29 13:04:53', NULL),
(465, 84, NULL, 'user', 199, NULL, 5.00, '2026-08-29 13:05:19', NULL),
(466, 84, NULL, 'user', 200, NULL, 5.00, '2026-08-29 13:05:19', NULL),
(467, 84, NULL, 'user', 201, NULL, 5.00, '2026-08-29 13:05:19', NULL),
(468, 84, NULL, 'user', 202, NULL, 5.00, '2026-08-29 13:05:19', NULL),
(469, 84, NULL, 'user', 203, NULL, 5.00, '2026-08-29 13:05:19', NULL),
(470, 86, NULL, 'user', 92, NULL, 5.00, '2026-08-29 13:07:08', NULL),
(471, 86, NULL, 'user', 93, NULL, 5.00, '2026-08-29 13:07:08', NULL),
(472, 86, NULL, 'user', 94, NULL, 5.00, '2026-08-29 13:07:08', NULL),
(473, 86, NULL, 'user', 95, NULL, 5.00, '2026-08-29 13:07:08', NULL),
(474, 86, NULL, 'user', 96, NULL, 5.00, '2026-08-29 13:07:08', NULL),
(475, 86, NULL, 'user', 97, NULL, 5.00, '2026-08-29 13:07:08', NULL),
(476, 86, NULL, 'user', 98, NULL, 5.00, '2026-08-29 13:07:08', NULL),
(477, 86, NULL, 'user', 99, NULL, 5.00, '2026-08-29 13:07:08', NULL),
(478, 86, NULL, 'user', 100, NULL, 5.00, '2026-08-29 13:07:08', NULL),
(479, 86, NULL, 'user', 101, NULL, 5.00, '2026-08-29 13:07:08', NULL),
(480, 87, NULL, 'user', 232, NULL, 5.00, '2026-08-29 13:07:30', NULL),
(481, 88, NULL, 'user', 237, NULL, 5.00, '2026-09-13 18:52:36', NULL),
(482, 88, NULL, 'user', 238, NULL, 4.00, '2026-09-13 18:52:36', NULL),
(483, 89, 206, 'evaluation', NULL, NULL, 5.00, '2026-09-13 18:54:47', NULL),
(484, 89, 203, 'evaluation', NULL, NULL, 4.00, '2026-09-13 18:54:47', NULL),
(485, 89, 204, 'evaluation', NULL, NULL, 5.00, '2026-09-13 18:54:47', NULL),
(486, 89, 205, 'evaluation', NULL, NULL, 4.00, '2026-09-13 18:54:47', NULL),
(487, 89, 207, 'evaluation', NULL, NULL, 5.00, '2026-09-13 18:54:47', NULL),
(488, 89, 208, 'evaluation', NULL, NULL, 5.00, '2026-09-13 18:54:47', NULL),
(489, 90, 235, 'evaluation', NULL, NULL, 5.00, '2026-09-13 22:05:54', NULL),
(490, 90, 233, 'evaluation', NULL, NULL, 4.00, '2026-09-13 22:05:54', NULL),
(491, 90, 236, 'evaluation', NULL, NULL, 5.00, '2026-09-13 22:05:54', NULL),
(492, 91, 209, 'evaluation', NULL, NULL, 5.00, '2026-09-14 08:30:27', NULL),
(493, 91, 218, 'evaluation', NULL, NULL, 5.00, '2026-09-14 08:30:27', NULL),
(494, 91, 210, 'evaluation', NULL, NULL, 4.00, '2026-09-14 08:30:27', NULL),
(495, 91, 211, 'evaluation', NULL, NULL, 5.00, '2026-09-14 08:30:27', NULL),
(496, 91, 212, 'evaluation', NULL, NULL, 5.00, '2026-09-14 08:30:27', NULL),
(497, 91, 213, 'evaluation', NULL, NULL, 4.00, '2026-09-14 08:30:27', NULL),
(498, 91, 214, 'evaluation', NULL, NULL, 5.00, '2026-09-14 08:30:27', NULL),
(499, 91, 215, 'evaluation', NULL, NULL, 4.00, '2026-09-14 08:30:27', NULL),
(500, 91, 216, 'evaluation', NULL, NULL, 5.00, '2026-09-14 08:30:27', NULL),
(501, 91, 217, 'evaluation', NULL, NULL, 4.00, '2026-09-14 08:30:27', NULL),
(502, 91, 219, 'evaluation', NULL, NULL, 3.00, '2026-09-14 08:30:27', NULL),
(503, 92, NULL, 'user', 236, NULL, 5.00, '2026-09-14 08:30:58', NULL),
(504, 93, NULL, 'user', 232, NULL, 5.00, '2026-09-14 08:31:12', NULL),
(505, 94, 206, 'evaluation', NULL, NULL, 5.00, '2026-09-14 08:37:19', NULL),
(506, 94, 203, 'evaluation', NULL, NULL, 4.00, '2026-09-14 08:37:19', NULL),
(507, 94, 204, 'evaluation', NULL, NULL, 5.00, '2026-09-14 08:37:19', NULL),
(508, 94, 205, 'evaluation', NULL, NULL, 4.00, '2026-09-14 08:37:19', NULL),
(509, 94, 207, 'evaluation', NULL, NULL, 5.00, '2026-09-14 08:37:19', NULL),
(510, 94, 208, 'evaluation', NULL, NULL, 5.00, '2026-09-14 08:37:19', NULL),
(511, 95, NULL, 'user', 237, NULL, 5.00, '2026-09-14 08:37:55', NULL),
(512, 95, NULL, 'user', 238, NULL, 4.00, '2026-09-14 08:37:55', NULL),
(513, 96, NULL, 'user', 239, NULL, 5.00, '2026-09-14 08:40:12', NULL),
(514, 96, NULL, 'user', 240, NULL, 5.00, '2026-09-14 08:40:12', NULL),
(515, 96, NULL, 'user', 241, NULL, 4.00, '2026-09-14 08:40:12', NULL),
(516, 96, NULL, 'user', 242, NULL, 5.00, '2026-09-14 08:40:12', NULL),
(517, 97, NULL, 'user', 233, NULL, 5.00, '2026-09-14 08:40:32', NULL),
(518, 98, NULL, 'user', 243, NULL, 5.00, '2026-09-14 08:42:23', NULL),
(519, 99, NULL, 'user', 243, NULL, 5.00, '2026-09-14 08:43:00', NULL),
(520, 100, NULL, 'user', 232, NULL, 5.00, '2026-09-14 08:43:05', NULL),
(521, 101, NULL, 'user', 232, NULL, 5.00, '2026-09-14 08:55:14', NULL),
(522, 101, NULL, 'user', 244, NULL, 4.00, '2026-09-14 08:55:14', NULL),
(523, 101, NULL, 'user', 245, NULL, 5.00, '2026-09-14 08:55:14', NULL),
(524, 102, NULL, 'user', 246, NULL, 5.00, '2026-09-14 08:55:59', NULL),
(525, 102, NULL, 'user', 247, NULL, 4.00, '2026-09-14 08:55:59', NULL),
(526, 102, NULL, 'user', 243, NULL, 5.00, '2026-09-14 08:55:59', NULL),
(527, 103, NULL, 'user', 232, NULL, 5.00, '2026-09-14 09:34:50', NULL),
(528, 103, NULL, 'user', 244, NULL, 5.00, '2026-09-14 09:34:50', NULL),
(529, 103, NULL, 'user', 245, NULL, 4.00, '2026-09-14 09:34:50', NULL),
(530, 104, 209, 'evaluation', NULL, NULL, 5.00, '2026-09-14 16:28:49', NULL),
(531, 104, 218, 'evaluation', NULL, NULL, 4.00, '2026-09-14 16:28:49', NULL),
(532, 104, 210, 'evaluation', NULL, NULL, 5.00, '2026-09-14 16:28:49', NULL),
(533, 104, 211, 'evaluation', NULL, NULL, 4.00, '2026-09-14 16:28:49', NULL),
(534, 104, 212, 'evaluation', NULL, NULL, 5.00, '2026-09-14 16:28:49', NULL),
(535, 104, 213, 'evaluation', NULL, NULL, 5.00, '2026-09-14 16:28:49', NULL),
(536, 104, 214, 'evaluation', NULL, NULL, 4.00, '2026-09-14 16:28:49', NULL),
(537, 104, 215, 'evaluation', NULL, NULL, 5.00, '2026-09-14 16:28:49', NULL),
(538, 104, 216, 'evaluation', NULL, NULL, 5.00, '2026-09-14 16:28:49', NULL),
(539, 104, 217, 'evaluation', NULL, NULL, 4.00, '2026-09-14 16:28:49', NULL),
(540, 104, 219, 'evaluation', NULL, NULL, 5.00, '2026-09-14 16:28:49', NULL),
(541, 105, NULL, 'user', 199, NULL, 5.00, '2026-09-14 16:29:01', NULL),
(542, 105, NULL, 'user', 200, NULL, 4.00, '2026-09-14 16:29:01', NULL),
(543, 105, NULL, 'user', 201, NULL, 5.00, '2026-09-14 16:29:01', NULL),
(544, 105, NULL, 'user', 202, NULL, 5.00, '2026-09-14 16:29:01', NULL),
(545, 105, NULL, 'user', 203, NULL, 4.00, '2026-09-14 16:29:01', NULL),
(546, 106, NULL, 'user', 232, NULL, 5.00, '2026-09-14 16:29:08', NULL),
(547, 106, NULL, 'user', 244, NULL, 4.00, '2026-09-14 16:29:08', NULL),
(548, 106, NULL, 'user', 245, NULL, 5.00, '2026-09-14 16:29:08', NULL),
(549, 107, NULL, 'user', 246, NULL, 5.00, '2026-09-14 16:29:19', NULL),
(550, 107, NULL, 'user', 247, NULL, 5.00, '2026-09-14 16:29:19', NULL),
(551, 107, NULL, 'user', 243, NULL, 4.00, '2026-09-14 16:29:19', NULL),
(552, 108, 224, 'evaluation', NULL, NULL, 5.00, '2026-09-14 21:41:32', NULL),
(553, 108, 226, 'evaluation', NULL, NULL, 4.00, '2026-09-14 21:41:32', NULL),
(554, 108, 230, 'evaluation', NULL, NULL, 5.00, '2026-09-14 21:41:32', NULL),
(555, 109, NULL, 'user', 234, NULL, 5.00, '2026-09-15 07:47:25', NULL),
(556, 109, NULL, 'user', 235, NULL, 5.00, '2026-09-15 07:47:25', NULL),
(557, 110, 237, 'evaluation', NULL, NULL, 5.00, '2026-09-15 07:49:43', NULL),
(558, 110, 238, 'evaluation', NULL, NULL, 4.00, '2026-09-15 07:49:43', NULL),
(559, 110, 239, 'evaluation', NULL, NULL, 5.00, '2026-09-15 07:49:43', NULL),
(560, 111, 231, 'evaluation', NULL, NULL, 5.00, '2026-09-15 14:30:22', NULL),
(561, 111, 232, 'evaluation', NULL, NULL, 4.00, '2026-09-15 14:30:22', NULL),
(562, 113, 231, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:11:34', NULL),
(563, 113, 232, 'evaluation', NULL, NULL, 4.00, '2026-09-15 16:11:34', NULL),
(564, 114, 234, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:22:04', NULL),
(565, 114, 240, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:22:04', NULL),
(566, 114, 241, 'evaluation', NULL, NULL, 4.00, '2026-09-15 16:22:04', NULL),
(567, 115, 295, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:40:12', NULL),
(568, 115, 296, 'evaluation', NULL, NULL, 4.00, '2026-09-15 16:40:12', NULL),
(569, 115, 297, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:40:12', NULL),
(570, 115, 298, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:40:12', NULL),
(571, 115, 299, 'evaluation', NULL, NULL, 4.00, '2026-09-15 16:40:12', NULL),
(572, 116, 315, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:41:50', NULL),
(573, 116, 157, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:41:50', NULL),
(574, 116, 314, 'evaluation', NULL, NULL, 4.00, '2026-09-15 16:41:50', NULL),
(575, 116, 154, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:41:50', NULL),
(576, 116, 155, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:41:50', NULL),
(577, 116, 158, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:41:50', NULL),
(578, 116, 159, 'evaluation', NULL, NULL, 5.00, '2026-09-15 16:41:50', NULL),
(579, 116, 156, 'evaluation', NULL, NULL, 4.00, '2026-09-15 16:41:50', NULL),
(580, 117, NULL, 'user', 234, NULL, 5.00, '2026-09-16 10:15:15', NULL),
(581, 117, NULL, 'user', 235, NULL, 5.00, '2026-09-16 10:15:15', NULL),
(582, 118, 209, 'evaluation', NULL, NULL, 5.00, '2026-09-17 13:51:08', NULL),
(583, 118, 218, 'evaluation', NULL, NULL, 4.00, '2026-09-17 13:51:08', NULL),
(584, 118, 210, 'evaluation', NULL, NULL, 4.00, '2026-09-17 13:51:08', NULL),
(585, 118, 211, 'evaluation', NULL, NULL, 4.00, '2026-09-17 13:51:08', NULL),
(586, 118, 212, 'evaluation', NULL, NULL, 4.00, '2026-09-17 13:51:08', NULL),
(587, 118, 213, 'evaluation', NULL, NULL, 5.00, '2026-09-17 13:51:08', NULL),
(588, 118, 214, 'evaluation', NULL, NULL, 4.00, '2026-09-17 13:51:08', NULL),
(589, 118, 215, 'evaluation', NULL, NULL, 5.00, '2026-09-17 13:51:08', NULL),
(590, 118, 216, 'evaluation', NULL, NULL, 4.00, '2026-09-17 13:51:08', NULL),
(591, 118, 217, 'evaluation', NULL, NULL, 5.00, '2026-09-17 13:51:08', NULL),
(592, 118, 219, 'evaluation', NULL, NULL, 4.00, '2026-09-17 13:51:08', NULL),
(593, 119, 300, 'evaluation', NULL, NULL, 5.00, '2026-09-17 14:02:33', NULL),
(594, 120, 209, 'evaluation', NULL, NULL, 5.00, '2026-09-18 07:45:03', NULL),
(595, 120, 218, 'evaluation', NULL, NULL, 5.00, '2026-09-18 07:45:03', NULL),
(596, 120, 210, 'evaluation', NULL, NULL, 4.00, '2026-09-18 07:45:03', NULL),
(597, 120, 211, 'evaluation', NULL, NULL, 5.00, '2026-09-18 07:45:03', NULL),
(598, 120, 212, 'evaluation', NULL, NULL, 4.00, '2026-09-18 07:45:03', NULL),
(599, 120, 213, 'evaluation', NULL, NULL, 5.00, '2026-09-18 07:45:03', NULL),
(600, 120, 214, 'evaluation', NULL, NULL, 5.00, '2026-09-18 07:45:03', NULL),
(601, 120, 215, 'evaluation', NULL, NULL, 4.00, '2026-09-18 07:45:03', NULL),
(602, 120, 216, 'evaluation', NULL, NULL, 5.00, '2026-09-18 07:45:03', NULL),
(603, 120, 217, 'evaluation', NULL, NULL, 5.00, '2026-09-18 07:45:03', NULL),
(604, 120, 219, 'evaluation', NULL, NULL, 4.00, '2026-09-18 07:45:03', NULL),
(605, 121, NULL, 'user', 232, NULL, 5.00, '2026-09-18 07:47:29', NULL),
(606, 121, NULL, 'user', 244, NULL, 4.00, '2026-09-18 07:47:29', NULL),
(607, 121, NULL, 'user', 245, NULL, 5.00, '2026-09-18 07:47:29', NULL),
(608, 122, 209, 'evaluation', NULL, NULL, 5.00, '2026-09-18 07:52:42', NULL),
(609, 122, 218, 'evaluation', NULL, NULL, 5.00, '2026-09-18 07:52:42', NULL),
(610, 122, 210, 'evaluation', NULL, NULL, 4.00, '2026-09-18 07:52:42', NULL),
(611, 122, 211, 'evaluation', NULL, NULL, 5.00, '2026-09-18 07:52:42', NULL),
(612, 122, 212, 'evaluation', NULL, NULL, 4.00, '2026-09-18 07:52:42', NULL),
(613, 122, 213, 'evaluation', NULL, NULL, 5.00, '2026-09-18 07:52:42', NULL),
(614, 122, 214, 'evaluation', NULL, NULL, 5.00, '2026-09-18 07:52:42', NULL),
(615, 122, 215, 'evaluation', NULL, NULL, 4.00, '2026-09-18 07:52:42', NULL),
(616, 122, 216, 'evaluation', NULL, NULL, 4.00, '2026-09-18 07:52:42', NULL),
(617, 122, 217, 'evaluation', NULL, NULL, 5.00, '2026-09-18 07:52:42', NULL),
(618, 122, 219, 'evaluation', NULL, NULL, 4.00, '2026-09-18 07:52:42', NULL),
(619, 123, 209, 'evaluation', NULL, NULL, 5.00, '2026-09-18 08:08:03', NULL),
(620, 123, 218, 'evaluation', NULL, NULL, 4.00, '2026-09-18 08:08:03', NULL),
(621, 123, 210, 'evaluation', NULL, NULL, 5.00, '2026-09-18 08:08:03', NULL),
(622, 123, 211, 'evaluation', NULL, NULL, 5.00, '2026-09-18 08:08:03', NULL),
(623, 123, 212, 'evaluation', NULL, NULL, 4.00, '2026-09-18 08:08:03', NULL),
(624, 123, 213, 'evaluation', NULL, NULL, 5.00, '2026-09-18 08:08:03', NULL),
(625, 123, 214, 'evaluation', NULL, NULL, 5.00, '2026-09-18 08:08:03', NULL),
(626, 123, 215, 'evaluation', NULL, NULL, 4.00, '2026-09-18 08:08:03', NULL),
(627, 123, 216, 'evaluation', NULL, NULL, 5.00, '2026-09-18 08:08:03', NULL),
(628, 123, 217, 'evaluation', NULL, NULL, 4.00, '2026-09-18 08:08:03', NULL),
(629, 123, 219, 'evaluation', NULL, NULL, 5.00, '2026-09-18 08:08:03', NULL),
(630, 124, NULL, 'user', 232, NULL, 5.00, '2026-09-18 08:08:52', NULL),
(631, 124, NULL, 'user', 244, NULL, 4.00, '2026-09-18 08:08:52', NULL),
(632, 124, NULL, 'user', 245, NULL, 5.00, '2026-09-18 08:08:52', NULL),
(633, 125, 225, 'evaluation', NULL, NULL, 5.00, '2026-09-18 14:48:11', NULL),
(634, 125, 227, 'evaluation', NULL, NULL, 5.00, '2026-09-18 14:48:11', NULL),
(635, 125, 229, 'evaluation', NULL, NULL, 5.00, '2026-09-18 14:48:11', NULL),
(636, 126, 315, 'evaluation', NULL, NULL, 5.00, '2026-09-18 15:15:38', NULL),
(637, 126, 319, 'evaluation', NULL, NULL, 5.00, '2026-09-18 15:15:38', NULL),
(638, 126, 157, 'evaluation', NULL, NULL, 4.00, '2026-09-18 15:15:38', NULL),
(639, 126, 314, 'evaluation', NULL, NULL, 5.00, '2026-09-18 15:15:38', NULL),
(640, 126, 154, 'evaluation', NULL, NULL, 5.00, '2026-09-18 15:15:38', NULL),
(641, 126, 155, 'evaluation', NULL, NULL, 4.00, '2026-09-18 15:15:38', NULL),
(642, 126, 158, 'evaluation', NULL, NULL, 5.00, '2026-09-18 15:15:38', NULL),
(643, 126, 159, 'evaluation', NULL, NULL, 5.00, '2026-09-18 15:15:38', NULL),
(644, 126, 156, 'evaluation', NULL, NULL, 5.00, '2026-09-18 15:15:38', NULL),
(645, 127, 225, 'evaluation', NULL, NULL, 5.00, '2026-09-19 10:56:15', NULL),
(646, 127, 227, 'evaluation', NULL, NULL, 4.00, '2026-09-19 10:56:15', NULL),
(647, 127, 229, 'evaluation', NULL, NULL, 5.00, '2026-09-19 10:56:15', NULL);

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
(4382, 'EA', 'Responsiveness', 'school_head', 4, '2026-09-13 17:05:28', 'principal');

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
(18, 209, NULL, NULL, 'staff', 'staff', 'Formation Services', 'Formation Services Coordinator/ CMO', '2026-09-18 15:48:54');

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
(1, 3, '2026-2027 — 1st Semester', '2026-2027', 208, 'Lorraine R. Sabay', '2026-09-19 11:05:57', '2026-09-19 12:44:36', 208, 'restored', 236, '{\"evaluation_tracker\":35,\"questionnaire_answers\":190,\"evaluation_answers\":11,\"evaluation_submissions\":0,\"evaluation_results\":0,\"peer_evaluation_submissions\":0,\"peer_evaluation_results\":0,\"evaluation_reminders\":0,\"analytics_reports\":0,\"notifications\":0,\"activity_log_snapshot\":0}', '{\"evaluation_tracker\":[{\"id\":\"73\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"136\",\"eval_bucket\":\"Faculty\",\"level\":\"college\",\"form_type\":\"faculty_dean\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":\"4.64\",\"remarks\":\"N/A\",\"eval_type\":\"dean\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-28 19:54:10\",\"updated_at\":\"2026-08-28 19:54:10\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"74\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"136\",\"eval_bucket\":\"Faculty\",\"level\":\"college\",\"form_type\":\"faculty_dean\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":\"4.55\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-28 19:55:39\",\"updated_at\":\"2026-08-28 19:55:39\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"78\",\"legacy_submission_id\":null,\"evaluator_id\":\"161\",\"target_user_id\":\"144\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"very good\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-29 12:56:21\",\"updated_at\":\"2026-08-29 12:56:21\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"79\",\"legacy_submission_id\":null,\"evaluator_id\":\"161\",\"target_user_id\":\"183\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"good\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-29 12:57:03\",\"updated_at\":\"2026-08-29 12:57:03\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"80\",\"legacy_submission_id\":null,\"evaluator_id\":\"161\",\"target_user_id\":\"172\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"very good\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-29 12:57:39\",\"updated_at\":\"2026-08-29 12:57:39\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"81\",\"legacy_submission_id\":null,\"evaluator_id\":\"161\",\"target_user_id\":\"181\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-29 12:58:14\",\"updated_at\":\"2026-08-29 12:58:14\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"82\",\"legacy_submission_id\":null,\"evaluator_id\":\"192\",\"target_user_id\":\"144\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-29 13:04:08\",\"updated_at\":\"2026-08-29 13:04:08\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"83\",\"legacy_submission_id\":null,\"evaluator_id\":\"192\",\"target_user_id\":\"136\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-29 13:04:53\",\"updated_at\":\"2026-08-29 13:04:53\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"84\",\"legacy_submission_id\":null,\"evaluator_id\":\"192\",\"target_user_id\":\"172\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-29 13:05:19\",\"updated_at\":\"2026-08-29 13:05:19\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"86\",\"legacy_submission_id\":null,\"evaluator_id\":\"192\",\"target_user_id\":\"152\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-29 13:07:08\",\"updated_at\":\"2026-08-29 13:07:08\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"multi_role\"},{\"id\":\"87\",\"legacy_submission_id\":null,\"evaluator_id\":\"192\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-29 13:07:30\",\"updated_at\":\"2026-08-29 13:07:30\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"90\",\"legacy_submission_id\":null,\"evaluator_id\":\"144\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"7\",\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":\"4.67\",\"remarks\":\"N/A\",\"eval_type\":\"staff\",\"peer_group\":\"Staff Evaluation\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-13 22:05:54\",\"updated_at\":\"2026-09-15 16:19:26\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"91\",\"legacy_submission_id\":null,\"evaluator_id\":\"161\",\"target_user_id\":\"194\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 08:30:27\",\"updated_at\":\"2026-09-14 08:30:27\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"92\",\"legacy_submission_id\":null,\"evaluator_id\":\"161\",\"target_user_id\":\"198\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 08:30:58\",\"updated_at\":\"2026-09-14 08:30:58\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"93\",\"legacy_submission_id\":null,\"evaluator_id\":\"161\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 08:31:12\",\"updated_at\":\"2026-09-14 08:31:12\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"94\",\"legacy_submission_id\":null,\"evaluator_id\":\"170\",\"target_user_id\":\"144\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":\"4.67\",\"remarks\":\"N/A\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Faculty\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 08:37:19\",\"updated_at\":\"2026-09-14 08:37:19\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"95\",\"legacy_submission_id\":null,\"evaluator_id\":\"170\",\"target_user_id\":\"172\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":\"4.50\",\"remarks\":\"N/A\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Staff\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 08:37:55\",\"updated_at\":\"2026-09-14 08:37:55\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"96\",\"legacy_submission_id\":null,\"evaluator_id\":\"170\",\"target_user_id\":\"157\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":\"4.75\",\"remarks\":\"N/A\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Dean / Principal\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 08:40:12\",\"updated_at\":\"2026-09-14 08:40:12\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"97\",\"legacy_submission_id\":null,\"evaluator_id\":\"170\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":\"5.00\",\"remarks\":\"N/A\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Dean / Principal\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 08:40:32\",\"updated_at\":\"2026-09-14 08:40:32\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"98\",\"legacy_submission_id\":null,\"evaluator_id\":\"161\",\"target_user_id\":\"157\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 08:42:23\",\"updated_at\":\"2026-09-14 08:42:23\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"108\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"183\",\"eval_bucket\":\"Faculty\",\"level\":\"college\",\"form_type\":\"school_head_dean_faculty\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":\"4.67\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 21:41:32\",\"updated_at\":\"2026-09-14 21:41:32\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"109\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"172\",\"eval_bucket\":\"Staff\",\"level\":\"college\",\"form_type\":\"school_head_dean_staff\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":\"5.00\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-15 07:47:25\",\"updated_at\":\"2026-09-15 07:47:25\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"110\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"125\",\"eval_bucket\":\"EA\",\"level\":\"college\",\"form_type\":\"school_head_dean_ea\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":\"4.67\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-15 07:49:43\",\"updated_at\":\"2026-09-15 07:49:43\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"111\",\"legacy_submission_id\":null,\"evaluator_id\":\"207\",\"target_user_id\":\"157\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"7\",\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":\"4.50\",\"remarks\":\"N/A\",\"eval_type\":\"staff\",\"peer_group\":\"Staff Evaluation\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-15 14:30:22\",\"updated_at\":\"2026-09-15 16:19:26\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"113\",\"legacy_submission_id\":null,\"evaluator_id\":\"144\",\"target_user_id\":\"157\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"7\",\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":\"4.50\",\"remarks\":\"N/A\",\"eval_type\":\"staff\",\"peer_group\":\"Staff Evaluation\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-15 16:11:34\",\"updated_at\":\"2026-09-15 16:19:26\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"114\",\"legacy_submission_id\":null,\"evaluator_id\":\"144\",\"target_user_id\":\"125\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"7\",\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":\"4.67\",\"remarks\":\"N/A\",\"eval_type\":\"staff\",\"peer_group\":\"Staff Evaluation\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-15 16:22:04\",\"updated_at\":\"2026-09-15 16:22:04\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"115\",\"legacy_submission_id\":null,\"evaluator_id\":\"125\",\"target_user_id\":\"181\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"ea\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-15 16:40:12\",\"updated_at\":\"2026-09-15 16:40:12\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"116\",\"legacy_submission_id\":null,\"evaluator_id\":\"125\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"ea\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-15 16:41:50\",\"updated_at\":\"2026-09-15 16:41:50\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"118\",\"legacy_submission_id\":null,\"evaluator_id\":\"161\",\"target_user_id\":\"170\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"wala\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-17 13:51:08\",\"updated_at\":\"2026-09-17 13:51:08\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"119\",\"legacy_submission_id\":null,\"evaluator_id\":\"208\",\"target_user_id\":\"198\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"ea\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-17 14:02:33\",\"updated_at\":\"2026-09-17 14:02:33\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"120\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"144\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-18 07:45:03\",\"updated_at\":\"2026-09-18 07:45:03\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"121\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-18 07:47:29\",\"updated_at\":\"2026-09-18 07:47:29\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"122\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"194\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-18 07:52:42\",\"updated_at\":\"2026-09-18 07:52:42\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"123\",\"legacy_submission_id\":null,\"evaluator_id\":\"186\",\"target_user_id\":\"144\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-18 08:08:03\",\"updated_at\":\"2026-09-18 08:08:03\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"124\",\"legacy_submission_id\":null,\"evaluator_id\":\"186\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"3\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-18 08:08:52\",\"updated_at\":\"2026-09-18 08:08:52\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"}],\"questionnaire_answers\":[{\"id\":\"384\",\"tracker_id\":\"74\",\"question_id\":\"209\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-28 19:55:39\",\"comments\":null},{\"id\":\"385\",\"tracker_id\":\"74\",\"question_id\":\"218\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-28 19:55:39\",\"comments\":null},{\"id\":\"386\",\"tracker_id\":\"74\",\"question_id\":\"210\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-28 19:55:39\",\"comments\":null},{\"id\":\"387\",\"tracker_id\":\"74\",\"question_id\":\"211\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-28 19:55:39\",\"comments\":null},{\"id\":\"388\",\"tracker_id\":\"74\",\"question_id\":\"212\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-28 19:55:39\",\"comments\":null},{\"id\":\"389\",\"tracker_id\":\"74\",\"question_id\":\"213\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-28 19:55:39\",\"comments\":null},{\"id\":\"390\",\"tracker_id\":\"74\",\"question_id\":\"214\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-28 19:55:39\",\"comments\":null},{\"id\":\"391\",\"tracker_id\":\"74\",\"question_id\":\"215\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-28 19:55:39\",\"comments\":null},{\"id\":\"392\",\"tracker_id\":\"74\",\"question_id\":\"216\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-28 19:55:39\",\"comments\":null},{\"id\":\"393\",\"tracker_id\":\"74\",\"question_id\":\"217\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-28 19:55:39\",\"comments\":null},{\"id\":\"394\",\"tracker_id\":\"74\",\"question_id\":\"219\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-28 19:55:39\",\"comments\":null},{\"id\":\"411\",\"tracker_id\":\"78\",\"question_id\":\"209\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:56:21\",\"comments\":null},{\"id\":\"412\",\"tracker_id\":\"78\",\"question_id\":\"218\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:56:21\",\"comments\":null},{\"id\":\"413\",\"tracker_id\":\"78\",\"question_id\":\"210\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:56:21\",\"comments\":null},{\"id\":\"414\",\"tracker_id\":\"78\",\"question_id\":\"211\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:56:21\",\"comments\":null},{\"id\":\"415\",\"tracker_id\":\"78\",\"question_id\":\"212\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:56:21\",\"comments\":null},{\"id\":\"416\",\"tracker_id\":\"78\",\"question_id\":\"213\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:56:21\",\"comments\":null},{\"id\":\"417\",\"tracker_id\":\"78\",\"question_id\":\"214\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:56:21\",\"comments\":null},{\"id\":\"418\",\"tracker_id\":\"78\",\"question_id\":\"215\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:56:21\",\"comments\":null},{\"id\":\"419\",\"tracker_id\":\"78\",\"question_id\":\"216\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:56:21\",\"comments\":null},{\"id\":\"420\",\"tracker_id\":\"78\",\"question_id\":\"217\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:56:21\",\"comments\":null},{\"id\":\"421\",\"tracker_id\":\"78\",\"question_id\":\"219\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:56:21\",\"comments\":null},{\"id\":\"422\",\"tracker_id\":\"79\",\"question_id\":\"209\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-29 12:57:03\",\"comments\":null},{\"id\":\"423\",\"tracker_id\":\"79\",\"question_id\":\"218\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-29 12:57:03\",\"comments\":null},{\"id\":\"424\",\"tracker_id\":\"79\",\"question_id\":\"210\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-29 12:57:03\",\"comments\":null},{\"id\":\"425\",\"tracker_id\":\"79\",\"question_id\":\"211\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-29 12:57:03\",\"comments\":null},{\"id\":\"426\",\"tracker_id\":\"79\",\"question_id\":\"212\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-29 12:57:03\",\"comments\":null},{\"id\":\"427\",\"tracker_id\":\"79\",\"question_id\":\"213\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-29 12:57:03\",\"comments\":null},{\"id\":\"428\",\"tracker_id\":\"79\",\"question_id\":\"214\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-29 12:57:03\",\"comments\":null},{\"id\":\"429\",\"tracker_id\":\"79\",\"question_id\":\"215\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-29 12:57:03\",\"comments\":null},{\"id\":\"430\",\"tracker_id\":\"79\",\"question_id\":\"216\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-29 12:57:03\",\"comments\":null},{\"id\":\"431\",\"tracker_id\":\"79\",\"question_id\":\"217\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-29 12:57:03\",\"comments\":null},{\"id\":\"432\",\"tracker_id\":\"79\",\"question_id\":\"219\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-29 12:57:03\",\"comments\":null},{\"id\":\"433\",\"tracker_id\":\"80\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"199\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:57:39\",\"comments\":null},{\"id\":\"434\",\"tracker_id\":\"80\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"200\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:57:39\",\"comments\":null},{\"id\":\"435\",\"tracker_id\":\"80\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"201\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:57:39\",\"comments\":null},{\"id\":\"436\",\"tracker_id\":\"80\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"202\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:57:39\",\"comments\":null},{\"id\":\"437\",\"tracker_id\":\"80\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"203\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:57:39\",\"comments\":null},{\"id\":\"438\",\"tracker_id\":\"81\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"219\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:58:14\",\"comments\":null},{\"id\":\"439\",\"tracker_id\":\"81\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"220\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:58:14\",\"comments\":null},{\"id\":\"440\",\"tracker_id\":\"81\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"221\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:58:14\",\"comments\":null},{\"id\":\"441\",\"tracker_id\":\"81\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"222\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:58:14\",\"comments\":null},{\"id\":\"442\",\"tracker_id\":\"81\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"223\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 12:58:14\",\"comments\":null},{\"id\":\"443\",\"tracker_id\":\"82\",\"question_id\":\"209\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:08\",\"comments\":null},{\"id\":\"444\",\"tracker_id\":\"82\",\"question_id\":\"218\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:08\",\"comments\":null},{\"id\":\"445\",\"tracker_id\":\"82\",\"question_id\":\"210\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:08\",\"comments\":null},{\"id\":\"446\",\"tracker_id\":\"82\",\"question_id\":\"211\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:08\",\"comments\":null},{\"id\":\"447\",\"tracker_id\":\"82\",\"question_id\":\"212\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:08\",\"comments\":null},{\"id\":\"448\",\"tracker_id\":\"82\",\"question_id\":\"213\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:08\",\"comments\":null},{\"id\":\"449\",\"tracker_id\":\"82\",\"question_id\":\"214\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:08\",\"comments\":null},{\"id\":\"450\",\"tracker_id\":\"82\",\"question_id\":\"215\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:08\",\"comments\":null},{\"id\":\"451\",\"tracker_id\":\"82\",\"question_id\":\"216\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:08\",\"comments\":null},{\"id\":\"452\",\"tracker_id\":\"82\",\"question_id\":\"217\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:08\",\"comments\":null},{\"id\":\"453\",\"tracker_id\":\"82\",\"question_id\":\"219\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:08\",\"comments\":null},{\"id\":\"454\",\"tracker_id\":\"83\",\"question_id\":\"209\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:53\",\"comments\":null},{\"id\":\"455\",\"tracker_id\":\"83\",\"question_id\":\"218\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:53\",\"comments\":null},{\"id\":\"456\",\"tracker_id\":\"83\",\"question_id\":\"210\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:53\",\"comments\":null},{\"id\":\"457\",\"tracker_id\":\"83\",\"question_id\":\"211\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:53\",\"comments\":null},{\"id\":\"458\",\"tracker_id\":\"83\",\"question_id\":\"212\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:53\",\"comments\":null},{\"id\":\"459\",\"tracker_id\":\"83\",\"question_id\":\"213\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:53\",\"comments\":null},{\"id\":\"460\",\"tracker_id\":\"83\",\"question_id\":\"214\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:53\",\"comments\":null},{\"id\":\"461\",\"tracker_id\":\"83\",\"question_id\":\"215\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:53\",\"comments\":null},{\"id\":\"462\",\"tracker_id\":\"83\",\"question_id\":\"216\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:53\",\"comments\":null},{\"id\":\"463\",\"tracker_id\":\"83\",\"question_id\":\"217\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:53\",\"comments\":null},{\"id\":\"464\",\"tracker_id\":\"83\",\"question_id\":\"219\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:04:53\",\"comments\":null},{\"id\":\"465\",\"tracker_id\":\"84\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"199\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:05:19\",\"comments\":null},{\"id\":\"466\",\"tracker_id\":\"84\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"200\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:05:19\",\"comments\":null},{\"id\":\"467\",\"tracker_id\":\"84\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"201\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:05:19\",\"comments\":null},{\"id\":\"468\",\"tracker_id\":\"84\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"202\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:05:19\",\"comments\":null},{\"id\":\"469\",\"tracker_id\":\"84\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"203\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:05:19\",\"comments\":null},{\"id\":\"470\",\"tracker_id\":\"86\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"92\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:07:08\",\"comments\":null},{\"id\":\"471\",\"tracker_id\":\"86\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"93\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:07:08\",\"comments\":null},{\"id\":\"472\",\"tracker_id\":\"86\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"94\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:07:08\",\"comments\":null},{\"id\":\"473\",\"tracker_id\":\"86\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"95\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:07:08\",\"comments\":null},{\"id\":\"474\",\"tracker_id\":\"86\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"96\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:07:08\",\"comments\":null},{\"id\":\"475\",\"tracker_id\":\"86\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"97\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:07:08\",\"comments\":null},{\"id\":\"476\",\"tracker_id\":\"86\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"98\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:07:08\",\"comments\":null},{\"id\":\"477\",\"tracker_id\":\"86\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"99\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:07:08\",\"comments\":null},{\"id\":\"478\",\"tracker_id\":\"86\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"100\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:07:08\",\"comments\":null},{\"id\":\"479\",\"tracker_id\":\"86\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"101\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:07:08\",\"comments\":null},{\"id\":\"480\",\"tracker_id\":\"87\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"232\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 13:07:30\",\"comments\":null},{\"id\":\"489\",\"tracker_id\":\"90\",\"question_id\":\"235\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-13 22:05:54\",\"comments\":null},{\"id\":\"490\",\"tracker_id\":\"90\",\"question_id\":\"233\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-13 22:05:54\",\"comments\":null},{\"id\":\"491\",\"tracker_id\":\"90\",\"question_id\":\"236\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-13 22:05:54\",\"comments\":null},{\"id\":\"492\",\"tracker_id\":\"91\",\"question_id\":\"209\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:30:27\",\"comments\":null},{\"id\":\"493\",\"tracker_id\":\"91\",\"question_id\":\"218\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:30:27\",\"comments\":null},{\"id\":\"494\",\"tracker_id\":\"91\",\"question_id\":\"210\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 08:30:27\",\"comments\":null},{\"id\":\"495\",\"tracker_id\":\"91\",\"question_id\":\"211\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:30:27\",\"comments\":null},{\"id\":\"496\",\"tracker_id\":\"91\",\"question_id\":\"212\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:30:27\",\"comments\":null},{\"id\":\"497\",\"tracker_id\":\"91\",\"question_id\":\"213\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 08:30:27\",\"comments\":null},{\"id\":\"498\",\"tracker_id\":\"91\",\"question_id\":\"214\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:30:27\",\"comments\":null},{\"id\":\"499\",\"tracker_id\":\"91\",\"question_id\":\"215\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 08:30:27\",\"comments\":null},{\"id\":\"500\",\"tracker_id\":\"91\",\"question_id\":\"216\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:30:27\",\"comments\":null},{\"id\":\"501\",\"tracker_id\":\"91\",\"question_id\":\"217\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 08:30:27\",\"comments\":null},{\"id\":\"502\",\"tracker_id\":\"91\",\"question_id\":\"219\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-14 08:30:27\",\"comments\":null},{\"id\":\"503\",\"tracker_id\":\"92\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"236\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:30:58\",\"comments\":null},{\"id\":\"504\",\"tracker_id\":\"93\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"232\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:31:12\",\"comments\":null},{\"id\":\"505\",\"tracker_id\":\"94\",\"question_id\":\"206\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:37:19\",\"comments\":null},{\"id\":\"506\",\"tracker_id\":\"94\",\"question_id\":\"203\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 08:37:19\",\"comments\":null},{\"id\":\"507\",\"tracker_id\":\"94\",\"question_id\":\"204\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:37:19\",\"comments\":null},{\"id\":\"508\",\"tracker_id\":\"94\",\"question_id\":\"205\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 08:37:19\",\"comments\":null},{\"id\":\"509\",\"tracker_id\":\"94\",\"question_id\":\"207\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:37:19\",\"comments\":null},{\"id\":\"510\",\"tracker_id\":\"94\",\"question_id\":\"208\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:37:19\",\"comments\":null},{\"id\":\"511\",\"tracker_id\":\"95\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"237\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:37:55\",\"comments\":null},{\"id\":\"512\",\"tracker_id\":\"95\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"238\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 08:37:55\",\"comments\":null},{\"id\":\"513\",\"tracker_id\":\"96\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"239\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:40:12\",\"comments\":null},{\"id\":\"514\",\"tracker_id\":\"96\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"240\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:40:12\",\"comments\":null},{\"id\":\"515\",\"tracker_id\":\"96\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"241\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 08:40:12\",\"comments\":null},{\"id\":\"516\",\"tracker_id\":\"96\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"242\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:40:12\",\"comments\":null},{\"id\":\"517\",\"tracker_id\":\"97\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"233\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:40:32\",\"comments\":null},{\"id\":\"518\",\"tracker_id\":\"98\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"243\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:42:23\",\"comments\":null},{\"id\":\"552\",\"tracker_id\":\"108\",\"question_id\":\"224\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 21:41:32\",\"comments\":null},{\"id\":\"553\",\"tracker_id\":\"108\",\"question_id\":\"226\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 21:41:32\",\"comments\":null},{\"id\":\"554\",\"tracker_id\":\"108\",\"question_id\":\"230\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 21:41:32\",\"comments\":null},{\"id\":\"555\",\"tracker_id\":\"109\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"234\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 07:47:25\",\"comments\":null},{\"id\":\"556\",\"tracker_id\":\"109\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"235\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 07:47:25\",\"comments\":null},{\"id\":\"557\",\"tracker_id\":\"110\",\"question_id\":\"237\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 07:49:43\",\"comments\":null},{\"id\":\"558\",\"tracker_id\":\"110\",\"question_id\":\"238\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-15 07:49:43\",\"comments\":null},{\"id\":\"559\",\"tracker_id\":\"110\",\"question_id\":\"239\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 07:49:43\",\"comments\":null},{\"id\":\"560\",\"tracker_id\":\"111\",\"question_id\":\"231\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 14:30:22\",\"comments\":null},{\"id\":\"561\",\"tracker_id\":\"111\",\"question_id\":\"232\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-15 14:30:22\",\"comments\":null},{\"id\":\"562\",\"tracker_id\":\"113\",\"question_id\":\"231\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:11:34\",\"comments\":null},{\"id\":\"563\",\"tracker_id\":\"113\",\"question_id\":\"232\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-15 16:11:34\",\"comments\":null},{\"id\":\"564\",\"tracker_id\":\"114\",\"question_id\":\"234\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:22:04\",\"comments\":null},{\"id\":\"565\",\"tracker_id\":\"114\",\"question_id\":\"240\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:22:04\",\"comments\":null},{\"id\":\"566\",\"tracker_id\":\"114\",\"question_id\":\"241\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-15 16:22:04\",\"comments\":null},{\"id\":\"567\",\"tracker_id\":\"115\",\"question_id\":\"295\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:40:12\",\"comments\":null},{\"id\":\"568\",\"tracker_id\":\"115\",\"question_id\":\"296\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-15 16:40:12\",\"comments\":null},{\"id\":\"569\",\"tracker_id\":\"115\",\"question_id\":\"297\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:40:12\",\"comments\":null},{\"id\":\"570\",\"tracker_id\":\"115\",\"question_id\":\"298\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:40:12\",\"comments\":null},{\"id\":\"571\",\"tracker_id\":\"115\",\"question_id\":\"299\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-15 16:40:12\",\"comments\":null},{\"id\":\"572\",\"tracker_id\":\"116\",\"question_id\":\"315\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:41:50\",\"comments\":null},{\"id\":\"573\",\"tracker_id\":\"116\",\"question_id\":\"157\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:41:50\",\"comments\":null},{\"id\":\"574\",\"tracker_id\":\"116\",\"question_id\":\"314\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-15 16:41:50\",\"comments\":null},{\"id\":\"575\",\"tracker_id\":\"116\",\"question_id\":\"154\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:41:50\",\"comments\":null},{\"id\":\"576\",\"tracker_id\":\"116\",\"question_id\":\"155\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:41:50\",\"comments\":null},{\"id\":\"577\",\"tracker_id\":\"116\",\"question_id\":\"158\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:41:50\",\"comments\":null},{\"id\":\"578\",\"tracker_id\":\"116\",\"question_id\":\"159\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-15 16:41:50\",\"comments\":null},{\"id\":\"579\",\"tracker_id\":\"116\",\"question_id\":\"156\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-15 16:41:50\",\"comments\":null},{\"id\":\"582\",\"tracker_id\":\"118\",\"question_id\":\"209\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-17 13:51:08\",\"comments\":null},{\"id\":\"583\",\"tracker_id\":\"118\",\"question_id\":\"218\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-17 13:51:08\",\"comments\":null},{\"id\":\"584\",\"tracker_id\":\"118\",\"question_id\":\"210\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-17 13:51:08\",\"comments\":null},{\"id\":\"585\",\"tracker_id\":\"118\",\"question_id\":\"211\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-17 13:51:08\",\"comments\":null},{\"id\":\"586\",\"tracker_id\":\"118\",\"question_id\":\"212\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-17 13:51:08\",\"comments\":null},{\"id\":\"587\",\"tracker_id\":\"118\",\"question_id\":\"213\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-17 13:51:08\",\"comments\":null},{\"id\":\"588\",\"tracker_id\":\"118\",\"question_id\":\"214\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-17 13:51:08\",\"comments\":null},{\"id\":\"589\",\"tracker_id\":\"118\",\"question_id\":\"215\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-17 13:51:08\",\"comments\":null},{\"id\":\"590\",\"tracker_id\":\"118\",\"question_id\":\"216\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-17 13:51:08\",\"comments\":null},{\"id\":\"591\",\"tracker_id\":\"118\",\"question_id\":\"217\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-17 13:51:08\",\"comments\":null},{\"id\":\"592\",\"tracker_id\":\"118\",\"question_id\":\"219\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-17 13:51:08\",\"comments\":null},{\"id\":\"593\",\"tracker_id\":\"119\",\"question_id\":\"300\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-17 14:02:33\",\"comments\":null},{\"id\":\"594\",\"tracker_id\":\"120\",\"question_id\":\"209\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:45:03\",\"comments\":null},{\"id\":\"595\",\"tracker_id\":\"120\",\"question_id\":\"218\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:45:03\",\"comments\":null},{\"id\":\"596\",\"tracker_id\":\"120\",\"question_id\":\"210\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 07:45:03\",\"comments\":null},{\"id\":\"597\",\"tracker_id\":\"120\",\"question_id\":\"211\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:45:03\",\"comments\":null},{\"id\":\"598\",\"tracker_id\":\"120\",\"question_id\":\"212\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 07:45:03\",\"comments\":null},{\"id\":\"599\",\"tracker_id\":\"120\",\"question_id\":\"213\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:45:03\",\"comments\":null},{\"id\":\"600\",\"tracker_id\":\"120\",\"question_id\":\"214\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:45:03\",\"comments\":null},{\"id\":\"601\",\"tracker_id\":\"120\",\"question_id\":\"215\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 07:45:03\",\"comments\":null},{\"id\":\"602\",\"tracker_id\":\"120\",\"question_id\":\"216\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:45:03\",\"comments\":null},{\"id\":\"603\",\"tracker_id\":\"120\",\"question_id\":\"217\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:45:03\",\"comments\":null},{\"id\":\"604\",\"tracker_id\":\"120\",\"question_id\":\"219\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 07:45:03\",\"comments\":null},{\"id\":\"605\",\"tracker_id\":\"121\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"232\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:47:29\",\"comments\":null},{\"id\":\"606\",\"tracker_id\":\"121\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"244\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 07:47:29\",\"comments\":null},{\"id\":\"607\",\"tracker_id\":\"121\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"245\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:47:29\",\"comments\":null},{\"id\":\"608\",\"tracker_id\":\"122\",\"question_id\":\"209\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:52:42\",\"comments\":null},{\"id\":\"609\",\"tracker_id\":\"122\",\"question_id\":\"218\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:52:42\",\"comments\":null},{\"id\":\"610\",\"tracker_id\":\"122\",\"question_id\":\"210\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 07:52:42\",\"comments\":null},{\"id\":\"611\",\"tracker_id\":\"122\",\"question_id\":\"211\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:52:42\",\"comments\":null},{\"id\":\"612\",\"tracker_id\":\"122\",\"question_id\":\"212\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 07:52:42\",\"comments\":null},{\"id\":\"613\",\"tracker_id\":\"122\",\"question_id\":\"213\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:52:42\",\"comments\":null},{\"id\":\"614\",\"tracker_id\":\"122\",\"question_id\":\"214\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:52:42\",\"comments\":null},{\"id\":\"615\",\"tracker_id\":\"122\",\"question_id\":\"215\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 07:52:42\",\"comments\":null},{\"id\":\"616\",\"tracker_id\":\"122\",\"question_id\":\"216\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 07:52:42\",\"comments\":null},{\"id\":\"617\",\"tracker_id\":\"122\",\"question_id\":\"217\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 07:52:42\",\"comments\":null},{\"id\":\"618\",\"tracker_id\":\"122\",\"question_id\":\"219\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 07:52:42\",\"comments\":null},{\"id\":\"619\",\"tracker_id\":\"123\",\"question_id\":\"209\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 08:08:03\",\"comments\":null},{\"id\":\"620\",\"tracker_id\":\"123\",\"question_id\":\"218\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 08:08:03\",\"comments\":null},{\"id\":\"621\",\"tracker_id\":\"123\",\"question_id\":\"210\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 08:08:03\",\"comments\":null},{\"id\":\"622\",\"tracker_id\":\"123\",\"question_id\":\"211\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 08:08:03\",\"comments\":null},{\"id\":\"623\",\"tracker_id\":\"123\",\"question_id\":\"212\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 08:08:03\",\"comments\":null},{\"id\":\"624\",\"tracker_id\":\"123\",\"question_id\":\"213\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 08:08:03\",\"comments\":null},{\"id\":\"625\",\"tracker_id\":\"123\",\"question_id\":\"214\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 08:08:03\",\"comments\":null},{\"id\":\"626\",\"tracker_id\":\"123\",\"question_id\":\"215\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 08:08:03\",\"comments\":null},{\"id\":\"627\",\"tracker_id\":\"123\",\"question_id\":\"216\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 08:08:03\",\"comments\":null},{\"id\":\"628\",\"tracker_id\":\"123\",\"question_id\":\"217\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 08:08:03\",\"comments\":null},{\"id\":\"629\",\"tracker_id\":\"123\",\"question_id\":\"219\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 08:08:03\",\"comments\":null},{\"id\":\"630\",\"tracker_id\":\"124\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"232\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 08:08:52\",\"comments\":null},{\"id\":\"631\",\"tracker_id\":\"124\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"244\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 08:08:52\",\"comments\":null},{\"id\":\"632\",\"tracker_id\":\"124\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"245\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 08:08:52\",\"comments\":null}],\"evaluation_answers\":[{\"id\":\"6\",\"tracker_id\":\"73\",\"category\":\"Professionalism\",\"question\":\"Explains lessons clearly and effectively.\",\"score\":\"5\"},{\"id\":\"7\",\"tracker_id\":\"73\",\"category\":\"Professionalism\",\"question\":\"Handles classroom concerns appropriately.\",\"score\":\"4\"},{\"id\":\"8\",\"tracker_id\":\"73\",\"category\":\"Teaching Effectiveness\",\"question\":\"Presents learning objectives clearly.\",\"score\":\"5\"},{\"id\":\"9\",\"tracker_id\":\"73\",\"category\":\"Teaching Effectiveness\",\"question\":\"Uses appropriate teaching strategies and methods.\",\"score\":\"5\"},{\"id\":\"10\",\"tracker_id\":\"73\",\"category\":\"Teaching Effectiveness\",\"question\":\"Provides clear instructions for activities and assignments.\",\"score\":\"5\"},{\"id\":\"11\",\"tracker_id\":\"73\",\"category\":\"Teaching Effectiveness\",\"question\":\"Encourages active participation during class discussions.\",\"score\":\"4\"},{\"id\":\"12\",\"tracker_id\":\"73\",\"category\":\"Teaching Effectiveness\",\"question\":\"Maintains proper classroom discipline.\",\"score\":\"4\"},{\"id\":\"13\",\"tracker_id\":\"73\",\"category\":\"Teaching Effectiveness\",\"question\":\"Creates a positive and respectful learning environment.\",\"score\":\"5\"},{\"id\":\"14\",\"tracker_id\":\"73\",\"category\":\"Teaching Effectiveness\",\"question\":\"Manages classroom activities effectively.\",\"score\":\"5\"},{\"id\":\"15\",\"tracker_id\":\"73\",\"category\":\"Teaching Effectiveness\",\"question\":\"Treats students fairly and respectfully.\",\"score\":\"5\"},{\"id\":\"16\",\"tracker_id\":\"73\",\"category\":\"Teaching Effectiveness\",\"question\":\"testing\",\"score\":\"4\"}],\"evaluation_submissions\":[],\"evaluation_results\":[],\"peer_evaluation_submissions\":[],\"peer_evaluation_results\":[],\"evaluation_reminders\":[],\"analytics_reports\":[],\"notifications\":[],\"activity_log_snapshot\":[]}');
INSERT INTO `system_archives` (`id`, `period_id`, `period_label`, `school_year`, `archived_by`, `archived_by_name`, `archived_at`, `restored_at`, `restored_by`, `status`, `record_count`, `summary_json`, `payload_json`) VALUES
(2, 5, '2026-2027 — Summer', '2026-2027', 208, 'Lorraine R. Sabay', '2026-09-18 17:03:50', '2026-09-18 17:05:09', 208, 'restored', 89, '{\"evaluation_tracker\":16,\"questionnaire_answers\":66,\"evaluation_answers\":5,\"evaluation_submissions\":0,\"evaluation_results\":0,\"peer_evaluation_submissions\":0,\"peer_evaluation_results\":0,\"evaluation_reminders\":2,\"analytics_reports\":0,\"notifications\":0,\"activity_log_snapshot\":0}', '{\"evaluation_tracker\":[{\"id\":\"26\",\"legacy_submission_id\":null,\"evaluator_id\":\"167\",\"target_user_id\":\"170\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-13 07:57:36\",\"updated_at\":\"2026-08-13 07:57:36\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"29\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"136\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-14 14:58:06\",\"updated_at\":\"2026-08-17 17:58:18\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"multi_role\"},{\"id\":\"38\",\"legacy_submission_id\":null,\"evaluator_id\":\"166\",\"target_user_id\":\"136\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-16 12:33:07\",\"updated_at\":\"2026-08-17 17:58:18\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"multi_role\"},{\"id\":\"45\",\"legacy_submission_id\":null,\"evaluator_id\":\"151\",\"target_user_id\":\"170\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-17 16:48:00\",\"updated_at\":\"2026-08-17 16:48:00\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"47\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"172\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-19 13:42:27\",\"updated_at\":\"2026-08-19 13:42:27\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"55\",\"legacy_submission_id\":null,\"evaluator_id\":\"161\",\"target_user_id\":\"144\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-24 14:03:23\",\"updated_at\":\"2026-08-24 14:03:23\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"58\",\"legacy_submission_id\":null,\"evaluator_id\":\"125\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":null,\"remarks\":\"\",\"eval_type\":\"ea\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-24 14:57:55\",\"updated_at\":\"2026-08-24 14:57:55\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"62\",\"legacy_submission_id\":null,\"evaluator_id\":\"167\",\"target_user_id\":\"136\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-26 10:35:38\",\"updated_at\":\"2026-08-27 15:02:30\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"64\",\"legacy_submission_id\":null,\"evaluator_id\":\"167\",\"target_user_id\":\"172\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-26 11:18:16\",\"updated_at\":\"2026-08-27 15:02:30\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"65\",\"legacy_submission_id\":null,\"evaluator_id\":\"167\",\"target_user_id\":\"181\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-26 11:18:28\",\"updated_at\":\"2026-08-27 15:02:30\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"67\",\"legacy_submission_id\":null,\"evaluator_id\":\"170\",\"target_user_id\":\"183\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":\"4.63\",\"remarks\":\"N/A\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Teacher\",\"status\":\"submitted\",\"submitted_at\":\"2026-08-26 11:42:05\",\"updated_at\":\"2026-08-27 15:02:30\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"68\",\"legacy_submission_id\":null,\"evaluator_id\":\"170\",\"target_user_id\":\"136\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":\"4.57\",\"remarks\":\"N/A\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Staff\",\"status\":\"submitted\",\"submitted_at\":\"2026-08-26 12:08:51\",\"updated_at\":\"2026-08-27 15:02:30\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"69\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"170\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-27 15:03:41\",\"updated_at\":\"2026-08-27 15:03:41\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"70\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"144\",\"eval_bucket\":\"Staff\",\"level\":\"college\",\"form_type\":\"staff_dean\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":\"4.80\",\"remarks\":\"N/A\",\"eval_type\":\"dean\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-27 15:59:01\",\"updated_at\":\"2026-08-27 15:59:01\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"71\",\"legacy_submission_id\":null,\"evaluator_id\":\"170\",\"target_user_id\":\"139\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":\"4.75\",\"remarks\":\"N/A\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Teacher\",\"status\":\"submitted\",\"submitted_at\":\"2026-08-27 16:02:21\",\"updated_at\":\"2026-08-27 16:02:21\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"72\",\"legacy_submission_id\":null,\"evaluator_id\":\"125\",\"target_user_id\":\"157\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"5\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"ea\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-27 16:55:58\",\"updated_at\":\"2026-08-27 16:55:58\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"}],\"questionnaire_answers\":[{\"id\":\"244\",\"tracker_id\":\"26\",\"question_id\":\"184\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-13 07:57:36\",\"comments\":null},{\"id\":\"245\",\"tracker_id\":\"26\",\"question_id\":\"185\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-08-13 07:57:36\",\"comments\":null},{\"id\":\"246\",\"tracker_id\":\"26\",\"question_id\":\"188\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-13 07:57:36\",\"comments\":null},{\"id\":\"247\",\"tracker_id\":\"26\",\"question_id\":\"189\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-13 07:57:36\",\"comments\":null},{\"id\":\"256\",\"tracker_id\":\"29\",\"question_id\":\"192\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-14 14:58:06\",\"comments\":null},{\"id\":\"265\",\"tracker_id\":\"38\",\"question_id\":\"192\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-16 12:33:07\",\"comments\":null},{\"id\":\"290\",\"tracker_id\":\"45\",\"question_id\":\"184\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-17 16:48:00\",\"comments\":null},{\"id\":\"291\",\"tracker_id\":\"45\",\"question_id\":\"185\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-17 16:48:00\",\"comments\":null},{\"id\":\"292\",\"tracker_id\":\"45\",\"question_id\":\"188\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-17 16:48:00\",\"comments\":null},{\"id\":\"293\",\"tracker_id\":\"45\",\"question_id\":\"189\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-17 16:48:00\",\"comments\":null},{\"id\":\"307\",\"tracker_id\":\"55\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"75\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-24 14:03:23\",\"comments\":null},{\"id\":\"308\",\"tracker_id\":\"55\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"73\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-24 14:03:23\",\"comments\":null},{\"id\":\"315\",\"tracker_id\":\"58\",\"question_id\":\"153\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-24 14:57:55\",\"comments\":null},{\"id\":\"316\",\"tracker_id\":\"58\",\"question_id\":\"79\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-08-24 14:57:55\",\"comments\":null},{\"id\":\"327\",\"tracker_id\":\"62\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"209\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-26 10:35:38\",\"comments\":null},{\"id\":\"328\",\"tracker_id\":\"62\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"210\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 10:35:38\",\"comments\":null},{\"id\":\"329\",\"tracker_id\":\"62\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"211\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-26 10:35:38\",\"comments\":null},{\"id\":\"330\",\"tracker_id\":\"62\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"212\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 10:35:38\",\"comments\":null},{\"id\":\"331\",\"tracker_id\":\"62\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"213\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 10:35:38\",\"comments\":null},{\"id\":\"332\",\"tracker_id\":\"64\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"199\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 11:18:16\",\"comments\":null},{\"id\":\"333\",\"tracker_id\":\"64\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"200\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 11:18:16\",\"comments\":null},{\"id\":\"334\",\"tracker_id\":\"64\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"201\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-26 11:18:16\",\"comments\":null},{\"id\":\"335\",\"tracker_id\":\"64\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"202\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 11:18:16\",\"comments\":null},{\"id\":\"336\",\"tracker_id\":\"64\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"203\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-26 11:18:16\",\"comments\":null},{\"id\":\"337\",\"tracker_id\":\"65\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"219\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 11:18:28\",\"comments\":null},{\"id\":\"338\",\"tracker_id\":\"65\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"220\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 11:18:28\",\"comments\":null},{\"id\":\"339\",\"tracker_id\":\"65\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"221\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-26 11:18:28\",\"comments\":null},{\"id\":\"340\",\"tracker_id\":\"65\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"222\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 11:18:28\",\"comments\":null},{\"id\":\"341\",\"tracker_id\":\"65\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"223\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-26 11:18:28\",\"comments\":null},{\"id\":\"347\",\"tracker_id\":\"67\",\"question_id\":\"201\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 11:42:05\",\"comments\":null},{\"id\":\"348\",\"tracker_id\":\"67\",\"question_id\":\"202\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 11:42:05\",\"comments\":null},{\"id\":\"349\",\"tracker_id\":\"67\",\"question_id\":\"206\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-26 11:42:05\",\"comments\":null},{\"id\":\"350\",\"tracker_id\":\"67\",\"question_id\":\"203\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-26 11:42:05\",\"comments\":null},{\"id\":\"351\",\"tracker_id\":\"67\",\"question_id\":\"204\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 11:42:05\",\"comments\":null},{\"id\":\"352\",\"tracker_id\":\"67\",\"question_id\":\"205\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 11:42:05\",\"comments\":null},{\"id\":\"353\",\"tracker_id\":\"67\",\"question_id\":\"207\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-26 11:42:05\",\"comments\":null},{\"id\":\"354\",\"tracker_id\":\"67\",\"question_id\":\"208\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 11:42:05\",\"comments\":null},{\"id\":\"355\",\"tracker_id\":\"68\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"224\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 12:08:51\",\"comments\":null},{\"id\":\"356\",\"tracker_id\":\"68\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"225\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 12:08:51\",\"comments\":null},{\"id\":\"357\",\"tracker_id\":\"68\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"226\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-26 12:08:51\",\"comments\":null},{\"id\":\"358\",\"tracker_id\":\"68\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"227\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 12:08:51\",\"comments\":null},{\"id\":\"359\",\"tracker_id\":\"68\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"228\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-26 12:08:51\",\"comments\":null},{\"id\":\"360\",\"tracker_id\":\"68\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"229\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-26 12:08:51\",\"comments\":null},{\"id\":\"361\",\"tracker_id\":\"68\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"230\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-26 12:08:51\",\"comments\":null},{\"id\":\"362\",\"tracker_id\":\"69\",\"question_id\":\"209\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-27 15:03:41\",\"comments\":null},{\"id\":\"363\",\"tracker_id\":\"69\",\"question_id\":\"218\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-27 15:03:41\",\"comments\":null},{\"id\":\"364\",\"tracker_id\":\"69\",\"question_id\":\"210\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-27 15:03:41\",\"comments\":null},{\"id\":\"365\",\"tracker_id\":\"69\",\"question_id\":\"211\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-27 15:03:41\",\"comments\":null},{\"id\":\"366\",\"tracker_id\":\"69\",\"question_id\":\"212\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-27 15:03:41\",\"comments\":null},{\"id\":\"367\",\"tracker_id\":\"69\",\"question_id\":\"213\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-27 15:03:41\",\"comments\":null},{\"id\":\"368\",\"tracker_id\":\"69\",\"question_id\":\"214\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-27 15:03:41\",\"comments\":null},{\"id\":\"369\",\"tracker_id\":\"69\",\"question_id\":\"215\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-27 15:03:41\",\"comments\":null},{\"id\":\"370\",\"tracker_id\":\"69\",\"question_id\":\"216\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-27 15:03:41\",\"comments\":null},{\"id\":\"371\",\"tracker_id\":\"69\",\"question_id\":\"217\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-27 15:03:41\",\"comments\":null},{\"id\":\"372\",\"tracker_id\":\"69\",\"question_id\":\"219\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-27 15:03:41\",\"comments\":null},{\"id\":\"373\",\"tracker_id\":\"71\",\"question_id\":\"201\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-27 16:02:21\",\"comments\":null},{\"id\":\"374\",\"tracker_id\":\"71\",\"question_id\":\"202\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-27 16:02:21\",\"comments\":null},{\"id\":\"375\",\"tracker_id\":\"71\",\"question_id\":\"206\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-27 16:02:21\",\"comments\":null},{\"id\":\"376\",\"tracker_id\":\"71\",\"question_id\":\"203\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-27 16:02:21\",\"comments\":null},{\"id\":\"377\",\"tracker_id\":\"71\",\"question_id\":\"204\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-27 16:02:21\",\"comments\":null},{\"id\":\"378\",\"tracker_id\":\"71\",\"question_id\":\"205\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-27 16:02:21\",\"comments\":null},{\"id\":\"379\",\"tracker_id\":\"71\",\"question_id\":\"207\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-27 16:02:21\",\"comments\":null},{\"id\":\"380\",\"tracker_id\":\"71\",\"question_id\":\"208\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-27 16:02:21\",\"comments\":null},{\"id\":\"381\",\"tracker_id\":\"72\",\"question_id\":\"80\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-27 16:55:58\",\"comments\":null},{\"id\":\"382\",\"tracker_id\":\"72\",\"question_id\":\"82\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-27 16:55:58\",\"comments\":null},{\"id\":\"383\",\"tracker_id\":\"72\",\"question_id\":\"83\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-27 16:55:58\",\"comments\":null}],\"evaluation_answers\":[{\"id\":\"1\",\"tracker_id\":\"70\",\"category\":\"Job Performance\",\"question\":\"Performs assigned duties competently and reliably.\",\"score\":\"5\"},{\"id\":\"2\",\"tracker_id\":\"70\",\"category\":\"Communication\",\"question\":\"Communicates clearly with colleagues and stakeholders.\",\"score\":\"4\"},{\"id\":\"3\",\"tracker_id\":\"70\",\"category\":\"Professionalism\",\"question\":\"Demonstrates professionalism in the workplace.\",\"score\":\"5\"},{\"id\":\"4\",\"tracker_id\":\"70\",\"category\":\"Punctuality\",\"question\":\"Is punctual and dependable.\",\"score\":\"5\"},{\"id\":\"5\",\"tracker_id\":\"70\",\"category\":\"Overall Rating\",\"question\":\"Overall, meets expectations for this role.\",\"score\":\"5\"}],\"evaluation_submissions\":[],\"evaluation_results\":[],\"peer_evaluation_submissions\":[],\"peer_evaluation_results\":[],\"evaluation_reminders\":[{\"id\":\"1\",\"period_id\":\"5\",\"sender_id\":\"157\",\"recipient_id\":\"151\",\"eval_type\":\"student\",\"level\":\"college\",\"created_at\":\"2026-08-15 13:03:02\"},{\"id\":\"2\",\"period_id\":\"5\",\"sender_id\":\"157\",\"recipient_id\":\"175\",\"eval_type\":\"student\",\"level\":\"college\",\"created_at\":\"2026-08-24 17:19:27\"}],\"analytics_reports\":[],\"notifications\":[],\"activity_log_snapshot\":[]}');
INSERT INTO `system_archives` (`id`, `period_id`, `period_label`, `school_year`, `archived_by`, `archived_by_name`, `archived_at`, `restored_at`, `restored_by`, `status`, `record_count`, `summary_json`, `payload_json`) VALUES
(3, 2, '2026-2027 — School Year', '2026-2027', 208, 'Lorraine R. Sabay', '2026-09-18 17:03:54', '2026-09-18 17:05:09', 208, 'restored', 88, '{\"evaluation_tracker\":17,\"questionnaire_answers\":71,\"evaluation_answers\":0,\"evaluation_submissions\":0,\"evaluation_results\":0,\"peer_evaluation_submissions\":0,\"peer_evaluation_results\":0,\"evaluation_reminders\":0,\"analytics_reports\":0,\"notifications\":0,\"activity_log_snapshot\":0}', '{\"evaluation_tracker\":[{\"id\":\"75\",\"legacy_submission_id\":null,\"evaluator_id\":\"158\",\"target_user_id\":\"183\",\"eval_bucket\":\"Teacher\",\"level\":\"\",\"form_type\":\"Teacher Performance Evaluation (Supervisor)\",\"form_id\":\"5\",\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"4.67\",\"remarks\":\"N/A\",\"eval_type\":\"supervisor_to_teacher\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-28 20:01:30\",\"updated_at\":\"2026-08-28 20:01:30\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"76\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"183\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-29 10:29:36\",\"updated_at\":\"2026-08-29 10:29:36\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"77\",\"legacy_submission_id\":null,\"evaluator_id\":\"125\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"errtyydd\",\"eval_type\":\"ea\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-08-29 11:13:11\",\"updated_at\":\"2026-08-29 11:13:11\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"88\",\"legacy_submission_id\":null,\"evaluator_id\":\"170\",\"target_user_id\":\"172\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"4.50\",\"remarks\":\"N/A\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Staff\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-13 18:52:36\",\"updated_at\":\"2026-09-13 18:52:36\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"89\",\"legacy_submission_id\":null,\"evaluator_id\":\"164\",\"target_user_id\":\"139\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"4.67\",\"remarks\":\"N/A\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Faculty\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-13 18:54:47\",\"updated_at\":\"2026-09-13 18:54:47\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"99\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"157\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 08:43:00\",\"updated_at\":\"2026-09-14 08:43:00\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"100\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 08:43:05\",\"updated_at\":\"2026-09-14 08:43:05\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"101\",\"legacy_submission_id\":null,\"evaluator_id\":\"167\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 08:55:14\",\"updated_at\":\"2026-09-14 08:55:14\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"102\",\"legacy_submission_id\":null,\"evaluator_id\":\"167\",\"target_user_id\":\"157\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 08:55:59\",\"updated_at\":\"2026-09-14 08:55:59\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"103\",\"legacy_submission_id\":null,\"evaluator_id\":\"166\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 09:34:50\",\"updated_at\":\"2026-09-14 09:34:50\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"104\",\"legacy_submission_id\":null,\"evaluator_id\":\"149\",\"target_user_id\":\"139\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 16:28:49\",\"updated_at\":\"2026-09-14 16:28:49\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"105\",\"legacy_submission_id\":null,\"evaluator_id\":\"149\",\"target_user_id\":\"172\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 16:29:01\",\"updated_at\":\"2026-09-14 16:29:01\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"106\",\"legacy_submission_id\":null,\"evaluator_id\":\"149\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 16:29:08\",\"updated_at\":\"2026-09-14 16:29:08\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"107\",\"legacy_submission_id\":null,\"evaluator_id\":\"149\",\"target_user_id\":\"157\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-14 16:29:19\",\"updated_at\":\"2026-09-14 16:29:19\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"117\",\"legacy_submission_id\":null,\"evaluator_id\":\"158\",\"target_user_id\":\"172\",\"eval_bucket\":\"Staff\",\"level\":\"\",\"form_type\":\"Principal Evaluation — Non-Teaching Staff\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"5.00\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-16 10:15:15\",\"updated_at\":\"2026-09-16 10:15:15\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"125\",\"legacy_submission_id\":null,\"evaluator_id\":\"158\",\"target_user_id\":\"170\",\"eval_bucket\":\"Faculty\",\"level\":\"\",\"form_type\":\"Principal Evaluation — Faculty\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"5.00\",\"remarks\":\"\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-18 14:48:11\",\"updated_at\":\"2026-09-18 14:48:11\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"126\",\"legacy_submission_id\":null,\"evaluator_id\":\"208\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"ea\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-18 15:15:38\",\"updated_at\":\"2026-09-18 15:15:38\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"}],\"questionnaire_answers\":[{\"id\":\"395\",\"tracker_id\":\"75\",\"question_id\":\"4\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-28 20:01:30\",\"comments\":null},{\"id\":\"396\",\"tracker_id\":\"75\",\"question_id\":\"5\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-28 20:01:30\",\"comments\":null},{\"id\":\"397\",\"tracker_id\":\"75\",\"question_id\":\"6\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-28 20:01:30\",\"comments\":null},{\"id\":\"398\",\"tracker_id\":\"76\",\"question_id\":\"209\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 10:29:36\",\"comments\":null},{\"id\":\"399\",\"tracker_id\":\"76\",\"question_id\":\"218\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-29 10:29:36\",\"comments\":null},{\"id\":\"400\",\"tracker_id\":\"76\",\"question_id\":\"210\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 10:29:36\",\"comments\":null},{\"id\":\"401\",\"tracker_id\":\"76\",\"question_id\":\"211\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 10:29:36\",\"comments\":null},{\"id\":\"402\",\"tracker_id\":\"76\",\"question_id\":\"212\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-29 10:29:36\",\"comments\":null},{\"id\":\"403\",\"tracker_id\":\"76\",\"question_id\":\"213\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 10:29:36\",\"comments\":null},{\"id\":\"404\",\"tracker_id\":\"76\",\"question_id\":\"214\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-29 10:29:36\",\"comments\":null},{\"id\":\"405\",\"tracker_id\":\"76\",\"question_id\":\"215\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-08-29 10:29:36\",\"comments\":null},{\"id\":\"406\",\"tracker_id\":\"76\",\"question_id\":\"216\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 10:29:36\",\"comments\":null},{\"id\":\"407\",\"tracker_id\":\"76\",\"question_id\":\"217\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 10:29:36\",\"comments\":null},{\"id\":\"408\",\"tracker_id\":\"76\",\"question_id\":\"219\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-08-29 10:29:36\",\"comments\":null},{\"id\":\"409\",\"tracker_id\":\"77\",\"question_id\":\"153\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 11:13:11\",\"comments\":null},{\"id\":\"410\",\"tracker_id\":\"77\",\"question_id\":\"79\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-08-29 11:13:11\",\"comments\":null},{\"id\":\"481\",\"tracker_id\":\"88\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"237\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-13 18:52:36\",\"comments\":null},{\"id\":\"482\",\"tracker_id\":\"88\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"238\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-13 18:52:36\",\"comments\":null},{\"id\":\"483\",\"tracker_id\":\"89\",\"question_id\":\"206\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-13 18:54:47\",\"comments\":null},{\"id\":\"484\",\"tracker_id\":\"89\",\"question_id\":\"203\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-13 18:54:47\",\"comments\":null},{\"id\":\"485\",\"tracker_id\":\"89\",\"question_id\":\"204\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-13 18:54:47\",\"comments\":null},{\"id\":\"486\",\"tracker_id\":\"89\",\"question_id\":\"205\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-13 18:54:47\",\"comments\":null},{\"id\":\"487\",\"tracker_id\":\"89\",\"question_id\":\"207\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-13 18:54:47\",\"comments\":null},{\"id\":\"488\",\"tracker_id\":\"89\",\"question_id\":\"208\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-13 18:54:47\",\"comments\":null},{\"id\":\"519\",\"tracker_id\":\"99\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"243\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:43:00\",\"comments\":null},{\"id\":\"520\",\"tracker_id\":\"100\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"232\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:43:05\",\"comments\":null},{\"id\":\"521\",\"tracker_id\":\"101\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"232\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:55:14\",\"comments\":null},{\"id\":\"522\",\"tracker_id\":\"101\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"244\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 08:55:14\",\"comments\":null},{\"id\":\"523\",\"tracker_id\":\"101\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"245\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:55:14\",\"comments\":null},{\"id\":\"524\",\"tracker_id\":\"102\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"246\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:55:59\",\"comments\":null},{\"id\":\"525\",\"tracker_id\":\"102\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"247\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 08:55:59\",\"comments\":null},{\"id\":\"526\",\"tracker_id\":\"102\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"243\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 08:55:59\",\"comments\":null},{\"id\":\"527\",\"tracker_id\":\"103\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"232\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 09:34:50\",\"comments\":null},{\"id\":\"528\",\"tracker_id\":\"103\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"244\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 09:34:50\",\"comments\":null},{\"id\":\"529\",\"tracker_id\":\"103\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"245\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 09:34:50\",\"comments\":null},{\"id\":\"530\",\"tracker_id\":\"104\",\"question_id\":\"209\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 16:28:49\",\"comments\":null},{\"id\":\"531\",\"tracker_id\":\"104\",\"question_id\":\"218\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 16:28:49\",\"comments\":null},{\"id\":\"532\",\"tracker_id\":\"104\",\"question_id\":\"210\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 16:28:49\",\"comments\":null},{\"id\":\"533\",\"tracker_id\":\"104\",\"question_id\":\"211\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 16:28:49\",\"comments\":null},{\"id\":\"534\",\"tracker_id\":\"104\",\"question_id\":\"212\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 16:28:49\",\"comments\":null},{\"id\":\"535\",\"tracker_id\":\"104\",\"question_id\":\"213\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 16:28:49\",\"comments\":null},{\"id\":\"536\",\"tracker_id\":\"104\",\"question_id\":\"214\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 16:28:49\",\"comments\":null},{\"id\":\"537\",\"tracker_id\":\"104\",\"question_id\":\"215\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 16:28:49\",\"comments\":null},{\"id\":\"538\",\"tracker_id\":\"104\",\"question_id\":\"216\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 16:28:49\",\"comments\":null},{\"id\":\"539\",\"tracker_id\":\"104\",\"question_id\":\"217\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 16:28:49\",\"comments\":null},{\"id\":\"540\",\"tracker_id\":\"104\",\"question_id\":\"219\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 16:28:49\",\"comments\":null},{\"id\":\"541\",\"tracker_id\":\"105\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"199\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 16:29:01\",\"comments\":null},{\"id\":\"542\",\"tracker_id\":\"105\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"200\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 16:29:01\",\"comments\":null},{\"id\":\"543\",\"tracker_id\":\"105\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"201\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 16:29:01\",\"comments\":null},{\"id\":\"544\",\"tracker_id\":\"105\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"202\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 16:29:01\",\"comments\":null},{\"id\":\"545\",\"tracker_id\":\"105\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"203\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 16:29:01\",\"comments\":null},{\"id\":\"546\",\"tracker_id\":\"106\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"232\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 16:29:08\",\"comments\":null},{\"id\":\"547\",\"tracker_id\":\"106\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"244\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 16:29:08\",\"comments\":null},{\"id\":\"548\",\"tracker_id\":\"106\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"245\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 16:29:08\",\"comments\":null},{\"id\":\"549\",\"tracker_id\":\"107\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"246\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 16:29:19\",\"comments\":null},{\"id\":\"550\",\"tracker_id\":\"107\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"247\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-14 16:29:19\",\"comments\":null},{\"id\":\"551\",\"tracker_id\":\"107\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"243\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-14 16:29:19\",\"comments\":null},{\"id\":\"580\",\"tracker_id\":\"117\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"234\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-16 10:15:15\",\"comments\":null},{\"id\":\"581\",\"tracker_id\":\"117\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"235\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-16 10:15:15\",\"comments\":null},{\"id\":\"633\",\"tracker_id\":\"125\",\"question_id\":\"225\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 14:48:11\",\"comments\":null},{\"id\":\"634\",\"tracker_id\":\"125\",\"question_id\":\"227\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 14:48:11\",\"comments\":null},{\"id\":\"635\",\"tracker_id\":\"125\",\"question_id\":\"229\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 14:48:11\",\"comments\":null},{\"id\":\"636\",\"tracker_id\":\"126\",\"question_id\":\"315\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 15:15:38\",\"comments\":null},{\"id\":\"637\",\"tracker_id\":\"126\",\"question_id\":\"319\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 15:15:38\",\"comments\":null},{\"id\":\"638\",\"tracker_id\":\"126\",\"question_id\":\"157\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 15:15:38\",\"comments\":null},{\"id\":\"639\",\"tracker_id\":\"126\",\"question_id\":\"314\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 15:15:38\",\"comments\":null},{\"id\":\"640\",\"tracker_id\":\"126\",\"question_id\":\"154\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 15:15:38\",\"comments\":null},{\"id\":\"641\",\"tracker_id\":\"126\",\"question_id\":\"155\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-18 15:15:38\",\"comments\":null},{\"id\":\"642\",\"tracker_id\":\"126\",\"question_id\":\"158\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 15:15:38\",\"comments\":null},{\"id\":\"643\",\"tracker_id\":\"126\",\"question_id\":\"159\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 15:15:38\",\"comments\":null},{\"id\":\"644\",\"tracker_id\":\"126\",\"question_id\":\"156\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-18 15:15:38\",\"comments\":null}],\"evaluation_answers\":[],\"evaluation_submissions\":[],\"evaluation_results\":[],\"peer_evaluation_submissions\":[],\"peer_evaluation_results\":[],\"evaluation_reminders\":[],\"analytics_reports\":[],\"notifications\":[],\"activity_log_snapshot\":[]}');

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
('acad_structure', 'jhs'),
('acad_term', 'School Year'),
('acad_year', '2026-2027'),
('auto_schedule', '1'),
('control_mode', 'schedule'),
('eval_end', '2026-09-19T16:07'),
('eval_start', '2026-09-19T14:35'),
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
('rule_require_all', '0');

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

--
-- Dumping data for table `teaching_assignments`
--

INSERT INTO `teaching_assignments` (`id`, `user_id`, `education_level`, `year_level`, `section`, `assigned_by`, `created_at`) VALUES
(2, 144, 'College', '4th Year College', NULL, 125, '2026-08-12 16:10:58');

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
(125, 'FLOWEN', '$2y$10$McAetVIQaYyYwehdL9BDuuCeYe9X5VUPM2FJP/7/Cj4nArP9J9hvK', 'Flowen Nina Anecito', 'flowen@gmail.com', 'adm_6a64c5e2705b58.11850790.jpg', 'Student', NULL, 'superadmin', NULL, 1, '2026-07-25 22:19:14', '2026-07-31 08:39:54', NULL, NULL, 'self', 'admin', 1, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(136, 'JONG', '$2y$10$yIgcKx4BXUAnDTdeVFNYc.VTSHH.6/RNTnzvmkLre1MqBxtmDoME2', 'John Kenneth M. Annecito', 'jong@gmail.com', 'usr_6a6bf4fe9dd670.57218068.jpg', 'Staff', 'Staff/Physical Plant Coordinator/ Computer Lab Custodian', 'staff', NULL, 1, '2026-07-31 09:06:06', '2026-09-13 09:10:34', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(139, 'JOSELLE', '$2y$10$xkqqXKvb/YeuzLlxn8QV3Oepaj3wc7K7yrzd0x3PcavVkPyIsvQxS', 'Joselle C. Sardina', 'joselle@gmail.com', 'sh_6a6bfe5c5c7ba8.23401888.jpg', 'Student', NULL, 'teacher', NULL, 1, '2026-07-31 09:46:04', '2026-08-29 10:04:27', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(144, 'AMELIA', '$2y$10$91M06KZVDkHgDF.HDgxyh.Rq2.o3r4s1aYcu9W8Di80jMSDZiqXzi', 'Amelia C. Candolita', 'amelia@gmail.com', 'usr_6a6c0604ed7ea9.70902212.jpg', 'Staff', NULL, 'staff', NULL, 1, '2026-07-31 10:18:45', '2026-09-13 17:07:28', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(147, 'MAE', '$2y$10$0DCP31ME2z44AdkQMY6uUeXLT7gQC2UB0Ie19CEtolJxbncgl6HjS', 'Mae', 'mae@gmail.com', NULL, 'Student', NULL, 'student', 'junior_high', 0, '2026-08-01 20:33:23', '2026-08-09 08:13:37', 'JHS', NULL, 'self', 'self', 0, NULL, 'blocked', NULL, NULL, NULL, NULL, NULL),
(148, 'SHANE', '$2y$10$Uxq42pUMebCF/.8qO1AfrOX07Fynr.GkswbE76YpgGFUzLOmHBLQ2', 'Shane Mae', 'shane@gmail.com', NULL, 'Student', NULL, 'student', 'junior_high', 0, '2026-08-01 20:34:18', '2026-08-09 08:13:37', 'JHS', NULL, 'self', 'self', 0, NULL, 'blocked', NULL, NULL, NULL, NULL, NULL),
(149, 'MANUEL', '$2y$10$KkFZ.qbX44UhDrdvTK8JCOq4MOm/pTmqQr1/5M.dqcHWawaIlkvkG', 'Manuel', 'manuel@gmail.com', NULL, 'Student', NULL, 'student', 'junior_high', 1, '2026-08-01 20:37:55', '2026-08-10 18:14:31', 'JHS', 'Grade 8', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(151, 'JOHN MANUEL', '$2y$10$AZ2iOeXQ/i3HP9gouaVqDe8LeARAgKMSHrqsn0NFiFtGEsSiJLEhm', 'John Manuel', 'john@gmail.com', NULL, 'Student', NULL, 'student', 'college', 1, '2026-08-01 20:48:10', '2026-08-10 18:14:07', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(152, 'Sheramay', '$2y$10$Tm3/LgiyY8jZN3w4a2SiAuoOorLDbai.lyUY7a5Fz7tlhM/v19I02', 'Sheramay Dawn S. Pamay', 'Sheramay@gmail.com', 'usr_6a6e05fe01c7c9.51646425.jpg', 'Staff', 'Staff/Cashier', 'staff', NULL, 1, '2026-08-01 22:43:10', '2026-08-17 18:00:10', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(157, 'RENALITA', '$2y$10$Hwg1Afa273XUtZk2LUtmmOgUen.3TL.hlM1k4rV9ObyFxBPWMlpoq', 'Renalita', 'renalita@gmail.com', NULL, 'Student', NULL, 'dean', 'college', 1, '2026-08-04 17:52:56', '2026-08-04 17:53:20', 'BSIT', NULL, 'self', 'self', 0, NULL, 'approved', '040506', NULL, NULL, NULL, NULL),
(158, 'RAY', '$2y$10$w4.0sOL2y99sh.GLmm.3Que0wRUt.ECQuR/hifUHHEf0ce5os2O7q', 'Evelyn M. Verano', 'ray@gmail.com', NULL, 'Student', NULL, 'principal', 'both', 1, '2026-08-05 15:38:01', '2026-09-19 10:51:11', '', NULL, 'self', 'self', 1, NULL, 'approved', '060708', NULL, NULL, NULL, NULL),
(161, 'NEIL', '$2y$10$ww/kd/xsVNEfgELFicHvpu/wxv9Vu09dnHvZgNJ1Ae6epGNU90rWO', 'Neil Alonsagay', 'neil@gmail.com', NULL, 'Student', NULL, 'student', 'college', 1, '2026-08-06 13:41:55', '2026-08-10 18:13:56', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(164, 'REYNALDO', '$2y$10$QstDY64FSyEg7QqgXmcEhuvEoDjmwpuUW7G4LHJLWF/p2jtlL4ZQC', 'Reynaldo C. Varon', 'reynaldo@gmail.com', 'usr_6a7449a9bc1589.35072434.jpg', 'Teacher', NULL, 'teacher', NULL, 1, '2026-08-06 16:45:29', '2026-09-14 08:29:05', NULL, NULL, 'self', 'self', 0, '2nd Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(166, 'REY', '$2y$10$HXxlbXpqMq0oSyd.6tGGaO7LgBIDSQ9D4nv9HCZv0L4pqS03i2rH6', 'rey', 'rey@gmail.com', NULL, 'Student', 'Student', 'student', 'junior_high', 1, '2026-08-07 23:49:57', '2026-08-07 23:50:27', 'JHS', 'Grade 7', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(167, 'MITCH', '$2y$10$eQrzPSnPHnEXbBZfkAJzg.CQUkFuoEsfgqn/zKUiZX8a5r1L92LYa', 'mitch', 'mitch@gmail.com', NULL, 'Student', 'Student', 'student', 'senior_high', 1, '2026-08-10 11:54:33', '2026-08-10 11:54:50', 'SHS', 'Grade 11', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(169, 'mike', '$2y$10$g1dQUAfERbzpVtHFz.XbbeWd2UZT2aJOtPN9CDiCaEF6vrCux/P4a', 'mike', 'mike@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 0, '2026-08-10 18:17:11', '2026-08-24 13:51:49', 'College', '4th Year College', 'self', 'self', 0, NULL, 'blocked', NULL, NULL, NULL, NULL, NULL),
(170, 'JINGLE', '$2y$10$bt4LdJXqYIOEM4KDXQr0XuKQsXL1zE31DDGhQdYusxMQtu28/pD0e', 'Jingle R. Ausan', 'jingle@gmail.com', 'usr_6a7c94c5d7f8d3.77865885.jpg', 'Teacher', 'Teacher/ librarian', 'teacher', NULL, 1, '2026-08-12 23:44:05', '2026-08-29 12:46:12', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(171, 'jeo', '$2y$10$.qFtE1a2ywTVnQUKGiSWAO.tHmTrkmdYEB.xi2WsmGGUvwnLdsAvm', 'jeo', 'jeo@gmail.com', 'stu_6aac8ee4e9f6e8.04613904.jpg', 'Student', 'Student', 'student', 'senior_high', 1, '2026-08-12 23:45:19', '2026-09-18 09:07:48', 'SHS', 'Grade 11', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(172, 'johnny_e._delos_santos_6518', 'NOT NULL', 'Johnny E. Delos Santos', NULL, 'p_6a818636de0682.39560707.jpg', 'Student', 'Personnel', 'staff', NULL, 1, '2026-08-16 17:43:18', '2026-08-18 13:08:38', NULL, NULL, 'self', 'admin_nologin', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(175, 'Dim', '$2y$10$NoPujTO4svEjolJP03d3C.c6jlJG8ShC2B3K/0f7EAwVC57CNiGai', 'Dim Mark Damaso', 'dim@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 1, '2026-08-18 16:28:09', '2026-08-18 16:28:19', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(181, 'valerie_jane_s._bendijo_8a42', 'NOT NULL', 'Valerie Jane S. Bendijo', NULL, 'p_6a8a4a6ec7c716.61866805.jpg', 'Student', 'Personnel', 'staff', NULL, 1, '2026-08-23 09:18:38', '2026-08-23 09:18:38', 'HEALTH SERVICES OFFICER SCHOOL NURSE', NULL, 'self', 'admin_nologin', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(183, 'jessie_a._aquillo_a0ed', 'NOT NULL', 'Jessie A. Aquillo', NULL, '', 'Student', 'Teacher', 'teacher', NULL, 1, '2026-08-23 09:52:08', '2026-08-29 12:55:38', 'Campus Ministry Officer  Formation Services Coordinator', NULL, 'self', 'admin_nologin', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(186, 'hannah', '$2y$10$dNABV5O5TASosm.18I5Jr.ME4QZWo7pYZDwk2LZ4aTGPcXyQZwKve', 'hannah mae solangon', 'hannahmaesolangon377@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 1, '2026-08-27 14:44:25', '2026-08-27 14:45:15', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(187, 'charlo', '$2y$10$qTK2Y8N9bUFF/uVu/u.ZTuZjvj7IVQlNBdCYD47gw8b6agroc7tMa', 'Charlo Ronquillio', 'charlo@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 1, '2026-08-27 14:54:15', '2026-08-27 14:54:17', 'College', '4th Year College', 'self', 'self', 0, NULL, 'pending', NULL, NULL, NULL, NULL, NULL),
(192, 'jen', '$2y$10$c8Szh4dkzPNXny4Wv/G./OkvwKdPtS283yi17yroW0k/vave98pgm', 'jen', '', NULL, 'Student', 'Student', 'student', 'junior_high', 1, '2026-08-29 13:01:57', '2026-08-29 13:02:32', 'JHS', 'Grade 7', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(194, 'jenjen', '$2y$10$B5wJnJT1uysAVeOx0GP1Y.DFqPToTqfaYVTQQ3SPj7MNMVOLvMKeW', 'Jennifer Biadora', 'j@gmail.com', NULL, 'Teacher', 'Teacher', 'teacher', NULL, 1, '2026-08-29 13:16:24', '2026-09-14 08:29:08', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(198, 'merlyn', '$2y$10$uOST1T6NFBfcBrUvQ0khFe1v1sgwvhHwdFJDOAeE4gruWMNV3NAXi', 'MERLYN MANTAC', 'm@gmail.com', NULL, 'Staff', 'Personnel', 'staff', NULL, 1, '2026-08-29 13:24:07', '2026-08-29 13:26:47', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(207, 'TERENCE', '$2y$10$2hKK5vAR2/nqs2uParR7TOWjHsdarLazxiiOMpreHtguAFM5AM8US', 'Terence Tendan', 'terence@gmail.com', 'usr_6aa8e5df34ba49.14763314.jpg', 'Staff', 'Personnel', 'staff', NULL, 1, '2026-09-15 14:29:51', '2026-09-15 14:30:00', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(208, 'lorraine', '$2y$10$u/57xCh0Y79h8NQruzbhF.Y08Wwm7pdh3Wgup8CL4veEEbpL5E0aW', 'Lorraine R. Sabay', 'lorraine@gmail.com', 'adm_6aaaa3471a2248.08134121.jpg', 'Student', NULL, 'superadmin', NULL, 1, '2026-09-16 22:10:15', '2026-09-16 22:10:23', NULL, NULL, 'self', 'self', 1, NULL, 'pending', NULL, NULL, NULL, NULL, NULL),
(209, 'JESSIE', '$2y$10$Mhlg9IrZizekQ8RHttBo1.guYFfwqgmV83Avf2BT2XTZCLO7hBU5e', 'Jessie A. Aquillo', 'jessie@gmail.com', 'usr_6aacec88926157.43007193.jpg', 'Staff', 'Formation Services Coordinator/ CMO', 'staff', NULL, 1, '2026-09-18 15:47:20', '2026-09-18 15:52:12', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(210, 'EVELYN', '$2y$10$AgC53awqMbcQ919ZKTXHaeLxXx2YA5VXn91WwUrofb0ijAZ4naYH6', 'Evelyn M. Verano', 'evelyn@gmail.com', NULL, 'Student', NULL, 'principal', 'both', 1, '2026-09-19 10:45:49', '2026-09-19 10:46:20', NULL, NULL, 'self', '', 1, NULL, 'approved', NULL, NULL, NULL, NULL, NULL);

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
(73, 144, 'Staff', 'student', 'General', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 1, '2026-08-06 05:25:56', '2026-08-15 04:35:11'),
(75, 144, 'Staff', 'student', 'Cooperaton', 'Demonstrates punctuality, excellent attendance *', 2, '2026-08-12 07:48:24', '2026-08-15 04:35:11'),
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
(143, 144, 'Multi-Role', 'student', 'Teaching & Student Support', 'Clearly explains lessons and course-related concepts.', 3, '2026-08-20 09:54:16', '2026-08-20 09:54:16'),
(144, 144, 'Multi-Role', 'student', 'Teaching & Student Support', 'Provides appropriate guidance to 4th Year College students.', 4, '2026-08-20 09:54:29', '2026-08-20 09:54:29'),
(145, 144, 'Multi-Role', 'student', 'Teaching & Student Support', 'Demonstrates adequate knowledge of the subject being taught.', 5, '2026-08-20 09:54:40', '2026-08-20 09:54:40'),
(146, 144, 'Multi-Role', 'student', 'Teaching & Student Support', 'Encourages students to participate in learning activities.', 6, '2026-08-20 09:54:47', '2026-08-20 09:54:47'),
(147, 144, 'Multi-Role', 'student', 'Teaching & Student Support', 'Provides helpful feedback regarding student work.', 7, '2026-08-20 09:55:04', '2026-08-20 09:55:04'),
(148, 144, 'Multi-Role', 'student', 'Teaching & Student Support', 'Communicates instructions clearly.', 8, '2026-08-20 09:55:12', '2026-08-20 09:55:12'),
(149, 144, 'Multi-Role', 'student', 'Teaching & Student Support', 'Responds appropriately to students\' academic concerns.', 9, '2026-08-20 09:55:22', '2026-08-20 09:55:22'),
(150, 144, 'Multi-Role', 'student', 'Teaching & Student Support', 'Uses appropriate activities to support student learning.', 10, '2026-08-20 09:55:31', '2026-08-20 09:55:31'),
(151, 144, 'Multi-Role', 'student', 'Teaching & Student Support', 'Treats students fairly and respectfully.', 11, '2026-08-20 09:55:49', '2026-08-20 09:55:49'),
(152, 144, 'Multi-Role', 'student', 'Teaching & Student Support', 'Performs her teaching assignment responsibly.', 12, '2026-08-20 09:55:59', '2026-08-20 09:55:59'),
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
(273, 144, 'Staff', 'ea', 'General', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 1, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
(274, 144, 'Staff', 'ea', 'Cooperaton', 'Demonstrates punctuality, excellent attendance *', 2, '2026-09-15 04:51:01', '2026-09-15 04:51:01'),
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
(319, 158, 'Principal', 'ea', 'Administrative Functions', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 7, '2026-09-18 07:14:46', '2026-09-18 07:14:46');

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
(14, 144, 'Staff', 'student', 'Cooperaton', 1),
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
(43, 144, 'Multi-Role', 'student', 'Teaching & Student Support', 2),
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
(72, 144, 'Staff', 'ea', 'Cooperaton', 1),
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
(97, 207, 'Staff', 'ea', 'Professionalism', 1);

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
(164, 136, '4th Year College'),
(146, 139, 'Grade 11'),
(145, 139, 'Grade 8'),
(172, 144, '4th Year College'),
(159, 164, '1st Year College'),
(160, 164, '3rd Year College'),
(161, 164, '4th Year College'),
(158, 164, 'Grade 12'),
(168, 170, 'Grade 11'),
(140, 183, '4th Year College'),
(139, 194, '4th Year College'),
(175, 207, '4th Year College'),
(174, 207, 'Grade 9'),
(176, 209, '4th Year College');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `evaluation_questions`
--
ALTER TABLE `evaluation_questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=244;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=648;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4384;

--
-- AUTO_INCREMENT for table `rating_certifications`
--
ALTER TABLE `rating_certifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_change_log`
--
ALTER TABLE `role_change_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
-- AUTO_INCREMENT for table `system_archives`
--
ALTER TABLE `system_archives`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=211;

--
-- AUTO_INCREMENT for table `user_management_log`
--
ALTER TABLE `user_management_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_questions`
--
ALTER TABLE `user_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=320;

--
-- AUTO_INCREMENT for table `user_question_categories`
--
ALTER TABLE `user_question_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `user_year_levels`
--
ALTER TABLE `user_year_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=177;

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
