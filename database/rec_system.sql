-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2026 at 10:49 AM
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
-- Database: `rec_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','rec_staff','rec_chair','rec_member','rec_secretary') DEFAULT NULL,
  `academic_rank` varchar(100) DEFAULT NULL,
  `academic_degree` varchar(100) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'pending',
  `signature` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `name`, `email`, `password`, `role`, `academic_rank`, `academic_degree`, `profile_image`, `status`, `signature`, `created_at`) VALUES
(1, 'System Admin', 'admin@dnsc.edu.ph', '$2y$10$c5EQYLa0XISx08dVqF.Ite0E7haQrpCYthPqHD5KuLxuaD1K1p59G', 'admin', NULL, NULL, NULL, 'active', 'sig_1771731600_699a7a90b81cb.png', '2026-02-21 03:32:53'),
(2, 'Dr. Robert Miller', 'robert.miller@dnsc.edu.ph', '$2y$10$/nSFNacad4oWcOz33HGo7OWG/iHQR0sfQg292.zx0yDQlbG8w/.Ae', 'rec_member', NULL, NULL, NULL, 'active', 'sig_1771731610_699a7a9a862c6.png', '2026-02-21 03:56:58'),
(3, 'Maria Santos', 'm.santos@dnsc.edu.ph', '$2y$10$09tSekxlHpOiU7qTZ1Eg6uymcDW079yl3udk.VoCJZsmwuAlDDFgu', 'rec_staff', NULL, NULL, NULL, 'active', 'sig_1774421892_69c38784c1116.png', '2026-02-21 03:56:58'),
(4, 'Juan Dela Cruz', 'j.delacruz@dnsc.edu.ph', '$2y$10$b2hu2damudo1ySOiSClEO.TA.7SqeZZNRUgWX2kc2YosRXZ.zOITq', 'rec_member', NULL, NULL, NULL, 'active', 'sig_1771731643_699a7abbedbef.png', '2026-02-21 03:56:58'),
(17, 'Ana Reyes', 'a.reyes@dnsc.edu.ph', '$2y$10$b2hu2damudo1ySOiSClEO.TA.7SqeZZNRUgWX2kc2YosRXZ.zOITq', 'rec_secretary', NULL, NULL, NULL, 'active', 'sig_1771731627_699a7aab61df4.png', '2026-02-22 02:44:42'),
(18, 'Carlos Bautista', 'c.bautista@dnsc.edu.ph', '$2y$10$b2hu2damudo1ySOiSClEO.TA.7SqeZZNRUgWX2kc2YosRXZ.zOITq', 'rec_member', NULL, NULL, NULL, 'active', 'sig_1773385950_69b3b8deaaf26.png', '2026-02-22 02:44:42'),
(19, 'Mark Van Buladaco', 'markvan.buladaco@dnsc.edu.ph', '$2y$10$X.4/rM/xHv1tXmLrcfMYA.HGE9L9UkLbFjSzGCaPZspHOq6/7s7.m', 'rec_chair', NULL, NULL, NULL, 'active', 'sig_1771730981_699a7825e5984.png', '2026-02-22 03:29:41'),
(21, 'NAVEA', 'aljemarie.canoy@dnsc.edu.ph', '$2y$10$cn4nXfJqj5rvdv0JubOi7exdS/580lrEZzaSWTVQXnVGbqzkm9XV2', 'rec_member', NULL, NULL, NULL, 'active', 'sig_file_1773626988_69b7666c69f1c.png', '2026-03-16 02:09:48');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `protocol_id` int(11) DEFAULT NULL,
  `action` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `user_id`, `protocol_id`, `action`, `timestamp`) VALUES
(1, 22, 1, 'Author submitted protocol online. Awaiting REC Code.', '2026-04-29 03:46:04'),
(2, 3, 1, 'Staff screening started.', '2026-04-29 03:46:22'),
(3, 3, 1, 'Forwarded to REC Chair for Initial Review. Generated official code: 2026-001-INT.-JPD', '2026-04-29 03:47:01'),
(4, 19, NULL, 'Successful Login', '2026-04-29 03:48:36'),
(5, 3, NULL, 'Successful Login', '2026-04-29 03:48:56'),
(6, 19, NULL, 'Successful Login', '2026-04-29 03:53:09'),
(7, 19, 1, 'Assigned 7 reviewers (Ana Reyes, Carlos Bautista, Dr. Robert Miller, Juan Dela Cruz, Maria Santos, NAVEA, Mark Van Buladaco (REC Chair)). Deadline auto-set to May 13, 2026 (14 days).', '2026-04-29 03:54:22'),
(8, 19, 1, 'Made final decision: Approved', '2026-04-29 04:02:06'),
(9, 19, 1, 'Reverted accidental approval back to Assigned state for member evaluation.', '2026-04-29 04:07:16'),
(10, 19, 1, 'Submitted Form 10 & 12 review for 2026-001-INT.-JPD', '2026-04-29 04:25:11'),
(11, 3, NULL, 'Successful Login', '2026-04-29 04:27:15'),
(12, 19, NULL, 'Successful Login', '2026-04-29 06:45:44'),
(13, 2, NULL, 'Successful Login', '2026-04-29 06:47:15'),
(14, 19, NULL, 'Successful Login', '2026-04-29 06:48:25'),
(15, 4, NULL, 'Successful Login', '2026-04-29 07:14:32'),
(16, 19, NULL, 'Successful Login', '2026-04-29 07:15:50'),
(17, 4, NULL, 'Successful Login', '2026-04-29 07:16:11'),
(18, 22, NULL, 'Successful Login', '2026-04-29 08:45:27'),
(19, 22, NULL, 'Successful Login', '2026-04-29 08:56:54'),
(20, 3, NULL, 'Successful Login', '2026-04-29 08:57:03'),
(21, 19, NULL, 'Successful Login', '2026-04-29 08:58:05'),
(22, 19, 1, 'Made final decision: Approved', '2026-04-29 08:58:27'),
(23, 22, NULL, 'Successful Login', '2026-04-29 09:07:43'),
(24, 19, NULL, 'Successful Login', '2026-05-16 02:13:00'),
(25, 19, NULL, 'Successful Login', '2026-05-16 02:25:55'),
(26, 19, NULL, 'Successful Login', '2026-05-16 02:28:45'),
(27, 22, NULL, 'Successful Login', '2026-05-16 02:29:00'),
(28, 22, NULL, 'Successful Login', '2026-05-16 02:29:21'),
(29, 19, NULL, 'Successful Login', '2026-05-16 02:39:03'),
(30, 22, NULL, 'Successful Login', '2026-05-16 02:39:41'),
(31, 19, NULL, 'Successful Login', '2026-05-16 02:39:58'),
(32, 22, NULL, 'Successful Login', '2026-05-16 02:40:29'),
(33, 19, NULL, 'Successful Login', '2026-05-16 02:41:40'),
(34, 22, NULL, 'Successful Login', '2026-05-19 07:26:16'),
(35, 3, NULL, 'Successful Login', '2026-05-19 07:50:25'),
(36, 3, 1, 'REC REC Staff officially released the Ethical Clearance (Form 25).', '2026-05-19 07:51:49'),
(37, 22, NULL, 'Successful Login', '2026-05-19 07:56:09'),
(38, 3, NULL, 'Successful Login', '2026-05-19 07:57:06'),
(39, 22, 1, 'Lead Researcher Dela Pena, Jhanrex Philip, G officially acknowledged receipt of Ethical Clearance (Form 25) and Approval Letter (Form 16) and confirmed all signatures.', '2026-05-19 08:14:52'),
(40, 22, 1, 'Lead Researcher Dela Pena, Jhanrex Philip, G officially acknowledged receipt of Ethical Clearance (Form 25) and Approval Letter (Form 16) and confirmed all signatures.', '2026-05-19 08:15:05'),
(41, 22, NULL, 'Updated personal profile', '2026-05-19 08:18:04'),
(42, 22, 1, 'Lead Researcher Dela Pena, Jhanrex Philip, G officially submitted the online Progress Report (REC Form 18a) to the database.', '2026-05-19 08:19:49'),
(43, 22, 1, 'Lead Researcher Dela Pena, Jhanrex Philip, G officially submitted the online Final Report (REC Form 19) to the database.', '2026-05-19 08:20:14'),
(44, 22, 1, 'Lead Researcher Dela Pena, Jhanrex Philip, G officially submitted the online Progress Report (REC Form 18a) to the database.', '2026-05-19 08:21:09'),
(45, 22, NULL, 'Updated personal profile', '2026-05-19 08:26:15'),
(46, 22, NULL, 'Updated personal profile', '2026-05-19 08:26:20'),
(47, 22, NULL, 'Updated personal profile', '2026-05-19 08:26:59'),
(48, 22, NULL, 'Updated personal profile', '2026-05-19 08:27:09'),
(49, 22, NULL, 'Updated personal profile', '2026-05-19 08:27:37'),
(50, 22, NULL, 'Updated personal profile', '2026-05-19 08:27:43'),
(51, 22, NULL, 'Updated personal profile', '2026-05-19 08:28:02'),
(52, 22, NULL, 'Updated personal profile', '2026-05-19 08:28:32'),
(53, 22, NULL, 'Updated personal profile', '2026-05-19 08:28:55'),
(54, 22, NULL, 'Successful Login', '2026-05-19 08:41:11'),
(55, 22, NULL, 'Successful Login', '2026-05-19 08:43:04');

-- --------------------------------------------------------

--
-- Table structure for table `final_decisions`
--

CREATE TABLE `final_decisions` (
  `decision_id` int(11) NOT NULL,
  `protocol_id` int(11) NOT NULL,
  `chair_id` int(11) NOT NULL,
  `meeting_date` date DEFAULT NULL,
  `final_decision` enum('Approved','Minor Revision','Major Revision','Disapproved') NOT NULL,
  `remarks` text DEFAULT NULL,
  `decision_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `final_decisions`
--

INSERT INTO `final_decisions` (`decision_id`, `protocol_id`, `chair_id`, `meeting_date`, `final_decision`, `remarks`, `decision_date`) VALUES
(2, 1, 19, '2026-04-29', 'Approved', 'congrats', '2026-04-29 08:58:27');

-- --------------------------------------------------------

--
-- Table structure for table `form9_answers`
--

CREATE TABLE `form9_answers` (
  `id` int(11) NOT NULL,
  `protocol_id` int(11) NOT NULL,
  `chair_id` int(11) NOT NULL,
  `section` varchar(255) NOT NULL,
  `decision` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `form10_answers`
--

CREATE TABLE `form10_answers` (
  `answer_id` int(11) NOT NULL,
  `protocol_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` enum('Yes','No','Unable to Assess') NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form10_answers`
--

INSERT INTO `form10_answers` (`answer_id`, `protocol_id`, `reviewer_id`, `question`, `answer`, `comment`, `created_at`) VALUES
(1, 1, 19, 'Does the study have social value?', 'Yes', 'ssadasd', '2026-04-29 04:25:11'),
(2, 1, 19, 'Is the study background adequate?', 'Yes', 'asdasd', '2026-04-29 04:25:11'),
(3, 1, 19, 'Are the research questions supported by the Review of Literature?', 'Yes', '', '2026-04-29 04:25:11'),
(4, 1, 19, 'Are the study objectives Specific, Measurable, Attainable, Realistic, Time-bound?', 'Yes', '', '2026-04-29 04:25:11'),
(5, 1, 19, 'Is the research design appropriate?', 'Yes', '', '2026-04-29 04:25:11'),
(6, 1, 19, 'SUB|Is the research design appropriate?|Is the population identified and defined?', 'Yes', '', '2026-04-29 04:25:11'),
(7, 1, 19, 'SUB|Is the research design appropriate?|Is the selection of study participants described?', 'Yes', '', '2026-04-29 04:25:11'),
(8, 1, 19, 'SUB|Is the research design appropriate?|Is the sample size justified?', 'Yes', '', '2026-04-29 04:25:11'),
(9, 1, 19, 'SUB|Is the research design appropriate?|Is the plan for data analysis described?', 'Yes', '', '2026-04-29 04:25:11'),
(10, 1, 19, 'SUB|Is the research design appropriate?|Are there dummy tables?', 'Yes', '', '2026-04-29 04:25:11'),
(11, 1, 19, 'Does the research need to be carried out with human participants?', 'Yes', '', '2026-04-29 04:25:11'),
(12, 1, 19, 'Does the study have a vulnerability issue?', 'Yes', '', '2026-04-29 04:25:11'),
(13, 1, 19, 'Are appropriate mechanisms/interventions in place to address the vulnerability issue/s?', 'Yes', '', '2026-04-29 04:25:11'),
(14, 1, 19, 'Are there risks/probable harms to the human participants in the study?', 'Yes', '', '2026-04-29 04:25:11'),
(15, 1, 19, 'Are there measures to mitigate the risks?', 'Yes', '', '2026-04-29 04:25:11'),
(16, 1, 19, 'Is the informed consent procedure/form adequate and culturally appropriate?', 'Yes', '', '2026-04-29 04:25:11'),
(17, 1, 19, 'Is/are the investigator/s adequately trained and do they have sufficient experience to undertake the study?', 'Yes', '', '2026-04-29 04:25:11'),
(18, 1, 19, 'Is there a disclosure of conflict of interest?', 'Yes', '', '2026-04-29 04:25:11'),
(19, 1, 19, 'Are the research facilities adequate?', 'Yes', '', '2026-04-29 04:25:11'),
(20, 1, 19, 'Are there any other concerns in the study?', 'Yes', 'dfgfdgdfg', '2026-04-29 04:25:11'),
(21, 1, 17, 'Simulated Question', 'Yes', 'Proceed.', '2026-04-29 06:49:27'),
(22, 1, 18, 'Simulated Question', 'Yes', 'Proceed.', '2026-04-29 06:49:27'),
(23, 1, 2, 'Simulated Question', 'Yes', 'Proceed.', '2026-04-29 06:49:27'),
(24, 1, 4, 'Simulated Question', 'Yes', 'Proceed.', '2026-04-29 06:49:27'),
(25, 1, 3, 'Simulated Question', 'Yes', 'Proceed.', '2026-04-29 06:49:27'),
(26, 1, 21, 'Simulated Question', 'Yes', 'Proceed.', '2026-04-29 06:49:27');

-- --------------------------------------------------------

--
-- Table structure for table `form12_answers`
--

CREATE TABLE `form12_answers` (
  `answer_id` int(11) NOT NULL,
  `protocol_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` enum('Yes','No','Unable to Assess') NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form12_answers`
--

INSERT INTO `form12_answers` (`answer_id`, `protocol_id`, `reviewer_id`, `question`, `answer`, `comment`, `created_at`) VALUES
(1, 1, 19, 'Is it necessary to seek the informed consent of the participants?', 'Yes', 'dfgdfg', '2026-04-29 04:25:11'),
(2, 1, 19, 'IFNO|Is it necessary to seek the informed consent of the participants?', '', '', '2026-04-29 04:25:11'),
(3, 1, 19, 'If YES, are the participants provided with sufficient information regarding:', '', 'dfbdfbd', '2026-04-29 04:25:11'),
(4, 1, 19, 'SUB|If YES, are the participants provided with sufficient information regarding:|Purpose of the study?', 'Yes', '', '2026-04-29 04:25:11'),
(5, 1, 19, 'SUB|If YES, are the participants provided with sufficient information regarding:|Expected duration of participation?', 'Yes', '', '2026-04-29 04:25:11'),
(6, 1, 19, 'SUB|If YES, are the participants provided with sufficient information regarding:|Procedures to be carried out?', 'Yes', '', '2026-04-29 04:25:11'),
(7, 1, 19, 'SUB|If YES, are the participants provided with sufficient information regarding:|Discomforts and inconveniences?', 'Yes', '', '2026-04-29 04:25:11'),
(8, 1, 19, 'SUB|If YES, are the participants provided with sufficient information regarding:|Risks (including possible discrimination)?', 'Yes', '', '2026-04-29 04:25:11'),
(9, 1, 19, 'SUB|If YES, are the participants provided with sufficient information regarding:|Random assignment to the trial treatments?', 'Yes', '', '2026-04-29 04:25:11'),
(10, 1, 19, 'SUB|If YES, are the participants provided with sufficient information regarding:|Benefits to the participants?', 'Yes', '', '2026-04-29 04:25:11'),
(11, 1, 19, 'SUB|If YES, are the participants provided with sufficient information regarding:|Alternative treatments/procedures?', 'Yes', '', '2026-04-29 04:25:11'),
(12, 1, 19, 'SUB|If YES, are the participants provided with sufficient information regarding:|Compensation and/or medical treatments in case of injury?', 'Yes', '', '2026-04-29 04:25:11'),
(13, 1, 19, 'SUB|If YES, are the participants provided with sufficient information regarding:|Who to contact for pertinent questions and/or for assistance in a research-related injury?', 'Yes', '', '2026-04-29 04:25:11'),
(14, 1, 19, 'SUB|If YES, are the participants provided with sufficient information regarding:|Refusal to participate or discontinuance at any time will involve penalty or loss of benefits to which the subject is entitled?', 'Yes', '', '2026-04-29 04:25:11'),
(15, 1, 19, 'SUB|If YES, are the participants provided with sufficient information regarding:|Extent of confidentiality?', 'Yes', '', '2026-04-29 04:25:11'),
(16, 1, 19, 'Is the informed consent written or presented in simple language that participants can understand?', 'Yes', '', '2026-04-29 04:25:11'),
(17, 1, 19, 'Does the protocol include an adequate process for ensuring that consent is voluntary?', 'Yes', '', '2026-04-29 04:25:11'),
(18, 1, 19, 'Do you have any other concerns?', 'Yes', '', '2026-04-29 04:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `form13_answers`
--

CREATE TABLE `form13_answers` (
  `id` int(11) NOT NULL,
  `protocol_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `is_submitted` enum('Yes','No','N/A') DEFAULT 'No',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form13_answers`
--

INSERT INTO `form13_answers` (`id`, `protocol_id`, `staff_id`, `category`, `is_submitted`, `remarks`, `created_at`) VALUES
(1, 1, 3, 'Research Protocol', 'Yes', 'test', '2026-04-29 03:46:49'),
(2, 1, 3, 'Informed Consent / Assent Consent', 'No', '', '2026-04-29 03:46:49'),
(3, 1, 3, 'Guide Questionnaire', 'No', '', '2026-04-29 03:46:49'),
(4, 1, 3, 'Curriculum Vitae', 'No', '', '2026-04-29 03:46:49'),
(5, 1, 3, 'Letter Request', 'No', '', '2026-04-29 03:46:49'),
(6, 1, 3, 'Endorsement', 'No', '', '2026-04-29 03:46:49');

-- --------------------------------------------------------

--
-- Table structure for table `form15_responses`
--

CREATE TABLE `form15_responses` (
  `id` int(11) NOT NULL,
  `protocol_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `rec_recommendation` text NOT NULL,
  `author_response` text NOT NULL,
  `page_reference` varchar(255) DEFAULT NULL,
  `rec_assessment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form15_responses`
--

INSERT INTO `form15_responses` (`id`, `protocol_id`, `author_id`, `rec_recommendation`, `author_response`, `page_reference`, `rec_assessment`, `created_at`) VALUES
(1, 1, 22, 'BOARD REVIEWER COMMENTS (Form 10):', 'asdasdasdas', '1', NULL, '2026-04-29 08:56:35'),
(2, 1, 22, '[Reviewer 3] Question: Simulated Question\r\n  [Reviewer Note] Proceed.', 'asdsadasd', '2', NULL, '2026-04-29 08:56:35'),
(3, 1, 22, '[Reviewer 5] Question: Simulated Question\r\n  [Reviewer Note] Proceed.', 'asdasd', '2', NULL, '2026-04-29 08:56:35'),
(4, 1, 22, '[Reviewer 4] Question: Simulated Question\r\n  [Reviewer Note] Proceed.', 'asdasd', '3', NULL, '2026-04-29 08:56:35'),
(5, 1, 22, '[Reviewer 1] Question: Simulated Question\r\n  [Reviewer Note] Proceed.', 'asdsad', '4', NULL, '2026-04-29 08:56:35'),
(6, 1, 22, '[Reviewer 2] Question: Simulated Question\r\n  [Reviewer Note] Proceed.', 'asdad', '2', NULL, '2026-04-29 08:56:35'),
(7, 1, 22, '[Reviewer 7] Question: Does the study have social value?\r\n  [Reviewer Note] ssadasd', '', '', NULL, '2026-04-29 08:56:35'),
(8, 1, 22, '[Reviewer 7] Question: Is the study background adequate?\r\n  [Reviewer Note] asdasd', 'asda', '1', NULL, '2026-04-29 08:56:35'),
(9, 1, 22, '[Reviewer 7] Question: Are there any other concerns in the study?\r\n  [Reviewer Note] dfgfdgdfg', 'asdsad', '2', NULL, '2026-04-29 08:56:35'),
(10, 1, 22, '[Reviewer 6] Question: Simulated Question\r\n  [Reviewer Note] Proceed.\r\n\r\n\r\nCONSENT CHECKLIST CONCERNS (Form 12):', 'asdsad', '2', NULL, '2026-04-29 08:56:35'),
(11, 1, 22, '[Reviewer 7] Check: Is it necessary to seek the informed consent of the participants?\r\n  [Reviewer Note] dfgdfg', 'asdsad', '2', NULL, '2026-04-29 08:56:35'),
(12, 1, 22, '[Reviewer 7] Check: IFNO|Is it necessary to seek the informed consent of the participants?', 'asdsd', '2', NULL, '2026-04-29 08:56:35'),
(13, 1, 22, '[Reviewer 7] Check: If YES, are the participants provided with sufficient information regarding:\r\n  [Reviewer Note] dfbdfbd\r\n\r\n\r\nSUMMARY OF REVIEWER DECISIONS:', 'asdasd', '1', NULL, '2026-04-29 08:56:35'),
(14, 1, 22, 'Reviewer 7: Approved', 'asdasd1', '2', NULL, '2026-04-29 08:56:35'),
(15, 1, 22, 'Reviewer 1: Approved', 'asdascasdc', '23', NULL, '2026-04-29 08:56:35'),
(16, 1, 22, 'Reviewer 2: Approved', 'asdsad', '2', NULL, '2026-04-29 08:56:35'),
(17, 1, 22, 'Reviewer 3: Approved', 'asdasd', '3', NULL, '2026-04-29 08:56:35'),
(18, 1, 22, 'Reviewer 4: Approved', 'asdasd', '2', NULL, '2026-04-29 08:56:35'),
(19, 1, 22, 'Reviewer 5: Approved', 'asdsad', '1', NULL, '2026-04-29 08:56:35'),
(20, 1, 22, 'Reviewer 6: Approved', 'asdasdasdasd', '1', NULL, '2026-04-29 08:56:35');

-- --------------------------------------------------------

--
-- Table structure for table `form18a_responses`
--

CREATE TABLE `form18a_responses` (
  `id` int(11) NOT NULL,
  `protocol_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `ethical_clearance_period` varchar(255) DEFAULT NULL,
  `start_date` varchar(100) DEFAULT NULL,
  `expected_end_date` varchar(100) DEFAULT NULL,
  `enrolled_participants` varchar(100) DEFAULT NULL,
  `required_participants` varchar(100) DEFAULT NULL,
  `withdrawn_participants` varchar(100) DEFAULT NULL,
  `withdrawal_reason` text DEFAULT NULL,
  `deviations` text DEFAULT NULL,
  `new_information` text DEFAULT NULL,
  `issues_encountered` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form18a_responses`
--

INSERT INTO `form18a_responses` (`id`, `protocol_id`, `author_id`, `ethical_clearance_period`, `start_date`, `expected_end_date`, `enrolled_participants`, `required_participants`, `withdrawn_participants`, `withdrawal_reason`, `deviations`, `new_information`, `issues_encountered`, `created_at`) VALUES
(1, 1, 22, 'asdcasdas', 'dasdas', 'dsadsadas', 'dasdas', 'dasdas', 'dsadsad', 'asd', 'asdasd', 'asdasd', 'asdasd', '2026-05-19 08:19:49');

-- --------------------------------------------------------

--
-- Table structure for table `form19_responses`
--

CREATE TABLE `form19_responses` (
  `id` int(11) NOT NULL,
  `protocol_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `ethical_clearance_start` varchar(100) DEFAULT NULL,
  `ethical_clearance_end` varchar(100) DEFAULT NULL,
  `start_date` varchar(100) DEFAULT NULL,
  `end_date` varchar(100) DEFAULT NULL,
  `enrolled_participants` varchar(100) DEFAULT NULL,
  `required_participants` varchar(100) DEFAULT NULL,
  `withdrawn_participants` varchar(100) DEFAULT NULL,
  `withdrawal_reason` text DEFAULT NULL,
  `deviations` text DEFAULT NULL,
  `issues_encountered` text DEFAULT NULL,
  `summary_findings` text DEFAULT NULL,
  `conclusions` text DEFAULT NULL,
  `dissemination_actions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form19_responses`
--

INSERT INTO `form19_responses` (`id`, `protocol_id`, `author_id`, `ethical_clearance_start`, `ethical_clearance_end`, `start_date`, `end_date`, `enrolled_participants`, `required_participants`, `withdrawn_participants`, `withdrawal_reason`, `deviations`, `issues_encountered`, `summary_findings`, `conclusions`, `dissemination_actions`, `created_at`) VALUES
(1, 1, 22, 'asdasd', 'asdasd', 'asdasd', 'asdasdasd', 'asdasd', 'asdas', 'dasdas', 'dasdas', 'dasdasd', 'asdasda', 'sdasda', 'sdasdasd', 'asdasd', '2026-05-19 08:20:14');

-- --------------------------------------------------------

--
-- Table structure for table `member_files`
--

CREATE TABLE `member_files` (
  `file_id` int(11) NOT NULL,
  `protocol_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('author','admin') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `user_type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 22, 'author', 'Protocol Submitted Successfully', 'Your protocol has been successfully submitted and is awaiting staff screening.', 'shared_view?id=1', 1, '2026-04-29 03:46:07'),
(2, 3, 'admin', 'New Protocol Submitted', 'A new protocol titled \"GWAPOO KO TESTG\" has been submitted and requires screening.', 'rec_staff/update_status.php?id=1', 1, '2026-04-29 03:46:07'),
(3, 22, 'author', 'Initial Assessment Completed', 'The REC Chair has completed the initial assessment. Review Type: FULL_BOARD', 'shared_view?id=1', 1, '2026-04-29 03:53:56'),
(4, 3, 'admin', 'Chair Confirmed Protocol', 'The REC Chair has confirmed protocol: \"GWAPOO KO TESTG\" and set Review Type to FULL_BOARD', 'rec_staff/update_status.php?id=1', 1, '2026-04-29 03:53:56'),
(5, 17, 'admin', 'Protocol Assigned for Review', 'You have been assigned to review the protocol: 2026-001-INT.-JPD - GWAPOO KO TESTG. Evaluation Deadline: May 13, 2026.', 'rec_member/review?id=1', 0, '2026-04-29 03:54:22'),
(6, 18, 'admin', 'Protocol Assigned for Review', 'You have been assigned to review the protocol: 2026-001-INT.-JPD - GWAPOO KO TESTG. Evaluation Deadline: May 13, 2026.', 'rec_member/review?id=1', 0, '2026-04-29 03:54:25'),
(7, 2, 'admin', 'Protocol Assigned for Review', 'You have been assigned to review the protocol: 2026-001-INT.-JPD - GWAPOO KO TESTG. Evaluation Deadline: May 13, 2026.', 'rec_member/review?id=1', 1, '2026-04-29 03:54:27'),
(8, 4, 'admin', 'Protocol Assigned for Review', 'You have been assigned to review the protocol: 2026-001-INT.-JPD - GWAPOO KO TESTG. Evaluation Deadline: May 13, 2026.', 'rec_member/review?id=1', 1, '2026-04-29 03:54:29'),
(9, 3, 'admin', 'Protocol Assigned for Review', 'You have been assigned to review the protocol: 2026-001-INT.-JPD - GWAPOO KO TESTG. Evaluation Deadline: May 13, 2026.', 'rec_member/review?id=1', 1, '2026-04-29 03:54:31'),
(10, 21, 'admin', 'Protocol Assigned for Review', 'You have been assigned to review the protocol: 2026-001-INT.-JPD - GWAPOO KO TESTG. Evaluation Deadline: May 13, 2026.', 'rec_member/review?id=1', 0, '2026-04-29 03:54:34'),
(11, 19, 'admin', 'Protocol Assigned for Review', 'You have been assigned to review the protocol: 2026-001-INT.-JPD - GWAPOO KO TESTG. Evaluation Deadline: May 13, 2026.', 'rec_member/review?id=1', 1, '2026-04-29 03:54:36'),
(12, 22, 'author', 'Reviewers Assigned', 'Reviewers have been assigned to your protocol \"GWAPOO KO TESTG\". Evaluation is now underway.', 'shared_view?id=1', 1, '2026-04-29 03:54:38'),
(13, 3, 'admin', 'Reviewers Assigned', 'Reviewers have been assigned to protocol: \"GWAPOO KO TESTG\" by the REC Chair.', 'rec_staff/update_status.php?id=1', 1, '2026-04-29 03:54:38'),
(14, 19, 'admin', 'Test Notification', 'If you see this, notifications are working!', 'rec_chair/protocols', 1, '2026-04-29 03:54:44'),
(15, 22, 'author', 'Final Decision Rendered', 'The REC Board has rendered a final decision on your protocol: \"GWAPOO KO TESTG\". Decision: APPROVED', 'shared_view?id=1', 1, '2026-04-29 04:02:06'),
(16, 3, 'admin', 'Decision Rendered', 'The REC Chair has finalized the decision for protocol: \"GWAPOO KO TESTG\". Result: APPROVED', 'rec_staff/update_status.php?id=1', 1, '2026-04-29 04:02:06'),
(17, 3, 'admin', 'Review Submitted', 'Reviewer Mark Van Buladaco has submitted an evaluation for protocol: \"GWAPOO KO TESTG\".', 'rec_chair/decision.php?id=1', 1, '2026-04-29 04:25:11'),
(18, 19, 'admin', 'Review Submitted', 'Reviewer Mark Van Buladaco has submitted an evaluation for protocol: \"GWAPOO KO TESTG\".', 'rec_chair/decision.php?id=1', 1, '2026-04-29 04:25:11'),
(19, 22, 'author', 'Revision Required (Fast-Track)', 'All board reviews for \"GWAPOO KO TESTG\" are complete. Fast-track triggered.', 'shared_view?id=1', 1, '2026-04-29 06:49:27'),
(20, 22, 'author', 'Final Decision Rendered', 'The REC Board has rendered a final decision on your protocol: \"GWAPOO KO TESTG\". Decision: APPROVED', 'shared_view?id=1', 1, '2026-04-29 08:58:27'),
(21, 3, 'admin', 'Decision Rendered', 'The REC Chair has finalized the decision for protocol: \"GWAPOO KO TESTG\". Result: APPROVED', 'rec_staff/update_status.php?id=1', 0, '2026-04-29 08:58:27'),
(22, 22, 'author', 'Ethical Clearance Issued', 'Congratulations! The Ethical Clearance for your protocol \"GWAPOO KO TESTG\" has been officially released.', 'shared_view?id=1', 0, '2026-05-19 07:51:49');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `protocols`
--

CREATE TABLE `protocols` (
  `protocol_id` int(11) NOT NULL,
  `rec_code` varchar(50) DEFAULT NULL,
  `tracking_code` varchar(50) DEFAULT NULL,
  `sequence_number` int(11) DEFAULT NULL,
  `protocol_type` enum('INT','EXT') DEFAULT 'INT',
  `author_initials` varchar(10) DEFAULT NULL,
  `submission_confirmed_at` timestamp NULL DEFAULT NULL,
  `title` text NOT NULL,
  `project_leader` varchar(255) NOT NULL,
  `author_email` varchar(255) DEFAULT NULL,
  `institution` varchar(255) NOT NULL,
  `review_type` enum('pending','exempt','expedited','full_board') NOT NULL DEFAULT 'pending',
  `status` enum('submitted','staff_review','needs_revision','initial_review','confirmed','assigned','under_review','revised','approved','rejected','clearance_released') NOT NULL DEFAULT 'submitted',
  `recommendations` text DEFAULT NULL,
  `final_notified` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `is_secretary_assigned` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `form13_status` enum('pending','completed') DEFAULT 'pending',
  `forwarded_to_chair_at` timestamp NULL DEFAULT NULL,
  `protocol_deadline` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `protocols`
--

INSERT INTO `protocols` (`protocol_id`, `rec_code`, `tracking_code`, `sequence_number`, `protocol_type`, `author_initials`, `submission_confirmed_at`, `title`, `project_leader`, `author_email`, `institution`, `review_type`, `status`, `recommendations`, `final_notified`, `created_by`, `is_secretary_assigned`, `created_at`, `form13_status`, `forwarded_to_chair_at`, `protocol_deadline`) VALUES
(1, '2026-001-INT.-JPD', NULL, 1, 'INT', 'JPD', NULL, 'GWAPOO KO TESTG', 'Dela Pena, Jhanrex Philip, G', 'delapena.jhanrexphilip@dnsc.edu.ph', 'Davao Del Norte State College', 'full_board', 'clearance_released', 'congrats', 0, 22, 0, '2026-04-29 03:46:04', 'completed', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `protocol_files`
--

CREATE TABLE `protocol_files` (
  `file_id` int(11) NOT NULL,
  `protocol_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `document_type` varchar(50) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `protocol_files`
--

INSERT INTO `protocol_files` (`file_id`, `protocol_id`, `file_name`, `file_path`, `document_type`, `uploaded_at`) VALUES
(1, 1, 'Reasearch_G4_BSIT3E_FINAL.pdf', 'REC_1777434364_1_69f17efcb21aa.pdf', 'Protocol', '2026-04-29 03:46:04'),
(2, 1, 'REVISED_JABAGAT_GROUP_THE INFLUENCE OF INTERNET ACCESSIBILITY ON ONLINE LEARNING PERFORMANCE AMONG BSIT STUDENTS AT DAVAO DEL NORTE STATE COLLEGE.pdf', 'REC_REV_1777452995_1_69f1c7c3a572a.pdf', NULL, '2026-04-29 08:56:35');

-- --------------------------------------------------------

--
-- Table structure for table `reviewer_assignments`
--

CREATE TABLE `reviewer_assignments` (
  `assignment_id` int(11) NOT NULL,
  `protocol_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deadline` date DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviewer_assignments`
--

INSERT INTO `reviewer_assignments` (`assignment_id`, `protocol_id`, `reviewer_id`, `status`, `assigned_at`, `deadline`, `is_primary`) VALUES
(1, 1, 17, 'completed', '2026-04-29 03:54:22', '2026-05-13', 0),
(2, 1, 18, 'completed', '2026-04-29 03:54:22', '2026-05-13', 0),
(3, 1, 2, 'completed', '2026-04-29 03:54:22', '2026-05-13', 0),
(4, 1, 4, 'completed', '2026-04-29 03:54:22', '2026-05-13', 0),
(5, 1, 3, 'completed', '2026-04-29 03:54:22', '2026-05-13', 0),
(6, 1, 21, 'completed', '2026-04-29 03:54:22', '2026-05-13', 0),
(7, 1, 19, 'completed', '2026-04-29 03:54:22', '2026-05-13', 0);

-- --------------------------------------------------------

--
-- Table structure for table `reviewer_recommendations`
--

CREATE TABLE `reviewer_recommendations` (
  `rec_id` int(11) NOT NULL,
  `protocol_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `form_type` tinyint(2) NOT NULL COMMENT '10 or 12',
  `recommendation` enum('Approved','Minor Revision','Major Revision','Disapproved') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviewer_recommendations`
--

INSERT INTO `reviewer_recommendations` (`rec_id`, `protocol_id`, `reviewer_id`, `form_type`, `recommendation`, `notes`, `created_at`) VALUES
(1, 1, 19, 10, 'Approved', '', '2026-04-29 04:25:11'),
(2, 1, 19, 12, 'Approved', '', '2026-04-29 04:25:11'),
(3, 1, 17, 10, 'Approved', 'Fast-tracked review.', '2026-04-29 06:49:27'),
(4, 1, 18, 10, 'Approved', 'Fast-tracked review.', '2026-04-29 06:49:27'),
(5, 1, 2, 10, 'Approved', 'Fast-tracked review.', '2026-04-29 06:49:27'),
(6, 1, 4, 10, 'Approved', 'Fast-tracked review.', '2026-04-29 06:49:27'),
(7, 1, 3, 10, 'Approved', 'Fast-tracked review.', '2026-04-29 06:49:27'),
(8, 1, 21, 10, 'Approved', 'Fast-tracked review.', '2026-04-29 06:49:27');

-- --------------------------------------------------------

--
-- Table structure for table `secretary_assignments`
--

CREATE TABLE `secretary_assignments` (
  `assignment_id` int(11) NOT NULL,
  `protocol_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_initial` varchar(10) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','pending','suspended','inactive') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `signature` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `last_name`, `first_name`, `middle_initial`, `email`, `password`, `status`, `created_at`, `signature`) VALUES
(22, 'Dela Pena', 'Jhanrex Philip', 'G.', 'delapena.jhanrexphilip@dnsc.edu.ph', '$2y$10$87gdxZKBz3gJWoN5UZxH2OOciGjPtKa5nb49UO/2rrpbyPKRuZFs2', 'active', '2026-03-25 05:00:10', 'sig_1779179219_6a0c1ed3de2c2.png');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `final_decisions`
--
ALTER TABLE `final_decisions`
  ADD PRIMARY KEY (`decision_id`),
  ADD KEY `protocol_id` (`protocol_id`),
  ADD KEY `chair_id` (`chair_id`);

--
-- Indexes for table `form9_answers`
--
ALTER TABLE `form9_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `protocol_id` (`protocol_id`),
  ADD KEY `chair_id` (`chair_id`);

--
-- Indexes for table `form10_answers`
--
ALTER TABLE `form10_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `protocol_id` (`protocol_id`),
  ADD KEY `reviewer_id` (`reviewer_id`);

--
-- Indexes for table `form12_answers`
--
ALTER TABLE `form12_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `protocol_id` (`protocol_id`),
  ADD KEY `reviewer_id` (`reviewer_id`);

--
-- Indexes for table `form13_answers`
--
ALTER TABLE `form13_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `protocol_id` (`protocol_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `form15_responses`
--
ALTER TABLE `form15_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `protocol_id` (`protocol_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `form18a_responses`
--
ALTER TABLE `form18a_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `protocol_id` (`protocol_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `form19_responses`
--
ALTER TABLE `form19_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `protocol_id` (`protocol_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `member_files`
--
ALTER TABLE `member_files`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `protocol_id` (`protocol_id`),
  ADD KEY `reviewer_id` (`reviewer_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `protocols`
--
ALTER TABLE `protocols`
  ADD PRIMARY KEY (`protocol_id`),
  ADD UNIQUE KEY `rec_code` (`rec_code`),
  ADD UNIQUE KEY `tracking_code` (`tracking_code`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `protocol_files`
--
ALTER TABLE `protocol_files`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `protocol_id` (`protocol_id`);

--
-- Indexes for table `reviewer_assignments`
--
ALTER TABLE `reviewer_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `protocol_id` (`protocol_id`),
  ADD KEY `reviewer_id` (`reviewer_id`);

--
-- Indexes for table `reviewer_recommendations`
--
ALTER TABLE `reviewer_recommendations`
  ADD PRIMARY KEY (`rec_id`),
  ADD KEY `protocol_reviewer` (`protocol_id`,`reviewer_id`);

--
-- Indexes for table `secretary_assignments`
--
ALTER TABLE `secretary_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `protocol_id` (`protocol_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `final_decisions`
--
ALTER TABLE `final_decisions`
  MODIFY `decision_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `form9_answers`
--
ALTER TABLE `form9_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `form10_answers`
--
ALTER TABLE `form10_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `form12_answers`
--
ALTER TABLE `form12_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `form13_answers`
--
ALTER TABLE `form13_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `form15_responses`
--
ALTER TABLE `form15_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `form18a_responses`
--
ALTER TABLE `form18a_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `form19_responses`
--
ALTER TABLE `form19_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `member_files`
--
ALTER TABLE `member_files`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `protocols`
--
ALTER TABLE `protocols`
  MODIFY `protocol_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `protocol_files`
--
ALTER TABLE `protocol_files`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reviewer_assignments`
--
ALTER TABLE `reviewer_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reviewer_recommendations`
--
ALTER TABLE `reviewer_recommendations`
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `secretary_assignments`
--
ALTER TABLE `secretary_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `final_decisions`
--
ALTER TABLE `final_decisions`
  ADD CONSTRAINT `final_decisions_ibfk_1` FOREIGN KEY (`protocol_id`) REFERENCES `protocols` (`protocol_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `final_decisions_ibfk_2` FOREIGN KEY (`chair_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `form10_answers`
--
ALTER TABLE `form10_answers`
  ADD CONSTRAINT `form10_answers_ibfk_1` FOREIGN KEY (`protocol_id`) REFERENCES `protocols` (`protocol_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `form10_answers_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `form12_answers`
--
ALTER TABLE `form12_answers`
  ADD CONSTRAINT `form12_answers_ibfk_1` FOREIGN KEY (`protocol_id`) REFERENCES `protocols` (`protocol_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `form12_answers_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `member_files`
--
ALTER TABLE `member_files`
  ADD CONSTRAINT `member_files_ibfk_1` FOREIGN KEY (`protocol_id`) REFERENCES `protocols` (`protocol_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `member_files_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `protocol_files`
--
ALTER TABLE `protocol_files`
  ADD CONSTRAINT `protocol_files_ibfk_1` FOREIGN KEY (`protocol_id`) REFERENCES `protocols` (`protocol_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviewer_assignments`
--
ALTER TABLE `reviewer_assignments`
  ADD CONSTRAINT `reviewer_assignments_ibfk_1` FOREIGN KEY (`protocol_id`) REFERENCES `protocols` (`protocol_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviewer_assignments_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `secretary_assignments`
--
ALTER TABLE `secretary_assignments`
  ADD CONSTRAINT `secretary_assignments_ibfk_1` FOREIGN KEY (`protocol_id`) REFERENCES `protocols` (`protocol_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `secretary_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
