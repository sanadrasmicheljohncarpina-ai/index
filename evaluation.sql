-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 10, 2026 at 06:27 AM
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
(60, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #5 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-09-28 22:25:56'),
(61, 'Lorraine R. Sabay', 'admin', 'Deleted evaluation archive #4 for 2026-2027 (2026-2027 — 1st Semester)', 'fa-trash', '#D6455D', '2026-09-30 20:50:27'),
(62, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #7)', 'fa-box-archive', '#2563EB', '2026-10-01 11:54:42'),
(63, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #5)', 'fa-box-archive', '#2563EB', '2026-10-01 11:54:42'),
(64, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #2)', 'fa-box-archive', '#2563EB', '2026-10-01 11:54:42'),
(65, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #6)', 'fa-box-archive', '#2563EB', '2026-10-01 11:54:42'),
(66, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #7 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-01 11:55:15'),
(67, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #6 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-01 11:55:30'),
(68, 'Lorraine R. Sabay', 'admin', 'Deleted evaluation archive #2 for 2026-2027 (2026-2027 — Summer)', 'fa-trash', '#D6455D', '2026-10-01 11:55:33'),
(69, 'Lorraine R. Sabay', 'admin', 'Deleted evaluation archive #5 for 2026-2027 (2026-2027 — 1st Semester)', 'fa-trash', '#D6455D', '2026-10-01 11:57:00'),
(70, 'Lorraine R. Sabay', 'admin', 'Deleted evaluation archive #6 for 2026-2027 (2026-2027 — School Year)', 'fa-trash', '#D6455D', '2026-10-01 11:58:13'),
(71, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #7)', 'fa-box-archive', '#2563EB', '2026-10-01 12:29:54'),
(72, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #8)', 'fa-box-archive', '#2563EB', '2026-10-01 12:29:54'),
(73, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #8 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-01 13:03:46'),
(74, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #7 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-01 13:03:46'),
(75, 'Lorraine R. Sabay', 'admin', 'Deleted evaluation archive #8 for 2026-2027 (2026-2027 — School Year)', 'fa-trash', '#D6455D', '2026-10-04 16:46:51'),
(76, 'Lorraine R. Sabay', 'admin', 'Restored deleted evaluation archive for 2026-2027 (2026-2027 — School Year) as Archive #8', 'fa-rotate-left', '#2563EB', '2026-10-04 16:46:57'),
(77, 'Lorraine R. Sabay', 'admin', 'Deleted evaluation archive #8 for 2026-2027 (2026-2027 — School Year)', 'fa-trash', '#D6455D', '2026-10-04 16:47:01'),
(78, 'Lorraine R. Sabay', 'admin', 'Restored deleted evaluation archive for 2026-2027 (2026-2027 — School Year) as Archive #8', 'fa-rotate-left', '#2563EB', '2026-10-04 16:47:06'),
(79, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #7)', 'fa-box-archive', '#2563EB', '2026-10-07 14:49:18'),
(80, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #9)', 'fa-box-archive', '#2563EB', '2026-10-07 14:49:18'),
(81, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #8)', 'fa-box-archive', '#2563EB', '2026-10-07 14:49:18'),
(82, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #9 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 14:49:24'),
(83, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #8 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 14:49:24'),
(84, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #7 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 14:49:25'),
(85, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #7)', 'fa-box-archive', '#2563EB', '2026-10-07 15:30:15'),
(86, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #9)', 'fa-box-archive', '#2563EB', '2026-10-07 15:30:15'),
(87, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #8)', 'fa-box-archive', '#2563EB', '2026-10-07 15:30:15'),
(88, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #9 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 15:30:33'),
(89, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #8 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 15:30:34'),
(90, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #7 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 15:30:34'),
(91, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #7)', 'fa-box-archive', '#2563EB', '2026-10-07 15:33:57'),
(92, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #9)', 'fa-box-archive', '#2563EB', '2026-10-07 15:33:57'),
(93, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #8)', 'fa-box-archive', '#2563EB', '2026-10-07 15:33:57'),
(94, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #9 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 15:36:39'),
(95, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #8 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 15:36:39'),
(96, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #7 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 15:36:39'),
(97, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #7)', 'fa-box-archive', '#2563EB', '2026-10-07 15:38:42'),
(98, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #9)', 'fa-box-archive', '#2563EB', '2026-10-07 15:38:42'),
(99, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #8)', 'fa-box-archive', '#2563EB', '2026-10-07 15:38:42'),
(100, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #9 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 15:41:44'),
(101, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #8 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 15:41:44'),
(102, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #7 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 15:41:44'),
(103, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #7)', 'fa-box-archive', '#2563EB', '2026-10-07 15:50:17'),
(104, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #9)', 'fa-box-archive', '#2563EB', '2026-10-07 15:50:17'),
(105, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #8)', 'fa-box-archive', '#2563EB', '2026-10-07 15:50:17'),
(106, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #9 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 15:54:48'),
(107, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #8 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 15:54:48'),
(108, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #7 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 15:54:48'),
(109, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #7)', 'fa-box-archive', '#2563EB', '2026-10-07 15:54:54'),
(110, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #8)', 'fa-box-archive', '#2563EB', '2026-10-07 15:54:57'),
(111, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #8 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 15:58:36'),
(112, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #7 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 15:58:38'),
(113, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #7)', 'fa-box-archive', '#2563EB', '2026-10-07 16:00:18'),
(114, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #9)', 'fa-box-archive', '#2563EB', '2026-10-07 16:00:18'),
(115, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #8)', 'fa-box-archive', '#2563EB', '2026-10-07 16:00:18'),
(116, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #9 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 16:03:42'),
(117, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #8 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 16:03:42'),
(118, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #7 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 16:03:42'),
(119, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #7)', 'fa-box-archive', '#2563EB', '2026-10-07 16:03:52'),
(120, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #9)', 'fa-box-archive', '#2563EB', '2026-10-07 16:03:52'),
(121, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #8)', 'fa-box-archive', '#2563EB', '2026-10-07 16:03:52'),
(122, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #9 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 16:05:51'),
(123, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #8 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 16:05:51'),
(124, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #7 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 16:05:52'),
(125, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #7)', 'fa-box-archive', '#2563EB', '2026-10-07 16:15:22'),
(126, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #9)', 'fa-box-archive', '#2563EB', '2026-10-07 16:15:22'),
(127, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #8)', 'fa-box-archive', '#2563EB', '2026-10-07 16:15:22'),
(128, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #9 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 16:22:04'),
(129, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #8 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 16:22:04'),
(130, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #7 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 16:22:04'),
(131, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #7)', 'fa-box-archive', '#2563EB', '2026-10-07 16:23:45'),
(132, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #9)', 'fa-box-archive', '#2563EB', '2026-10-07 16:23:45'),
(133, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #8)', 'fa-box-archive', '#2563EB', '2026-10-07 16:23:45'),
(134, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #9 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 16:26:40'),
(135, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #7 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 16:26:40'),
(136, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #7)', 'fa-box-archive', '#2563EB', '2026-10-07 16:48:13'),
(137, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #9)', 'fa-box-archive', '#2563EB', '2026-10-07 16:48:13'),
(138, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #9 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 16:59:34'),
(139, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #7 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-07 16:59:35'),
(140, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #7)', 'fa-box-archive', '#2563EB', '2026-10-09 15:18:57'),
(141, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #9)', 'fa-box-archive', '#2563EB', '2026-10-09 15:18:57'),
(142, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #9 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-09 16:18:23'),
(143, 'Lorraine R. Sabay', 'admin', 'Restored evaluation archive #7 for 2026-2027', 'fa-box-open', '#0F9F6E', '2026-10-09 16:18:23'),
(144, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #7)', 'fa-box-archive', '#2563EB', '2026-10-09 21:41:23'),
(145, 'Lorraine R. Sabay', 'admin', 'Archived evaluation data for 2026-2027 (Archive #9)', 'fa-box-archive', '#2563EB', '2026-10-09 21:41:23');

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
(72, 'login', 'jeo', '::1', 1, '2026-09-29 09:49:31'),
(73, 'login', 'dim', '::1', 1, '2026-10-01 13:44:29'),
(75, 'login', 'dim', '::1', 1, '2026-10-02 09:03:46'),
(76, 'reset', 'taniel', '127.0.0.1', 1, '2026-10-04 14:09:18'),
(77, 'reset', 'taniel', '127.0.0.1', 1, '2026-10-04 14:09:52'),
(78, 'login', 'taniel', '127.0.0.1', 1, '2026-10-04 14:10:24'),
(80, 'login', 'taniel', '127.0.0.1', 1, '2026-10-04 14:23:40'),
(81, 'login', 'dim', '127.0.0.1', 1, '2026-10-04 14:46:24'),
(82, 'login', 'dim', '127.0.0.1', 1, '2026-10-04 14:49:05'),
(83, 'login', 'dim', '127.0.0.1', 1, '2026-10-04 14:57:40'),
(85, 'login', 'jeo', '127.0.0.1', 1, '2026-10-04 15:26:43'),
(86, 'login', 'dim', '127.0.0.1', 1, '2026-10-04 15:31:16'),
(87, 'login', 'dim', '10.10.149.10', 1, '2026-10-06 13:58:31'),
(88, 'login', 'dim', '10.10.149.10', 1, '2026-10-06 14:18:44'),
(89, 'login', 'dim', '10.10.149.10', 1, '2026-10-06 14:24:57'),
(91, 'login', 'dim', '127.0.0.1', 1, '2026-10-06 16:06:42'),
(92, 'sqchange', 'uid:175', '127.0.0.1', 0, '2026-10-06 16:07:21'),
(93, 'login', 'jeo', '127.0.0.1', 1, '2026-10-06 16:07:34'),
(94, 'login', 'taniel', '127.0.0.1', 1, '2026-10-06 21:46:02'),
(96, 'login', 'dim', '127.0.0.1', 1, '2026-10-07 14:42:04'),
(97, 'login', 'dim', '127.0.0.1', 1, '2026-10-07 15:29:36'),
(99, 'login', 'jeo', '127.0.0.1', 1, '2026-10-07 16:25:02'),
(101, 'login', 'josesantos', '127.0.0.1', 1, '2026-10-07 16:49:58'),
(102, 'login', 'jeo', '127.0.0.1', 1, '2026-10-07 16:55:42'),
(103, 'login', 'jeo', '127.0.0.1', 1, '2026-10-09 13:15:29'),
(104, 'login', 'taniel', '127.0.0.1', 1, '2026-10-09 13:35:50'),
(105, 'login', 'taniel', '127.0.0.1', 1, '2026-10-09 14:14:06'),
(106, 'login', 'dim', '127.0.0.1', 1, '2026-10-09 14:28:29'),
(107, 'login', 'dim', '127.0.0.1', 1, '2026-10-09 21:05:49'),
(108, 'login', 'ashley', '127.0.0.1', 1, '2026-10-09 21:48:10'),
(110, 'login', 'jeo', '127.0.0.1', 1, '2026-10-09 21:51:09'),
(111, 'login', 'dim', '127.0.0.1', 1, '2026-10-09 22:33:24'),
(112, 'login', 'dim', '127.0.0.1', 1, '2026-10-09 23:50:44'),
(113, 'login', 'ashley', '127.0.0.1', 1, '2026-10-10 01:41:12'),
(116, 'reset', 'manuel', '127.0.0.1', 1, '2026-10-10 01:46:21'),
(117, 'login', 'manuel', '127.0.0.1', 1, '2026-10-10 01:46:35'),
(118, 'login', 'mikey', '127.0.0.1', 1, '2026-10-10 10:17:05');

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
(2, '2026-2027 — School Year', '2026-2027', 'School Year', '2026-10-07', '2026-10-08', 1, '2026-08-05 18:15:42', 0),
(3, '2026-2027 — 1st Semester', '2026-2027', '1st Semester', '2026-09-19', '2026-08-26', 0, '2026-08-05 18:16:56', 0),
(4, '2025-2-26 — School Year', '2025-2-26', 'School Year', '2026-08-06', '2026-08-06', 0, '2026-08-06 11:03:33', 0),
(5, '2026-2027 — Summer', '2026-2027', 'Summer', '2026-08-26', '2026-08-26', 0, '2026-08-06 13:21:02', 0),
(6, '2026-2027 — 1st Semester', '2026-2027', '1st Semester', '2026-10-07', '2026-10-08', 0, '2026-08-21 12:39:24', 0),
(7, '2026-2027 — 2nd Semester', '2026-2027', '2nd Semester', '2026-10-04', '2026-10-05', 0, '2026-08-24 09:52:56', 0),
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
(272, 'Faculty', 'Professionalism & Student Support', 'Demonstrates responsibility in performing teaching and academic duties.', 'general', '2026-09-23 12:30:12', 1, '2026-09-23 04:30:12', 'shared'),
(273, 'Faculty', 'General', 'fdbgd', 'general', '2026-10-04 14:50:56', 0, '2026-10-04 06:50:56', 'shared'),
(274, 'Faculty', 'Teaching & Learning', 'hgjhgcjgh', 'general', '2026-10-04 14:51:09', 0, '2026-10-04 06:51:09', 'shared');

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
(271, 4384),
(271, 4389),
(272, 4389),
(273, 4384),
(274, 4387);

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
(183, NULL, 171, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'N/A', 'student', NULL, 'submitted', '2026-10-07 16:25:22', '2026-10-07 16:25:22', NULL, NULL, 'school_head'),
(184, NULL, 219, 158, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'Good Job!', 'student', NULL, 'submitted', '2026-10-07 16:50:17', '2026-10-07 16:50:17', NULL, NULL, 'school_head'),
(185, NULL, 243, 217, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'Well Done!', 'student', NULL, 'submitted', '2026-10-09 21:48:40', '2026-10-09 21:48:40', NULL, NULL, 'teacher'),
(186, NULL, 171, 217, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'Good job!', 'student', NULL, 'submitted', '2026-10-09 21:51:38', '2026-10-09 21:51:38', NULL, NULL, 'teacher'),
(187, NULL, 171, 227, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'Well done!', 'student', NULL, 'submitted', '2026-10-09 21:52:24', '2026-10-09 21:52:24', NULL, NULL, 'teacher'),
(188, NULL, 171, 233, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'Good job po!', 'student', NULL, 'submitted', '2026-10-09 21:52:46', '2026-10-09 21:52:46', NULL, NULL, 'staff'),
(189, NULL, 222, 232, 'Faculty', NULL, '', 3, NULL, NULL, 2, 4.50, 'Good job!', 'faculty_peer', 'Staff', 'submitted', '2026-10-09 21:56:06', '2026-10-09 21:56:06', NULL, NULL, 'teacher'),
(190, NULL, 233, 236, 'Faculty', NULL, '', 7, NULL, NULL, 2, 4.60, 'Good job!', 'staff', 'Staff Evaluation', 'submitted', '2026-10-09 21:56:55', '2026-10-09 21:56:55', NULL, NULL, 'teacher'),
(191, NULL, 222, 233, 'Faculty', NULL, '', 3, NULL, NULL, 2, 4.60, 'Good job!', 'faculty_peer', 'Staff', 'submitted', '2026-10-09 22:38:00', '2026-10-09 22:38:00', NULL, NULL, 'teacher'),
(192, NULL, 222, 225, 'Faculty', NULL, '', 3, NULL, NULL, 2, 4.65, 'Good job!', 'faculty_peer', 'Faculty', 'submitted', '2026-10-09 23:33:18', '2026-10-09 23:33:18', NULL, NULL, 'teacher'),
(193, NULL, 157, 225, 'Faculty', 'college', 'school_head_dean_faculty', NULL, NULL, NULL, 6, 4.65, 'Good job!', 'school_head', NULL, 'submitted', '2026-10-09 23:55:10', '2026-10-09 23:55:10', NULL, NULL, 'teacher'),
(194, NULL, 175, 225, 'Faculty', NULL, '', NULL, NULL, NULL, 6, NULL, 'Well done!', 'student', NULL, 'submitted', '2026-10-09 23:57:41', '2026-10-09 23:57:41', NULL, NULL, 'teacher'),
(195, NULL, 175, 216, 'Faculty', NULL, '', NULL, NULL, NULL, 6, NULL, 'Good job!', 'student', NULL, 'submitted', '2026-10-09 23:58:22', '2026-10-09 23:58:22', NULL, NULL, 'teacher'),
(196, NULL, 222, 225, 'Faculty', NULL, '', 3, NULL, NULL, 6, 4.59, 'Good job!', 'faculty_peer', 'Faculty', 'submitted', '2026-10-10 00:01:10', '2026-10-10 00:01:10', NULL, NULL, 'teacher'),
(197, NULL, 230, 236, 'Faculty', NULL, '', 7, NULL, NULL, 6, 4.70, 'Good job!', 'staff', 'Staff Evaluation', 'submitted', '2026-10-10 00:22:06', '2026-10-10 00:22:06', NULL, NULL, 'teacher'),
(198, NULL, 233, 236, 'Faculty', NULL, '', 7, NULL, NULL, 6, 4.70, 'KEEP UP THE GOOD WORK!', 'staff', 'Staff Evaluation', 'submitted', '2026-10-10 00:26:15', '2026-10-10 00:26:15', NULL, NULL, 'teacher'),
(199, NULL, 232, 236, 'Faculty', NULL, '', 7, NULL, NULL, 6, 4.70, 'Well done!', 'staff', 'Staff Evaluation', 'submitted', '2026-10-10 00:28:41', '2026-10-10 00:28:41', NULL, NULL, 'teacher'),
(200, NULL, 222, 220, 'Faculty', NULL, '', 3, NULL, NULL, 6, 4.65, 'Good job!', 'faculty_peer', 'Faculty', 'submitted', '2026-10-10 01:02:54', '2026-10-10 01:02:54', NULL, NULL, 'teacher'),
(201, NULL, 222, 227, 'Faculty', NULL, '', 3, NULL, NULL, 6, 4.59, 'Well done!', 'faculty_peer', 'Faculty', 'submitted', '2026-10-10 01:08:59', '2026-10-10 01:08:59', NULL, NULL, 'teacher'),
(202, NULL, 222, 234, 'Faculty', NULL, '', 3, NULL, NULL, 6, 4.59, 'Good job!', 'faculty_peer', 'Faculty', 'submitted', '2026-10-10 01:10:01', '2026-10-10 01:10:01', NULL, NULL, 'teacher'),
(203, NULL, 216, 220, 'Faculty', NULL, '', 3, NULL, NULL, 6, 4.59, 'GOOD JOB!', 'faculty_peer', 'Faculty', 'submitted', '2026-10-10 01:12:19', '2026-10-10 01:12:19', NULL, NULL, 'teacher'),
(204, NULL, 216, 227, 'Faculty', NULL, '', 3, NULL, NULL, 6, 4.53, 'Well done!', 'faculty_peer', 'Faculty', 'submitted', '2026-10-10 01:13:17', '2026-10-10 01:13:17', NULL, NULL, 'teacher'),
(205, NULL, 243, 227, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'Good job!', 'student', NULL, 'submitted', '2026-10-10 01:41:52', '2026-10-10 01:41:52', NULL, NULL, 'teacher'),
(206, NULL, 244, 227, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'Good job!', 'student', NULL, 'submitted', '2026-10-10 01:47:07', '2026-10-10 01:47:07', NULL, NULL, 'teacher'),
(207, NULL, 232, 236, 'Faculty', NULL, '', 7, NULL, NULL, 2, 4.50, 'Good job!', 'staff', 'Staff Evaluation', 'submitted', '2026-10-10 02:03:49', '2026-10-10 02:03:49', NULL, NULL, 'teacher'),
(208, NULL, 245, 227, 'Faculty', NULL, '', NULL, NULL, NULL, 2, NULL, 'Good job!', 'student', NULL, 'submitted', '2026-10-10 10:22:57', '2026-10-10 10:22:57', NULL, NULL, 'teacher'),
(209, NULL, 226, 236, 'Faculty', NULL, '', 7, NULL, NULL, 2, 4.20, 'Good job!', 'staff', 'Staff Evaluation', 'submitted', '2026-10-10 10:25:40', '2026-10-10 10:25:40', NULL, NULL, 'teacher');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_received_keep`
--

CREATE TABLE `feedback_received_keep` (
  `id` int(10) UNSIGNED NOT NULL,
  `archive_id` int(10) UNSIGNED NOT NULL,
  `tracker_id` int(10) UNSIGNED NOT NULL,
  `target_user_id` int(10) UNSIGNED NOT NULL,
  `period_id` int(10) UNSIGNED NOT NULL,
  `school_year` varchar(30) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `period_label` varchar(150) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `score_sum` double NOT NULL DEFAULT 0,
  `score_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `answers_json` longtext DEFAULT NULL,
  `kept_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedback_received_keep`
--

INSERT INTO `feedback_received_keep` (`id`, `archive_id`, `tracker_id`, `target_user_id`, `period_id`, `school_year`, `semester`, `period_label`, `remarks`, `submitted_at`, `score_sum`, `score_count`, `answers_json`, `kept_at`) VALUES
(13, 8, 141, 158, 2, '2026-2027', 'School Year', '2026-2027 — School Year', 'N/A', '2026-09-22 11:00:39', 0, 0, '[]', '2026-10-07 16:23:45'),
(14, 8, 151, 158, 2, '2026-2027', 'School Year', '2026-2027 — School Year', 'N/A', '2026-09-23 10:29:32', 0, 0, '[]', '2026-10-07 16:23:45'),
(21, 7, 147, 157, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'N/A', '2026-09-22 11:20:04', 0, 0, '[]', '2026-10-09 21:41:23'),
(22, 7, 163, 157, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'N/A', '2026-09-30 21:43:44', 43, 10, '[{\"category\":\"Academic Leadership\",\"question_text\":\"Provides clear direction for the college academic programs.\",\"score\":5},{\"category\":\"Academic Leadership\",\"question_text\":\"Monitors the implementation of college-level curricula and academic requirements.\",\"score\":4},{\"category\":\"Academic Leadership\",\"question_text\":\"Coordinates effectively with college faculty regarding academic matters.\",\"score\":3},{\"category\":\"Academic Leadership\",\"question_text\":\"Addresses academic concerns of college students appropriately.\",\"score\":5},{\"category\":\"Academic Leadership\",\"question_text\":\"Supports the continuous improvement of college teaching and learning.\",\"score\":4},{\"category\":\"Faculty & College Management\",\"question_text\":\"Monitors faculty compliance with college academic responsibilities.\",\"score\":5},{\"category\":\"Faculty & College Management\",\"question_text\":\"Communicates college policies and academic requirements clearly.\",\"score\":4},{\"category\":\"Faculty & College Management\",\"question_text\":\"Coordinates college schedules, activities, and academic programs effectively.\",\"score\":5},{\"category\":\"Faculty & College Management\",\"question_text\":\"Promotes a professional and supportive environment for college students and faculty.\",\"score\":4},{\"category\":\"Faculty & College Management\",\"question_text\":\"Demonstrates accountability in managing college academic operations.\",\"score\":4}]', '2026-10-09 21:41:23'),
(23, 9, 176, 157, 7, '2026-2027', '2nd Semester', '2026-2027 — 2nd Semester', 'N/A', '2026-10-04 15:10:07', 47, 10, '[{\"category\":\"Academic Leadership\",\"question_text\":\"Provides clear direction for the college academic programs.\",\"score\":5},{\"category\":\"Academic Leadership\",\"question_text\":\"Monitors the implementation of college-level curricula and academic requirements.\",\"score\":4},{\"category\":\"Academic Leadership\",\"question_text\":\"Coordinates effectively with college faculty regarding academic matters.\",\"score\":5},{\"category\":\"Academic Leadership\",\"question_text\":\"Addresses academic concerns of college students appropriately.\",\"score\":5},{\"category\":\"Academic Leadership\",\"question_text\":\"Supports the continuous improvement of college teaching and learning.\",\"score\":5},{\"category\":\"Faculty & College Management\",\"question_text\":\"Monitors faculty compliance with college academic responsibilities.\",\"score\":4},{\"category\":\"Faculty & College Management\",\"question_text\":\"Communicates college policies and academic requirements clearly.\",\"score\":5},{\"category\":\"Faculty & College Management\",\"question_text\":\"Coordinates college schedules, activities, and academic programs effectively.\",\"score\":4},{\"category\":\"Faculty & College Management\",\"question_text\":\"Promotes a professional and supportive environment for college students and faculty.\",\"score\":5},{\"category\":\"Faculty & College Management\",\"question_text\":\"Demonstrates accountability in managing college academic operations.\",\"score\":5}]', '2026-10-09 21:41:23');

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
(26, 'evaluation_received', 165, 'You have received a new peer evaluation.', NULL, 0, '2026-08-14 18:52:31'),
(27, 'evaluation_received', 163, 'You have received a new peer evaluation.', NULL, 0, '2026-08-14 20:20:58'),
(33, 'evaluation_received', 160, 'You have received a new peer evaluation.', NULL, 0, '2026-08-24 14:22:02'),
(34, 'evaluation_received', 183, 'You have received a new evaluation.', NULL, 0, '2026-08-26 11:42:05'),
(35, 'evaluation_received', 136, 'You have received a new evaluation.', NULL, 0, '2026-08-26 12:08:51'),
(37, 'evaluation_received', 139, 'You have received a new evaluation.', NULL, 0, '2026-08-27 16:02:21'),
(41, 'evaluation_received', 172, 'You have received a new evaluation.', NULL, 0, '2026-09-13 18:52:36'),
(42, 'evaluation_received', 139, 'You have received a new evaluation.', NULL, 0, '2026-09-13 18:54:47'),
(43, 'evaluation_received', 158, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"Principal\",\"target_label\":\"Principal\"}', 0, '2026-09-13 22:05:54'),
(45, 'evaluation_received', 172, 'You have received a new evaluation.', NULL, 0, '2026-09-14 08:37:55'),
(46, 'evaluation_received', 157, 'You have received a new Dean / Principal evaluation.', NULL, 0, '2026-09-14 08:40:12'),
(47, 'evaluation_received', 158, 'You have received a new Dean / Principal evaluation.', NULL, 0, '2026-09-14 08:40:32'),
(48, 'evaluation_received', 157, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"Dean\",\"target_label\":\"Dean\"}', 0, '2026-09-15 14:30:22'),
(49, 'evaluation_received', 157, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"Dean\",\"target_label\":\"Dean\"}', 0, '2026-09-15 16:11:34'),
(50, 'evaluation_received', 236, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"EA\",\"target_label\":\"Executive Assistant\"}', 0, '2026-09-15 16:22:04'),
(54, 'evaluation_received', 194, 'You have received a new peer evaluation.', NULL, 0, '2026-09-21 22:34:07'),
(63, 'evaluation_received', 236, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"EA\",\"target_label\":\"Executive Assistant\"}', 0, '2026-09-22 11:09:59'),
(65, 'evaluation_received', 216, 'You have received a new evaluation.', NULL, 0, '2026-09-22 11:14:00'),
(67, 'evaluation_received', 217, 'You have received a new evaluation.', NULL, 0, '2026-09-22 11:58:19'),
(68, 'evaluation_received', 158, 'You have received a new Principal evaluation.', NULL, 0, '2026-09-23 10:29:32'),
(79, 'evaluation_received', 216, 'You have received a new evaluation.', NULL, 0, '2026-09-23 14:35:12'),
(80, 'evaluation_received', 216, 'You have received a new evaluation.', NULL, 0, '2026-09-23 14:38:24'),
(81, 'evaluation_received', 236, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"EA\",\"target_label\":\"Executive Assistant\"}', 0, '2026-09-28 15:58:31'),
(82, 'evaluation_received', 236, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"EA\",\"target_label\":\"Executive Assistant\"}', 0, '2026-09-28 16:00:48'),
(83, 'teaching_assignment', 234, 'The Executive Assistant assigned you to teach: Grade 12.', NULL, 0, '2026-10-01 20:25:05'),
(84, 'evaluation_schedule', 215, 'The Executive Assistant set the evaluation schedule: Oct 1, 2026 9:12 PM – Oct 2, 2026 11:00 PM.', NULL, 0, '2026-10-01 20:25:22'),
(85, 'evaluation_schedule', 216, 'The Executive Assistant set the evaluation schedule: Oct 1, 2026 9:12 PM – Oct 2, 2026 11:00 PM.', NULL, 0, '2026-10-01 20:25:22'),
(86, 'evaluation_schedule', 217, 'The Executive Assistant set the evaluation schedule: Oct 1, 2026 9:12 PM – Oct 2, 2026 11:00 PM.', NULL, 0, '2026-10-01 20:25:22'),
(87, 'evaluation_schedule', 218, 'The Executive Assistant set the evaluation schedule: Oct 1, 2026 9:12 PM – Oct 2, 2026 11:00 PM.', NULL, 0, '2026-10-01 20:25:22'),
(88, 'evaluation_schedule', 220, 'The Executive Assistant set the evaluation schedule: Oct 1, 2026 9:12 PM – Oct 2, 2026 11:00 PM.', NULL, 0, '2026-10-01 20:25:22'),
(89, 'evaluation_schedule', 222, 'The Executive Assistant set the evaluation schedule: Oct 1, 2026 9:12 PM – Oct 2, 2026 11:00 PM.', NULL, 1, '2026-10-01 20:25:22'),
(90, 'evaluation_schedule', 225, 'The Executive Assistant set the evaluation schedule: Oct 1, 2026 9:12 PM – Oct 2, 2026 11:00 PM.', NULL, 0, '2026-10-01 20:25:22'),
(91, 'evaluation_schedule', 226, 'The Executive Assistant set the evaluation schedule: Oct 1, 2026 9:12 PM – Oct 2, 2026 11:00 PM.', NULL, 0, '2026-10-01 20:25:22'),
(92, 'evaluation_schedule', 227, 'The Executive Assistant set the evaluation schedule: Oct 1, 2026 9:12 PM – Oct 2, 2026 11:00 PM.', NULL, 0, '2026-10-01 20:25:22'),
(93, 'evaluation_schedule', 230, 'The Executive Assistant set the evaluation schedule: Oct 1, 2026 9:12 PM – Oct 2, 2026 11:00 PM.', NULL, 0, '2026-10-01 20:25:22'),
(94, 'evaluation_schedule', 232, 'The Executive Assistant set the evaluation schedule: Oct 1, 2026 9:12 PM – Oct 2, 2026 11:00 PM.', NULL, 0, '2026-10-01 20:25:22'),
(95, 'evaluation_schedule', 233, 'The Executive Assistant set the evaluation schedule: Oct 1, 2026 9:12 PM – Oct 2, 2026 11:00 PM.', NULL, 0, '2026-10-01 20:25:22'),
(96, 'evaluation_schedule', 234, 'The Executive Assistant set the evaluation schedule: Oct 1, 2026 9:12 PM – Oct 2, 2026 11:00 PM.', NULL, 0, '2026-10-01 20:25:22'),
(97, 'evaluation_schedule', 215, 'The Executive Assistant set the evaluation schedule: Oct 4, 2026 10:51 AM – Oct 5, 2026 11:00 PM.', NULL, 0, '2026-10-04 10:49:54'),
(98, 'evaluation_schedule', 216, 'The Executive Assistant set the evaluation schedule: Oct 4, 2026 10:51 AM – Oct 5, 2026 11:00 PM.', NULL, 0, '2026-10-04 10:49:54'),
(99, 'evaluation_schedule', 217, 'The Executive Assistant set the evaluation schedule: Oct 4, 2026 10:51 AM – Oct 5, 2026 11:00 PM.', NULL, 0, '2026-10-04 10:49:54'),
(100, 'evaluation_schedule', 218, 'The Executive Assistant set the evaluation schedule: Oct 4, 2026 10:51 AM – Oct 5, 2026 11:00 PM.', NULL, 0, '2026-10-04 10:49:54'),
(101, 'evaluation_schedule', 220, 'The Executive Assistant set the evaluation schedule: Oct 4, 2026 10:51 AM – Oct 5, 2026 11:00 PM.', NULL, 0, '2026-10-04 10:49:54'),
(102, 'evaluation_schedule', 222, 'The Executive Assistant set the evaluation schedule: Oct 4, 2026 10:51 AM – Oct 5, 2026 11:00 PM.', NULL, 1, '2026-10-04 10:49:54'),
(103, 'evaluation_schedule', 225, 'The Executive Assistant set the evaluation schedule: Oct 4, 2026 10:51 AM – Oct 5, 2026 11:00 PM.', NULL, 0, '2026-10-04 10:49:54'),
(104, 'evaluation_schedule', 226, 'The Executive Assistant set the evaluation schedule: Oct 4, 2026 10:51 AM – Oct 5, 2026 11:00 PM.', NULL, 0, '2026-10-04 10:49:54'),
(105, 'evaluation_schedule', 227, 'The Executive Assistant set the evaluation schedule: Oct 4, 2026 10:51 AM – Oct 5, 2026 11:00 PM.', NULL, 0, '2026-10-04 10:49:54'),
(106, 'evaluation_schedule', 230, 'The Executive Assistant set the evaluation schedule: Oct 4, 2026 10:51 AM – Oct 5, 2026 11:00 PM.', NULL, 0, '2026-10-04 10:49:54'),
(107, 'evaluation_schedule', 232, 'The Executive Assistant set the evaluation schedule: Oct 4, 2026 10:51 AM – Oct 5, 2026 11:00 PM.', NULL, 0, '2026-10-04 10:49:54'),
(108, 'evaluation_schedule', 233, 'The Executive Assistant set the evaluation schedule: Oct 4, 2026 10:51 AM – Oct 5, 2026 11:00 PM.', NULL, 0, '2026-10-04 10:49:54'),
(109, 'evaluation_schedule', 234, 'The Executive Assistant set the evaluation schedule: Oct 4, 2026 10:51 AM – Oct 5, 2026 11:00 PM.', NULL, 0, '2026-10-04 10:49:54'),
(112, 'teaching_assignment', 222, 'The Executive Assistant updated your teaching assignment: Grade 12.', NULL, 1, '2026-10-04 10:50:33'),
(113, 'teaching_assignment', 222, 'The Executive Assistant updated your teaching assignment: Grade 12, Grade 8.', NULL, 1, '2026-10-04 10:50:42'),
(114, 'teaching_assignment', 222, 'The Executive Assistant updated your teaching assignment: Grade 12, Grade 8, Grade 9.', NULL, 1, '2026-10-04 10:50:50'),
(115, 'teaching_assignment', 220, 'The Executive Assistant updated your teaching assignment: 4th Year College, Grade 11, Grade 12, Grade 7.', NULL, 0, '2026-10-04 14:14:29'),
(116, 'teaching_assignment', 220, 'The Executive Assistant updated your teaching assignment: 4th Year College, Grade 12, Grade 7.', NULL, 0, '2026-10-04 15:27:39'),
(117, 'teaching_assignment', 220, 'The Executive Assistant updated your teaching assignment: Grade 12, Grade 7.', NULL, 0, '2026-10-04 15:28:05'),
(118, 'teaching_assignment', 227, 'The Executive Assistant assigned you to teach: Grade 7.', NULL, 0, '2026-10-04 15:28:27'),
(119, 'teaching_assignment', 227, 'The Executive Assistant updated your teaching assignment: Grade 11, Grade 7.', NULL, 0, '2026-10-04 15:28:32'),
(120, 'teaching_assignment', 220, 'The Executive Assistant updated your teaching assignment: 4th Year College, Grade 12, Grade 7.', NULL, 0, '2026-10-04 15:32:50'),
(121, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 15:56:05'),
(122, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 15:56:05'),
(123, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 15:56:05'),
(124, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 15:56:05'),
(125, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 15:56:05'),
(126, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 1, '2026-10-04 15:56:05'),
(127, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 15:56:05'),
(128, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 15:56:05'),
(129, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 15:56:05'),
(130, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 15:56:05'),
(131, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 15:56:05'),
(132, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 15:56:05'),
(133, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 15:56:05'),
(136, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:04:54'),
(137, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:04:54'),
(138, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:04:54'),
(139, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:04:54'),
(140, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:04:54'),
(141, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 1, '2026-10-04 16:04:54'),
(142, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:04:54'),
(143, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:04:54'),
(144, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:04:54'),
(145, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:04:54'),
(146, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:04:54'),
(147, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:04:54'),
(148, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:04:54'),
(151, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:05:09'),
(152, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:05:09'),
(153, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:05:09'),
(154, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:05:09'),
(155, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:05:09'),
(156, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 1, '2026-10-04 16:05:09'),
(157, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:05:09'),
(158, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:05:09'),
(159, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:05:09'),
(160, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:05:09'),
(161, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:05:09'),
(162, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:05:09'),
(163, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:05:09'),
(166, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:25:55'),
(167, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:25:55'),
(168, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:25:55'),
(169, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:25:55'),
(170, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:25:55'),
(171, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 1, '2026-10-04 16:25:55'),
(172, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:25:55'),
(173, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:25:55'),
(174, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:25:55'),
(175, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:25:55'),
(176, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:25:55'),
(177, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:25:55'),
(178, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:25:55'),
(181, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:41:44'),
(182, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:41:44'),
(183, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:41:44'),
(184, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:41:44'),
(185, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:41:44'),
(186, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 1, '2026-10-04 16:41:44'),
(187, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:41:44'),
(188, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:41:44'),
(189, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:41:44'),
(190, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:41:44'),
(191, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:41:44'),
(192, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:41:44'),
(193, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-04 16:41:44'),
(196, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:53:12'),
(197, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:53:12'),
(198, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:53:12'),
(199, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:53:12'),
(200, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:53:12'),
(201, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 1, '2026-10-04 16:53:12'),
(202, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:53:12'),
(203, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:53:12'),
(204, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:53:12'),
(205, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:53:12'),
(206, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:53:12'),
(207, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:53:12'),
(208, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-04 16:53:12'),
(209, 'evaluation_schedule', 215, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:40:51'),
(210, 'evaluation_schedule', 216, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:40:51'),
(211, 'evaluation_schedule', 217, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:40:51'),
(212, 'evaluation_schedule', 218, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:40:51'),
(213, 'evaluation_schedule', 220, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:40:51'),
(214, 'evaluation_schedule', 222, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:40:51'),
(215, 'evaluation_schedule', 225, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:40:51'),
(216, 'evaluation_schedule', 226, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:40:51'),
(217, 'evaluation_schedule', 227, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:40:51'),
(218, 'evaluation_schedule', 230, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:40:51'),
(219, 'evaluation_schedule', 232, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:40:51'),
(220, 'evaluation_schedule', 233, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:40:51'),
(221, 'evaluation_schedule', 234, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:40:51'),
(224, 'evaluation_schedule', 215, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(225, 'evaluation_schedule', 216, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(226, 'evaluation_schedule', 217, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(227, 'evaluation_schedule', 218, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(228, 'evaluation_schedule', 220, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(229, 'evaluation_schedule', 222, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(230, 'evaluation_schedule', 225, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(231, 'evaluation_schedule', 226, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(232, 'evaluation_schedule', 227, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(233, 'evaluation_schedule', 230, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(234, 'evaluation_schedule', 232, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(235, 'evaluation_schedule', 233, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(236, 'evaluation_schedule', 234, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:42 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(239, 'evaluation_schedule', 215, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:43 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(240, 'evaluation_schedule', 216, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:43 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(241, 'evaluation_schedule', 217, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:43 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(242, 'evaluation_schedule', 218, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:43 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(243, 'evaluation_schedule', 220, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:43 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(244, 'evaluation_schedule', 222, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:43 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(245, 'evaluation_schedule', 225, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:43 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(246, 'evaluation_schedule', 226, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:43 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(247, 'evaluation_schedule', 227, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:43 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(248, 'evaluation_schedule', 230, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:43 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(249, 'evaluation_schedule', 232, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:43 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(250, 'evaluation_schedule', 233, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:43 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(251, 'evaluation_schedule', 234, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:43 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:42:43'),
(254, 'evaluation_schedule', 215, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:44 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:43:39'),
(255, 'evaluation_schedule', 216, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:44 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:43:39'),
(256, 'evaluation_schedule', 217, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:44 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:43:39'),
(257, 'evaluation_schedule', 218, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:44 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:43:39'),
(258, 'evaluation_schedule', 220, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:44 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:43:39'),
(259, 'evaluation_schedule', 222, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:44 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:43:39'),
(260, 'evaluation_schedule', 225, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:44 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:43:39'),
(261, 'evaluation_schedule', 226, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:44 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:43:39'),
(262, 'evaluation_schedule', 227, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:44 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:43:39'),
(263, 'evaluation_schedule', 230, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:44 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:43:39'),
(264, 'evaluation_schedule', 232, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:44 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:43:39'),
(265, 'evaluation_schedule', 233, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:44 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:43:39'),
(266, 'evaluation_schedule', 234, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 2:44 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 14:43:39'),
(269, 'evaluation_schedule', 215, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:39 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:00'),
(270, 'evaluation_schedule', 216, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:39 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:00'),
(271, 'evaluation_schedule', 217, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:39 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:00'),
(272, 'evaluation_schedule', 218, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:39 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:00'),
(273, 'evaluation_schedule', 220, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:39 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:00'),
(274, 'evaluation_schedule', 222, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:39 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:00'),
(275, 'evaluation_schedule', 225, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:39 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:00'),
(276, 'evaluation_schedule', 226, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:39 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:00'),
(277, 'evaluation_schedule', 227, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:39 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:00'),
(278, 'evaluation_schedule', 230, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:39 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:00'),
(279, 'evaluation_schedule', 232, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:39 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:00'),
(280, 'evaluation_schedule', 233, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:39 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:00'),
(281, 'evaluation_schedule', 234, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:39 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:00'),
(284, 'evaluation_schedule', 215, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:06'),
(285, 'evaluation_schedule', 216, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:06'),
(286, 'evaluation_schedule', 217, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:06'),
(287, 'evaluation_schedule', 218, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:06'),
(288, 'evaluation_schedule', 220, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:06'),
(289, 'evaluation_schedule', 222, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:06'),
(290, 'evaluation_schedule', 225, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:06'),
(291, 'evaluation_schedule', 226, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:06'),
(292, 'evaluation_schedule', 227, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:06'),
(293, 'evaluation_schedule', 230, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:06'),
(294, 'evaluation_schedule', 232, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:06'),
(295, 'evaluation_schedule', 233, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:06'),
(296, 'evaluation_schedule', 234, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 3:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 15:39:06'),
(299, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-07 16:24:48'),
(300, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-07 16:24:48'),
(301, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-07 16:24:48'),
(302, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-07 16:24:48'),
(303, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-07 16:24:48'),
(304, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-07 16:24:48'),
(305, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-07 16:24:48'),
(306, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-07 16:24:48'),
(307, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-07 16:24:48'),
(308, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-07 16:24:48'),
(309, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-07 16:24:48'),
(310, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-07 16:24:48'),
(311, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-07 16:24:48'),
(314, 'evaluation_schedule', 215, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 6:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:46:47'),
(315, 'evaluation_schedule', 216, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 6:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:46:47'),
(316, 'evaluation_schedule', 217, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 6:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:46:47'),
(317, 'evaluation_schedule', 218, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 6:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:46:47'),
(318, 'evaluation_schedule', 220, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 6:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:46:47'),
(319, 'evaluation_schedule', 222, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 6:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:46:47'),
(320, 'evaluation_schedule', 225, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 6:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:46:47'),
(321, 'evaluation_schedule', 226, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 6:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:46:47'),
(322, 'evaluation_schedule', 227, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 6:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:46:47'),
(323, 'evaluation_schedule', 230, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 6:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:46:47'),
(324, 'evaluation_schedule', 232, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 6:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:46:47'),
(325, 'evaluation_schedule', 233, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 6:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:46:47'),
(326, 'evaluation_schedule', 234, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 6:40 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:46:47'),
(329, 'evaluation_schedule', 215, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:48 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:47:16'),
(330, 'evaluation_schedule', 216, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:48 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:47:16'),
(331, 'evaluation_schedule', 217, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:48 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:47:16'),
(332, 'evaluation_schedule', 218, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:48 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:47:16'),
(333, 'evaluation_schedule', 220, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:48 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:47:16'),
(334, 'evaluation_schedule', 222, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:48 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:47:16'),
(335, 'evaluation_schedule', 225, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:48 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:47:16'),
(336, 'evaluation_schedule', 226, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:48 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:47:16'),
(337, 'evaluation_schedule', 227, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:48 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:47:16'),
(338, 'evaluation_schedule', 230, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:48 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:47:16'),
(339, 'evaluation_schedule', 232, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:48 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:47:16'),
(340, 'evaluation_schedule', 233, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:48 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:47:16'),
(341, 'evaluation_schedule', 234, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:48 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:47:16'),
(344, 'evaluation_schedule', 215, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:49 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:48:23'),
(345, 'evaluation_schedule', 216, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:49 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:48:23'),
(346, 'evaluation_schedule', 217, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:49 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:48:23'),
(347, 'evaluation_schedule', 218, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:49 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:48:23'),
(348, 'evaluation_schedule', 220, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:49 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:48:23'),
(349, 'evaluation_schedule', 222, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:49 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:48:23'),
(350, 'evaluation_schedule', 225, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:49 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:48:23'),
(351, 'evaluation_schedule', 226, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:49 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:48:23'),
(352, 'evaluation_schedule', 227, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:49 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:48:23'),
(353, 'evaluation_schedule', 230, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:49 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:48:23'),
(354, 'evaluation_schedule', 232, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:49 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:48:23'),
(355, 'evaluation_schedule', 233, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:49 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:48:23'),
(356, 'evaluation_schedule', 234, 'The Executive Assistant set the evaluation schedule: Oct 7, 2026 4:49 PM – Oct 8, 2026 11:00 PM.', NULL, 0, '2026-10-07 16:48:23'),
(357, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 12:56:12'),
(358, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 12:56:12'),
(359, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 12:56:12'),
(360, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 12:56:12'),
(361, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 12:56:12'),
(362, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 12:56:12'),
(363, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 12:56:12'),
(364, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 12:56:12'),
(365, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 12:56:12'),
(366, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 12:56:12'),
(367, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 12:56:12'),
(368, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 12:56:12'),
(369, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 12:56:12'),
(372, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 13:09:46'),
(373, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 13:09:46'),
(374, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 13:09:46'),
(375, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 13:09:46'),
(376, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 13:09:46'),
(377, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 13:09:46'),
(378, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 13:09:46'),
(379, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 13:09:46'),
(380, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 13:09:46'),
(381, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 13:09:46'),
(382, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 13:09:46'),
(383, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 13:09:46'),
(384, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 13:09:46'),
(387, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 13:38:27'),
(388, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 13:38:27'),
(389, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 13:38:27'),
(390, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 13:38:27'),
(391, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 13:38:27'),
(392, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 13:38:27'),
(393, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 13:38:27'),
(394, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 13:38:27'),
(395, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 13:38:27'),
(396, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 13:38:27'),
(397, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 13:38:27'),
(398, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 13:38:27'),
(399, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 13:38:27'),
(402, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:00:55'),
(403, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:00:55'),
(404, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:00:55'),
(405, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:00:55'),
(406, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:00:55'),
(407, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:00:55'),
(408, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:00:55'),
(409, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:00:55'),
(410, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:00:55'),
(411, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:00:55'),
(412, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:00:55'),
(413, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:00:55'),
(414, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:00:55'),
(417, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:03:41'),
(418, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:03:41'),
(419, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:03:41'),
(420, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:03:41'),
(421, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:03:41'),
(422, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:03:41'),
(423, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:03:41'),
(424, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:03:41'),
(425, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:03:41'),
(426, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:03:41');
INSERT INTO `notifications` (`id`, `type`, `user_id`, `message`, `extra_data`, `is_read`, `created_at`) VALUES
(427, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:03:41'),
(428, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:03:41'),
(429, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:03:41'),
(432, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:09:15'),
(433, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:09:15'),
(434, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:09:15'),
(435, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:09:15'),
(436, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:09:15'),
(437, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:09:15'),
(438, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:09:15'),
(439, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:09:15'),
(440, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:09:15'),
(441, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:09:15'),
(442, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:09:15'),
(443, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:09:15'),
(444, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:09:15'),
(447, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:14:48'),
(448, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:14:48'),
(449, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:14:48'),
(450, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:14:48'),
(451, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:14:48'),
(452, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:14:48'),
(453, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:14:48'),
(454, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:14:48'),
(455, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:14:48'),
(456, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:14:48'),
(457, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:14:48'),
(458, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:14:48'),
(459, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:14:48'),
(462, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:15:18'),
(463, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:15:18'),
(464, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:15:18'),
(465, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:15:18'),
(466, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:15:18'),
(467, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:15:18'),
(468, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:15:18'),
(469, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:15:18'),
(470, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:15:18'),
(471, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:15:18'),
(472, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:15:18'),
(473, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:15:18'),
(474, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:15:18'),
(477, 'teaching_assignment', 220, 'The Executive Assistant updated your teaching assignment: 4th Year College.', NULL, 0, '2026-10-09 15:15:53'),
(478, 'teaching_assignment', 220, 'The Executive Assistant updated your teaching assignment: Grade 7, Grade 8.', NULL, 0, '2026-10-09 15:16:03'),
(479, 'teaching_assignment', 216, 'The Executive Assistant updated your teaching assignment: 1st Year College, 2nd Year College, 4th Year College.', NULL, 0, '2026-10-09 15:16:13'),
(480, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:16:55'),
(481, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:16:55'),
(482, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:16:55'),
(483, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:16:55'),
(484, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:16:55'),
(485, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:16:55'),
(486, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:16:55'),
(487, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:16:55'),
(488, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:16:55'),
(489, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:16:55'),
(490, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:16:55'),
(491, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:16:55'),
(492, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:16:55'),
(495, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:19:56'),
(496, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:19:56'),
(497, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:19:56'),
(498, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:19:56'),
(499, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:19:56'),
(500, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:19:56'),
(501, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:19:56'),
(502, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:19:56'),
(503, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:19:56'),
(504, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:19:56'),
(505, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:19:56'),
(506, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:19:56'),
(507, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:19:56'),
(510, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:29:31'),
(511, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:29:31'),
(512, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:29:31'),
(513, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:29:31'),
(514, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:29:31'),
(515, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:29:31'),
(516, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:29:31'),
(517, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:29:31'),
(518, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:29:31'),
(519, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:29:31'),
(520, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:29:31'),
(521, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:29:31'),
(522, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:29:31'),
(525, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:30:53'),
(526, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:30:53'),
(527, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:30:53'),
(528, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:30:53'),
(529, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:30:53'),
(530, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:30:53'),
(531, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:30:53'),
(532, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:30:53'),
(533, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:30:53'),
(534, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:30:53'),
(535, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:30:53'),
(536, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:30:53'),
(537, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 15:30:53'),
(540, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:32:01'),
(541, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:32:01'),
(542, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:32:01'),
(543, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:32:01'),
(544, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:32:01'),
(545, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:32:01'),
(546, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:32:01'),
(547, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:32:01'),
(548, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:32:01'),
(549, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:32:01'),
(550, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:32:01'),
(551, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:32:01'),
(552, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 15:32:01'),
(553, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:14:40'),
(554, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:14:40'),
(555, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:14:40'),
(556, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:14:40'),
(557, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:14:40'),
(558, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:14:40'),
(559, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:14:40'),
(560, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:14:40'),
(561, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:14:40'),
(562, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:14:40'),
(563, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:14:40'),
(564, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:14:40'),
(565, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:14:40'),
(568, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 16:20:46'),
(569, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 16:20:46'),
(570, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 16:20:46'),
(571, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 16:20:46'),
(572, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 16:20:46'),
(573, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 16:20:46'),
(574, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 16:20:46'),
(575, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 16:20:46'),
(576, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 16:20:46'),
(577, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 16:20:46'),
(578, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 16:20:46'),
(579, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 16:20:46'),
(580, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 16:20:46'),
(583, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:21:54'),
(584, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:21:54'),
(585, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:21:54'),
(586, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:21:54'),
(587, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:21:54'),
(588, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:21:54'),
(589, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:21:54'),
(590, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:21:54'),
(591, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:21:54'),
(592, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:21:54'),
(593, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:21:54'),
(594, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:21:54'),
(595, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 16:21:54'),
(596, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:06:57'),
(597, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:06:57'),
(598, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:06:57'),
(599, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:06:57'),
(600, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:06:57'),
(601, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:06:57'),
(602, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:06:57'),
(603, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:06:57'),
(604, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:06:57'),
(605, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:06:57'),
(606, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:06:57'),
(607, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:06:57'),
(608, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:06:57'),
(611, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:13:07'),
(612, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:13:07'),
(613, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:13:07'),
(614, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:13:07'),
(615, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:13:07'),
(616, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:13:07'),
(617, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:13:07'),
(618, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:13:07'),
(619, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:13:07'),
(620, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:13:07'),
(621, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:13:07'),
(622, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:13:07'),
(623, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:13:07'),
(626, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:20:51'),
(627, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:20:51'),
(628, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:20:51'),
(629, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:20:51'),
(630, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:20:51'),
(631, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:20:51'),
(632, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:20:51'),
(633, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:20:51'),
(634, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:20:51'),
(635, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:20:51'),
(636, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:20:51'),
(637, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:20:51'),
(638, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:20:51'),
(641, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:31:42'),
(642, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:31:42'),
(643, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:31:42'),
(644, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:31:42'),
(645, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:31:42'),
(646, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:31:42'),
(647, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:31:42'),
(648, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:31:42'),
(649, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:31:42'),
(650, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:31:42'),
(651, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:31:42'),
(652, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:31:42'),
(653, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:31:42'),
(656, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:32:58'),
(657, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:32:58'),
(658, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:32:58'),
(659, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:32:58'),
(660, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:32:58'),
(661, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:32:58'),
(662, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:32:58'),
(663, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:32:58'),
(664, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:32:58'),
(665, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:32:58'),
(666, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:32:58'),
(667, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:32:58'),
(668, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 21:32:58'),
(671, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:45:07'),
(672, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:45:07'),
(673, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:45:07'),
(674, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:45:07'),
(675, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:45:07'),
(676, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:45:07'),
(677, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:45:07'),
(678, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:45:07'),
(679, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:45:07'),
(680, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:45:07'),
(681, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:45:07'),
(682, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:45:07'),
(683, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 21:45:07'),
(686, 'evaluation_received', 232, 'You have received a new evaluation.', NULL, 0, '2026-10-09 21:56:06'),
(687, 'evaluation_received', 236, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"EA\",\"target_label\":\"Executive Assistant\"}', 0, '2026-10-09 21:56:55'),
(688, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 22:26:41'),
(689, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 22:26:41'),
(690, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 22:26:41'),
(691, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 22:26:41'),
(692, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 22:26:41'),
(693, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 22:26:41'),
(694, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 22:26:41'),
(695, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 22:26:41'),
(696, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 22:26:41'),
(697, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 22:26:41'),
(698, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 22:26:41'),
(699, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 22:26:41'),
(700, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 22:26:41'),
(703, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 22:37:22'),
(704, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 22:37:22'),
(705, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 22:37:22'),
(706, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 22:37:22'),
(707, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 22:37:22'),
(708, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 22:37:22'),
(709, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 22:37:22'),
(710, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 22:37:22'),
(711, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 22:37:22'),
(712, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 22:37:22'),
(713, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 22:37:22'),
(714, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 22:37:22'),
(715, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-09 22:37:22'),
(718, 'evaluation_received', 233, 'You have received a new evaluation.', NULL, 0, '2026-10-09 22:38:00'),
(719, 'evaluation_received', 225, 'You have received a new evaluation.', NULL, 0, '2026-10-09 23:33:18'),
(720, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 23:49:28'),
(721, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 23:49:28'),
(722, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 23:49:28'),
(723, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 23:49:28'),
(724, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 23:49:28'),
(725, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 23:49:28'),
(726, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 23:49:28'),
(727, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 23:49:28'),
(728, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 23:49:28'),
(729, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 23:49:28'),
(730, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 23:49:28'),
(731, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 23:49:28'),
(732, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-09 23:49:28'),
(735, 'evaluation_received', 225, 'You have received a new evaluation.', NULL, 0, '2026-10-10 00:01:10'),
(736, 'evaluation_received', 236, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"EA\",\"target_label\":\"Executive Assistant\"}', 0, '2026-10-10 00:22:06'),
(737, 'evaluation_received', 236, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"EA\",\"target_label\":\"Executive Assistant\"}', 0, '2026-10-10 00:26:15'),
(738, 'evaluation_received', 236, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"EA\",\"target_label\":\"Executive Assistant\"}', 0, '2026-10-10 00:28:41'),
(739, 'evaluation_received', 220, 'You have received a new evaluation.', NULL, 0, '2026-10-10 01:02:54'),
(740, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:05:47'),
(741, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:05:47'),
(742, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:05:47'),
(743, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:05:47'),
(744, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:05:47'),
(745, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:05:47'),
(746, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:05:47'),
(747, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:05:47'),
(748, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:05:47'),
(749, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:05:47'),
(750, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:05:47'),
(751, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:05:47'),
(752, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:05:47'),
(755, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:06:11'),
(756, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:06:11'),
(757, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:06:11'),
(758, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:06:11'),
(759, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:06:11'),
(760, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:06:11'),
(761, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:06:11'),
(762, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:06:11'),
(763, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:06:11'),
(764, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:06:11'),
(765, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:06:11'),
(766, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:06:11'),
(767, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:06:11'),
(770, 'evaluation_received', 227, 'You have received a new evaluation.', NULL, 0, '2026-10-10 01:08:59'),
(771, 'evaluation_received', 234, 'You have received a new evaluation.', NULL, 0, '2026-10-10 01:10:01'),
(772, 'evaluation_received', 220, 'You have received a new evaluation.', NULL, 0, '2026-10-10 01:12:19'),
(773, 'evaluation_received', 227, 'You have received a new evaluation.', NULL, 0, '2026-10-10 01:13:18'),
(774, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:14:30'),
(775, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:14:30'),
(776, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:14:30'),
(777, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:14:30'),
(778, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:14:30'),
(779, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:14:30'),
(780, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:14:30'),
(781, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:14:30'),
(782, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:14:30'),
(783, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:14:30'),
(784, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:14:30'),
(785, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:14:30'),
(786, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:14:30'),
(789, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:14:45'),
(790, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:14:45'),
(791, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:14:45'),
(792, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:14:45'),
(793, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:14:45'),
(794, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:14:45'),
(795, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:14:45'),
(796, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:14:45'),
(797, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:14:45'),
(798, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:14:45'),
(799, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:14:45'),
(800, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:14:45'),
(801, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:14:45'),
(804, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:17:56'),
(805, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:17:56'),
(806, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:17:56'),
(807, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:17:56'),
(808, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:17:56'),
(809, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:17:56'),
(810, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:17:56'),
(811, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:17:56'),
(812, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:17:56'),
(813, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:17:56'),
(814, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:17:56'),
(815, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:17:56'),
(816, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:17:56'),
(819, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:21:24'),
(820, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:21:24'),
(821, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:21:24'),
(822, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:21:24'),
(823, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:21:24'),
(824, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:21:24'),
(825, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:21:24'),
(826, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:21:24'),
(827, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:21:24'),
(828, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:21:24'),
(829, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:21:24');
INSERT INTO `notifications` (`id`, `type`, `user_id`, `message`, `extra_data`, `is_read`, `created_at`) VALUES
(830, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:21:24'),
(831, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 01:21:24'),
(834, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:41:01'),
(835, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:41:01'),
(836, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:41:01'),
(837, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:41:01'),
(838, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:41:01'),
(839, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:41:01'),
(840, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:41:01'),
(841, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:41:01'),
(842, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:41:01'),
(843, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:41:01'),
(844, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:41:01'),
(845, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:41:01'),
(846, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 01:41:01'),
(849, 'evaluation_received', 236, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"EA\",\"target_label\":\"Executive Assistant\"}', 0, '2026-10-10 02:03:49'),
(850, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:17:52'),
(851, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:17:52'),
(852, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:17:52'),
(853, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:17:52'),
(854, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:17:52'),
(855, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:17:52'),
(856, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:17:52'),
(857, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:17:52'),
(858, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:17:52'),
(859, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:17:52'),
(860, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:17:52'),
(861, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:17:52'),
(862, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:17:52'),
(865, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:21:54'),
(866, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:21:54'),
(867, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:21:54'),
(868, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:21:54'),
(869, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:21:54'),
(870, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:21:54'),
(871, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:21:54'),
(872, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:21:54'),
(873, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:21:54'),
(874, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:21:54'),
(875, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:21:54'),
(876, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:21:54'),
(877, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:21:54'),
(880, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:22:07'),
(881, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:22:07'),
(882, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:22:07'),
(883, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:22:07'),
(884, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:22:07'),
(885, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:22:07'),
(886, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:22:07'),
(887, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:22:07'),
(888, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:22:07'),
(889, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:22:07'),
(890, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:22:07'),
(891, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:22:07'),
(892, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · 1st Semester.', NULL, 0, '2026-10-10 10:22:07'),
(895, 'academic_period', 215, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:22:22'),
(896, 'academic_period', 216, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:22:22'),
(897, 'academic_period', 217, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:22:22'),
(898, 'academic_period', 218, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:22:22'),
(899, 'academic_period', 220, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:22:22'),
(900, 'academic_period', 222, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:22:22'),
(901, 'academic_period', 225, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:22:22'),
(902, 'academic_period', 226, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:22:22'),
(903, 'academic_period', 227, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:22:22'),
(904, 'academic_period', 230, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:22:22'),
(905, 'academic_period', 232, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:22:22'),
(906, 'academic_period', 233, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:22:22'),
(907, 'academic_period', 234, 'The Executive Assistant set the academic period to 2026-2027 · School Year.', NULL, 0, '2026-10-10 10:22:22'),
(910, 'teaching_assignment', 234, 'The Executive Assistant removed your teaching assignment.', NULL, 0, '2026-10-10 10:24:55'),
(911, 'evaluation_received', 236, 'You have received a new Staff Evaluation.', '{\"evaluation_type\":\"staff\",\"target_type\":\"EA\",\"target_label\":\"Executive Assistant\"}', 0, '2026-10-10 10:25:40');

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
-- Table structure for table `portal_feedback_keep`
--

CREATE TABLE `portal_feedback_keep` (
  `id` int(10) UNSIGNED NOT NULL,
  `archive_id` int(10) UNSIGNED NOT NULL,
  `tracker_id` int(10) UNSIGNED NOT NULL,
  `target_user_id` int(10) UNSIGNED NOT NULL,
  `period_id` int(10) UNSIGNED NOT NULL,
  `school_year` varchar(30) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `period_label` varchar(150) DEFAULT NULL,
  `eval_type` varchar(50) DEFAULT NULL,
  `peer_group` varchar(30) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `score` double DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `score_sum` double NOT NULL DEFAULT 0,
  `score_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `answers_json` longtext DEFAULT NULL,
  `kept_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `portal_feedback_keep`
--

INSERT INTO `portal_feedback_keep` (`id`, `archive_id`, `tracker_id`, `target_user_id`, `period_id`, `school_year`, `semester`, `period_label`, `eval_type`, `peer_group`, `status`, `score`, `remarks`, `submitted_at`, `score_sum`, `score_count`, `answers_json`, `kept_at`) VALUES
(1, 7, 138, 218, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'ea', NULL, 'submitted', NULL, 'N/A', '2026-09-22 10:49:47', 0, 0, '[]', '2026-10-07 16:15:22'),
(2, 7, 139, 236, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'school_head', NULL, 'submitted', 4.75, 'N/A', '2026-09-22 10:51:05', 0, 0, '[]', '2026-10-07 16:15:22'),
(3, 7, 143, 218, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'student', NULL, 'submitted', NULL, 'N/A', '2026-09-22 11:05:51', 0, 0, '[]', '2026-10-07 16:15:22'),
(4, 7, 144, 236, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'staff', 'Staff Evaluation', 'submitted', 5, 'N/A', '2026-09-22 11:09:59', 0, 0, '[]', '2026-10-07 16:15:22'),
(5, 7, 145, 216, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'faculty_peer', 'Faculty', 'submitted', 5, 'N/A', '2026-09-22 11:14:00', 55, 11, '[{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":5}]', '2026-10-07 16:15:22'),
(6, 7, 146, 215, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'student', NULL, 'submitted', NULL, 'N/A', '2026-09-22 11:19:31', 0, 0, '[]', '2026-10-07 16:15:22'),
(7, 7, 147, 157, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'student', NULL, 'submitted', NULL, 'N/A', '2026-09-22 11:20:04', 0, 0, '[]', '2026-10-07 16:15:22'),
(8, 7, 152, 158, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'ea', NULL, 'submitted', NULL, '', '2026-09-23 14:22:44', 39, 10, '[{\"category\":\"Leadership & Supervision\",\"question_text\":\"Provides clear direction for the high school division.\",\"score\":3},{\"category\":\"Leadership & Supervision\",\"question_text\":\"Monitors the implementation of high school academic programs.\",\"score\":4},{\"category\":\"Leadership & Supervision\",\"question_text\":\"Ensures that high school policies and procedures are properly followed.\",\"score\":4},{\"category\":\"Leadership & Supervision\",\"question_text\":\"Addresses concerns involving high school students, faculty, and staff appropriately.\",\"score\":3},{\"category\":\"Leadership & Supervision\",\"question_text\":\"Oversees the effective implementation of high school activities and programs.\",\"score\":5},{\"category\":\"School Management & Student Development\",\"question_text\":\"Promotes a positive and supportive environment for high school students.\",\"score\":5},{\"category\":\"School Management & Student Development\",\"question_text\":\"Communicates high school policies and expectations clearly.\",\"score\":4},{\"category\":\"School Management & Student Development\",\"question_text\":\"Coordinates effectively with high school faculty and staff.\",\"score\":4},{\"category\":\"School Management & Student Development\",\"question_text\":\"Supports programs that promote student development and achievement.\",\"score\":3},{\"category\":\"School Management & Student Development\",\"question_text\":\"Demonstrates accountability in managing high school operations.\",\"score\":4}]', '2026-10-07 16:15:22'),
(9, 7, 153, 216, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'faculty_peer', 'Faculty', 'submitted', 4.53, '', '2026-09-23 14:35:12', 77, 17, '[{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates punctuality and preparedness for classes\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Treats students fairly, respectfully, and professionally.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Maintains a positive and conducive learning environment.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Communicates effectively with students and fellow faculty members.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates responsibility in performing teaching and academic duties.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Effective teaching?\",\"score\":4}]', '2026-10-07 16:15:22'),
(10, 7, 154, 216, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'faculty_peer', 'Faculty', 'submitted', 4.06, 'N/A', '2026-09-23 14:38:24', 69, 17, '[{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates punctuality and preparedness for classes\",\"score\":3},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Treats students fairly, respectfully, and professionally.\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Maintains a positive and conducive learning environment.\",\"score\":2},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Communicates effectively with students and fellow faculty members.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates responsibility in performing teaching and academic duties.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":1},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Effective teaching?\",\"score\":5}]', '2026-10-07 16:15:22'),
(11, 7, 157, 217, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'student', NULL, 'submitted', NULL, 'N/A', '2026-09-24 09:38:06', 133, 29, '[{\"category\":\"General\",\"question_text\":\"Collaborates effectively with colleagues in academic activities.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Shares knowledge, ideas, and teaching resources with other teachers.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"Shows willingness to support colleagues in addressing teaching-related concerns.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Contributes positively to a collaborative and supportive school environment.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Demonstrates professionalism and respect toward colleagues.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"Accepts feedback and suggestions from fellow teachers constructively.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Explains lessons clearly and effectively.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Handles classroom concerns appropriately.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":5},{\"category\":\"General\",\"question_text\":\"dvfdxv\",\"score\":4},{\"category\":\"General\",\"question_text\":\"dsfsdsdfsd\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates punctuality and preparedness for classes\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Treats students fairly, respectfully, and professionally.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Maintains a positive and conducive learning environment.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Communicates effectively with students and fellow faculty members.\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates responsibility in performing teaching and academic duties.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Clearly explains lessons and course-related concepts.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Effective teaching?\",\"score\":5}]', '2026-10-07 16:15:22'),
(12, 7, 158, 217, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'student', NULL, 'submitted', NULL, 'N/A', '2026-09-24 09:38:47', 125, 29, '[{\"category\":\"General\",\"question_text\":\"Collaborates effectively with colleagues in academic activities.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Shares knowledge, ideas, and teaching resources with other teachers.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"Shows willingness to support colleagues in addressing teaching-related concerns.\",\"score\":3},{\"category\":\"General\",\"question_text\":\"Contributes positively to a collaborative and supportive school environment.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Demonstrates professionalism and respect toward colleagues.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Accepts feedback and suggestions from fellow teachers constructively.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"Explains lessons clearly and effectively.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Handles classroom concerns appropriately.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *\",\"score\":3},{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":5},{\"category\":\"General\",\"question_text\":\"dvfdxv\",\"score\":4},{\"category\":\"General\",\"question_text\":\"dsfsdsdfsd\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates punctuality and preparedness for classes\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Treats students fairly, respectfully, and professionally.\",\"score\":3},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Maintains a positive and conducive learning environment.\",\"score\":3},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Communicates effectively with students and fellow faculty members.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates responsibility in performing teaching and academic duties.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Clearly explains lessons and course-related concepts.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Effective teaching?\",\"score\":5}]', '2026-10-07 16:15:22'),
(13, 7, 159, 225, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'student', NULL, 'submitted', NULL, 'N/A', '2026-09-24 16:39:57', 128, 29, '[{\"category\":\"General\",\"question_text\":\"Collaborates effectively with colleagues in academic activities.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Shares knowledge, ideas, and teaching resources with other teachers.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"Shows willingness to support colleagues in addressing teaching-related concerns.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Contributes positively to a collaborative and supportive school environment.\",\"score\":3},{\"category\":\"General\",\"question_text\":\"Demonstrates professionalism and respect toward colleagues.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Accepts feedback and suggestions from fellow teachers constructively.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"Explains lessons clearly and effectively.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Handles classroom concerns appropriately.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":2},{\"category\":\"General\",\"question_text\":\"dvfdxv\",\"score\":5},{\"category\":\"General\",\"question_text\":\"dsfsdsdfsd\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates punctuality and preparedness for classes\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Treats students fairly, respectfully, and professionally.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Maintains a positive and conducive learning environment.\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Communicates effectively with students and fellow faculty members.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates responsibility in performing teaching and academic duties.\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Clearly explains lessons and course-related concepts.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Effective teaching?\",\"score\":4}]', '2026-10-07 16:15:22'),
(14, 7, 160, 236, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'staff', 'Staff Evaluation', 'submitted', 4.3, 'N/A', '2026-09-28 15:58:31', 43, 10, '[{\"category\":\"Administrative Support & Coordination\",\"question_text\":\"Provides timely and organized administrative support to school management.\",\"score\":5},{\"category\":\"Administrative Support & Coordination\",\"question_text\":\"Coordinates meetings, schedules, and official activities effectively.\",\"score\":4},{\"category\":\"Administrative Support & Coordination\",\"question_text\":\"Prepares and organizes documents and administrative records accurately.\",\"score\":5},{\"category\":\"Administrative Support & Coordination\",\"question_text\":\"Communicates information and instructions clearly to faculty, staff, and other stakeholders.\",\"score\":4},{\"category\":\"Administrative Support & Coordination\",\"question_text\":\"Responds promptly to administrative requests and concerns.\",\"score\":5},{\"category\":\"Professionalism & Office Management\",\"question_text\":\"Maintains confidentiality of official and sensitive information.\",\"score\":4},{\"category\":\"Professionalism & Office Management\",\"question_text\":\"Demonstrates accuracy and attention to detail in administrative tasks.\",\"score\":4},{\"category\":\"Professionalism & Office Management\",\"question_text\":\"Coordinates effectively with different school offices and personnel.\",\"score\":5},{\"category\":\"Professionalism & Office Management\",\"question_text\":\"Manages assigned responsibilities in an organized and dependable manner.\",\"score\":3},{\"category\":\"Professionalism & Office Management\",\"question_text\":\"Demonstrates professionalism and courtesy when dealing with students, faculty, staff, and administrators.\",\"score\":4}]', '2026-10-07 16:15:22'),
(15, 7, 162, 217, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'student', NULL, 'submitted', NULL, 'N/A', '2026-09-28 22:00:21', 76, 17, '[{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates punctuality and preparedness for classes\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Treats students fairly, respectfully, and professionally.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Maintains a positive and conducive learning environment.\",\"score\":3},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Communicates effectively with students and fellow faculty members.\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates responsibility in performing teaching and academic duties.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Effective teaching?\",\"score\":5}]', '2026-10-07 16:15:22'),
(16, 7, 163, 157, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'ea', NULL, 'submitted', NULL, 'N/A', '2026-09-30 21:43:44', 43, 10, '[{\"category\":\"Academic Leadership\",\"question_text\":\"Provides clear direction for the college academic programs.\",\"score\":5},{\"category\":\"Academic Leadership\",\"question_text\":\"Monitors the implementation of college-level curricula and academic requirements.\",\"score\":4},{\"category\":\"Academic Leadership\",\"question_text\":\"Coordinates effectively with college faculty regarding academic matters.\",\"score\":3},{\"category\":\"Academic Leadership\",\"question_text\":\"Addresses academic concerns of college students appropriately.\",\"score\":5},{\"category\":\"Academic Leadership\",\"question_text\":\"Supports the continuous improvement of college teaching and learning.\",\"score\":4},{\"category\":\"Faculty & College Management\",\"question_text\":\"Monitors faculty compliance with college academic responsibilities.\",\"score\":5},{\"category\":\"Faculty & College Management\",\"question_text\":\"Communicates college policies and academic requirements clearly.\",\"score\":4},{\"category\":\"Faculty & College Management\",\"question_text\":\"Coordinates college schedules, activities, and academic programs effectively.\",\"score\":5},{\"category\":\"Faculty & College Management\",\"question_text\":\"Promotes a professional and supportive environment for college students and faculty.\",\"score\":4},{\"category\":\"Faculty & College Management\",\"question_text\":\"Demonstrates accountability in managing college academic operations.\",\"score\":4}]', '2026-10-07 16:15:22'),
(17, 7, 164, 217, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'school_head', NULL, 'submitted', 4.59, 'N/A', '2026-09-30 22:12:48', 78, 17, '[{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates punctuality and preparedness for classes\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Treats students fairly, respectfully, and professionally.\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Maintains a positive and conducive learning environment.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Communicates effectively with students and fellow faculty members.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates responsibility in performing teaching and academic duties.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Effective teaching?\",\"score\":4}]', '2026-10-07 16:15:22'),
(18, 7, 165, 222, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'school_head', NULL, 'submitted', 4.53, 'N/A', '2026-09-30 22:13:41', 77, 17, '[{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates punctuality and preparedness for classes\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Treats students fairly, respectfully, and professionally.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Maintains a positive and conducive learning environment.\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Communicates effectively with students and fellow faculty members.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates responsibility in performing teaching and academic duties.\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Effective teaching?\",\"score\":4}]', '2026-10-07 16:15:22'),
(19, 7, 166, 225, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'school_head', NULL, 'submitted', 4.65, 'N/A', '2026-09-30 22:15:35', 79, 17, '[{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates punctuality and preparedness for classes\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Treats students fairly, respectfully, and professionally.\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Maintains a positive and conducive learning environment.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Communicates effectively with students and fellow faculty members.\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates responsibility in performing teaching and academic duties.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Effective teaching?\",\"score\":5}]', '2026-10-07 16:15:22'),
(20, 7, 167, 230, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'school_head', NULL, 'submitted', 4.47, 'N/A', '2026-09-30 22:15:58', 76, 17, '[{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates punctuality and preparedness for classes\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Treats students fairly, respectfully, and professionally.\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Maintains a positive and conducive learning environment.\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Communicates effectively with students and fellow faculty members.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates responsibility in performing teaching and academic duties.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Effective teaching?\",\"score\":4}]', '2026-10-07 16:15:22'),
(21, 7, 182, 226, 6, '2026-2027', '1st Semester', '2026-2027 — 1st Semester', 'student', NULL, 'submitted', NULL, 'keep it up', '2026-10-04 16:39:22', 33, 10, '[{\"category\":\"General\",\"question_text\":\"Performs maintenance duties responsibly and professionally.\",\"score\":5},{\"category\":\"Maintenance Performance & Service\",\"question_text\":\"Maintains school facilities in clean and functional condition.\",\"score\":4},{\"category\":\"Maintenance Performance & Service\",\"question_text\":\"Responds promptly to maintenance requests.\",\"score\":3},{\"category\":\"Maintenance Performance & Service\",\"question_text\":\"Performs repairs and maintenance tasks properly.\",\"score\":4},{\"category\":\"Maintenance Performance & Service\",\"question_text\":\"Regularly checks facilities for possible problems or hazards.\",\"score\":4},{\"category\":\"Maintenance Performance & Service\",\"question_text\":\"Completes assigned maintenance tasks efficiently.\",\"score\":3},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Uses tools and equipment properly and safely.\",\"score\":2},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Reports facility problems that require further assistance.\",\"score\":3},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Coordinates effectively with school personnel.\",\"score\":2},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Helps maintain a safe environment for students and staff.\",\"score\":3}]', '2026-10-07 16:15:22'),
(22, 9, 168, 217, 7, '2026-2027', '2nd Semester', '2026-2027 — 2nd Semester', 'school_head', NULL, 'submitted', 4.59, 'N/A', '2026-10-01 20:12:04', 78, 17, '[{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates punctuality and preparedness for classes\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Treats students fairly, respectfully, and professionally.\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Maintains a positive and conducive learning environment.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Communicates effectively with students and fellow faculty members.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates responsibility in performing teaching and academic duties.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Effective teaching?\",\"score\":4}]', '2026-10-07 16:15:22'),
(23, 9, 169, 225, 7, '2026-2027', '2nd Semester', '2026-2027 — 2nd Semester', 'school_head', NULL, 'submitted', 4.59, 'N/A', '2026-10-01 20:12:30', 78, 17, '[{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates punctuality and preparedness for classes\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Treats students fairly, respectfully, and professionally.\",\"score\":3},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Maintains a positive and conducive learning environment.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Communicates effectively with students and fellow faculty members.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates responsibility in performing teaching and academic duties.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Effective teaching?\",\"score\":5}]', '2026-10-07 16:15:22'),
(24, 9, 170, 236, 7, '2026-2027', '2nd Semester', '2026-2027 — 2nd Semester', 'school_head', NULL, 'submitted', 4.5, 'N/A', '2026-10-01 20:12:48', 45, 10, '[{\"category\":\"Administrative Support & Coordination\",\"question_text\":\"Provides timely and organized administrative support to school management.\",\"score\":5},{\"category\":\"Administrative Support & Coordination\",\"question_text\":\"Coordinates meetings, schedules, and official activities effectively.\",\"score\":4},{\"category\":\"Administrative Support & Coordination\",\"question_text\":\"Prepares and organizes documents and administrative records accurately.\",\"score\":4},{\"category\":\"Administrative Support & Coordination\",\"question_text\":\"Communicates information and instructions clearly to faculty, staff, and other stakeholders.\",\"score\":5},{\"category\":\"Administrative Support & Coordination\",\"question_text\":\"Responds promptly to administrative requests and concerns.\",\"score\":4},{\"category\":\"Professionalism & Office Management\",\"question_text\":\"Maintains confidentiality of official and sensitive information.\",\"score\":5},{\"category\":\"Professionalism & Office Management\",\"question_text\":\"Demonstrates accuracy and attention to detail in administrative tasks.\",\"score\":4},{\"category\":\"Professionalism & Office Management\",\"question_text\":\"Coordinates effectively with different school offices and personnel.\",\"score\":5},{\"category\":\"Professionalism & Office Management\",\"question_text\":\"Manages assigned responsibilities in an organized and dependable manner.\",\"score\":4},{\"category\":\"Professionalism & Office Management\",\"question_text\":\"Demonstrates professionalism and courtesy when dealing with students, faculty, staff, and administrators.\",\"score\":5}]', '2026-10-07 16:15:22'),
(25, 9, 171, 234, 7, '2026-2027', '2nd Semester', '2026-2027 — 2nd Semester', 'school_head', NULL, 'submitted', 4.7, 'N/A', '2026-10-01 20:13:19', 47, 10, '[{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":4},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Maintains professional relationships with students and colleagues.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Performs teaching and coordination responsibilities responsibly.\",\"score\":4},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Demonstrates commitment to students\' learning and development.\",\"score\":5},{\"category\":\"Teaching Performance & Service\",\"question_text\":\"Explains lessons and instructions clearly.\",\"score\":5},{\"category\":\"Teaching Performance & Service\",\"question_text\":\"Demonstrates adequate knowledge of the subject matter.\",\"score\":4},{\"category\":\"Teaching Performance & Service\",\"question_text\":\"Uses appropriate teaching strategies and learning activities.\",\"score\":5},{\"category\":\"Teaching Performance & Service\",\"question_text\":\"Provides useful feedback on student performance.\",\"score\":5},{\"category\":\"Teaching Performance & Service\",\"question_text\":\"Encourages students to actively participate in learning.\",\"score\":5}]', '2026-10-07 16:15:22'),
(26, 9, 172, 220, 7, '2026-2027', '2nd Semester', '2026-2027 — 2nd Semester', 'student', NULL, 'submitted', NULL, 'good job', '2026-10-04 14:16:12', 47, 17, '[{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates punctuality and preparedness for classes\",\"score\":3},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Treats students fairly, respectfully, and professionally.\",\"score\":2},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Maintains a positive and conducive learning environment.\",\"score\":1},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Communicates effectively with students and fellow faculty members.\",\"score\":2},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates responsibility in performing teaching and academic duties.\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":2},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":1},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":2},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":2},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":2},{\"category\":\"Teaching & Learning\",\"question_text\":\"Effective teaching?\",\"score\":4}]', '2026-10-07 16:15:22'),
(27, 9, 173, 220, 7, '2026-2027', '2nd Semester', '2026-2027 — 2nd Semester', 'student', NULL, 'submitted', NULL, 'Good job!', '2026-10-04 15:01:26', 77, 17, '[{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates punctuality and preparedness for classes\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Treats students fairly, respectfully, and professionally.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Maintains a positive and conducive learning environment.\",\"score\":3},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Communicates effectively with students and fellow faculty members.\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates responsibility in performing teaching and academic duties.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Effective teaching?\",\"score\":5}]', '2026-10-07 16:15:22');
INSERT INTO `portal_feedback_keep` (`id`, `archive_id`, `tracker_id`, `target_user_id`, `period_id`, `school_year`, `semester`, `period_label`, `eval_type`, `peer_group`, `status`, `score`, `remarks`, `submitted_at`, `score_sum`, `score_count`, `answers_json`, `kept_at`) VALUES
(28, 9, 174, 233, 7, '2026-2027', '2nd Semester', '2026-2027 — 2nd Semester', 'student', NULL, 'submitted', NULL, 'Well done!', '2026-10-04 15:04:14', 44, 10, '[{\"category\":\"Financial Performance & Service\",\"question_text\":\"Records financial transactions accurately.\",\"score\":5},{\"category\":\"Financial Performance & Service\",\"question_text\":\"Maintains organized financial records.\",\"score\":4},{\"category\":\"Financial Performance & Service\",\"question_text\":\"Processes financial documents in a timely manner.\",\"score\":5},{\"category\":\"Financial Performance & Service\",\"question_text\":\"Provides accurate financial information when needed.\",\"score\":4},{\"category\":\"Financial Performance & Service\",\"question_text\":\"Properly manages receipts, vouchers, and supporting documents.\",\"score\":3},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Maintains confidentiality of financial information.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Follows established accounting and financial procedures.\",\"score\":4},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Demonstrates attention to detail in financial tasks.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Coordinates effectively with administrators and other personnel.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Handles financial responsibilities honestly and professionally.\",\"score\":4}]', '2026-10-07 16:15:22'),
(29, 9, 175, 232, 7, '2026-2027', '2nd Semester', '2026-2027 — 2nd Semester', 'student', NULL, 'submitted', NULL, 'Good job!', '2026-10-04 15:09:23', 44, 10, '[{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Maintains confidentiality of student information.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Handles documents carefully and responsibly.\",\"score\":4},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Follows established administrative procedures.\",\"score\":4},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Coordinates effectively with other school offices.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Performs assigned responsibilities accurately and professionally.\",\"score\":5},{\"category\":\"Service & Administrative Performance\",\"question_text\":\"Processes student records and documents accurately.\",\"score\":4},{\"category\":\"Service & Administrative Performance\",\"question_text\":\"Provides timely assistance to students and personnel.\",\"score\":5},{\"category\":\"Service & Administrative Performance\",\"question_text\":\"Communicates registration procedures and requirements clearly.\",\"score\":3},{\"category\":\"Service & Administrative Performance\",\"question_text\":\"Maintains organized and updated records.\",\"score\":5},{\"category\":\"Service & Administrative Performance\",\"question_text\":\"Responds effectively to registration-related concerns.\",\"score\":4}]', '2026-10-07 16:15:22'),
(30, 9, 176, 157, 7, '2026-2027', '2nd Semester', '2026-2027 — 2nd Semester', 'student', NULL, 'submitted', NULL, 'N/A', '2026-10-04 15:10:07', 47, 10, '[{\"category\":\"Academic Leadership\",\"question_text\":\"Provides clear direction for the college academic programs.\",\"score\":5},{\"category\":\"Academic Leadership\",\"question_text\":\"Monitors the implementation of college-level curricula and academic requirements.\",\"score\":4},{\"category\":\"Academic Leadership\",\"question_text\":\"Coordinates effectively with college faculty regarding academic matters.\",\"score\":5},{\"category\":\"Academic Leadership\",\"question_text\":\"Addresses academic concerns of college students appropriately.\",\"score\":5},{\"category\":\"Academic Leadership\",\"question_text\":\"Supports the continuous improvement of college teaching and learning.\",\"score\":5},{\"category\":\"Faculty & College Management\",\"question_text\":\"Monitors faculty compliance with college academic responsibilities.\",\"score\":4},{\"category\":\"Faculty & College Management\",\"question_text\":\"Communicates college policies and academic requirements clearly.\",\"score\":5},{\"category\":\"Faculty & College Management\",\"question_text\":\"Coordinates college schedules, activities, and academic programs effectively.\",\"score\":4},{\"category\":\"Faculty & College Management\",\"question_text\":\"Promotes a professional and supportive environment for college students and faculty.\",\"score\":5},{\"category\":\"Faculty & College Management\",\"question_text\":\"Demonstrates accountability in managing college academic operations.\",\"score\":5}]', '2026-10-07 16:15:22'),
(31, 9, 177, 230, 7, '2026-2027', '2nd Semester', '2026-2027 — 2nd Semester', 'student', NULL, 'submitted', NULL, 'Good job!', '2026-10-04 15:10:41', 47, 10, '[{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Maintains confidentiality when handling sensitive concerns.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Coordinates effectively with school personnel during programs and emergencies.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Promotes safety and responsible participation in activities.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Follows established school safety and program procedures.\",\"score\":4},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Performs assigned responsibilities reliably and professionally.\",\"score\":5},{\"category\":\"Program Performance & Service\",\"question_text\":\"Provides appropriate assistance to students and school personnel.\",\"score\":4},{\"category\":\"Program Performance & Service\",\"question_text\":\"Organizes assigned programs and activities effectively.\",\"score\":5},{\"category\":\"Program Performance & Service\",\"question_text\":\"Responds appropriately to student or program-related concerns.\",\"score\":4},{\"category\":\"Program Performance & Service\",\"question_text\":\"Coordinates activities, schedules, and resources efficiently.\",\"score\":5},{\"category\":\"Program Performance & Service\",\"question_text\":\"Supports the successful implementation of school programs.\",\"score\":5}]', '2026-10-07 16:15:22'),
(32, 9, 178, 226, 7, '2026-2027', '2nd Semester', '2026-2027 — 2nd Semester', 'student', NULL, 'submitted', NULL, 'Well done!', '2026-10-04 15:11:05', 44, 10, '[{\"category\":\"General\",\"question_text\":\"Performs maintenance duties responsibly and professionally.\",\"score\":5},{\"category\":\"Maintenance Performance & Service\",\"question_text\":\"Maintains school facilities in clean and functional condition.\",\"score\":4},{\"category\":\"Maintenance Performance & Service\",\"question_text\":\"Responds promptly to maintenance requests.\",\"score\":3},{\"category\":\"Maintenance Performance & Service\",\"question_text\":\"Performs repairs and maintenance tasks properly.\",\"score\":5},{\"category\":\"Maintenance Performance & Service\",\"question_text\":\"Regularly checks facilities for possible problems or hazards.\",\"score\":5},{\"category\":\"Maintenance Performance & Service\",\"question_text\":\"Completes assigned maintenance tasks efficiently.\",\"score\":4},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Uses tools and equipment properly and safely.\",\"score\":4},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Reports facility problems that require further assistance.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Coordinates effectively with school personnel.\",\"score\":4},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Helps maintain a safe environment for students and staff.\",\"score\":5}]', '2026-10-07 16:15:22'),
(33, 9, 179, 218, 7, '2026-2027', '2nd Semester', '2026-2027 — 2nd Semester', 'student', NULL, 'submitted', NULL, 'Good job!', '2026-10-04 15:11:20', 46, 10, '[{\"category\":\"General\",\"question_text\":\"Performs library responsibilities efficiently and professionally.\",\"score\":5},{\"category\":\"Library Service & Performance\",\"question_text\":\"Maintains an organized and orderly library.\",\"score\":5},{\"category\":\"Library Service & Performance\",\"question_text\":\"Assists users in locating appropriate library resources.\",\"score\":4},{\"category\":\"Library Service & Performance\",\"question_text\":\"Maintains accurate borrowing and return records.\",\"score\":5},{\"category\":\"Library Service & Performance\",\"question_text\":\"Keeps library materials properly organized and maintained.\",\"score\":5},{\"category\":\"Library Service & Performance\",\"question_text\":\"Provides helpful and timely assistance to library users.\",\"score\":4},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Enforces library rules fairly and consistently.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Promotes proper care and use of library resources.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Maintains a quiet, safe, and conducive library environment.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Communicates respectfully with students and personnel.\",\"score\":3}]', '2026-10-07 16:15:22'),
(34, 9, 180, 215, 7, '2026-2027', '2nd Semester', '2026-2027 — 2nd Semester', 'student', NULL, 'submitted', NULL, '', '2026-10-04 15:19:14', 45, 10, '[{\"category\":\"Academic Performance & Participation\",\"question_text\":\"Completes academic requirements responsibly.\",\"score\":4},{\"category\":\"Academic Performance & Participation\",\"question_text\":\"Participates actively in classroom and learning activities.\",\"score\":5},{\"category\":\"Academic Performance & Participation\",\"question_text\":\"Demonstrates willingness to learn new knowledge and skills.\",\"score\":5},{\"category\":\"Academic Performance & Participation\",\"question_text\":\"Manages academic responsibilities effectively.\",\"score\":4},{\"category\":\"Academic Performance & Participation\",\"question_text\":\"Applies learned knowledge appropriately during academic activities.\",\"score\":3},{\"category\":\"Conduct & Professionalism\",\"question_text\":\"Demonstrates respect toward teachers, staff, and fellow students.\",\"score\":5},{\"category\":\"Conduct & Professionalism\",\"question_text\":\"Follows school rules and established procedures.\",\"score\":5},{\"category\":\"Conduct & Professionalism\",\"question_text\":\"Communicates appropriately with others.\",\"score\":4},{\"category\":\"Conduct & Professionalism\",\"question_text\":\"Cooperates effectively during group activities.\",\"score\":5},{\"category\":\"Conduct & Professionalism\",\"question_text\":\"Demonstrates responsible and professional behavior.\",\"score\":5}]', '2026-10-07 16:15:22'),
(35, 8, 140, 217, 2, '2026-2027', 'School Year', '2026-2027 — School Year', 'school_head', NULL, 'submitted', 4.91, 'N/A', '2026-09-22 10:58:50', 54, 11, '[{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":5}]', '2026-10-07 16:15:22'),
(36, 8, 141, 158, 2, '2026-2027', 'School Year', '2026-2027 — School Year', 'student', NULL, 'submitted', NULL, 'N/A', '2026-09-22 11:00:39', 0, 0, '[]', '2026-10-07 16:15:22'),
(37, 8, 142, 218, 2, '2026-2027', 'School Year', '2026-2027 — School Year', 'student', NULL, 'submitted', NULL, 'N/A', '2026-09-22 11:01:58', 0, 0, '[]', '2026-10-07 16:15:22'),
(38, 8, 148, 220, 2, '2026-2027', 'School Year', '2026-2027 — School Year', 'student', NULL, 'submitted', NULL, 'Palaging Late', '2026-09-22 11:45:15', 79, 23, '[{\"category\":\"General\",\"question_text\":\"Contributes positively to a collaborative and supportive school environment.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"dsfsdsdfsd\",\"score\":4},{\"category\":\"General\",\"question_text\":\"Collaborates effectively with colleagues in academic activities.\",\"score\":2},{\"category\":\"General\",\"question_text\":\"Shares knowledge, ideas, and teaching resources with other teachers.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Shows willingness to support colleagues in addressing teaching-related concerns.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"dvfdxv\",\"score\":2},{\"category\":\"General\",\"question_text\":\"Demonstrates professionalism and respect toward colleagues.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"Accepts feedback and suggestions from fellow teachers constructively.\",\"score\":3},{\"category\":\"General\",\"question_text\":\"Explains lessons clearly and effectively.\",\"score\":3},{\"category\":\"General\",\"question_text\":\"Handles classroom concerns appropriately.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *\",\"score\":2},{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":1},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":2},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":1},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Clearly explains lessons and course-related concepts.\",\"score\":5}]', '2026-10-07 16:15:22'),
(39, 8, 149, 220, 2, '2026-2027', 'School Year', '2026-2027 — School Year', 'student', NULL, 'submitted', NULL, 'Sleeping', '2026-09-22 11:52:17', 92, 23, '[{\"category\":\"General\",\"question_text\":\"Contributes positively to a collaborative and supportive school environment.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"dsfsdsdfsd\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Collaborates effectively with colleagues in academic activities.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Shares knowledge, ideas, and teaching resources with other teachers.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"Shows willingness to support colleagues in addressing teaching-related concerns.\",\"score\":3},{\"category\":\"General\",\"question_text\":\"dvfdxv\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Demonstrates professionalism and respect toward colleagues.\",\"score\":2},{\"category\":\"General\",\"question_text\":\"Accepts feedback and suggestions from fellow teachers constructively.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"Explains lessons clearly and effectively.\",\"score\":3},{\"category\":\"General\",\"question_text\":\"Handles classroom concerns appropriately.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *\",\"score\":3},{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":1},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Clearly explains lessons and course-related concepts.\",\"score\":5}]', '2026-10-07 16:15:22'),
(40, 8, 150, 217, 2, '2026-2027', 'School Year', '2026-2027 — School Year', 'faculty_peer', 'Faculty', 'submitted', 3.45, '', '2026-09-22 11:58:19', 38, 11, '[{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":2},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":1},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":2},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":4}]', '2026-10-07 16:15:22'),
(41, 8, 151, 158, 2, '2026-2027', 'School Year', '2026-2027 — School Year', 'faculty_peer', 'Principal', 'submitted', 4.75, 'N/A', '2026-09-23 10:29:32', 0, 0, '[]', '2026-10-07 16:15:22'),
(42, 8, 155, 217, 2, '2026-2027', 'School Year', '2026-2027 — School Year', 'student', NULL, 'submitted', NULL, 'N/A', '2026-09-23 15:51:42', 137, 29, '[{\"category\":\"General\",\"question_text\":\"Collaborates effectively with colleagues in academic activities.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Shares knowledge, ideas, and teaching resources with other teachers.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"Shows willingness to support colleagues in addressing teaching-related concerns.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Contributes positively to a collaborative and supportive school environment.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Demonstrates professionalism and respect toward colleagues.\",\"score\":3},{\"category\":\"General\",\"question_text\":\"Accepts feedback and suggestions from fellow teachers constructively.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Explains lessons clearly and effectively.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Handles classroom concerns appropriately.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":5},{\"category\":\"General\",\"question_text\":\"dvfdxv\",\"score\":5},{\"category\":\"General\",\"question_text\":\"dsfsdsdfsd\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates punctuality and preparedness for classes\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Treats students fairly, respectfully, and professionally.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Maintains a positive and conducive learning environment.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Communicates effectively with students and fellow faculty members.\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates responsibility in performing teaching and academic duties.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Clearly explains lessons and course-related concepts.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Effective teaching?\",\"score\":4}]', '2026-10-07 16:15:22'),
(43, 8, 156, 217, 2, '2026-2027', 'School Year', '2026-2027 — School Year', 'student', NULL, 'submitted', NULL, 'N/A', '2026-09-24 09:35:00', 128, 29, '[{\"category\":\"General\",\"question_text\":\"Collaborates effectively with colleagues in academic activities.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Shares knowledge, ideas, and teaching resources with other teachers.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"Shows willingness to support colleagues in addressing teaching-related concerns.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Contributes positively to a collaborative and supportive school environment.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Demonstrates professionalism and respect toward colleagues.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"Accepts feedback and suggestions from fellow teachers constructively.\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Explains lessons clearly and effectively.\",\"score\":4},{\"category\":\"General\",\"question_text\":\"Handles classroom concerns appropriately.\",\"score\":3},{\"category\":\"General\",\"question_text\":\"integrates the school’s Vision, Mission, Objectives and Core Values in the lesson for the day *\",\"score\":5},{\"category\":\"General\",\"question_text\":\"Demonstrates punctuality, excellent attendance *\",\"score\":4},{\"category\":\"General\",\"question_text\":\"dvfdxv\",\"score\":5},{\"category\":\"General\",\"question_text\":\"dsfsdsdfsd\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Attends In every Class\",\"score\":3},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates punctuality and preparedness for classes\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Treats students fairly, respectfully, and professionally.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Maintains a positive and conducive learning environment.\",\"score\":5},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Communicates effectively with students and fellow faculty members.\",\"score\":4},{\"category\":\"Professionalism & Student Support\",\"question_text\":\"Demonstrates responsibility in performing teaching and academic duties.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Presents learning objectives clearly.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Uses appropriate teaching strategies and methods.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Provides clear instructions for activities and assignments.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Encourages active participation during class discussions.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Maintains proper classroom discipline.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"Creates a positive and respectful learning environment.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Manages classroom activities effectively.\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Treats students fairly and respectfully.\",\"score\":4},{\"category\":\"Teaching & Learning\",\"question_text\":\"testing\",\"score\":5},{\"category\":\"Teaching & Learning\",\"question_text\":\"Clearly explains lessons and course-related concepts.\",\"score\":3},{\"category\":\"Teaching & Learning\",\"question_text\":\"Effective teaching?\",\"score\":5}]', '2026-10-07 16:15:22'),
(44, 8, 161, 236, 2, '2026-2027', 'School Year', '2026-2027 — School Year', 'staff', 'Staff Evaluation', 'submitted', 4.4, 'N/A', '2026-09-28 16:00:48', 44, 10, '[{\"category\":\"Administrative Support & Coordination\",\"question_text\":\"Provides timely and organized administrative support to school management.\",\"score\":5},{\"category\":\"Administrative Support & Coordination\",\"question_text\":\"Coordinates meetings, schedules, and official activities effectively.\",\"score\":4},{\"category\":\"Administrative Support & Coordination\",\"question_text\":\"Prepares and organizes documents and administrative records accurately.\",\"score\":4},{\"category\":\"Administrative Support & Coordination\",\"question_text\":\"Communicates information and instructions clearly to faculty, staff, and other stakeholders.\",\"score\":5},{\"category\":\"Administrative Support & Coordination\",\"question_text\":\"Responds promptly to administrative requests and concerns.\",\"score\":3},{\"category\":\"Professionalism & Office Management\",\"question_text\":\"Maintains confidentiality of official and sensitive information.\",\"score\":5},{\"category\":\"Professionalism & Office Management\",\"question_text\":\"Demonstrates accuracy and attention to detail in administrative tasks.\",\"score\":5},{\"category\":\"Professionalism & Office Management\",\"question_text\":\"Coordinates effectively with different school offices and personnel.\",\"score\":4},{\"category\":\"Professionalism & Office Management\",\"question_text\":\"Manages assigned responsibilities in an organized and dependable manner.\",\"score\":5},{\"category\":\"Professionalism & Office Management\",\"question_text\":\"Demonstrates professionalism and courtesy when dealing with students, faculty, staff, and administrators.\",\"score\":4}]', '2026-10-07 16:15:22'),
(45, 8, 181, 233, 2, '2026-2027', 'School Year', '2026-2027 — School Year', 'student', NULL, 'submitted', NULL, 'Good job!', '2026-10-04 15:56:34', 45, 10, '[{\"category\":\"Financial Performance & Service\",\"question_text\":\"Records financial transactions accurately.\",\"score\":5},{\"category\":\"Financial Performance & Service\",\"question_text\":\"Maintains organized financial records.\",\"score\":4},{\"category\":\"Financial Performance & Service\",\"question_text\":\"Processes financial documents in a timely manner.\",\"score\":5},{\"category\":\"Financial Performance & Service\",\"question_text\":\"Provides accurate financial information when needed.\",\"score\":4},{\"category\":\"Financial Performance & Service\",\"question_text\":\"Properly manages receipts, vouchers, and supporting documents.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Maintains confidentiality of financial information.\",\"score\":4},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Follows established accounting and financial procedures.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Demonstrates attention to detail in financial tasks.\",\"score\":3},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Coordinates effectively with administrators and other personnel.\",\"score\":5},{\"category\":\"Professionalism & Responsibility\",\"question_text\":\"Handles financial responsibilities honestly and professionally.\",\"score\":5}]', '2026-10-07 16:15:22');

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
(1457, 183, NULL, 'user', 529, NULL, 5.00, '2026-10-07 16:25:22', NULL),
(1458, 183, NULL, 'user', 530, NULL, 4.00, '2026-10-07 16:25:22', NULL),
(1459, 183, NULL, 'user', 531, NULL, 5.00, '2026-10-07 16:25:22', NULL),
(1460, 183, NULL, 'user', 532, NULL, 4.00, '2026-10-07 16:25:22', NULL),
(1461, 183, NULL, 'user', 533, NULL, 5.00, '2026-10-07 16:25:22', NULL),
(1462, 183, NULL, 'user', 534, NULL, 4.00, '2026-10-07 16:25:22', NULL),
(1463, 183, NULL, 'user', 535, NULL, 5.00, '2026-10-07 16:25:22', NULL),
(1464, 183, NULL, 'user', 536, NULL, 4.00, '2026-10-07 16:25:22', NULL),
(1465, 183, NULL, 'user', 537, NULL, 5.00, '2026-10-07 16:25:22', NULL),
(1466, 183, NULL, 'user', 538, NULL, 4.00, '2026-10-07 16:25:22', NULL),
(1467, 184, NULL, 'user', 529, NULL, 5.00, '2026-10-07 16:50:17', NULL),
(1468, 184, NULL, 'user', 530, NULL, 4.00, '2026-10-07 16:50:17', NULL),
(1469, 184, NULL, 'user', 531, NULL, 5.00, '2026-10-07 16:50:17', NULL),
(1470, 184, NULL, 'user', 532, NULL, 4.00, '2026-10-07 16:50:17', NULL),
(1471, 184, NULL, 'user', 533, NULL, 5.00, '2026-10-07 16:50:17', NULL),
(1472, 184, NULL, 'user', 534, NULL, 3.00, '2026-10-07 16:50:17', NULL),
(1473, 184, NULL, 'user', 535, NULL, 5.00, '2026-10-07 16:50:17', NULL),
(1474, 184, NULL, 'user', 536, NULL, 4.00, '2026-10-07 16:50:17', NULL),
(1475, 184, NULL, 'user', 537, NULL, 5.00, '2026-10-07 16:50:17', NULL),
(1476, 184, NULL, 'user', 538, NULL, 5.00, '2026-10-07 16:50:17', NULL),
(1477, 185, 262, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:48:40', NULL),
(1478, 185, 266, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:48:40', NULL),
(1479, 185, 268, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:48:40', NULL),
(1480, 185, 269, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:48:40', NULL),
(1481, 185, 270, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:48:40', NULL),
(1482, 185, 271, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:48:40', NULL),
(1483, 185, 272, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:48:40', NULL),
(1484, 185, 251, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:48:40', NULL),
(1485, 185, 252, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:48:40', NULL),
(1486, 185, 253, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:48:40', NULL),
(1487, 185, 254, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:48:40', NULL),
(1488, 185, 255, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:48:40', NULL),
(1489, 185, 256, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:48:40', NULL),
(1490, 185, 257, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:48:40', NULL),
(1491, 185, 258, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:48:40', NULL),
(1492, 185, 260, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:48:40', NULL),
(1493, 185, 267, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:48:40', NULL),
(1494, 186, 262, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:51:38', NULL),
(1495, 186, 266, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:51:38', NULL),
(1496, 186, 268, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:51:38', NULL),
(1497, 186, 269, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:51:38', NULL),
(1498, 186, 270, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:51:38', NULL),
(1499, 186, 271, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:51:38', NULL),
(1500, 186, 272, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:51:38', NULL),
(1501, 186, 251, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:51:38', NULL),
(1502, 186, 252, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:51:38', NULL),
(1503, 186, 253, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:51:38', NULL),
(1504, 186, 254, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:51:38', NULL),
(1505, 186, 255, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:51:38', NULL),
(1506, 186, 256, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:51:38', NULL),
(1507, 186, 257, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:51:38', NULL),
(1508, 186, 258, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:51:38', NULL),
(1509, 186, 260, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:51:38', NULL),
(1510, 186, 267, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:51:38', NULL),
(1511, 187, 262, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:52:24', NULL),
(1512, 187, 266, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:52:24', NULL),
(1513, 187, 268, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:52:24', NULL),
(1514, 187, 269, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:52:24', NULL),
(1515, 187, 270, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:52:24', NULL),
(1516, 187, 271, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:52:24', NULL),
(1517, 187, 272, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:52:24', NULL),
(1518, 187, 251, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:52:24', NULL),
(1519, 187, 252, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:52:24', NULL),
(1520, 187, 253, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:52:24', NULL),
(1521, 187, 254, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:52:24', NULL),
(1522, 187, 255, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:52:24', NULL),
(1523, 187, 256, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:52:24', NULL),
(1524, 187, 257, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:52:24', NULL),
(1525, 187, 258, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:52:24', NULL),
(1526, 187, 260, 'evaluation', NULL, NULL, 4.00, '2026-10-09 21:52:24', NULL),
(1527, 187, 267, 'evaluation', NULL, NULL, 5.00, '2026-10-09 21:52:24', NULL),
(1528, 188, NULL, 'user', 475, NULL, 5.00, '2026-10-09 21:52:46', NULL),
(1529, 188, NULL, 'user', 476, NULL, 4.00, '2026-10-09 21:52:46', NULL),
(1530, 188, NULL, 'user', 477, NULL, 5.00, '2026-10-09 21:52:46', NULL),
(1531, 188, NULL, 'user', 478, NULL, 4.00, '2026-10-09 21:52:46', NULL),
(1532, 188, NULL, 'user', 479, NULL, 5.00, '2026-10-09 21:52:46', NULL),
(1533, 188, NULL, 'user', 483, NULL, 4.00, '2026-10-09 21:52:46', NULL),
(1534, 188, NULL, 'user', 484, NULL, 5.00, '2026-10-09 21:52:46', NULL),
(1535, 188, NULL, 'user', 485, NULL, 4.00, '2026-10-09 21:52:46', NULL),
(1536, 188, NULL, 'user', 486, NULL, 5.00, '2026-10-09 21:52:46', NULL),
(1537, 188, NULL, 'user', 487, NULL, 5.00, '2026-10-09 21:52:46', NULL),
(1538, 189, NULL, 'user', 470, NULL, 5.00, '2026-10-09 21:56:06', NULL),
(1539, 189, NULL, 'user', 471, NULL, 4.00, '2026-10-09 21:56:06', NULL),
(1540, 189, NULL, 'user', 472, NULL, 5.00, '2026-10-09 21:56:06', NULL),
(1541, 189, NULL, 'user', 473, NULL, 4.00, '2026-10-09 21:56:06', NULL),
(1542, 189, NULL, 'user', 474, NULL, 5.00, '2026-10-09 21:56:06', NULL),
(1543, 189, NULL, 'user', 465, NULL, 4.00, '2026-10-09 21:56:06', NULL),
(1544, 189, NULL, 'user', 466, NULL, 5.00, '2026-10-09 21:56:06', NULL),
(1545, 189, NULL, 'user', 467, NULL, 4.00, '2026-10-09 21:56:06', NULL),
(1546, 189, NULL, 'user', 468, NULL, 5.00, '2026-10-09 21:56:06', NULL),
(1547, 189, NULL, 'user', 469, NULL, 4.00, '2026-10-09 21:56:06', NULL),
(1548, 190, NULL, 'user', 549, NULL, 5.00, '2026-10-09 21:56:55', NULL),
(1549, 190, NULL, 'user', 550, NULL, 4.00, '2026-10-09 21:56:55', NULL),
(1550, 190, NULL, 'user', 551, NULL, 5.00, '2026-10-09 21:56:55', NULL),
(1551, 190, NULL, 'user', 552, NULL, 4.00, '2026-10-09 21:56:55', NULL),
(1552, 190, NULL, 'user', 553, NULL, 4.00, '2026-10-09 21:56:55', NULL),
(1553, 190, NULL, 'user', 554, NULL, 5.00, '2026-10-09 21:56:55', NULL),
(1554, 190, NULL, 'user', 555, NULL, 5.00, '2026-10-09 21:56:55', NULL),
(1555, 190, NULL, 'user', 556, NULL, 5.00, '2026-10-09 21:56:55', NULL),
(1556, 190, NULL, 'user', 557, NULL, 4.00, '2026-10-09 21:56:55', NULL),
(1557, 190, NULL, 'user', 558, NULL, 5.00, '2026-10-09 21:56:55', NULL),
(1558, 191, NULL, 'user', 475, NULL, 5.00, '2026-10-09 22:38:00', NULL),
(1559, 191, NULL, 'user', 476, NULL, 4.00, '2026-10-09 22:38:00', NULL),
(1560, 191, NULL, 'user', 477, NULL, 4.00, '2026-10-09 22:38:00', NULL),
(1561, 191, NULL, 'user', 478, NULL, 5.00, '2026-10-09 22:38:00', NULL),
(1562, 191, NULL, 'user', 479, NULL, 5.00, '2026-10-09 22:38:00', NULL),
(1563, 191, NULL, 'user', 483, NULL, 4.00, '2026-10-09 22:38:00', NULL),
(1564, 191, NULL, 'user', 484, NULL, 5.00, '2026-10-09 22:38:00', NULL),
(1565, 191, NULL, 'user', 485, NULL, 5.00, '2026-10-09 22:38:00', NULL),
(1566, 191, NULL, 'user', 486, NULL, 4.00, '2026-10-09 22:38:00', NULL),
(1567, 191, NULL, 'user', 487, NULL, 5.00, '2026-10-09 22:38:00', NULL),
(1568, 192, 262, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:33:18', NULL),
(1569, 192, 266, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:33:18', NULL),
(1570, 192, 268, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:33:18', NULL),
(1571, 192, 269, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:33:18', NULL),
(1572, 192, 270, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:33:18', NULL),
(1573, 192, 271, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:33:18', NULL),
(1574, 192, 272, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:33:18', NULL),
(1575, 192, 251, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:33:18', NULL),
(1576, 192, 252, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:33:18', NULL),
(1577, 192, 253, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:33:18', NULL),
(1578, 192, 254, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:33:18', NULL),
(1579, 192, 255, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:33:18', NULL),
(1580, 192, 256, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:33:18', NULL),
(1581, 192, 257, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:33:18', NULL),
(1582, 192, 258, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:33:18', NULL),
(1583, 192, 260, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:33:18', NULL),
(1584, 192, 267, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:33:18', NULL),
(1585, 193, 262, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:55:10', NULL),
(1586, 193, 266, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:55:10', NULL),
(1587, 193, 268, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:55:10', NULL),
(1588, 193, 269, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:55:10', NULL),
(1589, 193, 270, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:55:10', NULL),
(1590, 193, 271, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:55:10', NULL),
(1591, 193, 272, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:55:10', NULL),
(1592, 193, 251, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:55:10', NULL),
(1593, 193, 252, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:55:10', NULL),
(1594, 193, 253, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:55:10', NULL),
(1595, 193, 254, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:55:10', NULL),
(1596, 193, 255, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:55:10', NULL),
(1597, 193, 256, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:55:10', NULL),
(1598, 193, 257, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:55:10', NULL),
(1599, 193, 258, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:55:10', NULL),
(1600, 193, 260, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:55:10', NULL),
(1601, 193, 267, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:55:10', NULL),
(1602, 194, 262, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:57:41', NULL),
(1603, 194, 266, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:57:41', NULL),
(1604, 194, 268, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:57:41', NULL),
(1605, 194, 269, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:57:41', NULL),
(1606, 194, 270, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:57:41', NULL),
(1607, 194, 271, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:57:41', NULL),
(1608, 194, 272, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:57:41', NULL),
(1609, 194, 251, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:57:41', NULL),
(1610, 194, 252, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:57:41', NULL),
(1611, 194, 253, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:57:41', NULL),
(1612, 194, 254, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:57:41', NULL),
(1613, 194, 255, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:57:41', NULL),
(1614, 194, 256, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:57:41', NULL),
(1615, 194, 257, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:57:41', NULL),
(1616, 194, 258, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:57:41', NULL),
(1617, 194, 260, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:57:41', NULL),
(1618, 194, 267, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:57:41', NULL),
(1619, 195, 262, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:58:22', NULL),
(1620, 195, 266, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:58:22', NULL),
(1621, 195, 268, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:58:22', NULL),
(1622, 195, 269, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:58:22', NULL),
(1623, 195, 270, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:58:22', NULL),
(1624, 195, 271, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:58:22', NULL),
(1625, 195, 272, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:58:22', NULL),
(1626, 195, 251, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:58:22', NULL),
(1627, 195, 252, 'evaluation', NULL, NULL, 3.00, '2026-10-09 23:58:22', NULL),
(1628, 195, 253, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:58:22', NULL),
(1629, 195, 254, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:58:22', NULL),
(1630, 195, 255, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:58:22', NULL),
(1631, 195, 256, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:58:22', NULL),
(1632, 195, 257, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:58:22', NULL),
(1633, 195, 258, 'evaluation', NULL, NULL, 4.00, '2026-10-09 23:58:22', NULL),
(1634, 195, 260, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:58:22', NULL),
(1635, 195, 267, 'evaluation', NULL, NULL, 5.00, '2026-10-09 23:58:22', NULL),
(1636, 196, 262, 'evaluation', NULL, NULL, 5.00, '2026-10-10 00:01:10', NULL),
(1637, 196, 266, 'evaluation', NULL, NULL, 4.00, '2026-10-10 00:01:10', NULL),
(1638, 196, 268, 'evaluation', NULL, NULL, 5.00, '2026-10-10 00:01:10', NULL),
(1639, 196, 269, 'evaluation', NULL, NULL, 4.00, '2026-10-10 00:01:10', NULL),
(1640, 196, 270, 'evaluation', NULL, NULL, 3.00, '2026-10-10 00:01:10', NULL),
(1641, 196, 271, 'evaluation', NULL, NULL, 5.00, '2026-10-10 00:01:10', NULL),
(1642, 196, 272, 'evaluation', NULL, NULL, 5.00, '2026-10-10 00:01:10', NULL),
(1643, 196, 251, 'evaluation', NULL, NULL, 4.00, '2026-10-10 00:01:10', NULL),
(1644, 196, 252, 'evaluation', NULL, NULL, 5.00, '2026-10-10 00:01:10', NULL),
(1645, 196, 253, 'evaluation', NULL, NULL, 5.00, '2026-10-10 00:01:10', NULL),
(1646, 196, 254, 'evaluation', NULL, NULL, 4.00, '2026-10-10 00:01:10', NULL),
(1647, 196, 255, 'evaluation', NULL, NULL, 5.00, '2026-10-10 00:01:10', NULL),
(1648, 196, 256, 'evaluation', NULL, NULL, 5.00, '2026-10-10 00:01:10', NULL),
(1649, 196, 257, 'evaluation', NULL, NULL, 4.00, '2026-10-10 00:01:10', NULL),
(1650, 196, 258, 'evaluation', NULL, NULL, 5.00, '2026-10-10 00:01:10', NULL),
(1651, 196, 260, 'evaluation', NULL, NULL, 5.00, '2026-10-10 00:01:10', NULL),
(1652, 196, 267, 'evaluation', NULL, NULL, 5.00, '2026-10-10 00:01:10', NULL),
(1653, 197, NULL, 'user', 549, NULL, 5.00, '2026-10-10 00:22:06', NULL),
(1654, 197, NULL, 'user', 550, NULL, 4.00, '2026-10-10 00:22:06', NULL),
(1655, 197, NULL, 'user', 551, NULL, 5.00, '2026-10-10 00:22:06', NULL),
(1656, 197, NULL, 'user', 552, NULL, 5.00, '2026-10-10 00:22:06', NULL),
(1657, 197, NULL, 'user', 553, NULL, 4.00, '2026-10-10 00:22:06', NULL),
(1658, 197, NULL, 'user', 554, NULL, 5.00, '2026-10-10 00:22:06', NULL),
(1659, 197, NULL, 'user', 555, NULL, 5.00, '2026-10-10 00:22:06', NULL),
(1660, 197, NULL, 'user', 556, NULL, 4.00, '2026-10-10 00:22:06', NULL),
(1661, 197, NULL, 'user', 557, NULL, 5.00, '2026-10-10 00:22:06', NULL),
(1662, 197, NULL, 'user', 558, NULL, 5.00, '2026-10-10 00:22:06', NULL),
(1663, 198, NULL, 'user', 549, NULL, 5.00, '2026-10-10 00:26:15', NULL),
(1664, 198, NULL, 'user', 550, NULL, 4.00, '2026-10-10 00:26:15', NULL),
(1665, 198, NULL, 'user', 551, NULL, 5.00, '2026-10-10 00:26:15', NULL),
(1666, 198, NULL, 'user', 552, NULL, 4.00, '2026-10-10 00:26:15', NULL),
(1667, 198, NULL, 'user', 553, NULL, 5.00, '2026-10-10 00:26:15', NULL),
(1668, 198, NULL, 'user', 554, NULL, 5.00, '2026-10-10 00:26:15', NULL),
(1669, 198, NULL, 'user', 555, NULL, 5.00, '2026-10-10 00:26:15', NULL),
(1670, 198, NULL, 'user', 556, NULL, 5.00, '2026-10-10 00:26:15', NULL),
(1671, 198, NULL, 'user', 557, NULL, 4.00, '2026-10-10 00:26:15', NULL),
(1672, 198, NULL, 'user', 558, NULL, 5.00, '2026-10-10 00:26:15', NULL),
(1673, 199, NULL, 'user', 549, NULL, 5.00, '2026-10-10 00:28:41', NULL),
(1674, 199, NULL, 'user', 550, NULL, 4.00, '2026-10-10 00:28:41', NULL),
(1675, 199, NULL, 'user', 551, NULL, 5.00, '2026-10-10 00:28:41', NULL),
(1676, 199, NULL, 'user', 552, NULL, 5.00, '2026-10-10 00:28:41', NULL),
(1677, 199, NULL, 'user', 553, NULL, 5.00, '2026-10-10 00:28:41', NULL),
(1678, 199, NULL, 'user', 554, NULL, 4.00, '2026-10-10 00:28:41', NULL),
(1679, 199, NULL, 'user', 555, NULL, 5.00, '2026-10-10 00:28:41', NULL),
(1680, 199, NULL, 'user', 556, NULL, 4.00, '2026-10-10 00:28:41', NULL),
(1681, 199, NULL, 'user', 557, NULL, 5.00, '2026-10-10 00:28:41', NULL),
(1682, 199, NULL, 'user', 558, NULL, 5.00, '2026-10-10 00:28:41', NULL),
(1683, 200, 262, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:02:54', NULL),
(1684, 200, 266, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:02:54', NULL),
(1685, 200, 268, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:02:54', NULL),
(1686, 200, 269, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:02:54', NULL),
(1687, 200, 270, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:02:54', NULL),
(1688, 200, 271, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:02:54', NULL),
(1689, 200, 272, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:02:54', NULL),
(1690, 200, 251, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:02:54', NULL),
(1691, 200, 252, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:02:54', NULL),
(1692, 200, 253, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:02:54', NULL),
(1693, 200, 254, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:02:54', NULL),
(1694, 200, 255, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:02:54', NULL),
(1695, 200, 256, 'evaluation', NULL, NULL, 3.00, '2026-10-10 01:02:54', NULL),
(1696, 200, 257, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:02:54', NULL),
(1697, 200, 258, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:02:54', NULL),
(1698, 200, 260, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:02:54', NULL),
(1699, 200, 267, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:02:54', NULL),
(1700, 201, 262, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:08:59', NULL),
(1701, 201, 266, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:08:59', NULL),
(1702, 201, 268, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:08:59', NULL),
(1703, 201, 269, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:08:59', NULL),
(1704, 201, 270, 'evaluation', NULL, NULL, 3.00, '2026-10-10 01:08:59', NULL),
(1705, 201, 271, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:08:59', NULL),
(1706, 201, 272, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:08:59', NULL),
(1707, 201, 251, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:08:59', NULL),
(1708, 201, 252, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:08:59', NULL),
(1709, 201, 253, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:08:59', NULL),
(1710, 201, 254, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:08:59', NULL),
(1711, 201, 255, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:08:59', NULL),
(1712, 201, 256, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:08:59', NULL),
(1713, 201, 257, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:08:59', NULL),
(1714, 201, 258, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:08:59', NULL),
(1715, 201, 260, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:08:59', NULL),
(1716, 201, 267, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:08:59', NULL),
(1717, 202, 262, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:10:01', NULL),
(1718, 202, 266, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:10:01', NULL),
(1719, 202, 268, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:10:01', NULL),
(1720, 202, 269, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:10:01', NULL),
(1721, 202, 270, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:10:01', NULL),
(1722, 202, 271, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:10:01', NULL),
(1723, 202, 272, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:10:01', NULL),
(1724, 202, 251, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:10:01', NULL),
(1725, 202, 252, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:10:01', NULL),
(1726, 202, 253, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:10:01', NULL),
(1727, 202, 254, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:10:01', NULL),
(1728, 202, 255, 'evaluation', NULL, NULL, 3.00, '2026-10-10 01:10:01', NULL),
(1729, 202, 256, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:10:01', NULL),
(1730, 202, 257, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:10:01', NULL),
(1731, 202, 258, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:10:01', NULL),
(1732, 202, 260, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:10:01', NULL),
(1733, 202, 267, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:10:01', NULL),
(1734, 203, 262, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:12:19', NULL),
(1735, 203, 266, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:12:19', NULL),
(1736, 203, 268, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:12:19', NULL),
(1737, 203, 269, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:12:19', NULL),
(1738, 203, 270, 'evaluation', NULL, NULL, 3.00, '2026-10-10 01:12:19', NULL),
(1739, 203, 271, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:12:19', NULL),
(1740, 203, 272, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:12:19', NULL),
(1741, 203, 251, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:12:19', NULL),
(1742, 203, 252, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:12:19', NULL),
(1743, 203, 253, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:12:19', NULL),
(1744, 203, 254, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:12:19', NULL),
(1745, 203, 255, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:12:19', NULL),
(1746, 203, 256, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:12:19', NULL),
(1747, 203, 257, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:12:19', NULL),
(1748, 203, 258, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:12:19', NULL),
(1749, 203, 260, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:12:19', NULL),
(1750, 203, 267, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:12:19', NULL),
(1751, 204, 262, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:13:17', NULL),
(1752, 204, 266, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:13:17', NULL),
(1753, 204, 268, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:13:17', NULL),
(1754, 204, 269, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:13:17', NULL),
(1755, 204, 270, 'evaluation', NULL, NULL, 3.00, '2026-10-10 01:13:17', NULL),
(1756, 204, 271, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:13:17', NULL),
(1757, 204, 272, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:13:17', NULL),
(1758, 204, 251, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:13:17', NULL),
(1759, 204, 252, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:13:17', NULL),
(1760, 204, 253, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:13:17', NULL),
(1761, 204, 254, 'evaluation', NULL, NULL, 3.00, '2026-10-10 01:13:17', NULL),
(1762, 204, 255, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:13:17', NULL),
(1763, 204, 256, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:13:17', NULL),
(1764, 204, 257, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:13:17', NULL),
(1765, 204, 258, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:13:17', NULL),
(1766, 204, 260, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:13:17', NULL),
(1767, 204, 267, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:13:17', NULL),
(1768, 205, 262, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:41:52', NULL),
(1769, 205, 266, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:41:52', NULL),
(1770, 205, 268, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:41:52', NULL),
(1771, 205, 269, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:41:52', NULL),
(1772, 205, 270, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:41:52', NULL),
(1773, 205, 271, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:41:52', NULL),
(1774, 205, 272, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:41:52', NULL),
(1775, 205, 251, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:41:52', NULL),
(1776, 205, 252, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:41:52', NULL),
(1777, 205, 253, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:41:52', NULL),
(1778, 205, 254, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:41:52', NULL),
(1779, 205, 255, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:41:52', NULL),
(1780, 205, 256, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:41:52', NULL),
(1781, 205, 257, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:41:52', NULL),
(1782, 205, 258, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:41:52', NULL),
(1783, 205, 260, 'evaluation', NULL, NULL, 3.00, '2026-10-10 01:41:52', NULL),
(1784, 205, 267, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:41:52', NULL),
(1785, 206, 262, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:47:07', NULL),
(1786, 206, 266, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:47:07', NULL),
(1787, 206, 268, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:47:07', NULL),
(1788, 206, 269, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:47:07', NULL),
(1789, 206, 270, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:47:07', NULL),
(1790, 206, 271, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:47:07', NULL),
(1791, 206, 272, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:47:07', NULL),
(1792, 206, 251, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:47:07', NULL),
(1793, 206, 252, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:47:07', NULL),
(1794, 206, 253, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:47:07', NULL),
(1795, 206, 254, 'evaluation', NULL, NULL, 4.00, '2026-10-10 01:47:07', NULL),
(1796, 206, 255, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:47:07', NULL),
(1797, 206, 256, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:47:07', NULL),
(1798, 206, 257, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:47:07', NULL),
(1799, 206, 258, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:47:07', NULL),
(1800, 206, 260, 'evaluation', NULL, NULL, 3.00, '2026-10-10 01:47:07', NULL),
(1801, 206, 267, 'evaluation', NULL, NULL, 5.00, '2026-10-10 01:47:07', NULL),
(1802, 207, NULL, 'user', 549, NULL, 5.00, '2026-10-10 02:03:49', NULL),
(1803, 207, NULL, 'user', 550, NULL, 4.00, '2026-10-10 02:03:49', NULL),
(1804, 207, NULL, 'user', 551, NULL, 5.00, '2026-10-10 02:03:49', NULL),
(1805, 207, NULL, 'user', 552, NULL, 5.00, '2026-10-10 02:03:49', NULL),
(1806, 207, NULL, 'user', 553, NULL, 5.00, '2026-10-10 02:03:49', NULL),
(1807, 207, NULL, 'user', 554, NULL, 4.00, '2026-10-10 02:03:49', NULL),
(1808, 207, NULL, 'user', 555, NULL, 5.00, '2026-10-10 02:03:49', NULL),
(1809, 207, NULL, 'user', 556, NULL, 3.00, '2026-10-10 02:03:49', NULL),
(1810, 207, NULL, 'user', 557, NULL, 5.00, '2026-10-10 02:03:49', NULL),
(1811, 207, NULL, 'user', 558, NULL, 4.00, '2026-10-10 02:03:49', NULL),
(1812, 208, 262, 'evaluation', NULL, NULL, 4.00, '2026-10-10 10:22:57', NULL),
(1813, 208, 266, 'evaluation', NULL, NULL, 5.00, '2026-10-10 10:22:57', NULL),
(1814, 208, 268, 'evaluation', NULL, NULL, 3.00, '2026-10-10 10:22:57', NULL),
(1815, 208, 269, 'evaluation', NULL, NULL, 5.00, '2026-10-10 10:22:57', NULL),
(1816, 208, 270, 'evaluation', NULL, NULL, 2.00, '2026-10-10 10:22:57', NULL),
(1817, 208, 271, 'evaluation', NULL, NULL, 5.00, '2026-10-10 10:22:57', NULL),
(1818, 208, 272, 'evaluation', NULL, NULL, 5.00, '2026-10-10 10:22:57', NULL),
(1819, 208, 251, 'evaluation', NULL, NULL, 4.00, '2026-10-10 10:22:57', NULL),
(1820, 208, 252, 'evaluation', NULL, NULL, 5.00, '2026-10-10 10:22:57', NULL),
(1821, 208, 253, 'evaluation', NULL, NULL, 5.00, '2026-10-10 10:22:57', NULL),
(1822, 208, 254, 'evaluation', NULL, NULL, 3.00, '2026-10-10 10:22:57', NULL),
(1823, 208, 255, 'evaluation', NULL, NULL, 5.00, '2026-10-10 10:22:57', NULL),
(1824, 208, 256, 'evaluation', NULL, NULL, 5.00, '2026-10-10 10:22:57', NULL),
(1825, 208, 257, 'evaluation', NULL, NULL, 5.00, '2026-10-10 10:22:57', NULL),
(1826, 208, 258, 'evaluation', NULL, NULL, 4.00, '2026-10-10 10:22:57', NULL),
(1827, 208, 260, 'evaluation', NULL, NULL, 5.00, '2026-10-10 10:22:57', NULL),
(1828, 208, 267, 'evaluation', NULL, NULL, 5.00, '2026-10-10 10:22:57', NULL),
(1829, 209, NULL, 'user', 549, NULL, 5.00, '2026-10-10 10:25:40', NULL),
(1830, 209, NULL, 'user', 550, NULL, 4.00, '2026-10-10 10:25:40', NULL),
(1831, 209, NULL, 'user', 551, NULL, 2.00, '2026-10-10 10:25:40', NULL),
(1832, 209, NULL, 'user', 552, NULL, 5.00, '2026-10-10 10:25:40', NULL),
(1833, 209, NULL, 'user', 553, NULL, 4.00, '2026-10-10 10:25:40', NULL),
(1834, 209, NULL, 'user', 554, NULL, 3.00, '2026-10-10 10:25:40', NULL),
(1835, 209, NULL, 'user', 555, NULL, 5.00, '2026-10-10 10:25:40', NULL),
(1836, 209, NULL, 'user', 556, NULL, 5.00, '2026-10-10 10:25:40', NULL),
(1837, 209, NULL, 'user', 557, NULL, 5.00, '2026-10-10 10:25:40', NULL),
(1838, 209, NULL, 'user', 558, NULL, 4.00, '2026-10-10 10:25:40', NULL);

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
(4389, 'Faculty', 'Professionalism & Student Support', 'general', 0, '2026-09-22 10:46:18', 'shared'),
(4391, 'Faculty', 'Cooperaton', 'general', 0, '2026-10-04 16:36:41', 'shared');

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
(38, 234, NULL, NULL, 'staff', 'staff', 'Personnel', 'VE/CLE COORDINATOR/ HS TEACHER', '2026-09-23 11:52:55'),
(39, 222, NULL, NULL, 'faculty', 'faculty', 'Teacher/ ESC/ SHSVP/ TSS Encoder YEARBOOK IN CHARGE', 'Personnel', '2026-10-09 13:21:55'),
(40, 222, NULL, NULL, 'faculty', 'faculty', 'Personnel', 'Personnelmn', '2026-10-09 15:45:09'),
(41, 222, NULL, NULL, 'faculty', 'faculty', 'Personnelmn', 'Personnel', '2026-10-09 15:45:13');

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
(36, 240, 3, 'parents_met', '$2y$10$w.U9DzXZoYgq.pdFVfFi9uNzUXBsuQ2RWSXf.D6G8Pf.KuhmbPnly', '2026-09-24 08:22:50'),
(40, 242, 1, 'nickname', '$2y$10$CR7nEl0wE2FrwQT1N.E4CeMn93k1hRm4gXSBHwe7jJ1AwmSxux.L2', '2026-10-09 03:25:54'),
(41, 242, 2, 'fav_food', '$2y$10$ErzzpC5VoiDgrHoLc7YQ5Obwin.dRQczx2kUHaplsCWMazvtZ.J7S', '2026-10-09 03:25:54'),
(42, 242, 3, 'fav_color', '$2y$10$c6kvaPEq7EpETVl/E2xClef1cyEVjNEQQqTgasivlccIrdmGQal5S', '2026-10-09 03:25:54'),
(43, 241, 1, 'nickname', '$2y$10$mDxUI4VJCrCNp9BDwGwIKuOQVP.nQeL2naXJzCh.ZnqK6d4KAiex.', '2026-10-09 05:36:30'),
(44, 241, 2, 'fav_food', '$2y$10$MFy2CB0stw0OUoLbW.BOOu7TyuH4ZYx.xo0WT1AVYIj2eN3d7MfLe', '2026-10-09 05:36:30'),
(45, 241, 3, 'fav_color', '$2y$10$rGX.vLny0soyaxP4q46cXeZzBE8I20/M/6fZNqLbLxmLXUY9apgbW', '2026-10-09 05:36:30'),
(46, 243, 1, 'nickname', '$2y$10$d5azxTPjs21fRyx/z2a94.sOrnad3k1RwqngsyeEKGptlnSH.pHAe', '2026-10-09 13:47:46'),
(47, 243, 2, 'fav_fruit', '$2y$10$PSYzv7dQX615GfV4xKzcyOhjbjGz/0ZFNdC3RHFaPttcmQOgYbTfa', '2026-10-09 13:47:46'),
(48, 243, 3, 'fav_drink', '$2y$10$g3twCDXXBDDDHhqyrtWQVOP6FHOYNMvRC.YoepLeAsHmOKinYy9hm', '2026-10-09 13:47:46'),
(49, 244, 1, 'called_by', '$2y$10$87b3Rl6a82o1YAZx.WKsgeHqTTZpvKWYTVNJXkmWG2j2z9qhLAogi', '2026-10-09 17:44:51'),
(50, 244, 2, 'fav_color', '$2y$10$bYif9fmNkcDvQCRwTdtuPOmEeXwtEdCiNjwJkYhd6mxRTcQdJ4qdy', '2026-10-09 17:44:51'),
(51, 244, 3, 'fav_food', '$2y$10$eaBSnRP4NJXOdrjqv37CYOfHEnyo5AhLjb8aA5UmFzeyWgK0TScAq', '2026-10-09 17:44:51'),
(52, 245, 1, 'fav_sport', '$2y$10$BrtVONj8Gfw9uhJHAowKt.btrM8fMIhr70Y1iVI6z4TMnNSw8b3eu', '2026-10-10 02:16:44'),
(53, 245, 2, 'fav_place', '$2y$10$4mmfM2RhHSpBvnGCjl.Vv.TV8QKjExK7rA9tz/IWB4sv9/OVw5/92', '2026-10-10 02:16:44'),
(54, 245, 3, 'hometown', '$2y$10$FU7c2gQN2Tf449a182P9vepJ8bvHBZOssyGLk2iZAwCkCtXM5YTp6', '2026-10-10 02:16:44');

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
(7, 6, '2026-2027 — 1st Semester', '2026-2027', 236, 'Lorraine R. Sabay', '2026-10-09 21:41:23', NULL, NULL, 'archived', 278, '{\"evaluation_tracker\":21,\"questionnaire_answers\":257,\"evaluation_answers\":0,\"evaluation_submissions\":0,\"evaluation_results\":0,\"peer_evaluation_submissions\":0,\"peer_evaluation_results\":0,\"evaluation_reminders\":0,\"analytics_reports\":0,\"notifications\":0,\"activity_log_snapshot\":61}', '{\"evaluation_tracker\":[{\"id\":\"138\",\"legacy_submission_id\":null,\"evaluator_id\":\"236\",\"target_user_id\":\"218\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"ea\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 10:49:47\",\"updated_at\":\"2026-09-24 14:36:32\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"139\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"236\",\"eval_bucket\":\"EA\",\"level\":\"college\",\"form_type\":\"school_head_dean_ea\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":\"4.75\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 10:51:05\",\"updated_at\":\"2026-09-24 14:36:32\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"143\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"218\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:05:51\",\"updated_at\":\"2026-09-22 11:05:51\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"144\",\"legacy_submission_id\":null,\"evaluator_id\":\"218\",\"target_user_id\":\"236\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"7\",\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":\"5.00\",\"remarks\":\"N/A\",\"eval_type\":\"staff\",\"peer_group\":\"Staff Evaluation\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:09:59\",\"updated_at\":\"2026-09-24 14:36:32\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"145\",\"legacy_submission_id\":null,\"evaluator_id\":\"217\",\"target_user_id\":\"216\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":\"5.00\",\"remarks\":\"N/A\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Faculty\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:14:00\",\"updated_at\":\"2026-09-22 11:14:00\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"146\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"215\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:19:31\",\"updated_at\":\"2026-09-22 11:19:31\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"147\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"157\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:20:04\",\"updated_at\":\"2026-09-22 11:20:04\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"152\",\"legacy_submission_id\":null,\"evaluator_id\":\"236\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":null,\"remarks\":\"\",\"eval_type\":\"ea\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-23 14:22:44\",\"updated_at\":\"2026-09-24 14:36:32\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"153\",\"legacy_submission_id\":null,\"evaluator_id\":\"222\",\"target_user_id\":\"216\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":\"4.53\",\"remarks\":\"\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Faculty\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-23 14:35:12\",\"updated_at\":\"2026-09-23 14:35:12\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"154\",\"legacy_submission_id\":null,\"evaluator_id\":\"227\",\"target_user_id\":\"216\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":\"4.06\",\"remarks\":\"N/A\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Faculty\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-23 14:38:24\",\"updated_at\":\"2026-09-23 14:38:24\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"157\",\"legacy_submission_id\":null,\"evaluator_id\":\"235\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-24 09:38:06\",\"updated_at\":\"2026-09-24 09:38:06\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"158\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-24 09:38:47\",\"updated_at\":\"2026-09-24 09:38:47\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"159\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"225\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-24 16:39:57\",\"updated_at\":\"2026-09-24 16:39:57\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"160\",\"legacy_submission_id\":null,\"evaluator_id\":\"233\",\"target_user_id\":\"236\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"7\",\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":\"4.30\",\"remarks\":\"N/A\",\"eval_type\":\"staff\",\"peer_group\":\"Staff Evaluation\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-28 15:58:31\",\"updated_at\":\"2026-09-28 15:58:31\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"162\",\"legacy_submission_id\":null,\"evaluator_id\":\"237\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-28 22:00:21\",\"updated_at\":\"2026-09-28 22:00:21\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"163\",\"legacy_submission_id\":null,\"evaluator_id\":\"236\",\"target_user_id\":\"157\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"ea\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-30 21:43:44\",\"updated_at\":\"2026-09-30 21:43:44\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"164\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":\"college\",\"form_type\":\"school_head_dean_faculty\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":\"4.59\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-30 22:12:48\",\"updated_at\":\"2026-09-30 22:12:48\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"165\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"222\",\"eval_bucket\":\"Faculty\",\"level\":\"college\",\"form_type\":\"school_head_dean_faculty\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":\"4.53\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-30 22:13:41\",\"updated_at\":\"2026-09-30 22:13:41\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"166\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"225\",\"eval_bucket\":\"Faculty\",\"level\":\"college\",\"form_type\":\"school_head_dean_faculty\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":\"4.65\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-30 22:15:35\",\"updated_at\":\"2026-09-30 22:15:35\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"167\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"230\",\"eval_bucket\":\"Faculty\",\"level\":\"college\",\"form_type\":\"school_head_dean_faculty\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":\"4.47\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-30 22:15:58\",\"updated_at\":\"2026-09-30 22:15:58\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"182\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"226\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"6\",\"score\":null,\"remarks\":\"keep it up\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-10-04 16:39:22\",\"updated_at\":\"2026-10-04 16:39:22\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"}],\"questionnaire_answers\":[{\"id\":\"881\",\"tracker_id\":\"145\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:14:00\",\"comments\":null},{\"id\":\"882\",\"tracker_id\":\"145\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:14:00\",\"comments\":null},{\"id\":\"883\",\"tracker_id\":\"145\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:14:00\",\"comments\":null},{\"id\":\"884\",\"tracker_id\":\"145\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:14:00\",\"comments\":null},{\"id\":\"885\",\"tracker_id\":\"145\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:14:00\",\"comments\":null},{\"id\":\"886\",\"tracker_id\":\"145\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:14:00\",\"comments\":null},{\"id\":\"887\",\"tracker_id\":\"145\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:14:00\",\"comments\":null},{\"id\":\"888\",\"tracker_id\":\"145\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:14:00\",\"comments\":null},{\"id\":\"889\",\"tracker_id\":\"145\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:14:00\",\"comments\":null},{\"id\":\"890\",\"tracker_id\":\"145\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:14:00\",\"comments\":null},{\"id\":\"891\",\"tracker_id\":\"145\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:14:00\",\"comments\":null},{\"id\":\"975\",\"tracker_id\":\"152\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"529\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-23 14:22:44\",\"comments\":null},{\"id\":\"976\",\"tracker_id\":\"152\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"530\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:22:44\",\"comments\":null},{\"id\":\"977\",\"tracker_id\":\"152\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"531\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:22:44\",\"comments\":null},{\"id\":\"978\",\"tracker_id\":\"152\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"532\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-23 14:22:44\",\"comments\":null},{\"id\":\"979\",\"tracker_id\":\"152\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"533\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:22:44\",\"comments\":null},{\"id\":\"980\",\"tracker_id\":\"152\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"534\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:22:44\",\"comments\":null},{\"id\":\"981\",\"tracker_id\":\"152\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"535\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:22:44\",\"comments\":null},{\"id\":\"982\",\"tracker_id\":\"152\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"536\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:22:44\",\"comments\":null},{\"id\":\"983\",\"tracker_id\":\"152\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"537\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-23 14:22:44\",\"comments\":null},{\"id\":\"984\",\"tracker_id\":\"152\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"538\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:22:44\",\"comments\":null},{\"id\":\"985\",\"tracker_id\":\"153\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"986\",\"tracker_id\":\"153\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"987\",\"tracker_id\":\"153\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"988\",\"tracker_id\":\"153\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"989\",\"tracker_id\":\"153\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"990\",\"tracker_id\":\"153\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"991\",\"tracker_id\":\"153\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"992\",\"tracker_id\":\"153\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"993\",\"tracker_id\":\"153\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"994\",\"tracker_id\":\"153\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"995\",\"tracker_id\":\"153\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"996\",\"tracker_id\":\"153\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"997\",\"tracker_id\":\"153\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"998\",\"tracker_id\":\"153\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"999\",\"tracker_id\":\"153\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"1000\",\"tracker_id\":\"153\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"1001\",\"tracker_id\":\"153\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:35:12\",\"comments\":null},{\"id\":\"1002\",\"tracker_id\":\"154\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1003\",\"tracker_id\":\"154\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1004\",\"tracker_id\":\"154\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1005\",\"tracker_id\":\"154\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1006\",\"tracker_id\":\"154\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1007\",\"tracker_id\":\"154\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1008\",\"tracker_id\":\"154\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1009\",\"tracker_id\":\"154\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1010\",\"tracker_id\":\"154\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1011\",\"tracker_id\":\"154\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1012\",\"tracker_id\":\"154\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1013\",\"tracker_id\":\"154\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1014\",\"tracker_id\":\"154\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1015\",\"tracker_id\":\"154\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1016\",\"tracker_id\":\"154\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1017\",\"tracker_id\":\"154\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1018\",\"tracker_id\":\"154\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 14:38:24\",\"comments\":null},{\"id\":\"1077\",\"tracker_id\":\"157\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1078\",\"tracker_id\":\"157\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1079\",\"tracker_id\":\"157\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1080\",\"tracker_id\":\"157\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1081\",\"tracker_id\":\"157\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1082\",\"tracker_id\":\"157\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1083\",\"tracker_id\":\"157\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1084\",\"tracker_id\":\"157\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1085\",\"tracker_id\":\"157\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1086\",\"tracker_id\":\"157\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1087\",\"tracker_id\":\"157\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1088\",\"tracker_id\":\"157\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1089\",\"tracker_id\":\"157\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1090\",\"tracker_id\":\"157\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1091\",\"tracker_id\":\"157\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1092\",\"tracker_id\":\"157\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1093\",\"tracker_id\":\"157\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1094\",\"tracker_id\":\"157\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1095\",\"tracker_id\":\"157\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1096\",\"tracker_id\":\"157\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1097\",\"tracker_id\":\"157\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1098\",\"tracker_id\":\"157\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1099\",\"tracker_id\":\"157\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1100\",\"tracker_id\":\"157\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1101\",\"tracker_id\":\"157\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1102\",\"tracker_id\":\"157\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1103\",\"tracker_id\":\"157\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1104\",\"tracker_id\":\"157\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1105\",\"tracker_id\":\"157\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:06\",\"comments\":null},{\"id\":\"1106\",\"tracker_id\":\"158\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1107\",\"tracker_id\":\"158\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1108\",\"tracker_id\":\"158\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1109\",\"tracker_id\":\"158\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1110\",\"tracker_id\":\"158\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1111\",\"tracker_id\":\"158\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1112\",\"tracker_id\":\"158\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1113\",\"tracker_id\":\"158\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1114\",\"tracker_id\":\"158\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1115\",\"tracker_id\":\"158\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1116\",\"tracker_id\":\"158\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1117\",\"tracker_id\":\"158\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1118\",\"tracker_id\":\"158\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1119\",\"tracker_id\":\"158\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1120\",\"tracker_id\":\"158\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1121\",\"tracker_id\":\"158\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1122\",\"tracker_id\":\"158\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1123\",\"tracker_id\":\"158\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1124\",\"tracker_id\":\"158\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1125\",\"tracker_id\":\"158\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1126\",\"tracker_id\":\"158\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1127\",\"tracker_id\":\"158\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1128\",\"tracker_id\":\"158\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1129\",\"tracker_id\":\"158\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1130\",\"tracker_id\":\"158\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1131\",\"tracker_id\":\"158\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1132\",\"tracker_id\":\"158\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1133\",\"tracker_id\":\"158\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1134\",\"tracker_id\":\"158\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:38:47\",\"comments\":null},{\"id\":\"1135\",\"tracker_id\":\"159\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1136\",\"tracker_id\":\"159\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1137\",\"tracker_id\":\"159\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1138\",\"tracker_id\":\"159\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1139\",\"tracker_id\":\"159\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1140\",\"tracker_id\":\"159\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1141\",\"tracker_id\":\"159\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1142\",\"tracker_id\":\"159\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1143\",\"tracker_id\":\"159\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1144\",\"tracker_id\":\"159\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1145\",\"tracker_id\":\"159\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1146\",\"tracker_id\":\"159\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1147\",\"tracker_id\":\"159\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1148\",\"tracker_id\":\"159\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1149\",\"tracker_id\":\"159\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1150\",\"tracker_id\":\"159\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1151\",\"tracker_id\":\"159\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1152\",\"tracker_id\":\"159\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1153\",\"tracker_id\":\"159\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1154\",\"tracker_id\":\"159\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1155\",\"tracker_id\":\"159\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1156\",\"tracker_id\":\"159\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1157\",\"tracker_id\":\"159\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1158\",\"tracker_id\":\"159\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1159\",\"tracker_id\":\"159\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1160\",\"tracker_id\":\"159\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1161\",\"tracker_id\":\"159\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1162\",\"tracker_id\":\"159\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1163\",\"tracker_id\":\"159\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 16:39:57\",\"comments\":null},{\"id\":\"1164\",\"tracker_id\":\"160\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"549\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 15:58:31\",\"comments\":null},{\"id\":\"1165\",\"tracker_id\":\"160\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"550\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 15:58:31\",\"comments\":null},{\"id\":\"1166\",\"tracker_id\":\"160\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"551\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 15:58:31\",\"comments\":null},{\"id\":\"1167\",\"tracker_id\":\"160\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"552\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 15:58:31\",\"comments\":null},{\"id\":\"1168\",\"tracker_id\":\"160\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"553\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 15:58:31\",\"comments\":null},{\"id\":\"1169\",\"tracker_id\":\"160\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"554\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 15:58:31\",\"comments\":null},{\"id\":\"1170\",\"tracker_id\":\"160\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"555\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 15:58:31\",\"comments\":null},{\"id\":\"1171\",\"tracker_id\":\"160\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"556\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 15:58:31\",\"comments\":null},{\"id\":\"1172\",\"tracker_id\":\"160\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"557\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-28 15:58:31\",\"comments\":null},{\"id\":\"1173\",\"tracker_id\":\"160\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"558\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 15:58:31\",\"comments\":null},{\"id\":\"1184\",\"tracker_id\":\"162\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1185\",\"tracker_id\":\"162\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1186\",\"tracker_id\":\"162\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1187\",\"tracker_id\":\"162\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1188\",\"tracker_id\":\"162\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1189\",\"tracker_id\":\"162\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1190\",\"tracker_id\":\"162\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1191\",\"tracker_id\":\"162\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1192\",\"tracker_id\":\"162\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1193\",\"tracker_id\":\"162\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1194\",\"tracker_id\":\"162\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1195\",\"tracker_id\":\"162\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1196\",\"tracker_id\":\"162\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1197\",\"tracker_id\":\"162\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1198\",\"tracker_id\":\"162\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1199\",\"tracker_id\":\"162\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1200\",\"tracker_id\":\"162\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 22:00:21\",\"comments\":null},{\"id\":\"1201\",\"tracker_id\":\"163\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"539\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 21:43:44\",\"comments\":null},{\"id\":\"1202\",\"tracker_id\":\"163\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"540\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 21:43:44\",\"comments\":null},{\"id\":\"1203\",\"tracker_id\":\"163\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"541\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-30 21:43:44\",\"comments\":null},{\"id\":\"1204\",\"tracker_id\":\"163\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"542\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 21:43:44\",\"comments\":null},{\"id\":\"1205\",\"tracker_id\":\"163\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"543\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 21:43:44\",\"comments\":null},{\"id\":\"1206\",\"tracker_id\":\"163\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"544\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 21:43:44\",\"comments\":null},{\"id\":\"1207\",\"tracker_id\":\"163\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"545\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 21:43:44\",\"comments\":null},{\"id\":\"1208\",\"tracker_id\":\"163\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"546\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 21:43:44\",\"comments\":null},{\"id\":\"1209\",\"tracker_id\":\"163\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"547\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 21:43:44\",\"comments\":null},{\"id\":\"1210\",\"tracker_id\":\"163\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"548\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 21:43:44\",\"comments\":null},{\"id\":\"1211\",\"tracker_id\":\"164\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1212\",\"tracker_id\":\"164\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1213\",\"tracker_id\":\"164\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1214\",\"tracker_id\":\"164\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1215\",\"tracker_id\":\"164\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1216\",\"tracker_id\":\"164\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1217\",\"tracker_id\":\"164\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1218\",\"tracker_id\":\"164\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1219\",\"tracker_id\":\"164\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1220\",\"tracker_id\":\"164\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1221\",\"tracker_id\":\"164\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1222\",\"tracker_id\":\"164\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1223\",\"tracker_id\":\"164\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1224\",\"tracker_id\":\"164\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1225\",\"tracker_id\":\"164\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1226\",\"tracker_id\":\"164\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1227\",\"tracker_id\":\"164\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:12:48\",\"comments\":null},{\"id\":\"1228\",\"tracker_id\":\"165\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1229\",\"tracker_id\":\"165\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1230\",\"tracker_id\":\"165\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1231\",\"tracker_id\":\"165\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1232\",\"tracker_id\":\"165\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1233\",\"tracker_id\":\"165\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1234\",\"tracker_id\":\"165\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1235\",\"tracker_id\":\"165\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1236\",\"tracker_id\":\"165\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1237\",\"tracker_id\":\"165\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1238\",\"tracker_id\":\"165\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1239\",\"tracker_id\":\"165\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1240\",\"tracker_id\":\"165\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1241\",\"tracker_id\":\"165\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1242\",\"tracker_id\":\"165\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1243\",\"tracker_id\":\"165\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1244\",\"tracker_id\":\"165\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:13:41\",\"comments\":null},{\"id\":\"1245\",\"tracker_id\":\"166\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1246\",\"tracker_id\":\"166\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1247\",\"tracker_id\":\"166\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1248\",\"tracker_id\":\"166\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1249\",\"tracker_id\":\"166\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1250\",\"tracker_id\":\"166\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1251\",\"tracker_id\":\"166\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1252\",\"tracker_id\":\"166\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1253\",\"tracker_id\":\"166\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1254\",\"tracker_id\":\"166\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1255\",\"tracker_id\":\"166\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1256\",\"tracker_id\":\"166\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1257\",\"tracker_id\":\"166\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1258\",\"tracker_id\":\"166\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1259\",\"tracker_id\":\"166\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1260\",\"tracker_id\":\"166\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1261\",\"tracker_id\":\"166\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:35\",\"comments\":null},{\"id\":\"1262\",\"tracker_id\":\"167\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1263\",\"tracker_id\":\"167\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1264\",\"tracker_id\":\"167\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1265\",\"tracker_id\":\"167\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1266\",\"tracker_id\":\"167\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1267\",\"tracker_id\":\"167\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1268\",\"tracker_id\":\"167\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1269\",\"tracker_id\":\"167\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1270\",\"tracker_id\":\"167\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1271\",\"tracker_id\":\"167\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1272\",\"tracker_id\":\"167\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1273\",\"tracker_id\":\"167\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1274\",\"tracker_id\":\"167\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1275\",\"tracker_id\":\"167\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1276\",\"tracker_id\":\"167\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1277\",\"tracker_id\":\"167\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1278\",\"tracker_id\":\"167\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-30 22:15:58\",\"comments\":null},{\"id\":\"1447\",\"tracker_id\":\"182\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"508\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 16:39:22\",\"comments\":null},{\"id\":\"1448\",\"tracker_id\":\"182\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"499\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 16:39:22\",\"comments\":null},{\"id\":\"1449\",\"tracker_id\":\"182\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"500\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-10-04 16:39:22\",\"comments\":null},{\"id\":\"1450\",\"tracker_id\":\"182\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"501\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 16:39:22\",\"comments\":null},{\"id\":\"1451\",\"tracker_id\":\"182\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"502\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 16:39:22\",\"comments\":null},{\"id\":\"1452\",\"tracker_id\":\"182\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"503\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-10-04 16:39:22\",\"comments\":null},{\"id\":\"1453\",\"tracker_id\":\"182\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"504\",\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-10-04 16:39:22\",\"comments\":null},{\"id\":\"1454\",\"tracker_id\":\"182\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"505\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-10-04 16:39:22\",\"comments\":null},{\"id\":\"1455\",\"tracker_id\":\"182\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"506\",\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-10-04 16:39:22\",\"comments\":null},{\"id\":\"1456\",\"tracker_id\":\"182\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"507\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-10-04 16:39:22\",\"comments\":null}],\"evaluation_answers\":[],\"evaluation_submissions\":[],\"evaluation_results\":[],\"peer_evaluation_submissions\":[],\"peer_evaluation_results\":[],\"evaluation_reminders\":[],\"analytics_reports\":[],\"notifications\":[],\"activity_log_snapshot\":[{\"id\":\"79\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #7)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 14:49:18\"},{\"id\":\"80\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #9)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 14:49:18\"},{\"id\":\"81\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #8)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 14:49:18\"},{\"id\":\"82\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #9 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 14:49:24\"},{\"id\":\"83\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #8 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 14:49:24\"},{\"id\":\"84\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #7 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 14:49:25\"},{\"id\":\"85\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #7)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 15:30:15\"},{\"id\":\"86\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #9)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 15:30:15\"},{\"id\":\"87\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #8)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 15:30:15\"},{\"id\":\"88\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #9 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 15:30:33\"},{\"id\":\"89\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #8 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 15:30:34\"},{\"id\":\"90\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #7 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 15:30:34\"},{\"id\":\"91\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #7)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 15:33:57\"},{\"id\":\"92\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #9)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 15:33:57\"},{\"id\":\"93\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #8)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 15:33:57\"},{\"id\":\"94\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #9 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 15:36:39\"},{\"id\":\"95\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #8 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 15:36:39\"},{\"id\":\"96\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #7 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 15:36:39\"},{\"id\":\"97\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #7)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 15:38:42\"},{\"id\":\"98\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #9)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 15:38:42\"},{\"id\":\"99\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #8)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 15:38:42\"},{\"id\":\"100\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #9 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 15:41:44\"},{\"id\":\"101\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #8 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 15:41:44\"},{\"id\":\"102\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #7 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 15:41:44\"},{\"id\":\"103\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #7)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 15:50:17\"},{\"id\":\"104\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #9)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 15:50:17\"},{\"id\":\"105\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #8)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 15:50:17\"},{\"id\":\"106\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #9 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 15:54:48\"},{\"id\":\"107\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #8 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 15:54:48\"},{\"id\":\"108\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #7 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 15:54:48\"},{\"id\":\"109\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #7)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 15:54:54\"},{\"id\":\"110\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #8)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 15:54:57\"},{\"id\":\"111\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #8 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 15:58:36\"},{\"id\":\"112\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #7 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 15:58:38\"},{\"id\":\"113\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #7)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 16:00:18\"},{\"id\":\"114\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #9)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 16:00:18\"},{\"id\":\"115\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #8)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 16:00:18\"},{\"id\":\"116\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #9 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 16:03:42\"},{\"id\":\"117\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #8 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 16:03:42\"},{\"id\":\"118\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #7 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 16:03:42\"},{\"id\":\"119\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #7)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 16:03:52\"},{\"id\":\"120\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #9)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 16:03:52\"},{\"id\":\"121\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #8)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 16:03:52\"},{\"id\":\"122\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #9 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 16:05:51\"},{\"id\":\"123\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #8 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 16:05:51\"},{\"id\":\"124\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #7 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 16:05:52\"},{\"id\":\"125\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #7)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 16:15:22\"},{\"id\":\"126\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #9)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 16:15:22\"},{\"id\":\"127\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #8)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 16:15:22\"},{\"id\":\"128\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #9 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 16:22:04\"},{\"id\":\"129\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #8 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 16:22:04\"},{\"id\":\"130\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #7 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 16:22:04\"},{\"id\":\"131\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #7)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 16:23:45\"},{\"id\":\"132\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #9)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 16:23:45\"},{\"id\":\"133\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #8)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 16:23:45\"},{\"id\":\"134\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #9 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 16:26:40\"},{\"id\":\"135\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #7 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 16:26:40\"},{\"id\":\"136\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #7)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 16:48:13\"},{\"id\":\"137\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #9)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-07 16:48:13\"},{\"id\":\"138\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #9 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 16:59:34\"},{\"id\":\"139\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #7 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-10-07 16:59:35\"}],\"period_state\":{\"is_active\":1,\"tracking_enabled\":0}}');
INSERT INTO `system_archives` (`id`, `period_id`, `period_label`, `school_year`, `archived_by`, `archived_by_name`, `archived_at`, `restored_at`, `restored_by`, `status`, `record_count`, `summary_json`, `payload_json`) VALUES
(8, 2, '2026-2027 — School Year', '2026-2027', 236, 'Lorraine R. Sabay', '2026-10-07 16:23:45', NULL, NULL, 'archived', 157, '{\"evaluation_tracker\":11,\"questionnaire_answers\":146,\"evaluation_answers\":0,\"evaluation_submissions\":0,\"evaluation_results\":0,\"peer_evaluation_submissions\":0,\"peer_evaluation_results\":0,\"evaluation_reminders\":0,\"analytics_reports\":0,\"notifications\":0,\"activity_log_snapshot\":4}', '{\"evaluation_tracker\":[{\"id\":\"140\",\"legacy_submission_id\":null,\"evaluator_id\":\"158\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":\"\",\"form_type\":\"Principal Evaluation — Faculty\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"4.91\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 10:58:50\",\"updated_at\":\"2026-09-22 10:58:50\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"141\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:00:39\",\"updated_at\":\"2026-09-22 11:00:39\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"142\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"218\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:01:58\",\"updated_at\":\"2026-09-22 11:01:58\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"148\",\"legacy_submission_id\":null,\"evaluator_id\":\"219\",\"target_user_id\":\"220\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"Palaging Late\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:45:15\",\"updated_at\":\"2026-09-22 11:45:15\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"149\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"220\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"Sleeping\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:52:17\",\"updated_at\":\"2026-09-22 11:52:17\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"150\",\"legacy_submission_id\":null,\"evaluator_id\":\"220\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"3.45\",\"remarks\":\"\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Faculty\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:58:19\",\"updated_at\":\"2026-09-22 11:58:19\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"151\",\"legacy_submission_id\":null,\"evaluator_id\":\"217\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"4.75\",\"remarks\":\"N/A\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Principal\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-23 10:29:32\",\"updated_at\":\"2026-09-23 10:29:32\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"155\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-23 15:51:42\",\"updated_at\":\"2026-09-23 15:51:42\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"156\",\"legacy_submission_id\":null,\"evaluator_id\":\"219\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-24 09:35:00\",\"updated_at\":\"2026-09-24 09:35:00\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"161\",\"legacy_submission_id\":null,\"evaluator_id\":\"233\",\"target_user_id\":\"236\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"7\",\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"4.40\",\"remarks\":\"N/A\",\"eval_type\":\"staff\",\"peer_group\":\"Staff Evaluation\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-28 16:00:48\",\"updated_at\":\"2026-09-28 16:00:48\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"181\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"233\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"Good job!\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-10-04 15:56:34\",\"updated_at\":\"2026-10-04 15:56:34\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"}],\"questionnaire_answers\":[{\"id\":\"852\",\"tracker_id\":\"140\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"853\",\"tracker_id\":\"140\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"854\",\"tracker_id\":\"140\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"855\",\"tracker_id\":\"140\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"856\",\"tracker_id\":\"140\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"857\",\"tracker_id\":\"140\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"858\",\"tracker_id\":\"140\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"859\",\"tracker_id\":\"140\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"860\",\"tracker_id\":\"140\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"861\",\"tracker_id\":\"140\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"862\",\"tracker_id\":\"140\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"906\",\"tracker_id\":\"148\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"907\",\"tracker_id\":\"148\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"908\",\"tracker_id\":\"148\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"909\",\"tracker_id\":\"148\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"910\",\"tracker_id\":\"148\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"911\",\"tracker_id\":\"148\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"912\",\"tracker_id\":\"148\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"913\",\"tracker_id\":\"148\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"914\",\"tracker_id\":\"148\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"915\",\"tracker_id\":\"148\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"916\",\"tracker_id\":\"148\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"917\",\"tracker_id\":\"148\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"918\",\"tracker_id\":\"148\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"919\",\"tracker_id\":\"148\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"920\",\"tracker_id\":\"148\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"921\",\"tracker_id\":\"148\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"922\",\"tracker_id\":\"148\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"923\",\"tracker_id\":\"148\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"924\",\"tracker_id\":\"148\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"925\",\"tracker_id\":\"148\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"926\",\"tracker_id\":\"148\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"927\",\"tracker_id\":\"148\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"928\",\"tracker_id\":\"148\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"929\",\"tracker_id\":\"149\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"930\",\"tracker_id\":\"149\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"931\",\"tracker_id\":\"149\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"932\",\"tracker_id\":\"149\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"933\",\"tracker_id\":\"149\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"934\",\"tracker_id\":\"149\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"935\",\"tracker_id\":\"149\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"936\",\"tracker_id\":\"149\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"937\",\"tracker_id\":\"149\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"938\",\"tracker_id\":\"149\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"939\",\"tracker_id\":\"149\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"940\",\"tracker_id\":\"149\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"941\",\"tracker_id\":\"149\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"942\",\"tracker_id\":\"149\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"943\",\"tracker_id\":\"149\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"944\",\"tracker_id\":\"149\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"945\",\"tracker_id\":\"149\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"946\",\"tracker_id\":\"149\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"947\",\"tracker_id\":\"149\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"948\",\"tracker_id\":\"149\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"949\",\"tracker_id\":\"149\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"950\",\"tracker_id\":\"149\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"951\",\"tracker_id\":\"149\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"952\",\"tracker_id\":\"150\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"953\",\"tracker_id\":\"150\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"954\",\"tracker_id\":\"150\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"955\",\"tracker_id\":\"150\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"956\",\"tracker_id\":\"150\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"957\",\"tracker_id\":\"150\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"958\",\"tracker_id\":\"150\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"959\",\"tracker_id\":\"150\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"960\",\"tracker_id\":\"150\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"961\",\"tracker_id\":\"150\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"962\",\"tracker_id\":\"150\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"1019\",\"tracker_id\":\"155\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1020\",\"tracker_id\":\"155\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1021\",\"tracker_id\":\"155\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1022\",\"tracker_id\":\"155\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1023\",\"tracker_id\":\"155\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1024\",\"tracker_id\":\"155\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1025\",\"tracker_id\":\"155\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1026\",\"tracker_id\":\"155\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1027\",\"tracker_id\":\"155\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1028\",\"tracker_id\":\"155\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1029\",\"tracker_id\":\"155\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1030\",\"tracker_id\":\"155\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1031\",\"tracker_id\":\"155\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1032\",\"tracker_id\":\"155\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1033\",\"tracker_id\":\"155\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1034\",\"tracker_id\":\"155\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1035\",\"tracker_id\":\"155\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1036\",\"tracker_id\":\"155\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1037\",\"tracker_id\":\"155\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1038\",\"tracker_id\":\"155\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1039\",\"tracker_id\":\"155\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1040\",\"tracker_id\":\"155\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1041\",\"tracker_id\":\"155\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1042\",\"tracker_id\":\"155\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1043\",\"tracker_id\":\"155\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1044\",\"tracker_id\":\"155\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1045\",\"tracker_id\":\"155\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1046\",\"tracker_id\":\"155\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1047\",\"tracker_id\":\"155\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1048\",\"tracker_id\":\"156\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1049\",\"tracker_id\":\"156\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1050\",\"tracker_id\":\"156\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1051\",\"tracker_id\":\"156\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1052\",\"tracker_id\":\"156\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1053\",\"tracker_id\":\"156\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1054\",\"tracker_id\":\"156\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1055\",\"tracker_id\":\"156\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1056\",\"tracker_id\":\"156\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1057\",\"tracker_id\":\"156\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1058\",\"tracker_id\":\"156\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1059\",\"tracker_id\":\"156\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1060\",\"tracker_id\":\"156\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1061\",\"tracker_id\":\"156\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1062\",\"tracker_id\":\"156\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1063\",\"tracker_id\":\"156\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1064\",\"tracker_id\":\"156\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1065\",\"tracker_id\":\"156\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1066\",\"tracker_id\":\"156\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1067\",\"tracker_id\":\"156\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1068\",\"tracker_id\":\"156\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1069\",\"tracker_id\":\"156\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1070\",\"tracker_id\":\"156\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1071\",\"tracker_id\":\"156\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1072\",\"tracker_id\":\"156\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1073\",\"tracker_id\":\"156\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1074\",\"tracker_id\":\"156\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1075\",\"tracker_id\":\"156\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1076\",\"tracker_id\":\"156\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1174\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"549\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1175\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"550\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1176\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"551\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1177\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"552\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1178\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"553\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1179\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"554\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1180\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"555\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1181\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"556\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1182\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"557\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1183\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"558\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1437\",\"tracker_id\":\"181\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"475\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:56:34\",\"comments\":null},{\"id\":\"1438\",\"tracker_id\":\"181\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"476\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:56:34\",\"comments\":null},{\"id\":\"1439\",\"tracker_id\":\"181\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"477\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:56:34\",\"comments\":null},{\"id\":\"1440\",\"tracker_id\":\"181\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"478\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:56:34\",\"comments\":null},{\"id\":\"1441\",\"tracker_id\":\"181\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"479\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:56:34\",\"comments\":null},{\"id\":\"1442\",\"tracker_id\":\"181\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"483\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:56:34\",\"comments\":null},{\"id\":\"1443\",\"tracker_id\":\"181\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"484\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:56:34\",\"comments\":null},{\"id\":\"1444\",\"tracker_id\":\"181\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"485\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-10-04 15:56:34\",\"comments\":null},{\"id\":\"1445\",\"tracker_id\":\"181\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"486\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:56:34\",\"comments\":null},{\"id\":\"1446\",\"tracker_id\":\"181\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"487\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:56:34\",\"comments\":null}],\"evaluation_answers\":[],\"evaluation_submissions\":[],\"evaluation_results\":[],\"peer_evaluation_submissions\":[],\"peer_evaluation_results\":[],\"evaluation_reminders\":[],\"analytics_reports\":[],\"notifications\":[],\"activity_log_snapshot\":[{\"id\":\"75\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Deleted evaluation archive #8 for 2026-2027 (2026-2027 — School Year)\",\"icon\":\"fa-trash\",\"color\":\"#D6455D\",\"created_at\":\"2026-10-04 16:46:51\"},{\"id\":\"76\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored deleted evaluation archive for 2026-2027 (2026-2027 — School Year) as Archive #8\",\"icon\":\"fa-rotate-left\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-04 16:46:57\"},{\"id\":\"77\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Deleted evaluation archive #8 for 2026-2027 (2026-2027 — School Year)\",\"icon\":\"fa-trash\",\"color\":\"#D6455D\",\"created_at\":\"2026-10-04 16:47:01\"},{\"id\":\"78\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored deleted evaluation archive for 2026-2027 (2026-2027 — School Year) as Archive #8\",\"icon\":\"fa-rotate-left\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-04 16:47:06\"}],\"period_state\":{\"is_active\":0,\"tracking_enabled\":0}}');
INSERT INTO `system_archives` (`id`, `period_id`, `period_label`, `school_year`, `archived_by`, `archived_by_name`, `archived_at`, `restored_at`, `restored_by`, `status`, `record_count`, `summary_json`, `payload_json`) VALUES
(9, 7, '2026-2027 — 2nd Semester', '2026-2027', 236, 'Lorraine R. Sabay', '2026-10-09 21:41:23', NULL, NULL, 'archived', 171, '{\"evaluation_tracker\":13,\"questionnaire_answers\":158,\"evaluation_answers\":0,\"evaluation_submissions\":0,\"evaluation_results\":0,\"peer_evaluation_submissions\":0,\"peer_evaluation_results\":0,\"evaluation_reminders\":0,\"analytics_reports\":0,\"notifications\":0,\"activity_log_snapshot\":4}', '{\"evaluation_tracker\":[{\"id\":\"168\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":\"college\",\"form_type\":\"school_head_dean_faculty\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"7\",\"score\":\"4.59\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-10-01 20:12:04\",\"updated_at\":\"2026-10-01 20:12:04\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"169\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"225\",\"eval_bucket\":\"Faculty\",\"level\":\"college\",\"form_type\":\"school_head_dean_faculty\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"7\",\"score\":\"4.59\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-10-01 20:12:30\",\"updated_at\":\"2026-10-01 20:12:30\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"170\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"236\",\"eval_bucket\":\"EA\",\"level\":\"college\",\"form_type\":\"school_head_dean_ea\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"7\",\"score\":\"4.50\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-10-01 20:12:48\",\"updated_at\":\"2026-10-01 20:12:48\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"171\",\"legacy_submission_id\":null,\"evaluator_id\":\"157\",\"target_user_id\":\"234\",\"eval_bucket\":\"Staff\",\"level\":\"college\",\"form_type\":\"school_head_dean_staff\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"7\",\"score\":\"4.70\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-10-01 20:13:19\",\"updated_at\":\"2026-10-01 20:13:19\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"172\",\"legacy_submission_id\":null,\"evaluator_id\":\"241\",\"target_user_id\":\"220\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"7\",\"score\":null,\"remarks\":\"good job\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-10-04 14:16:12\",\"updated_at\":\"2026-10-04 14:16:12\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"173\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"220\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"7\",\"score\":null,\"remarks\":\"Good job!\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-10-04 15:01:26\",\"updated_at\":\"2026-10-04 15:01:26\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"174\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"233\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"7\",\"score\":null,\"remarks\":\"Well done!\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-10-04 15:04:14\",\"updated_at\":\"2026-10-04 15:04:14\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"175\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"232\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"7\",\"score\":null,\"remarks\":\"Good job!\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-10-04 15:09:23\",\"updated_at\":\"2026-10-04 15:09:23\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"176\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"157\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"7\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-10-04 15:10:07\",\"updated_at\":\"2026-10-04 15:10:07\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"177\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"230\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"7\",\"score\":null,\"remarks\":\"Good job!\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-10-04 15:10:41\",\"updated_at\":\"2026-10-04 15:10:41\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"178\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"226\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"7\",\"score\":null,\"remarks\":\"Well done!\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-10-04 15:11:05\",\"updated_at\":\"2026-10-04 15:11:05\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"179\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"218\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"7\",\"score\":null,\"remarks\":\"Good job!\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-10-04 15:11:20\",\"updated_at\":\"2026-10-04 15:11:20\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"180\",\"legacy_submission_id\":null,\"evaluator_id\":\"175\",\"target_user_id\":\"215\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"7\",\"score\":null,\"remarks\":\"\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-10-04 15:19:14\",\"updated_at\":\"2026-10-04 15:19:14\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"}],\"questionnaire_answers\":[{\"id\":\"1279\",\"tracker_id\":\"168\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1280\",\"tracker_id\":\"168\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1281\",\"tracker_id\":\"168\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1282\",\"tracker_id\":\"168\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1283\",\"tracker_id\":\"168\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1284\",\"tracker_id\":\"168\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1285\",\"tracker_id\":\"168\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1286\",\"tracker_id\":\"168\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1287\",\"tracker_id\":\"168\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1288\",\"tracker_id\":\"168\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1289\",\"tracker_id\":\"168\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1290\",\"tracker_id\":\"168\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1291\",\"tracker_id\":\"168\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1292\",\"tracker_id\":\"168\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1293\",\"tracker_id\":\"168\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1294\",\"tracker_id\":\"168\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1295\",\"tracker_id\":\"168\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:04\",\"comments\":null},{\"id\":\"1296\",\"tracker_id\":\"169\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1297\",\"tracker_id\":\"169\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1298\",\"tracker_id\":\"169\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1299\",\"tracker_id\":\"169\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1300\",\"tracker_id\":\"169\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1301\",\"tracker_id\":\"169\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1302\",\"tracker_id\":\"169\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1303\",\"tracker_id\":\"169\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1304\",\"tracker_id\":\"169\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1305\",\"tracker_id\":\"169\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1306\",\"tracker_id\":\"169\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1307\",\"tracker_id\":\"169\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1308\",\"tracker_id\":\"169\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1309\",\"tracker_id\":\"169\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1310\",\"tracker_id\":\"169\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1311\",\"tracker_id\":\"169\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1312\",\"tracker_id\":\"169\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:30\",\"comments\":null},{\"id\":\"1313\",\"tracker_id\":\"170\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"549\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:48\",\"comments\":null},{\"id\":\"1314\",\"tracker_id\":\"170\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"550\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:48\",\"comments\":null},{\"id\":\"1315\",\"tracker_id\":\"170\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"551\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:48\",\"comments\":null},{\"id\":\"1316\",\"tracker_id\":\"170\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"552\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:48\",\"comments\":null},{\"id\":\"1317\",\"tracker_id\":\"170\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"553\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:48\",\"comments\":null},{\"id\":\"1318\",\"tracker_id\":\"170\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"554\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:48\",\"comments\":null},{\"id\":\"1319\",\"tracker_id\":\"170\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"555\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:48\",\"comments\":null},{\"id\":\"1320\",\"tracker_id\":\"170\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"556\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:48\",\"comments\":null},{\"id\":\"1321\",\"tracker_id\":\"170\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"557\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:12:48\",\"comments\":null},{\"id\":\"1322\",\"tracker_id\":\"170\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"558\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:12:48\",\"comments\":null},{\"id\":\"1323\",\"tracker_id\":\"171\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"460\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:13:19\",\"comments\":null},{\"id\":\"1324\",\"tracker_id\":\"171\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"461\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:13:19\",\"comments\":null},{\"id\":\"1325\",\"tracker_id\":\"171\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"462\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:13:19\",\"comments\":null},{\"id\":\"1326\",\"tracker_id\":\"171\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"463\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:13:19\",\"comments\":null},{\"id\":\"1327\",\"tracker_id\":\"171\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"464\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:13:19\",\"comments\":null},{\"id\":\"1328\",\"tracker_id\":\"171\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"455\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:13:19\",\"comments\":null},{\"id\":\"1329\",\"tracker_id\":\"171\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"456\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-01 20:13:19\",\"comments\":null},{\"id\":\"1330\",\"tracker_id\":\"171\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"457\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:13:19\",\"comments\":null},{\"id\":\"1331\",\"tracker_id\":\"171\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"458\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:13:19\",\"comments\":null},{\"id\":\"1332\",\"tracker_id\":\"171\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"459\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-01 20:13:19\",\"comments\":null},{\"id\":\"1333\",\"tracker_id\":\"172\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1334\",\"tracker_id\":\"172\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1335\",\"tracker_id\":\"172\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1336\",\"tracker_id\":\"172\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1337\",\"tracker_id\":\"172\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1338\",\"tracker_id\":\"172\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1339\",\"tracker_id\":\"172\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1340\",\"tracker_id\":\"172\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1341\",\"tracker_id\":\"172\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1342\",\"tracker_id\":\"172\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1343\",\"tracker_id\":\"172\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1344\",\"tracker_id\":\"172\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1345\",\"tracker_id\":\"172\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1346\",\"tracker_id\":\"172\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1347\",\"tracker_id\":\"172\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1348\",\"tracker_id\":\"172\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1349\",\"tracker_id\":\"172\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 14:16:12\",\"comments\":null},{\"id\":\"1350\",\"tracker_id\":\"173\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1351\",\"tracker_id\":\"173\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1352\",\"tracker_id\":\"173\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1353\",\"tracker_id\":\"173\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1354\",\"tracker_id\":\"173\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1355\",\"tracker_id\":\"173\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1356\",\"tracker_id\":\"173\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1357\",\"tracker_id\":\"173\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1358\",\"tracker_id\":\"173\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1359\",\"tracker_id\":\"173\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1360\",\"tracker_id\":\"173\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1361\",\"tracker_id\":\"173\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1362\",\"tracker_id\":\"173\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1363\",\"tracker_id\":\"173\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1364\",\"tracker_id\":\"173\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1365\",\"tracker_id\":\"173\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1366\",\"tracker_id\":\"173\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:01:26\",\"comments\":null},{\"id\":\"1367\",\"tracker_id\":\"174\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"475\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:04:14\",\"comments\":null},{\"id\":\"1368\",\"tracker_id\":\"174\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"476\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:04:14\",\"comments\":null},{\"id\":\"1369\",\"tracker_id\":\"174\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"477\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:04:14\",\"comments\":null},{\"id\":\"1370\",\"tracker_id\":\"174\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"478\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:04:14\",\"comments\":null},{\"id\":\"1371\",\"tracker_id\":\"174\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"479\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-10-04 15:04:14\",\"comments\":null},{\"id\":\"1372\",\"tracker_id\":\"174\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"483\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:04:14\",\"comments\":null},{\"id\":\"1373\",\"tracker_id\":\"174\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"484\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:04:14\",\"comments\":null},{\"id\":\"1374\",\"tracker_id\":\"174\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"485\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:04:14\",\"comments\":null},{\"id\":\"1375\",\"tracker_id\":\"174\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"486\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:04:14\",\"comments\":null},{\"id\":\"1376\",\"tracker_id\":\"174\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"487\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:04:14\",\"comments\":null},{\"id\":\"1377\",\"tracker_id\":\"175\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"470\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:09:23\",\"comments\":null},{\"id\":\"1378\",\"tracker_id\":\"175\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"471\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:09:23\",\"comments\":null},{\"id\":\"1379\",\"tracker_id\":\"175\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"472\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:09:23\",\"comments\":null},{\"id\":\"1380\",\"tracker_id\":\"175\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"473\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:09:23\",\"comments\":null},{\"id\":\"1381\",\"tracker_id\":\"175\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"474\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:09:23\",\"comments\":null},{\"id\":\"1382\",\"tracker_id\":\"175\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"465\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:09:23\",\"comments\":null},{\"id\":\"1383\",\"tracker_id\":\"175\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"466\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:09:23\",\"comments\":null},{\"id\":\"1384\",\"tracker_id\":\"175\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"467\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-10-04 15:09:23\",\"comments\":null},{\"id\":\"1385\",\"tracker_id\":\"175\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"468\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:09:23\",\"comments\":null},{\"id\":\"1386\",\"tracker_id\":\"175\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"469\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:09:23\",\"comments\":null},{\"id\":\"1387\",\"tracker_id\":\"176\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"539\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:10:07\",\"comments\":null},{\"id\":\"1388\",\"tracker_id\":\"176\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"540\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:10:07\",\"comments\":null},{\"id\":\"1389\",\"tracker_id\":\"176\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"541\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:10:07\",\"comments\":null},{\"id\":\"1390\",\"tracker_id\":\"176\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"542\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:10:07\",\"comments\":null},{\"id\":\"1391\",\"tracker_id\":\"176\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"543\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:10:07\",\"comments\":null},{\"id\":\"1392\",\"tracker_id\":\"176\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"544\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:10:07\",\"comments\":null},{\"id\":\"1393\",\"tracker_id\":\"176\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"545\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:10:07\",\"comments\":null},{\"id\":\"1394\",\"tracker_id\":\"176\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"546\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:10:07\",\"comments\":null},{\"id\":\"1395\",\"tracker_id\":\"176\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"547\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:10:07\",\"comments\":null},{\"id\":\"1396\",\"tracker_id\":\"176\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"548\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:10:07\",\"comments\":null},{\"id\":\"1397\",\"tracker_id\":\"177\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"488\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:10:41\",\"comments\":null},{\"id\":\"1398\",\"tracker_id\":\"177\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"490\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:10:41\",\"comments\":null},{\"id\":\"1399\",\"tracker_id\":\"177\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"491\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:10:41\",\"comments\":null},{\"id\":\"1400\",\"tracker_id\":\"177\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"492\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:10:41\",\"comments\":null},{\"id\":\"1401\",\"tracker_id\":\"177\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"493\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:10:41\",\"comments\":null},{\"id\":\"1402\",\"tracker_id\":\"177\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"494\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:10:41\",\"comments\":null},{\"id\":\"1403\",\"tracker_id\":\"177\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"495\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:10:41\",\"comments\":null},{\"id\":\"1404\",\"tracker_id\":\"177\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"496\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:10:41\",\"comments\":null},{\"id\":\"1405\",\"tracker_id\":\"177\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"497\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:10:41\",\"comments\":null},{\"id\":\"1406\",\"tracker_id\":\"177\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"498\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:10:41\",\"comments\":null},{\"id\":\"1407\",\"tracker_id\":\"178\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"508\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:11:05\",\"comments\":null},{\"id\":\"1408\",\"tracker_id\":\"178\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"499\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:11:05\",\"comments\":null},{\"id\":\"1409\",\"tracker_id\":\"178\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"500\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-10-04 15:11:05\",\"comments\":null},{\"id\":\"1410\",\"tracker_id\":\"178\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"501\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:11:05\",\"comments\":null},{\"id\":\"1411\",\"tracker_id\":\"178\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"502\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:11:05\",\"comments\":null},{\"id\":\"1412\",\"tracker_id\":\"178\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"503\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:11:05\",\"comments\":null},{\"id\":\"1413\",\"tracker_id\":\"178\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"504\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:11:05\",\"comments\":null},{\"id\":\"1414\",\"tracker_id\":\"178\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"505\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:11:05\",\"comments\":null},{\"id\":\"1415\",\"tracker_id\":\"178\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"506\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:11:05\",\"comments\":null},{\"id\":\"1416\",\"tracker_id\":\"178\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"507\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:11:05\",\"comments\":null},{\"id\":\"1417\",\"tracker_id\":\"179\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"518\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:11:20\",\"comments\":null},{\"id\":\"1418\",\"tracker_id\":\"179\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"509\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:11:20\",\"comments\":null},{\"id\":\"1419\",\"tracker_id\":\"179\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"510\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:11:20\",\"comments\":null},{\"id\":\"1420\",\"tracker_id\":\"179\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"511\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:11:20\",\"comments\":null},{\"id\":\"1421\",\"tracker_id\":\"179\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"512\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:11:20\",\"comments\":null},{\"id\":\"1422\",\"tracker_id\":\"179\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"513\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:11:20\",\"comments\":null},{\"id\":\"1423\",\"tracker_id\":\"179\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"514\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:11:20\",\"comments\":null},{\"id\":\"1424\",\"tracker_id\":\"179\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"515\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:11:20\",\"comments\":null},{\"id\":\"1425\",\"tracker_id\":\"179\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"516\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:11:20\",\"comments\":null},{\"id\":\"1426\",\"tracker_id\":\"179\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"517\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-10-04 15:11:20\",\"comments\":null},{\"id\":\"1427\",\"tracker_id\":\"180\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"519\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:19:14\",\"comments\":null},{\"id\":\"1428\",\"tracker_id\":\"180\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"520\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:19:14\",\"comments\":null},{\"id\":\"1429\",\"tracker_id\":\"180\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"521\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:19:14\",\"comments\":null},{\"id\":\"1430\",\"tracker_id\":\"180\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"522\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:19:14\",\"comments\":null},{\"id\":\"1431\",\"tracker_id\":\"180\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"523\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-10-04 15:19:14\",\"comments\":null},{\"id\":\"1432\",\"tracker_id\":\"180\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"524\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:19:14\",\"comments\":null},{\"id\":\"1433\",\"tracker_id\":\"180\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"525\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:19:14\",\"comments\":null},{\"id\":\"1434\",\"tracker_id\":\"180\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"526\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-10-04 15:19:14\",\"comments\":null},{\"id\":\"1435\",\"tracker_id\":\"180\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"527\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:19:14\",\"comments\":null},{\"id\":\"1436\",\"tracker_id\":\"180\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"528\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-10-04 15:19:14\",\"comments\":null}],\"evaluation_answers\":[],\"evaluation_submissions\":[],\"evaluation_results\":[],\"peer_evaluation_submissions\":[],\"peer_evaluation_results\":[],\"evaluation_reminders\":[],\"analytics_reports\":[],\"notifications\":[],\"activity_log_snapshot\":[{\"id\":\"75\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Deleted evaluation archive #8 for 2026-2027 (2026-2027 — School Year)\",\"icon\":\"fa-trash\",\"color\":\"#D6455D\",\"created_at\":\"2026-10-04 16:46:51\"},{\"id\":\"76\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored deleted evaluation archive for 2026-2027 (2026-2027 — School Year) as Archive #8\",\"icon\":\"fa-rotate-left\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-04 16:46:57\"},{\"id\":\"77\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Deleted evaluation archive #8 for 2026-2027 (2026-2027 — School Year)\",\"icon\":\"fa-trash\",\"color\":\"#D6455D\",\"created_at\":\"2026-10-04 16:47:01\"},{\"id\":\"78\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored deleted evaluation archive for 2026-2027 (2026-2027 — School Year) as Archive #8\",\"icon\":\"fa-rotate-left\",\"color\":\"#2563EB\",\"created_at\":\"2026-10-04 16:47:06\"}],\"period_state\":{\"is_active\":0,\"tracking_enabled\":0}}');

-- --------------------------------------------------------

--
-- Table structure for table `system_archive_deletions`
--

CREATE TABLE `system_archive_deletions` (
  `id` int(10) UNSIGNED NOT NULL,
  `archive_id` int(10) UNSIGNED NOT NULL,
  `period_id` int(10) UNSIGNED DEFAULT NULL,
  `period_label` varchar(150) NOT NULL,
  `school_year` varchar(30) NOT NULL,
  `status_at_delete` varchar(20) NOT NULL,
  `record_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  `archived_by_name` varchar(150) DEFAULT NULL,
  `deleted_by` int(10) UNSIGNED DEFAULT NULL,
  `deleted_by_name` varchar(150) DEFAULT NULL,
  `deleted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `archived_by` int(10) UNSIGNED DEFAULT NULL,
  `restored_at` datetime DEFAULT NULL,
  `restored_by` int(10) UNSIGNED DEFAULT NULL,
  `summary_json` longtext DEFAULT NULL,
  `payload_json` longtext DEFAULT NULL,
  `recovered_at` datetime DEFAULT NULL,
  `recovered_by_name` varchar(150) DEFAULT NULL,
  `purged_at` datetime DEFAULT NULL,
  `purge_reason` varchar(20) DEFAULT NULL,
  `purged_by_name` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_archive_deletions`
--

INSERT INTO `system_archive_deletions` (`id`, `archive_id`, `period_id`, `period_label`, `school_year`, `status_at_delete`, `record_count`, `archived_at`, `archived_by_name`, `deleted_by`, `deleted_by_name`, `deleted_at`, `archived_by`, `restored_at`, `restored_by`, `summary_json`, `payload_json`, `recovered_at`, `recovered_by_name`, `purged_at`, `purge_reason`, `purged_by_name`) VALUES
(1, 6, 2, '2026-2027 — School Year', '2026-2027', 'restored', 146, '2026-10-01 11:54:42', 'Lorraine R. Sabay', 236, 'Lorraine R. Sabay', '2026-10-01 11:58:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 8, 2, '2026-2027 — School Year', '2026-2027', 'restored', 146, '2026-10-01 12:29:54', 'Lorraine R. Sabay', 236, 'Lorraine R. Sabay', '2026-10-04 16:46:51', 236, '2026-10-01 13:03:46', 236, '{\"evaluation_tracker\":10,\"questionnaire_answers\":136,\"evaluation_answers\":0,\"evaluation_submissions\":0,\"evaluation_results\":0,\"peer_evaluation_submissions\":0,\"peer_evaluation_results\":0,\"evaluation_reminders\":0,\"analytics_reports\":0,\"notifications\":0,\"activity_log_snapshot\":6}', '{\"evaluation_tracker\":[{\"id\":\"140\",\"legacy_submission_id\":null,\"evaluator_id\":\"158\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":\"\",\"form_type\":\"Principal Evaluation — Faculty\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"4.91\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 10:58:50\",\"updated_at\":\"2026-09-22 10:58:50\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"141\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:00:39\",\"updated_at\":\"2026-09-22 11:00:39\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"142\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"218\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:01:58\",\"updated_at\":\"2026-09-22 11:01:58\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"148\",\"legacy_submission_id\":null,\"evaluator_id\":\"219\",\"target_user_id\":\"220\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"Palaging Late\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:45:15\",\"updated_at\":\"2026-09-22 11:45:15\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"149\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"220\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"Sleeping\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:52:17\",\"updated_at\":\"2026-09-22 11:52:17\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"150\",\"legacy_submission_id\":null,\"evaluator_id\":\"220\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"3.45\",\"remarks\":\"\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Faculty\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:58:19\",\"updated_at\":\"2026-09-22 11:58:19\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"151\",\"legacy_submission_id\":null,\"evaluator_id\":\"217\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"4.75\",\"remarks\":\"N/A\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Principal\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-23 10:29:32\",\"updated_at\":\"2026-09-23 10:29:32\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"155\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-23 15:51:42\",\"updated_at\":\"2026-09-23 15:51:42\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"156\",\"legacy_submission_id\":null,\"evaluator_id\":\"219\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-24 09:35:00\",\"updated_at\":\"2026-09-24 09:35:00\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"161\",\"legacy_submission_id\":null,\"evaluator_id\":\"233\",\"target_user_id\":\"236\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"7\",\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"4.40\",\"remarks\":\"N/A\",\"eval_type\":\"staff\",\"peer_group\":\"Staff Evaluation\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-28 16:00:48\",\"updated_at\":\"2026-09-28 16:00:48\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"}],\"questionnaire_answers\":[{\"id\":\"852\",\"tracker_id\":\"140\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"853\",\"tracker_id\":\"140\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"854\",\"tracker_id\":\"140\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"855\",\"tracker_id\":\"140\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"856\",\"tracker_id\":\"140\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"857\",\"tracker_id\":\"140\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"858\",\"tracker_id\":\"140\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"859\",\"tracker_id\":\"140\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"860\",\"tracker_id\":\"140\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"861\",\"tracker_id\":\"140\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"862\",\"tracker_id\":\"140\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"906\",\"tracker_id\":\"148\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"907\",\"tracker_id\":\"148\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"908\",\"tracker_id\":\"148\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"909\",\"tracker_id\":\"148\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"910\",\"tracker_id\":\"148\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"911\",\"tracker_id\":\"148\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"912\",\"tracker_id\":\"148\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"913\",\"tracker_id\":\"148\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"914\",\"tracker_id\":\"148\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"915\",\"tracker_id\":\"148\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"916\",\"tracker_id\":\"148\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"917\",\"tracker_id\":\"148\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"918\",\"tracker_id\":\"148\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"919\",\"tracker_id\":\"148\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"920\",\"tracker_id\":\"148\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"921\",\"tracker_id\":\"148\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"922\",\"tracker_id\":\"148\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"923\",\"tracker_id\":\"148\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"924\",\"tracker_id\":\"148\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"925\",\"tracker_id\":\"148\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"926\",\"tracker_id\":\"148\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"927\",\"tracker_id\":\"148\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"928\",\"tracker_id\":\"148\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"929\",\"tracker_id\":\"149\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"930\",\"tracker_id\":\"149\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"931\",\"tracker_id\":\"149\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"932\",\"tracker_id\":\"149\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"933\",\"tracker_id\":\"149\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"934\",\"tracker_id\":\"149\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"935\",\"tracker_id\":\"149\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"936\",\"tracker_id\":\"149\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"937\",\"tracker_id\":\"149\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"938\",\"tracker_id\":\"149\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"939\",\"tracker_id\":\"149\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"940\",\"tracker_id\":\"149\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"941\",\"tracker_id\":\"149\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"942\",\"tracker_id\":\"149\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"943\",\"tracker_id\":\"149\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"944\",\"tracker_id\":\"149\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"945\",\"tracker_id\":\"149\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"946\",\"tracker_id\":\"149\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"947\",\"tracker_id\":\"149\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"948\",\"tracker_id\":\"149\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"949\",\"tracker_id\":\"149\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"950\",\"tracker_id\":\"149\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"951\",\"tracker_id\":\"149\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"952\",\"tracker_id\":\"150\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"953\",\"tracker_id\":\"150\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"954\",\"tracker_id\":\"150\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"955\",\"tracker_id\":\"150\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"956\",\"tracker_id\":\"150\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"957\",\"tracker_id\":\"150\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"958\",\"tracker_id\":\"150\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"959\",\"tracker_id\":\"150\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"960\",\"tracker_id\":\"150\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"961\",\"tracker_id\":\"150\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"962\",\"tracker_id\":\"150\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"1019\",\"tracker_id\":\"155\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1020\",\"tracker_id\":\"155\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1021\",\"tracker_id\":\"155\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1022\",\"tracker_id\":\"155\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1023\",\"tracker_id\":\"155\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1024\",\"tracker_id\":\"155\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1025\",\"tracker_id\":\"155\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1026\",\"tracker_id\":\"155\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1027\",\"tracker_id\":\"155\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1028\",\"tracker_id\":\"155\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1029\",\"tracker_id\":\"155\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1030\",\"tracker_id\":\"155\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1031\",\"tracker_id\":\"155\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1032\",\"tracker_id\":\"155\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1033\",\"tracker_id\":\"155\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1034\",\"tracker_id\":\"155\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1035\",\"tracker_id\":\"155\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1036\",\"tracker_id\":\"155\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1037\",\"tracker_id\":\"155\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1038\",\"tracker_id\":\"155\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1039\",\"tracker_id\":\"155\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1040\",\"tracker_id\":\"155\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1041\",\"tracker_id\":\"155\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1042\",\"tracker_id\":\"155\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1043\",\"tracker_id\":\"155\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1044\",\"tracker_id\":\"155\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1045\",\"tracker_id\":\"155\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1046\",\"tracker_id\":\"155\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1047\",\"tracker_id\":\"155\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1048\",\"tracker_id\":\"156\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1049\",\"tracker_id\":\"156\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1050\",\"tracker_id\":\"156\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1051\",\"tracker_id\":\"156\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1052\",\"tracker_id\":\"156\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1053\",\"tracker_id\":\"156\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1054\",\"tracker_id\":\"156\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1055\",\"tracker_id\":\"156\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1056\",\"tracker_id\":\"156\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1057\",\"tracker_id\":\"156\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1058\",\"tracker_id\":\"156\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1059\",\"tracker_id\":\"156\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1060\",\"tracker_id\":\"156\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1061\",\"tracker_id\":\"156\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1062\",\"tracker_id\":\"156\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1063\",\"tracker_id\":\"156\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1064\",\"tracker_id\":\"156\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1065\",\"tracker_id\":\"156\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1066\",\"tracker_id\":\"156\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1067\",\"tracker_id\":\"156\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1068\",\"tracker_id\":\"156\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1069\",\"tracker_id\":\"156\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1070\",\"tracker_id\":\"156\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1071\",\"tracker_id\":\"156\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1072\",\"tracker_id\":\"156\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1073\",\"tracker_id\":\"156\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1074\",\"tracker_id\":\"156\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1075\",\"tracker_id\":\"156\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1076\",\"tracker_id\":\"156\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1174\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"549\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1175\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"550\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1176\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"551\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1177\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"552\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1178\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"553\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1179\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"554\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1180\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"555\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1181\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"556\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1182\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"557\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1183\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"558\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null}],\"evaluation_answers\":[],\"evaluation_submissions\":[],\"evaluation_results\":[],\"peer_evaluation_submissions\":[],\"peer_evaluation_results\":[],\"evaluation_reminders\":[],\"analytics_reports\":[],\"notifications\":[],\"activity_log_snapshot\":[{\"id\":\"55\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #5)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-09-28 22:21:11\"},{\"id\":\"56\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #6)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-09-28 22:21:14\"},{\"id\":\"57\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #2)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-09-28 22:21:16\"},{\"id\":\"58\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #2 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-09-28 22:25:56\"},{\"id\":\"59\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #6 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-09-28 22:25:56\"},{\"id\":\"60\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #5 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-09-28 22:25:56\"}]}', '2026-10-04 16:46:57', 'Lorraine R. Sabay', NULL, NULL, NULL);
INSERT INTO `system_archive_deletions` (`id`, `archive_id`, `period_id`, `period_label`, `school_year`, `status_at_delete`, `record_count`, `archived_at`, `archived_by_name`, `deleted_by`, `deleted_by_name`, `deleted_at`, `archived_by`, `restored_at`, `restored_by`, `summary_json`, `payload_json`, `recovered_at`, `recovered_by_name`, `purged_at`, `purge_reason`, `purged_by_name`) VALUES
(3, 8, 2, '2026-2027 — School Year', '2026-2027', 'restored', 146, '2026-10-01 12:29:54', 'Lorraine R. Sabay', 236, 'Lorraine R. Sabay', '2026-10-04 16:47:01', 236, '2026-10-01 13:03:46', 236, '{\"evaluation_tracker\":10,\"questionnaire_answers\":136,\"evaluation_answers\":0,\"evaluation_submissions\":0,\"evaluation_results\":0,\"peer_evaluation_submissions\":0,\"peer_evaluation_results\":0,\"evaluation_reminders\":0,\"analytics_reports\":0,\"notifications\":0,\"activity_log_snapshot\":6}', '{\"evaluation_tracker\":[{\"id\":\"140\",\"legacy_submission_id\":null,\"evaluator_id\":\"158\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":\"\",\"form_type\":\"Principal Evaluation — Faculty\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"4.91\",\"remarks\":\"N/A\",\"eval_type\":\"school_head\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 10:58:50\",\"updated_at\":\"2026-09-22 10:58:50\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"141\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:00:39\",\"updated_at\":\"2026-09-22 11:00:39\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"school_head\"},{\"id\":\"142\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"218\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:01:58\",\"updated_at\":\"2026-09-22 11:01:58\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"staff\"},{\"id\":\"148\",\"legacy_submission_id\":null,\"evaluator_id\":\"219\",\"target_user_id\":\"220\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"Palaging Late\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:45:15\",\"updated_at\":\"2026-09-22 11:45:15\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"149\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"220\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"Sleeping\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:52:17\",\"updated_at\":\"2026-09-22 11:52:17\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"150\",\"legacy_submission_id\":null,\"evaluator_id\":\"220\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"3.45\",\"remarks\":\"\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Faculty\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-22 11:58:19\",\"updated_at\":\"2026-09-22 11:58:19\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"151\",\"legacy_submission_id\":null,\"evaluator_id\":\"217\",\"target_user_id\":\"158\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"3\",\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"4.75\",\"remarks\":\"N/A\",\"eval_type\":\"faculty_peer\",\"peer_group\":\"Principal\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-23 10:29:32\",\"updated_at\":\"2026-09-23 10:29:32\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"155\",\"legacy_submission_id\":null,\"evaluator_id\":\"171\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-23 15:51:42\",\"updated_at\":\"2026-09-23 15:51:42\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"156\",\"legacy_submission_id\":null,\"evaluator_id\":\"219\",\"target_user_id\":\"217\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":null,\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":null,\"remarks\":\"N/A\",\"eval_type\":\"student\",\"peer_group\":null,\"status\":\"submitted\",\"submitted_at\":\"2026-09-24 09:35:00\",\"updated_at\":\"2026-09-24 09:35:00\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"},{\"id\":\"161\",\"legacy_submission_id\":null,\"evaluator_id\":\"233\",\"target_user_id\":\"236\",\"eval_bucket\":\"Faculty\",\"level\":null,\"form_type\":\"\",\"form_id\":\"7\",\"source_module\":null,\"period\":null,\"period_id\":\"2\",\"score\":\"4.40\",\"remarks\":\"N/A\",\"eval_type\":\"staff\",\"peer_group\":\"Staff Evaluation\",\"status\":\"submitted\",\"submitted_at\":\"2026-09-28 16:00:48\",\"updated_at\":\"2026-09-28 16:00:48\",\"evaluator_year_level\":null,\"evaluator_department\":null,\"evaluation_context\":\"teacher\"}],\"questionnaire_answers\":[{\"id\":\"852\",\"tracker_id\":\"140\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"853\",\"tracker_id\":\"140\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"854\",\"tracker_id\":\"140\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"855\",\"tracker_id\":\"140\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"856\",\"tracker_id\":\"140\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"857\",\"tracker_id\":\"140\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"858\",\"tracker_id\":\"140\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"859\",\"tracker_id\":\"140\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"860\",\"tracker_id\":\"140\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"861\",\"tracker_id\":\"140\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"862\",\"tracker_id\":\"140\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 10:58:50\",\"comments\":null},{\"id\":\"906\",\"tracker_id\":\"148\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"907\",\"tracker_id\":\"148\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"908\",\"tracker_id\":\"148\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"909\",\"tracker_id\":\"148\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"910\",\"tracker_id\":\"148\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"911\",\"tracker_id\":\"148\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"912\",\"tracker_id\":\"148\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"913\",\"tracker_id\":\"148\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"914\",\"tracker_id\":\"148\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"915\",\"tracker_id\":\"148\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"916\",\"tracker_id\":\"148\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"917\",\"tracker_id\":\"148\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"918\",\"tracker_id\":\"148\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"919\",\"tracker_id\":\"148\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"920\",\"tracker_id\":\"148\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"921\",\"tracker_id\":\"148\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"922\",\"tracker_id\":\"148\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"923\",\"tracker_id\":\"148\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"924\",\"tracker_id\":\"148\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"925\",\"tracker_id\":\"148\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"926\",\"tracker_id\":\"148\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"927\",\"tracker_id\":\"148\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"928\",\"tracker_id\":\"148\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:45:15\",\"comments\":null},{\"id\":\"929\",\"tracker_id\":\"149\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"930\",\"tracker_id\":\"149\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"931\",\"tracker_id\":\"149\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"932\",\"tracker_id\":\"149\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"933\",\"tracker_id\":\"149\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"934\",\"tracker_id\":\"149\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"935\",\"tracker_id\":\"149\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"936\",\"tracker_id\":\"149\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"937\",\"tracker_id\":\"149\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"938\",\"tracker_id\":\"149\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"939\",\"tracker_id\":\"149\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"940\",\"tracker_id\":\"149\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"941\",\"tracker_id\":\"149\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"942\",\"tracker_id\":\"149\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"943\",\"tracker_id\":\"149\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"944\",\"tracker_id\":\"149\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"945\",\"tracker_id\":\"149\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"946\",\"tracker_id\":\"149\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"947\",\"tracker_id\":\"149\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"948\",\"tracker_id\":\"149\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"949\",\"tracker_id\":\"149\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"950\",\"tracker_id\":\"149\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"951\",\"tracker_id\":\"149\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:52:17\",\"comments\":null},{\"id\":\"952\",\"tracker_id\":\"150\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"953\",\"tracker_id\":\"150\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"954\",\"tracker_id\":\"150\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"955\",\"tracker_id\":\"150\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"956\",\"tracker_id\":\"150\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"1.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"957\",\"tracker_id\":\"150\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"2.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"958\",\"tracker_id\":\"150\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"959\",\"tracker_id\":\"150\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"960\",\"tracker_id\":\"150\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"961\",\"tracker_id\":\"150\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"962\",\"tracker_id\":\"150\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-22 11:58:19\",\"comments\":null},{\"id\":\"1019\",\"tracker_id\":\"155\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1020\",\"tracker_id\":\"155\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1021\",\"tracker_id\":\"155\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1022\",\"tracker_id\":\"155\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1023\",\"tracker_id\":\"155\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1024\",\"tracker_id\":\"155\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1025\",\"tracker_id\":\"155\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1026\",\"tracker_id\":\"155\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1027\",\"tracker_id\":\"155\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1028\",\"tracker_id\":\"155\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1029\",\"tracker_id\":\"155\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1030\",\"tracker_id\":\"155\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1031\",\"tracker_id\":\"155\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1032\",\"tracker_id\":\"155\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1033\",\"tracker_id\":\"155\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1034\",\"tracker_id\":\"155\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1035\",\"tracker_id\":\"155\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1036\",\"tracker_id\":\"155\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1037\",\"tracker_id\":\"155\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1038\",\"tracker_id\":\"155\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1039\",\"tracker_id\":\"155\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1040\",\"tracker_id\":\"155\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1041\",\"tracker_id\":\"155\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1042\",\"tracker_id\":\"155\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1043\",\"tracker_id\":\"155\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1044\",\"tracker_id\":\"155\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1045\",\"tracker_id\":\"155\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1046\",\"tracker_id\":\"155\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1047\",\"tracker_id\":\"155\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-23 15:51:42\",\"comments\":null},{\"id\":\"1048\",\"tracker_id\":\"156\",\"question_id\":\"244\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1049\",\"tracker_id\":\"156\",\"question_id\":\"245\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1050\",\"tracker_id\":\"156\",\"question_id\":\"246\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1051\",\"tracker_id\":\"156\",\"question_id\":\"247\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1052\",\"tracker_id\":\"156\",\"question_id\":\"248\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1053\",\"tracker_id\":\"156\",\"question_id\":\"249\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1054\",\"tracker_id\":\"156\",\"question_id\":\"250\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1055\",\"tracker_id\":\"156\",\"question_id\":\"259\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1056\",\"tracker_id\":\"156\",\"question_id\":\"261\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1057\",\"tracker_id\":\"156\",\"question_id\":\"262\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1058\",\"tracker_id\":\"156\",\"question_id\":\"263\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1059\",\"tracker_id\":\"156\",\"question_id\":\"265\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1060\",\"tracker_id\":\"156\",\"question_id\":\"266\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1061\",\"tracker_id\":\"156\",\"question_id\":\"268\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1062\",\"tracker_id\":\"156\",\"question_id\":\"269\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1063\",\"tracker_id\":\"156\",\"question_id\":\"270\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1064\",\"tracker_id\":\"156\",\"question_id\":\"271\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1065\",\"tracker_id\":\"156\",\"question_id\":\"272\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1066\",\"tracker_id\":\"156\",\"question_id\":\"251\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1067\",\"tracker_id\":\"156\",\"question_id\":\"252\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1068\",\"tracker_id\":\"156\",\"question_id\":\"253\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1069\",\"tracker_id\":\"156\",\"question_id\":\"254\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1070\",\"tracker_id\":\"156\",\"question_id\":\"255\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1071\",\"tracker_id\":\"156\",\"question_id\":\"256\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1072\",\"tracker_id\":\"156\",\"question_id\":\"257\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1073\",\"tracker_id\":\"156\",\"question_id\":\"258\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1074\",\"tracker_id\":\"156\",\"question_id\":\"260\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1075\",\"tracker_id\":\"156\",\"question_id\":\"264\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1076\",\"tracker_id\":\"156\",\"question_id\":\"267\",\"question_source\":\"evaluation\",\"user_question_id\":null,\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-24 09:35:00\",\"comments\":null},{\"id\":\"1174\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"549\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1175\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"550\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1176\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"551\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1177\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"552\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1178\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"553\",\"answer_text\":null,\"answer_score\":\"3.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1179\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"554\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1180\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"555\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1181\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"556\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1182\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"557\",\"answer_text\":null,\"answer_score\":\"5.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null},{\"id\":\"1183\",\"tracker_id\":\"161\",\"question_id\":null,\"question_source\":\"user\",\"user_question_id\":\"558\",\"answer_text\":null,\"answer_score\":\"4.00\",\"submitted_at\":\"2026-09-28 16:00:48\",\"comments\":null}],\"evaluation_answers\":[],\"evaluation_submissions\":[],\"evaluation_results\":[],\"peer_evaluation_submissions\":[],\"peer_evaluation_results\":[],\"evaluation_reminders\":[],\"analytics_reports\":[],\"notifications\":[],\"activity_log_snapshot\":[{\"id\":\"55\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #5)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-09-28 22:21:11\"},{\"id\":\"56\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #6)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-09-28 22:21:14\"},{\"id\":\"57\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Archived evaluation data for 2026-2027 (Archive #2)\",\"icon\":\"fa-box-archive\",\"color\":\"#2563EB\",\"created_at\":\"2026-09-28 22:21:16\"},{\"id\":\"58\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #2 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-09-28 22:25:56\"},{\"id\":\"59\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #6 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-09-28 22:25:56\"},{\"id\":\"60\",\"actor_name\":\"Lorraine R. Sabay\",\"actor_type\":\"admin\",\"action_text\":\"Restored evaluation archive #5 for 2026-2027\",\"icon\":\"fa-box-open\",\"color\":\"#0F9F6E\",\"created_at\":\"2026-09-28 22:25:56\"}]}', '2026-10-04 16:47:06', 'Lorraine R. Sabay', NULL, NULL, NULL);

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
('control_mode', 'open'),
('eval_end', '2026-10-08T23:00'),
('eval_start', '2026-10-07T16:49'),
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
(157, 'Rinalita', '$2y$10$Hwg1Afa273XUtZk2LUtmmOgUen.3TL.hlM1k4rV9ObyFxBPWMlpoq', 'Rinalita S. Tutor', 'rinalita@gmail.com', NULL, 'Student', 'DEAN', 'dean', 'college', 1, '2026-08-04 17:52:56', '2026-10-09 13:05:46', 'BSIT', NULL, 'self', 'self', 0, NULL, 'approved', '040506', NULL, NULL, NULL, NULL),
(158, 'RAY', '$2y$10$w4.0sOL2y99sh.GLmm.3Que0wRUt.ECQuR/hifUHHEf0ce5os2O7q', 'Evelyn M. Verano', 'ray@gmail.com', NULL, 'Student', NULL, 'principal', 'both', 1, '2026-08-05 15:38:01', '2026-09-19 10:51:11', '', NULL, 'self', 'self', 1, NULL, 'approved', '060708', NULL, NULL, NULL, NULL),
(171, 'jeo', '$2y$10$.qFtE1a2ywTVnQUKGiSWAO.tHmTrkmdYEB.xi2WsmGGUvwnLdsAvm', 'jeo', 'jeo@gmail.com', 'stu_6aac8ee4e9f6e8.04613904.jpg', 'Student', 'Student', 'student', 'senior_high', 1, '2026-08-12 23:45:19', '2026-09-22 09:33:49', 'SHS', 'Grade 11', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(175, 'Dim', '$2y$10$NoPujTO4svEjolJP03d3C.c6jlJG8ShC2B3K/0f7EAwVC57CNiGai', 'Dim Mark Damaso', 'dim@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 1, '2026-08-18 16:28:09', '2026-09-26 22:18:00', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(215, 'Valerie', '$2y$10$7aawtl2Ba53xdo5VBZNSt.Ee4WAs/IZqzB6vifBgP/2Y.GWnj00ge', 'Valerie Jane S. Bendijo', 'valerie@gmail.com', 'usr_6ab1df39eb6404.56845468.jpg', 'Staff', 'BS Nursing', 'staff', NULL, 1, '2026-09-22 09:51:54', '2026-09-22 10:09:01', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(216, 'Stephanie', '$2y$10$8iab0i7yVIqTleak05eDI.NHV5dSFLikKxb4t2wUKjqjng/m3NIde', 'Stephanie M. Puntal', 'stephanie@gmail.com', 'usr_6ab1e532bcc723.93036759.jpg', 'Teacher', 'BS Information Technology  with Certificate in Teaching – Social Studies', 'teacher', NULL, 1, '2026-09-22 10:17:22', '2026-10-09 15:16:25', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(217, 'Cedrick', '$2y$10$QZPaDcfro.BW8A3uSXFgu.o4MwRGMynAhzJFTQvYxTx70iueHoNMO', 'Cedrick Dante Espillo', 'cedrick@gmail.com', 'usr_6ab1e58b175c02.72807880.jpg', 'Teacher', 'Teacher/ Coordinator', 'teacher', NULL, 1, '2026-09-22 10:18:51', '2026-09-22 11:15:25', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(218, 'Maluo', '$2y$10$EGHjk/Jiqo.4hf9hb7xplemI2eCQM1.CbnTyV2AUpN7ibYtBAvvQG', 'Malou De la Torre', 'maluo@gmail.com', 'usr_6ab1e9f519e7f0.00650896.jpg', 'Staff', 'Librarian', 'staff', NULL, 1, '2026-09-22 10:37:41', '2026-09-22 11:08:19', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(219, 'josesantos', '$2y$10$GC8Y.eElesI4rxXGH83TOeJ3laR9J/sxJhDov5/yEn2kIuidZsS0q', 'Jose K. Santos', 'jose@gmail.com', NULL, 'Student', 'Student', 'student', 'junior_high', 1, '2026-09-22 11:33:12', '2026-09-22 11:33:51', 'JHS', 'Grade 7', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(220, 'Em26', '$2y$10$t4w5yiDb8iaUg0tksEMbnuNQ5mcQKQptJSVttiykaW7YAQ.903wre', 'Barcibal Emily R.', 'em@gmail.com', 'usr_6ab1f8a95ddf77.68369057.jpg', 'Teacher', 'Teacher/ Cashier', 'teacher', NULL, 1, '2026-09-22 11:40:25', '2026-10-04 14:14:53', NULL, NULL, 'self', 'self', 0, '2nd Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(222, 'Jen jen', '$2y$10$uMKlZHbQzjYKQq/4d8OLhe4/1fkjKntXxqxGaKpINgZ63fOGD3/hC', 'Jennifer A. Biadora', 'jen@gmail.com', 'usr_6ab340d75b1240.55091137.jpg', 'Teacher', 'Personnel', 'teacher', NULL, 1, '2026-09-23 11:00:39', '2026-10-09 15:45:13', NULL, NULL, 'self', 'self', 0, '2nd Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(225, 'Jhong', '$2y$10$BJN/.a6HFx5OQ6T5.xKLFe1WCG80yIBic5fJb5Hkop9cn82fBdsfi', 'Anecito John Kenneth M.', 'jhong@gmail.com', 'usr_6ab34615511401.85637987.jpg', 'Staff', 'Physical Plant Coordinator/ Computer Lab Custodian', 'staff', NULL, 1, '2026-09-23 11:23:01', '2026-09-24 10:48:20', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(226, 'Johnny', '$2y$10$e8RUwdkMbrUh.jVeqyyMe.s3JLncs7O051OeyGs7I3NbxY8G8cr1m', 'Delos Santos Johnny E.', 'johnny@gmail.com', 'usr_6ab346a3ba25d3.72583291.jpg', 'Staff', 'MAINTENANCE OFFICER', 'staff', NULL, 1, '2026-09-23 11:25:23', '2026-09-23 11:25:48', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(227, 'Joselle', '$2y$10$Z5FBPjfYF///65dyfVXmVO/ox.qy5WEN1JpJYPnCOvHYVlCP5a/Ue', 'Sardina Joselle C.', 'joselle@gmail.com', 'usr_6ab3474341f3f6.96519829.jpg', 'Teacher', 'Ang Kingke Adviser/ Grade 7 - St. Albert Adviser/ HS TEACHER', 'teacher', NULL, 1, '2026-09-23 11:28:03', '2026-09-23 11:42:39', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(230, 'Gerald', '$2y$10$GRKf/z5Vy5DEm339bHgoMeXDY7LcxWkzjaYExiw1GwZx558MmPWZS', 'Delos Santos Gerald V.', 'gerald@gmail.com', 'usr_6ab3482a0ff224.35204052.jpg', 'Staff', 'GUIDANCE STAFF/ SPORTS PROGRAM MANAGER/ SDRRM OFFICER', 'staff', NULL, 1, '2026-09-23 11:31:54', '2026-09-24 10:48:38', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(232, 'Raffy', '$2y$10$E3E8KCvHAQeBq3AZbG/Tfuz5ABvL7kBSMvOE1bTnnCpYMzUvDH6s6', 'Arevalo Raffy E.', 'raffy@gmail.com', 'usr_6ab34934f240f1.26407181.jpg', 'Staff', 'School Registrar/ ADMIN COORDINATOR', 'staff', NULL, 1, '2026-09-23 11:36:21', '2026-09-23 11:36:38', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(233, 'Amelia', '$2y$10$B3ILoeABU7l6AIvjvHRfyOnaE.NJlnlmoJtINwJcNiGnPte8YM/7e', 'Amelia C. Candolita', 'amelia@gmail.com', 'usr_6ab34c5cea1ef0.90072910.jpg', 'Staff', 'Bookkeeper', 'staff', NULL, 1, '2026-09-23 11:49:49', '2026-10-04 10:11:47', NULL, NULL, 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(234, 'Jessie', '$2y$10$vCT8odfTibas8JCtgrzNM.FUfmSMBmFSExV79im.0meZQ8AWq5BWG', 'Aquillo Jessie A.', 'jessie@gmail.com', 'usr_6ab34ceaaa0f73.47035302.jpg', 'Staff', 'VE/CLE COORDINATOR/ HS TEACHER', 'staff', NULL, 1, '2026-09-23 11:52:10', '2026-10-01 19:20:01', NULL, NULL, 'self', 'self', 0, '1st Semester', 'approved', NULL, NULL, NULL, NULL, NULL),
(235, 'john', '$2y$10$IDejaBEkRwZY9uEti3fksOV3LWLtlgON7lXo2NLjVgyX/JOpKwM5C', 'Amelia C. Candolita', 'john@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 0, '2026-09-23 16:05:55', '2026-09-24 11:20:11', 'College', '4th Year College', 'self', 'self', 0, NULL, 'blocked', NULL, NULL, NULL, NULL, NULL),
(236, 'Lorraine', '$2y$10$Nk6Rv1yLiqnYF5S/T4fsbOyd6VLKR5Snog3HDp35fa7pkZSDebBsG', 'Lorraine R. Sabay', 'lorraine@gmail.com', 'adm_6ab46d4f554d89.08860627.jpg', 'Student', NULL, 'superadmin', NULL, 1, '2026-09-24 08:22:39', '2026-09-24 14:36:32', NULL, NULL, 'self', 'self', 1, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(237, 'Michel', '$2y$10$oyyMVA80ce7KnMFXg9uxi.xCTKIZMR8xWW2zH1sQvJsEoXnhtmp8W', 'John Michel Sardañas', 'michel@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 1, '2026-09-24 11:22:29', '2026-09-24 11:22:36', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(238, 'Hannah', '$2y$10$7G8dzT9rUZHQl1xvJlXTHubTMqjt5wd6iAWQChY3LQvTEBSz/7nDK', 'Hannah Mae Solangon', 'hannah@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 1, '2026-09-24 11:27:31', '2026-09-24 11:29:35', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(240, 'Dimmy', '$2y$10$RV3/dcsS7Z4SSIR65K7hrez1ybQrvrsn7E7nxErE39ckCp9U60l6y', 'Damaso Dim Mark', 'damasodimmark@gmail.com', NULL, 'Student', 'Student', 'student', 'college', 1, '2026-09-24 16:22:50', '2026-09-24 16:23:13', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(241, 'taniel', '$2y$10$xdca3COkv4Eo92LZnbX.MuwM/RO0aiQQ0mYf.PmTiHzagdjcdXvSG', 'taniel', 'ssolomoonnn@gmail.com', 'stu_6ac1eff7c4b850.10990001.png', 'Student', 'Student', 'student', 'college', 1, '2026-10-04 14:06:54', '2026-10-04 14:23:02', 'College', '4th Year College', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(242, 'kim', '$2y$10$XLmfBdjPmVyYInj0b4I8M.xBw/.eAIyJ9J/cMox5SvC2Q4WczL/1O', 'kim', NULL, NULL, 'Student', 'Student', 'student', 'college', 1, '2026-10-09 11:25:54', '2026-10-09 11:26:03', 'College', '4th Year College', 'self', 'self', 0, NULL, 'pending', NULL, NULL, NULL, NULL, NULL),
(243, 'Ashley', '$2y$10$ENfrIlgovfb.1UEmxDB3sOmgyzYGB8kaX7cHPD6tTrZKYcUSZ2MVa', 'Ashley M. Rioja', NULL, NULL, 'Student', 'Student', 'student', 'senior_high', 1, '2026-10-09 21:47:46', '2026-10-09 21:47:57', 'SHS', 'Grade 11', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(244, 'Manuel', '$2y$10$JW4ZS92CPjXOGxJL.bTUjOdC2nb.b6uI6Sn67JgmJ9VH4jlUiblMC', 'Manuel A. Angusto', NULL, NULL, 'Student', 'Student', 'student', 'junior_high', 1, '2026-10-10 01:44:51', '2026-10-10 01:57:55', 'JHS', 'Grade 7', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL),
(245, 'Mikey', '$2y$10$zdCxd8kJKlP9NmToq8yDPuheCdQOvXAnTmKCL3HXjNrJNCCTDhKvO', 'Mikey Tuman', NULL, NULL, 'Student', 'Student', 'student', 'junior_high', 1, '2026-10-10 10:16:44', '2026-10-10 10:16:54', 'JHS', 'Grade 7', 'self', 'self', 0, NULL, 'approved', NULL, NULL, NULL, NULL, NULL);

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
  `compact_dashboard` tinyint(1) NOT NULL DEFAULT 0,
  `notify_evaluation_schedule` tinyint(1) NOT NULL DEFAULT 1,
  `notify_teaching_assignment` tinyint(1) NOT NULL DEFAULT 1,
  `notify_academic_period` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`user_id`, `email_on_designation_update`, `email_on_new_evaluation`, `updated_at`, `show_result_details`, `compact_dashboard`, `notify_evaluation_schedule`, `notify_teaching_assignment`, `notify_academic_period`) VALUES
(170, 1, 1, '2026-09-21 21:31:41', 1, 0, 1, 1, 1),
(222, 1, 1, '2026-10-04 10:16:00', 1, 0, 1, 1, 1),
(233, 1, 1, '2026-10-04 10:14:11', 1, 0, 1, 1, 1);

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
(258, 216, '1st Year College'),
(259, 216, '2nd Year College'),
(260, 216, '4th Year College'),
(221, 217, '4th Year College'),
(220, 217, 'Grade 11'),
(219, 217, 'Grade 7'),
(256, 220, 'Grade 7'),
(257, 220, 'Grade 8'),
(239, 222, 'Grade 12'),
(237, 222, 'Grade 8'),
(238, 222, 'Grade 9'),
(231, 225, '4th Year College'),
(251, 227, 'Grade 11'),
(250, 227, 'Grade 7');

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
-- Indexes for table `feedback_received_keep`
--
ALTER TABLE `feedback_received_keep`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_keep_archive_tracker` (`archive_id`,`tracker_id`),
  ADD KEY `idx_keep_target_period` (`target_user_id`,`period_id`),
  ADD KEY `idx_keep_archive` (`archive_id`);

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
-- Indexes for table `portal_feedback_keep`
--
ALTER TABLE `portal_feedback_keep`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_portal_keep_archive_tracker` (`archive_id`,`tracker_id`),
  ADD KEY `idx_portal_keep_target` (`target_user_id`,`submitted_at`),
  ADD KEY `idx_portal_keep_archive` (`archive_id`);

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
-- Indexes for table `system_archive_deletions`
--
ALTER TABLE `system_archive_deletions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `analytics_reports`
--
ALTER TABLE `analytics_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auth_attempts`
--
ALTER TABLE `auth_attempts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `business_hours`
--
ALTER TABLE `business_hours`
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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=275;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=210;

--
-- AUTO_INCREMENT for table `feedback_received_keep`
--
ALTER TABLE `feedback_received_keep`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `login_confirmations`
--
ALTER TABLE `login_confirmations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=912;

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
-- AUTO_INCREMENT for table `portal_feedback_keep`
--
ALTER TABLE `portal_feedback_keep`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `questionnaire_answers`
--
ALTER TABLE `questionnaire_answers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1839;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4392;

--
-- AUTO_INCREMENT for table `rating_certifications`
--
ALTER TABLE `rating_certifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_change_log`
--
ALTER TABLE `role_change_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

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
-- AUTO_INCREMENT for table `student_security_answers`
--
ALTER TABLE `student_security_answers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `system_archives`
--
ALTER TABLE `system_archives`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `system_archive_deletions`
--
ALTER TABLE `system_archive_deletions`
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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=246;

--
-- AUTO_INCREMENT for table `user_management_log`
--
ALTER TABLE `user_management_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_questions`
--
ALTER TABLE `user_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=560;

--
-- AUTO_INCREMENT for table `user_question_categories`
--
ALTER TABLE `user_question_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=182;

--
-- AUTO_INCREMENT for table `user_year_levels`
--
ALTER TABLE `user_year_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=261;

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
