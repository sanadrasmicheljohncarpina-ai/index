-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 24, 2026 at 05:55 AM
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
(3, 146, '2026-08-13 07:41:59');

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
(2, '2026-2027 — School Year', '2026-2027', 'School Year', '2026-08-07', '2026-08-07', 0, '2026-08-05 18:15:42', 1),
(3, '2026-2027 — 1st Semester', '2026-2027', '1st Semester', '2026-08-07', '2026-08-07', 0, '2026-08-05 18:16:56', 0),
(4, '2025-2-26 — School Year', '2025-2-26', 'School Year', '2026-08-06', '2026-08-06', 0, '2026-08-06 11:03:33', 0),
(5, '2026-2027 — Summer', '2026-2027', 'Summer', '2026-08-07', '2026-08-07', 0, '2026-08-06 13:21:02', 1),
(6, '1st semester 2026-2027', '2026-2027', '1st Semester', '2026-08-21', '2026-08-22', 0, '2026-08-21 12:39:24', 0),
(7, '2026-2027 — 2nd Semester', '2026-2027', '2nd Semester', '2026-08-07', '2026-08-07', 1, '2026-08-24 09:52:56', 0);

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_questions`
--

CREATE TABLE `evaluation_questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `target_type` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'General',
  `question_text` text NOT NULL,
  `eval_type` enum('student','peer','school_head','principal','ea') NOT NULL DEFAULT 'student',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluation_questions`
--

INSERT INTO `evaluation_questions` (`id`, `target_type`, `category`, `question_text`, `eval_type`, `created_at`, `is_active`, `date_added`) VALUES
(190, 'Teacher', 'General', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day.', 'peer', '2026-08-13 13:43:50', 1, '2026-08-15 04:35:11'),
(191, 'Teacher', 'Professionalism', 'Demonstrates punctuality, excellent attendance .', 'peer', '2026-08-13 13:43:58', 1, '2026-08-15 04:35:11'),
(193, 'Multi-Role', 'General Performance', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 'student', '2026-08-18 15:08:56', 1, '2026-08-18 07:08:56'),
(195, 'Teacher', 'Attendance', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 'student', '2026-08-20 10:36:01', 1, '2026-08-20 02:36:01'),
(196, 'Teacher', 'Professionalism', 'Demonstrates punctuality, excellent attendance *', 'student', '2026-08-20 10:36:07', 1, '2026-08-20 02:36:07'),
(197, 'Teacher', 'Initiative', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 'peer', '2026-08-20 10:37:11', 1, '2026-08-20 02:37:11'),
(198, 'Teacher', 'Attendance', 'Demonstrates punctuality, excellent attendance *', 'student', '2026-08-20 10:43:52', 1, '2026-08-20 02:43:52'),
(199, 'Teacher', 'Professionalism', 'Starts and ends class on time', 'student', '2026-08-20 19:56:01', 1, '2026-08-20 11:56:01'),
(200, 'Teacher', 'Professionalism', 'corrects, records, evaluates and feed backs students’ performance regularly and on time', 'student', '2026-08-20 19:56:26', 1, '2026-08-20 11:56:26');

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
(191, 93),
(193, 75),
(195, 103),
(196, 106),
(197, 95),
(197, 96),
(197, 104),
(198, 103),
(198, 106),
(199, 106),
(200, 106);

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
(1, 5, 157, 151, 'student', 'college', '2026-08-15 13:03:02');

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

INSERT INTO `evaluation_tracker` (`id`, `legacy_submission_id`, `evaluator_id`, `target_user_id`, `eval_bucket`, `level`, `form_type`, `form_id`, `period`, `period_id`, `score`, `remarks`, `eval_type`, `peer_group`, `status`, `submitted_at`, `updated_at`, `evaluator_year_level`, `evaluator_department`, `evaluation_context`) VALUES
(23, NULL, 161, 146, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-12 22:00:24', '2026-08-17 17:58:18', NULL, NULL, 'multi_role'),
(24, NULL, 167, 163, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-13 07:49:34', '2026-08-13 07:49:34', NULL, NULL, 'teacher'),
(25, NULL, 167, 160, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-13 07:57:25', '2026-08-13 07:57:25', NULL, NULL, 'teacher'),
(26, NULL, 167, 170, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-13 07:57:36', '2026-08-13 07:57:36', NULL, NULL, 'teacher'),
(27, NULL, 171, 163, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-13 07:58:53', '2026-08-13 07:58:53', NULL, NULL, 'teacher'),
(28, NULL, 151, 163, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-14 14:53:00', '2026-08-14 14:53:00', NULL, NULL, 'teacher'),
(29, NULL, 171, 136, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-14 14:58:06', '2026-08-17 17:58:18', NULL, NULL, 'multi_role'),
(30, NULL, 169, 163, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-14 18:35:06', '2026-08-14 18:35:06', NULL, NULL, 'teacher'),
(31, NULL, 163, 165, 'Faculty', NULL, '', 3, NULL, 5, 4.50, 'N/A', 'peer', 'Teacher', 'submitted', '2026-08-14 18:52:31', '2026-08-14 20:09:29', NULL, NULL, 'teacher'),
(37, NULL, 136, 163, 'Faculty', NULL, 'staff_peer', NULL, NULL, 5, NULL, 'N/A', 'staff_peer', 'Teacher', 'submitted', '2026-08-14 20:20:58', '2026-08-14 20:20:58', NULL, NULL, 'teacher'),
(38, NULL, 166, 136, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-16 12:33:07', '2026-08-17 17:58:18', NULL, NULL, 'multi_role'),
(39, NULL, 161, 168, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-17 12:06:03', '2026-08-17 12:06:03', NULL, NULL, 'teacher'),
(40, NULL, 161, 165, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-17 12:08:57', '2026-08-17 12:08:57', NULL, NULL, 'teacher'),
(41, NULL, 171, 160, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-17 12:09:39', '2026-08-17 12:09:39', NULL, NULL, 'teacher'),
(42, NULL, 161, 163, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-17 16:33:44', '2026-08-17 16:33:44', NULL, NULL, 'teacher'),
(43, NULL, 151, 165, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-17 16:37:02', '2026-08-17 16:37:02', NULL, NULL, 'teacher'),
(44, NULL, 151, 160, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-17 16:37:59', '2026-08-17 16:37:59', NULL, NULL, 'teacher'),
(45, NULL, 151, 170, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-17 16:48:00', '2026-08-17 16:48:00', NULL, NULL, 'teacher'),
(46, NULL, 161, 160, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-18 12:26:08', '2026-08-18 12:26:08', NULL, NULL, 'teacher'),
(47, NULL, 175, 172, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-19 13:42:27', '2026-08-19 13:42:27', NULL, NULL, 'staff'),
(49, NULL, 171, 146, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'N/A', 'student', NULL, 'submitted', '2026-08-19 21:16:20', '2026-08-19 21:16:20', NULL, NULL, 'staff'),
(50, NULL, 177, 158, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'wala', 'student', NULL, 'submitted', '2026-08-21 20:41:53', '2026-08-21 20:41:53', NULL, NULL, 'school_head'),
(51, NULL, 178, 144, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'panis', 'student', NULL, 'submitted', '2026-08-22 09:47:31', '2026-08-22 09:47:31', NULL, NULL, 'staff'),
(52, NULL, 178, 172, 'Faculty', NULL, '', NULL, NULL, 5, NULL, 'yay', 'student', NULL, 'submitted', '2026-08-22 09:53:12', '2026-08-22 09:53:12', NULL, NULL, 'staff');

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
(32, 'designation_update', 179, 'testing updated their designation from \"Teacher\" to \"Cashier\".', '{\"user_id\":179,\"full_name\":\"testing\",\"role\":\"teacher\",\"old_desig\":\"Teacher\",\"new_desig\":\"Cashier\"}', 0, '2026-08-22 10:14:59');

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
(235, 23, 72, 'evaluation', NULL, NULL, 4.00, '2026-08-12 22:00:24', NULL),
(236, 24, 184, 'evaluation', NULL, NULL, 5.00, '2026-08-13 07:49:34', NULL),
(237, 24, 185, 'evaluation', NULL, NULL, 4.00, '2026-08-13 07:49:34', NULL),
(238, 24, 188, 'evaluation', NULL, NULL, 4.00, '2026-08-13 07:49:34', NULL),
(239, 24, 189, 'evaluation', NULL, NULL, 5.00, '2026-08-13 07:49:34', NULL),
(240, 25, 184, 'evaluation', NULL, NULL, 4.00, '2026-08-13 07:57:25', NULL),
(241, 25, 185, 'evaluation', NULL, NULL, 3.00, '2026-08-13 07:57:25', NULL),
(242, 25, 188, 'evaluation', NULL, NULL, 5.00, '2026-08-13 07:57:25', NULL),
(243, 25, 189, 'evaluation', NULL, NULL, 5.00, '2026-08-13 07:57:25', NULL),
(244, 26, 184, 'evaluation', NULL, NULL, 5.00, '2026-08-13 07:57:36', NULL),
(245, 26, 185, 'evaluation', NULL, NULL, 2.00, '2026-08-13 07:57:36', NULL),
(246, 26, 188, 'evaluation', NULL, NULL, 5.00, '2026-08-13 07:57:36', NULL),
(247, 26, 189, 'evaluation', NULL, NULL, 4.00, '2026-08-13 07:57:36', NULL),
(248, 27, 184, 'evaluation', NULL, NULL, 5.00, '2026-08-13 07:58:53', NULL),
(249, 27, 185, 'evaluation', NULL, NULL, 4.00, '2026-08-13 07:58:53', NULL),
(250, 27, 188, 'evaluation', NULL, NULL, 5.00, '2026-08-13 07:58:53', NULL),
(251, 27, 189, 'evaluation', NULL, NULL, 4.00, '2026-08-13 07:58:53', NULL),
(252, 28, 184, 'evaluation', NULL, NULL, 5.00, '2026-08-14 14:53:00', NULL),
(253, 28, 185, 'evaluation', NULL, NULL, 4.00, '2026-08-14 14:53:00', NULL),
(254, 28, 188, 'evaluation', NULL, NULL, 5.00, '2026-08-14 14:53:00', NULL),
(255, 28, 189, 'evaluation', NULL, NULL, 4.00, '2026-08-14 14:53:00', NULL),
(256, 29, 192, 'evaluation', NULL, NULL, 5.00, '2026-08-14 14:58:06', NULL),
(257, 30, 184, 'evaluation', NULL, NULL, 5.00, '2026-08-14 18:35:06', NULL),
(258, 30, 185, 'evaluation', NULL, NULL, 2.00, '2026-08-14 18:35:06', NULL),
(259, 30, 188, 'evaluation', NULL, NULL, 2.00, '2026-08-14 18:35:06', NULL),
(260, 30, 189, 'evaluation', NULL, NULL, 2.00, '2026-08-14 18:35:06', NULL),
(261, 31, 190, 'evaluation', NULL, NULL, 5.00, '2026-08-14 18:52:31', NULL),
(262, 31, 191, 'evaluation', NULL, NULL, 4.00, '2026-08-14 18:52:31', NULL),
(263, 37, 190, 'evaluation', NULL, NULL, 5.00, '2026-08-14 20:20:58', NULL),
(264, 37, 191, 'evaluation', NULL, NULL, 5.00, '2026-08-14 20:20:58', NULL),
(265, 38, 192, 'evaluation', NULL, NULL, 4.00, '2026-08-16 12:33:07', NULL),
(266, 39, 184, 'evaluation', NULL, NULL, 5.00, '2026-08-17 12:06:03', NULL),
(267, 39, 185, 'evaluation', NULL, NULL, 4.00, '2026-08-17 12:06:03', NULL),
(268, 39, 188, 'evaluation', NULL, NULL, 5.00, '2026-08-17 12:06:03', NULL),
(269, 39, 189, 'evaluation', NULL, NULL, 5.00, '2026-08-17 12:06:03', NULL),
(270, 40, 184, 'evaluation', NULL, NULL, 5.00, '2026-08-17 12:08:57', NULL),
(271, 40, 185, 'evaluation', NULL, NULL, 5.00, '2026-08-17 12:08:57', NULL),
(272, 40, 188, 'evaluation', NULL, NULL, 4.00, '2026-08-17 12:08:57', NULL),
(273, 40, 189, 'evaluation', NULL, NULL, 5.00, '2026-08-17 12:08:57', NULL),
(274, 41, 184, 'evaluation', NULL, NULL, 5.00, '2026-08-17 12:09:39', NULL),
(275, 41, 185, 'evaluation', NULL, NULL, 4.00, '2026-08-17 12:09:39', NULL),
(276, 41, 188, 'evaluation', NULL, NULL, 5.00, '2026-08-17 12:09:39', NULL),
(277, 41, 189, 'evaluation', NULL, NULL, 5.00, '2026-08-17 12:09:39', NULL),
(278, 42, 184, 'evaluation', NULL, NULL, 5.00, '2026-08-17 16:33:44', NULL),
(279, 42, 185, 'evaluation', NULL, NULL, 4.00, '2026-08-17 16:33:44', NULL),
(280, 42, 188, 'evaluation', NULL, NULL, 5.00, '2026-08-17 16:33:44', NULL),
(281, 42, 189, 'evaluation', NULL, NULL, 5.00, '2026-08-17 16:33:44', NULL),
(282, 43, 184, 'evaluation', NULL, NULL, 5.00, '2026-08-17 16:37:02', NULL),
(283, 43, 185, 'evaluation', NULL, NULL, 4.00, '2026-08-17 16:37:02', NULL),
(284, 43, 188, 'evaluation', NULL, NULL, 5.00, '2026-08-17 16:37:02', NULL),
(285, 43, 189, 'evaluation', NULL, NULL, 5.00, '2026-08-17 16:37:02', NULL),
(286, 44, 184, 'evaluation', NULL, NULL, 5.00, '2026-08-17 16:37:59', NULL),
(287, 44, 185, 'evaluation', NULL, NULL, 4.00, '2026-08-17 16:37:59', NULL),
(288, 44, 188, 'evaluation', NULL, NULL, 5.00, '2026-08-17 16:37:59', NULL),
(289, 44, 189, 'evaluation', NULL, NULL, 4.00, '2026-08-17 16:37:59', NULL),
(290, 45, 184, 'evaluation', NULL, NULL, 5.00, '2026-08-17 16:48:00', NULL),
(291, 45, 185, 'evaluation', NULL, NULL, 4.00, '2026-08-17 16:48:00', NULL),
(292, 45, 188, 'evaluation', NULL, NULL, 5.00, '2026-08-17 16:48:00', NULL),
(293, 45, 189, 'evaluation', NULL, NULL, 5.00, '2026-08-17 16:48:00', NULL),
(294, 46, 184, 'evaluation', NULL, NULL, 5.00, '2026-08-18 12:26:08', NULL),
(295, 46, 185, 'evaluation', NULL, NULL, 4.00, '2026-08-18 12:26:08', NULL),
(296, 46, 188, 'evaluation', NULL, NULL, 5.00, '2026-08-18 12:26:08', NULL),
(297, 46, 189, 'evaluation', NULL, NULL, 4.00, '2026-08-18 12:26:08', NULL),
(298, 47, NULL, 'user', 87, NULL, 5.00, '2026-08-19 13:42:27', NULL),
(299, 47, NULL, 'user', 88, NULL, 5.00, '2026-08-19 13:42:27', NULL),
(301, 50, NULL, 'user', 153, NULL, 5.00, '2026-08-21 20:41:53', NULL),
(302, 50, NULL, 'user', 79, NULL, 4.00, '2026-08-21 20:41:53', NULL),
(303, 51, NULL, 'user', 75, NULL, 5.00, '2026-08-22 09:47:31', NULL),
(304, 51, NULL, 'user', 73, NULL, 4.00, '2026-08-22 09:47:31', NULL),
(305, 52, NULL, 'user', 87, NULL, 5.00, '2026-08-22 09:53:12', NULL),
(306, 52, NULL, 'user', 88, NULL, 3.00, '2026-08-22 09:53:12', NULL);

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
  `eval_type` enum('student','peer') NOT NULL DEFAULT 'student',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `question_categories`
--

INSERT INTO `question_categories` (`id`, `target_type`, `category_name`, `eval_type`, `sort_order`, `created_at`) VALUES
(6, 'Registrar', 'Service Quality', 'student', 0, '2026-06-15 07:39:21'),
(7, 'Registrar', 'Accuracy', 'student', 1, '2026-06-15 07:39:21'),
(8, 'Registrar', 'Professionalism', 'student', 2, '2026-06-15 07:39:21'),
(9, 'Registrar', 'Responsiveness', 'student', 3, '2026-06-15 07:39:21'),
(13, 'Cashier', 'Courtesy', 'student', 3, '2026-06-15 07:39:21'),
(14, 'Bookkeeper', 'Accuracy', 'student', 0, '2026-06-15 07:39:21'),
(15, 'Bookkeeper', 'Timeliness', 'student', 1, '2026-06-15 07:39:21'),
(16, 'Bookkeeper', 'Professionalism', 'student', 2, '2026-06-15 07:39:21'),
(17, 'Bookkeeper', 'Financial Reporting', 'student', 3, '2026-06-15 07:39:21'),
(18, 'Librarian', 'Service Quality', 'student', 0, '2026-06-15 07:39:21'),
(19, 'Librarian', 'Resource Management', 'student', 1, '2026-06-15 07:39:21'),
(20, 'Librarian', 'Professionalism', 'student', 2, '2026-06-15 07:39:21'),
(21, 'Librarian', 'Assistance', 'student', 3, '2026-06-15 07:39:21'),
(22, 'Guidance', 'Counseling Quality', 'student', 0, '2026-06-15 07:39:21'),
(23, 'Guidance', 'Approachability', 'student', 1, '2026-06-15 07:39:21'),
(24, 'Guidance', 'Professionalism', 'student', 2, '2026-06-15 07:39:21'),
(25, 'Guidance', 'Student Support', 'student', 3, '2026-06-15 07:39:21'),
(26, 'Nurse', 'Medical Service', 'student', 0, '2026-06-15 07:39:21'),
(27, 'Nurse', 'Responsiveness', 'student', 1, '2026-06-15 07:39:21'),
(28, 'Nurse', 'Professionalism', 'student', 2, '2026-06-15 07:39:21'),
(30, 'Personnel', 'Work Performance', 'student', 0, '2026-06-15 07:39:21'),
(31, 'Personnel', 'Professionalism', 'student', 1, '2026-06-15 07:39:21'),
(32, 'Personnel', 'Communication', 'student', 2, '2026-06-15 07:39:21'),
(33, 'Personnel', 'Responsiveness', 'student', 3, '2026-06-15 07:39:21'),
(34, 'Bookkeeper', 'Approachable', 'student', 4, '2026-06-15 07:40:00'),
(36, 'Registrar', 'category', 'student', 4, '2026-06-15 16:04:07'),
(42, 'Faculty', 'Collaboration', 'peer', 0, '2026-06-21 14:44:05'),
(43, 'Faculty', 'Professionalism', 'peer', 1, '2026-06-21 14:44:05'),
(44, 'Faculty', 'Communication', 'peer', 2, '2026-06-21 14:44:05'),
(45, 'Faculty', 'Initiative', 'peer', 3, '2026-06-21 14:44:05'),
(46, 'Faculty', 'Dependability', 'peer', 4, '2026-06-21 14:44:05'),
(47, 'Registrar', 'Cooperation', 'peer', 0, '2026-06-21 14:44:05'),
(48, 'Registrar', 'Accuracy', 'peer', 1, '2026-06-21 14:44:05'),
(49, 'Registrar', 'Professionalism', 'peer', 2, '2026-06-21 14:44:05'),
(50, 'Registrar', 'Responsiveness', 'peer', 3, '2026-06-21 14:44:05'),
(51, 'Cashier', 'Teamwork', 'peer', 0, '2026-06-21 14:44:05'),
(52, 'Cashier', 'Accuracy', 'peer', 1, '2026-06-21 14:44:05'),
(53, 'Cashier', 'Professionalism', 'peer', 2, '2026-06-21 14:44:05'),
(54, 'Cashier', 'Reliability', 'peer', 3, '2026-06-21 14:44:05'),
(55, 'Bookkeeper', 'Accuracy', 'peer', 0, '2026-06-21 14:44:05'),
(56, 'Bookkeeper', 'Timeliness', 'peer', 1, '2026-06-21 14:44:05'),
(57, 'Bookkeeper', 'Professionalism', 'peer', 2, '2026-06-21 14:44:05'),
(58, 'Bookkeeper', 'Transparency', 'peer', 3, '2026-06-21 14:44:05'),
(59, 'Librarian', 'Teamwork', 'peer', 0, '2026-06-21 14:44:05'),
(60, 'Librarian', 'Resource Management', 'peer', 1, '2026-06-21 14:44:05'),
(61, 'Librarian', 'Professionalism', 'peer', 2, '2026-06-21 14:44:05'),
(62, 'Librarian', 'Helpfulness', 'peer', 3, '2026-06-21 14:44:05'),
(63, 'Guidance', 'Collaboration', 'peer', 0, '2026-06-21 14:44:05'),
(64, 'Guidance', 'Approachability', 'peer', 1, '2026-06-21 14:44:05'),
(65, 'Guidance', 'Professionalism', 'peer', 2, '2026-06-21 14:44:05'),
(66, 'Guidance', 'Empathy', 'peer', 3, '2026-06-21 14:44:05'),
(67, 'Nurse', 'Teamwork', 'peer', 0, '2026-06-21 14:44:05'),
(68, 'Nurse', 'Responsiveness', 'peer', 1, '2026-06-21 14:44:05'),
(69, 'Nurse', 'Professionalism', 'peer', 2, '2026-06-21 14:44:05'),
(70, 'Nurse', 'Care Quality', 'peer', 3, '2026-06-21 14:44:05'),
(71, 'Personnel', 'Work Performance', 'peer', 0, '2026-06-21 14:44:05'),
(72, 'Personnel', 'Professionalism', 'peer', 1, '2026-06-21 14:44:05'),
(73, 'Personnel', 'Communication', 'peer', 2, '2026-06-21 14:44:05'),
(74, 'Personnel', 'Team Spirit', 'peer', 3, '2026-06-21 14:44:05'),
(75, 'Multi-Role', 'General Performance', 'student', 0, '2026-06-23 11:41:01'),
(76, 'Multi-Role', 'Cross-Role Responsibilities', 'student', 1, '2026-06-23 11:41:01'),
(77, 'Multi-Role', 'Professionalism', 'student', 2, '2026-06-23 11:41:01'),
(78, 'Multi-Role', 'Adaptability', 'student', 3, '2026-06-23 11:41:01'),
(79, 'Multi-Role', 'Communication', 'student', 4, '2026-06-23 11:41:01'),
(80, 'Multi-Role', 'Cross-Role Collaboration', 'peer', 0, '2026-06-23 11:41:01'),
(81, 'Multi-Role', 'Professionalism', 'peer', 1, '2026-06-23 11:41:01'),
(82, 'Multi-Role', 'Adaptability', 'peer', 2, '2026-06-23 11:41:01'),
(83, 'Multi-Role', 'Communication', 'peer', 3, '2026-06-23 11:41:01'),
(84, 'Multi-Role', 'Initiative', 'peer', 4, '2026-06-23 11:41:01'),
(85, 'Staff', 'Cooperaton', 'student', 1, '2026-06-24 09:43:30'),
(86, 'Staff', 'Attendance', 'student', 2, '2026-06-24 09:44:27'),
(87, 'Staff', 'Relationship', 'student', 3, '2026-06-24 09:45:22'),
(88, 'Staff', 'QUALITY OF WORK', 'student', 4, '2026-06-24 09:46:14'),
(90, 'Faculty', 'Professionalism', 'student', 1, '2026-06-25 10:41:59'),
(92, 'Teacher', 'Collaboration', 'peer', 0, '2026-07-25 17:33:30'),
(93, 'Teacher', 'Professionalism', 'peer', 1, '2026-07-25 17:33:30'),
(94, 'Teacher', 'Communication', 'peer', 2, '2026-07-25 17:33:30'),
(95, 'Teacher', 'Initiative', 'peer', 3, '2026-07-25 17:33:30'),
(96, 'Teacher', 'Dependability', 'peer', 4, '2026-07-25 17:33:30'),
(97, 'Staff', 'Teamwork', 'peer', 0, '2026-07-25 17:33:30'),
(98, 'Staff', 'Professionalism', 'peer', 1, '2026-07-25 17:33:30'),
(99, 'Staff', 'Communication', 'peer', 2, '2026-07-25 17:33:30'),
(100, 'Staff', 'Reliability', 'peer', 3, '2026-07-25 17:33:30'),
(101, 'Staff', 'Cooperation', 'peer', 4, '2026-07-25 17:33:30'),
(103, 'Teacher', 'Attendance', 'student', 2, '2026-08-20 10:35:53'),
(104, 'Teacher', 'Attendance', 'peer', 5, '2026-08-20 10:37:02'),
(105, 'Teacher', 'Cooperaton', 'student', 3, '2026-08-20 10:43:42'),
(106, 'Teacher', 'Professionalism', 'student', 4, '2026-08-20 10:45:16');

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
  `old_role` varchar(60) NOT NULL DEFAULT '',
  `new_role` varchar(60) NOT NULL DEFAULT '',
  `old_designation` varchar(120) DEFAULT NULL,
  `new_designation` varchar(120) DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_change_log`
--

INSERT INTO `role_change_log` (`id`, `user_id`, `old_role`, `new_role`, `old_designation`, `new_designation`, `changed_at`) VALUES
(1, 79, 'faculty', 'faculty', 'BSIT – II ADVISER', 'BSIT – II ADVISER/ cashier/ bookkeeper', '2026-07-13 17:36:19'),
(2, 79, 'faculty', 'faculty', 'BSIT – II ADVISER/ cashier/ bookkeeper', 'BSIT – II ADVISER/', '2026-07-13 18:28:02'),
(3, 118, 'faculty', 'faculty', 'Teacher', 'Department Head', '2026-07-26 14:57:01'),
(4, 118, 'faculty', 'faculty', 'Department Head', 'Teacher/Department Head', '2026-07-26 14:57:16'),
(5, 131, 'faculty', 'faculty', 'Teacher', 'Teacher/cashier/bookkeeper', '2026-07-31 09:00:55'),
(6, 136, 'staff', 'staff', 'Personnel', 'Personnel/ Physical Plant Coordinator/ Computer Lab Custodian', '2026-07-31 16:00:18'),
(7, 136, 'staff', 'staff', 'Staff', 'Staff/Physical Plant Coordinator/ Computer Lab Custodian', '2026-08-14 11:17:13'),
(8, 152, 'staff', 'staff', 'Staff', 'Staff/Cashier', '2026-08-17 18:00:10'),
(9, 146, 'staff', 'staff', 'Staff', 'Staff/ GUIDANCE STAFF/ SPORTS PROGRAM MANAGER/ SDRRM OFFICER', '2026-08-18 11:45:01'),
(10, 160, 'faculty', 'faculty', 'Teacher', 'Teacher/ CC   102 - Computer Programming 1** IPT   101 - Integrative Programming and Technologies 1', '2026-08-18 12:25:16'),
(11, 176, 'staff', 'staff', 'Personnel', 'Personnel/ Registrar', '2026-08-20 17:07:59'),
(12, 179, 'faculty', 'faculty', 'Teacher', 'Cashier', '2026-08-22 10:14:59');

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
('acad_term', '2nd Semester'),
('acad_year', '2026-2027'),
('auto_schedule', '0'),
('control_mode', 'open'),
('eval_end', '2026-08-07T01:00'),
('eval_start', '2026-08-07T23:00'),
('maintenance', '0'),
('notify_eval_closing', '1'),
('notify_eval_open', '1'),
('notify_faculty_complete', '1'),
('notify_reminders', '0'),
('publish_state', 'published'),
('rule_auto_lock', '1'),
('rule_countdown', '1'),
('rule_edit_after_submit', '0'),
('rule_one_submission', '1'),
('rule_only_during_period', '1'),
('rule_prevent_late', '1'),
('rule_require_all', '1');

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
(1, 144, 'Basic Education', 'Grade 7', 'St.Thomas', 125, '2026-08-07 15:31:25'),
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
(136, 'JONG', '$2y$10$CWkRS6zSTNwZpyHalrfsIOJR1uj94v7qBYyiIKXs6eDW0LrkBhnC.', 'John Kenneth M. Annecito', 'jong@gmail.com', 'usr_6a6bf4fe9dd670.57218068.jpg', 'Staff', 'Staff/Physical Plant Coordinator/ Computer Lab Custodian', 'staff', NULL, 1, '2026-07-31 09:06:06', '2026-08-14 11:17:13', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(139, 'JOSELLE', '$2y$10$xkqqXKvb/YeuzLlxn8QV3Oepaj3wc7K7yrzd0x3PcavVkPyIsvQxS', 'Joselle C. Sardina', 'joselle@gmail.com', 'sh_6a6bfe5c5c7ba8.23401888.jpg', 'Student', NULL, 'teacher', NULL, 1, '2026-07-31 09:46:04', '2026-08-11 14:37:19', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(144, 'AMELIA', '$2y$10$91M06KZVDkHgDF.HDgxyh.Rq2.o3r4s1aYcu9W8Di80jMSDZiqXzi', 'Amelia C. Candolita', 'amelia@gmail.com', 'usr_6a6c0604ed7ea9.70902212.jpg', 'Staff', NULL, 'staff', NULL, 1, '2026-07-31 10:18:45', '2026-08-06 17:31:18', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(146, 'GERALD', '$2y$10$C51/19/qnqoX0gGRth9SfendTHXedwA6pG3wERcJb0iih3b9jM/MK', 'Gerald Delos Santos', 'gerald@gmail.com', 'usr_6a6c09ae031987.29202901.jpg', 'Staff', 'Staff/ GUIDANCE STAFF/ SPORTS PROGRAM MANAGER/ SDRRM OFFICER', 'staff', NULL, 1, '2026-07-31 10:34:22', '2026-08-20 12:01:04', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(147, 'MAE', '$2y$10$0DCP31ME2z44AdkQMY6uUeXLT7gQC2UB0Ie19CEtolJxbncgl6HjS', 'Mae', 'mae@gmail.com', NULL, 'Student', NULL, 'student', 'junior_high', 0, '2026-08-01 20:33:23', '2026-08-09 08:13:37', 'JHS', NULL, 'self', 'self', 0, NULL, 'blocked', NULL, NULL, NULL, NULL, NULL),
(148, 'SHANE', '$2y$10$Uxq42pUMebCF/.8qO1AfrOX07Fynr.GkswbE76YpgGFUzLOmHBLQ2', 'Shane Mae', 'shane@gmail.com', NULL, 'Student', NULL, 'student', 'junior_high', 0, '2026-08-01 20:34:18', '2026-08-09 08:13:37', 'JHS', NULL, 'self', 'self', 0, NULL, 'blocked', NULL, NULL, NULL, NULL, NULL),
(149, 'MANUEL', '$2y$10$KkFZ.qbX44UhDrdvTK8JCOq4MOm/pTmqQr1/5M.dqcHWawaIlkvkG', 'Manuel', 'manuel@gmail.com', NULL, 'Student', NULL, 'student', 'junior_high', 1, '2026-08-01 20:37:55', '2026-08-10 18:14:31', 'JHS', 'Grade 8', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(151, 'JOHN MANUEL', '$2y$10$AZ2iOeXQ/i3HP9gouaVqDe8LeARAgKMSHrqsn0NFiFtGEsSiJLEhm', 'John Manuel', 'john@gmail.com', NULL, 'Student', NULL, 'student', 'college', 1, '2026-08-01 20:48:10', '2026-08-10 18:14:07', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(152, 'Sheramay', '$2y$10$Tm3/LgiyY8jZN3w4a2SiAuoOorLDbai.lyUY7a5Fz7tlhM/v19I02', 'Sheramay Dawn S. Pamay', 'Sheramay@gmail.com', 'usr_6a6e05fe01c7c9.51646425.jpg', 'Staff', 'Staff/Cashier', 'staff', NULL, 1, '2026-08-01 22:43:10', '2026-08-17 18:00:10', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(157, 'RENALITA', '$2y$10$Hwg1Afa273XUtZk2LUtmmOgUen.3TL.hlM1k4rV9ObyFxBPWMlpoq', 'Renalita', 'renalita@gmail.com', NULL, 'Student', NULL, 'dean', 'college', 1, '2026-08-04 17:52:56', '2026-08-04 17:53:20', 'BSIT', NULL, 'self', 'self', 0, NULL, 'approved', '040506', NULL, NULL, NULL, NULL),
(158, 'RAY', '$2y$10$w4.0sOL2y99sh.GLmm.3Que0wRUt.ECQuR/hifUHHEf0ce5os2O7q', 'ray', 'ray@gmail.com', NULL, 'Student', NULL, 'principal', 'both', 1, '2026-08-05 15:38:01', '2026-08-05 15:38:33', '', NULL, 'self', 'self', 0, NULL, 'approved', '060708', NULL, NULL, NULL, NULL),
(160, 'JENNIFER', '$2y$10$c.e3SBCP.uXkiuVdrLVp/.PW4qpWAxNNJkzgWK3Qs4sB6GhKbwGsW', 'Jennifer A. Biadora', 'jennifer@gmail.com', 'usr_6a741e3f48ce38.96566884.jpg', 'Teacher', 'Teacher/ CC   102 - Computer Programming 1** IPT   101 - Integrative Programming and Technologies 1', 'teacher', NULL, 1, '2026-08-06 13:40:15', '2026-08-23 22:11:56', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(161, 'NEIL', '$2y$10$ww/kd/xsVNEfgELFicHvpu/wxv9Vu09dnHvZgNJ1Ae6epGNU90rWO', 'Neil Alonsagay', 'neil@gmail.com', NULL, 'Student', NULL, 'student', 'college', 1, '2026-08-06 13:41:55', '2026-08-10 18:13:56', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(163, 'ELLYN', '$2y$10$3vYt2rM/dNWjtXpIqgrenOBbvXYQq5PoqppzpqaLQ.t1P11I6KSw.', 'Ellyn S. Verano', 'ellyn@gmail.com', 'usr_6a74497087d9b8.86868053.jpg', 'Teacher', NULL, 'teacher', NULL, 1, '2026-08-06 16:44:32', '2026-08-20 12:19:13', NULL, NULL, 'self', 'self', 0, '2nd Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(164, 'REYNALDO', '$2y$10$QstDY64FSyEg7QqgXmcEhuvEoDjmwpuUW7G4LHJLWF/p2jtlL4ZQC', 'Reynaldo C. Varon', 'reynaldo@gmail.com', 'usr_6a7449a9bc1589.35072434.jpg', 'Teacher', NULL, 'teacher', NULL, 1, '2026-08-06 16:45:29', '2026-08-20 12:19:02', NULL, NULL, 'self', 'self', 0, '2nd Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(165, 'GERLIE', '$2y$10$d7/QlxogWNYCDA1aow.X.ewk1EYFaDfTIjaYeQSzlWVIaVxLFc1oG', 'Gerlie A. Velasco', 'gerlie@gmail.com', NULL, 'Teacher', NULL, 'teacher', NULL, 1, '2026-08-06 18:18:04', '2026-08-20 12:19:16', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(166, 'REY', '$2y$10$HXxlbXpqMq0oSyd.6tGGaO7LgBIDSQ9D4nv9HCZv0L4pqS03i2rH6', 'rey', 'rey@gmail.com', NULL, 'Student', 'Student', 'student', 'junior_high', 1, '2026-08-07 23:49:57', '2026-08-07 23:50:27', 'JHS', 'Grade 7', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(167, 'MITCH', '$2y$10$eQrzPSnPHnEXbBZfkAJzg.CQUkFuoEsfgqn/zKUiZX8a5r1L92LYa', 'mitch', 'mitch@gmail.com', NULL, 'Student', 'Student', 'student', 'senior_high', 1, '2026-08-10 11:54:33', '2026-08-10 11:54:50', 'SHS', 'Grade 11', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(168, 'STEPHANIE', '$2y$10$e5pQeKiJCRTIcyCIdwF4w.wIpexWQs1uTFrbC.rFMjQHoQt/d2Hfu', 'Stephanie M. Puntal', 'stephanie@gmail.com', NULL, 'Teacher', 'Teacher', 'teacher', NULL, 1, '2026-08-10 17:21:41', '2026-08-20 12:19:07', NULL, NULL, 'self', 'self', 0, '2nd Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(169, 'mike', '$2y$10$g1dQUAfERbzpVtHFz.XbbeWd2UZT2aJOtPN9CDiCaEF6vrCux/P4a', 'mike', 'mike@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 1, '2026-08-10 18:17:11', '2026-08-10 18:17:22', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(170, 'JINGLE', '$2y$10$bt4LdJXqYIOEM4KDXQr0XuKQsXL1zE31DDGhQdYusxMQtu28/pD0e', 'Jingle R. Ausan', 'jingle@gmail.com', 'usr_6a7c94c5d7f8d3.77865885.jpg', 'Teacher', 'Teacher', 'teacher', NULL, 1, '2026-08-12 23:44:05', '2026-08-20 12:19:26', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(171, 'jeo', '$2y$10$.qFtE1a2ywTVnQUKGiSWAO.tHmTrkmdYEB.xi2WsmGGUvwnLdsAvm', 'jeo', 'jeo@gmail.com', NULL, 'Student', 'Student', 'student', 'senior_high', 1, '2026-08-12 23:45:19', '2026-08-12 23:45:29', 'SHS', 'Grade 11', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(172, 'johnny_e._delos_santos_6518', 'NOT NULL', 'Johnny E. Delos Santos', NULL, 'p_6a818636de0682.39560707.jpg', 'Student', 'Personnel', 'staff', NULL, 1, '2026-08-16 17:43:18', '2026-08-18 13:08:38', NULL, NULL, 'self', 'admin_nologin', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(174, 'jonaliza_jontilano_5fc8', 'NOT NULL', 'Jonaliza Jontilano', NULL, 'p_6a8413306285b6.14537980.jpg', 'Student', 'Personnel', 'staff', NULL, 1, '2026-08-18 16:09:20', '2026-08-18 16:09:20', 'dfg', NULL, 'self', 'admin_nologin', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(175, 'Dim', '$2y$10$NoPujTO4svEjolJP03d3C.c6jlJG8ShC2B3K/0f7EAwVC57CNiGai', 'Dim Mark Damaso', 'dim@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 1, '2026-08-18 16:28:09', '2026-08-18 16:28:19', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(176, 'RAFFY', '$2y$10$ag9hJeGCuU23I9tnrUlIv.fqHXQ.hl8yHW1MS9Eti4/MxF/4ohK/.', 'Raffy E. Arevalo', 'raffy@gmail.com', 'usr_6a86c3c4af1b81.39515176.jpg', 'Staff', 'Personnel/ Registrar', 'staff', NULL, 1, '2026-08-20 17:07:16', '2026-08-20 17:07:59', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(177, 'ril', '$2y$10$kkeboCRUqs4G6NpxG0j53.axkZPNl4bMtlEPFhyRCuDtsr22YZbK.', 'rila', 'ril@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 1, '2026-08-21 20:40:45', '2026-08-21 20:41:13', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(178, 'liv', '$2y$10$QdjGJoD0Cf.GEJiAHIHQvOVErZ83hNSX9D.2TzLk/pYVgkxKFDoJ.', 'olivia', 'liv@gmail.com', 'stu_6a8902d2c2d3d4.95769441.jpg', 'Student', 'Student', 'student', 'college', 1, '2026-08-22 09:42:40', '2026-08-22 10:00:50', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(179, 'test', '$2y$10$shGhZhLMT1IWM2/SF3HMq.WwZTYcSKyPP5uKIm0AYtEaV9cYsMmLO', 'testing', 'test@gmail.com', NULL, 'Teacher', 'Cashier', 'teacher', NULL, 1, '2026-08-22 10:04:19', '2026-08-22 10:14:59', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(181, 'valerie_jane_s._bendijo_8a42', 'NOT NULL', 'Valerie Jane S. Bendijo', NULL, 'p_6a8a4a6ec7c716.61866805.jpg', 'Student', 'Personnel', 'staff', NULL, 1, '2026-08-23 09:18:38', '2026-08-23 09:18:38', 'HEALTH SERVICES OFFICER SCHOOL NURSE', NULL, 'self', 'admin_nologin', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(183, 'jessie_a._aquillo_a0ed', 'NOT NULL', 'Jessie A. Aquillo', NULL, '', 'Student', 'Teacher', 'teacher', NULL, 1, '2026-08-23 09:52:08', '2026-08-23 09:52:08', 'Campus Ministry Officer  Formation Services Coordinator', NULL, 'self', 'admin_nologin', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL);

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
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(69, 136, 'Staff', 'student', 'Professionalism', 'Arrives at school on time', 1, '2026-08-01 10:39:19', '2026-08-15 04:35:11'),
(70, 136, 'Staff', 'student', 'Professionalism', 'Demonstrates punctuality, excellent attendance', 2, '2026-08-01 10:39:27', '2026-08-15 04:35:11'),
(71, 136, 'Staff', 'student', 'Professionalism', 'Observes proper entrance and exit.', 3, '2026-08-01 10:39:48', '2026-08-15 04:35:11'),
(73, 144, 'Staff', 'student', 'General', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 1, '2026-08-06 05:25:56', '2026-08-15 04:35:11'),
(74, 152, 'Staff', 'student', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 1, '2026-08-11 03:53:23', '2026-08-15 04:35:11'),
(75, 144, 'Staff', 'student', 'Cooperaton', 'Demonstrates punctuality, excellent attendance *', 2, '2026-08-12 07:48:24', '2026-08-15 04:35:11'),
(79, 158, 'Principal', 'school_head', 'General', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 2, '2026-08-16 05:06:49', '2026-08-16 05:06:49'),
(80, 157, 'Dean', 'school_head', 'Cooperaton', 'Demonstrates punctuality, excellent attendance *', 3, '2026-08-16 05:28:39', '2026-08-16 05:28:39'),
(82, 157, 'Dean', 'school_head', 'Cooperaton', 'Demonstrates punctuality, excellent attendance *', 4, '2026-08-16 08:30:46', '2026-08-16 08:30:46'),
(83, 157, 'Dean', 'school_head', 'Cooperaton', 'Implements diocesan and DepEd policies and directives.', 5, '2026-08-16 08:31:09', '2026-08-16 08:31:09'),
(87, 172, 'Staff', 'student', 'Professionalism', 'Demonstrates punctuality, excellent attendance *', 1, '2026-08-19 05:42:07', '2026-08-19 05:42:07'),
(88, 172, 'Staff', 'student', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *', 2, '2026-08-19 05:42:14', '2026-08-19 05:42:14'),
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
(196, 146, 'Staff', 'student', 'Professionalism', 'integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day', 12, '2026-08-24 02:05:44', '2026-08-24 02:05:44'),
(197, 146, 'Staff', 'student', 'Professionalism', 'Demonstrates punctuality, excellent attendance *', 13, '2026-08-24 02:05:56', '2026-08-24 02:05:56'),
(198, 146, 'Staff', 'student', 'Cooperaton', 'Clearly explains lessons and course-related concepts.', 14, '2026-08-24 02:06:11', '2026-08-24 02:06:11');

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
(45, 146, 'Staff', 'student', 'Cooperaton', 4);

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
(99, 136, 'Grade 11'),
(98, 136, 'Grade 7'),
(77, 139, 'Grade 12'),
(97, 144, '4th Year College'),
(112, 146, 'Grade 11'),
(113, 152, '1st Year College'),
(114, 152, '4th Year College'),
(102, 160, '4th Year College'),
(101, 160, 'Grade 11'),
(100, 160, 'Grade 7'),
(79, 163, '4th Year College'),
(78, 163, 'Grade 11'),
(58, 164, '1st Year College'),
(59, 164, '2nd Year College'),
(60, 164, '3rd Year College'),
(61, 164, '4th Year College'),
(62, 165, '1st Year College'),
(63, 165, '2nd Year College'),
(64, 165, '3rd Year College'),
(65, 165, '4th Year College'),
(105, 168, '4th Year College'),
(104, 168, 'Grade 11'),
(103, 168, 'Grade 7'),
(81, 170, '4th Year College'),
(80, 170, 'Grade 11');

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
  ADD UNIQUE KEY `unique_cat` (`target_type`,`category_name`,`eval_type`);

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
  ADD KEY `idx_user` (`user_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `analytics_archive`
--
ALTER TABLE `analytics_archive`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `analytics_reports`
--
ALTER TABLE `analytics_reports`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluation_periods`
--
ALTER TABLE `evaluation_periods`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `evaluation_questions`
--
ALTER TABLE `evaluation_questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

--
-- AUTO_INCREMENT for table `evaluation_reminders`
--
ALTER TABLE `evaluation_reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=307;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `rating_certifications`
--
ALTER TABLE `rating_certifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_change_log`
--
ALTER TABLE `role_change_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT for table `user_management_log`
--
ALTER TABLE `user_management_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_questions`
--
ALTER TABLE `user_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=199;

--
-- AUTO_INCREMENT for table `user_question_categories`
--
ALTER TABLE `user_question_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `user_year_levels`
--
ALTER TABLE `user_year_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `analytics_reports`
--
ALTER TABLE `analytics_reports`
  ADD CONSTRAINT `fk_ar_generator` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
