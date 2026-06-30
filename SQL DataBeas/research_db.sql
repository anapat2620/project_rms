-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 17, 2026 at 12:52 PM
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
-- Database: `research_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `data`
--

CREATE TABLE `data` (
  `ID` int(11) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Username` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Facuity` varchar(255) NOT NULL COMMENT 'สะกดคงเดิมเพื่อความเข้ากันได้',
  `Position` varchar(255) NOT NULL,
  `Quantity` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `data`
--

INSERT INTO `data` (`ID`, `Password`, `Username`, `Email`, `Facuity`, `Position`, `Quantity`) VALUES
(1, 'admin', 'ผู้ดูแลระบบ', 'admin@msu.ac.th', 'คณะการบัญชี และการจัดการ', 'Admin', 0),
(2, 'boss', 'boss', 'boss@msu.ac.th', 'คณะการบัญชี และการจัดการ', 'คณบดี', 0);

-- --------------------------------------------------------

--
-- Table structure for table `disbursement_items`
--

CREATE TABLE `disbursement_items` (
  `id` int(11) NOT NULL,
  `fiscal_year` int(11) NOT NULL,
  `description` varchar(500) NOT NULL COMMENT 'รายละเอียดการเบิกจ่าย',
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'จำนวนเงิน',
  `disbursement_date` date NOT NULL COMMENT 'วันที่เบิกจ่าย',
  `created_by` varchar(255) NOT NULL COMMENT 'ผู้สร้าง',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disbursement_summary`
--

CREATE TABLE `disbursement_summary` (
  `id` int(11) NOT NULL,
  `fiscal_year` int(11) NOT NULL,
  `budget_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `disbursed_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `updated_by` varchar(255) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculties`
--

CREATE TABLE `faculties` (
  `id` int(11) NOT NULL,
  `faculty_name` varchar(255) NOT NULL COMMENT 'ชื่อคณะ',
  `faculty_code` varchar(10) NOT NULL COMMENT 'รหัสคณะ',
  `description` text DEFAULT NULL COMMENT 'คำอธิบายคณะ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่สร้าง'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faculties`
--

INSERT INTO `faculties` (`id`, `faculty_name`, `faculty_code`, `description`, `created_at`) VALUES
(0, 'คณะการบัญชี และการจัดการ', 'ACC', 'คณะการบัญชีและการจัดการ มหาวิทยาลัยมหาสารคาม', '2025-02-24 10:43:00'),
(0, 'คณะนิติศาสตร์', 'LAW', 'คณะนิติศาสตร์ มหาวิทยาลัยมหาสารคาม', '2025-02-24 10:43:00'),
(0, 'คณะรัฐศาสตร์', 'POL', 'คณะรัฐศาสตร์ มหาวิทยาลัยมหาสารคาม', '2025-02-24 10:43:00'),
(0, 'คณะเศรษฐศาสตร์', 'ECO', 'คณะเศรษฐศาสตร์ มหาวิทยาลัยมหาสารคาม', '2025-02-24 10:43:00'),
(0, 'คณะบริหารธุรกิจ', 'BUS', 'คณะบริหารธุรกิจ มหาวิทยาลัยมหาสารคาม', '2025-02-24 10:43:00'),
(0, 'คณะการท่องเที่ยวและการโรงแรม', 'TOUR', 'คณะการท่องเที่ยวและการโรงแรม มหาวิทยาลัยมหาสารคาม', '2025-02-24 10:43:00'),
(0, 'คณะการบัญชี และการจัดการ', 'ACC', 'คณะการบัญชีและการจัดการ มหาวิทยาลัยมหาสารคาม', '2025-02-24 10:43:00');

-- --------------------------------------------------------

--
-- Table structure for table `fund_disbursement_history`
--

CREATE TABLE `fund_disbursement_history` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `disbursement_phase` enum('1st','2nd','3rd') NOT NULL COMMENT 'งวดการจ่ายเงิน',
  `status` enum('รอการจ่าย','จ่ายแล้ว','ไม่จ่าย') NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL COMMENT 'จำนวนเงิน',
  `disbursement_date` datetime DEFAULT NULL COMMENT 'วันที่จ่ายเงิน',
  `comment` text DEFAULT NULL COMMENT 'หมายเหตุ',
  `updated_by` varchar(255) NOT NULL COMMENT 'ผู้อัปเดต',
  `updated_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่อัปเดต',
  `proof_link` varchar(500) DEFAULT NULL COMMENT 'ลิงก์หลักฐานการจ่ายเงิน'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fund_support`
--

CREATE TABLE `fund_support` (
  `FunID` varchar(10) NOT NULL,
  `FunName` varchar(50) NOT NULL,
  `BH1` int(11) NOT NULL,
  `BH2` int(11) NOT NULL,
  `B3` int(11) NOT NULL,
  `TH_Bath` decimal(12,2) NOT NULL,
  `Year` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fund_support`
--

INSERT INTO `fund_support` (`FunID`, `FunName`, `BH1`, `BH2`, `B3`, `TH_Bath`, `Year`) VALUES
('F011', 'F-FastTrackQ1', 50, 50, 0, 140000.00, 2568),
('F012', 'F-FastTrackQ2', 50, 50, 0, 120000.00, 2568),
('F013', 'F-FastTrackQ3', 50, 50, 0, 120000.00, 2568),
('F014', 'F-FastTrackQ4', 50, 50, 0, 100000.00, 2568),
('F020', 'F-BachSTD', 50, 30, 20, 15000.00, 2568),
('F030', 'F-GradSTD', 50, 30, 20, 100000.00, 2568),
('F040', 'F-Officer', 50, 30, 20, 30000.00, 2568),
('F050', 'F-Innovation', 50, 30, 20, 140000.00, 2568),
('F060', 'F-FF', 60, 30, 10, 140000.00, 2568),
('F070', 'F-666', 20, 20, 60, 1000000.00, 2569);

-- --------------------------------------------------------

--
-- Table structure for table `fund_type_selections`
--

CREATE TABLE `fund_type_selections` (
  `id` int(11) NOT NULL,
  `fund_name` varchar(50) NOT NULL COMMENT 'ชื่อประเภททุน (FunName)',
  `selection_count` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนครั้งที่ถูกเลือก',
  `table_source` varchar(50) DEFAULT NULL COMMENT 'ตารางต้นทาง (research_proposals, research_teacher, research_personnel)',
  `proposal_id` int(11) DEFAULT NULL COMMENT 'ID ของคำขอ',
  `selected_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่เลือก',
  `year` int(11) DEFAULT NULL COMMENT 'ปีงบประมาณ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางเก็บสถิติการเลือกประเภททุน';

-- --------------------------------------------------------

--
-- Table structure for table `news_board`
--

CREATE TABLE `news_board` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'หัวข้อข่าว',
  `content` text NOT NULL COMMENT 'เนื้อหาข่าว',
  `date_posted` date NOT NULL COMMENT 'วันที่ประกาศ',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'สถานะการแสดงผล',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่สร้าง',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'วันที่อัปเดต'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news_board`
--

INSERT INTO `news_board` (`id`, `title`, `content`, `date_posted`, `is_active`, `created_at`, `updated_at`) VALUES
(0, 'ระบบเปิดรับสมัครทุนวิจัยรอบใหม่แล้ววันนี้', 'ระบบเปิดรับสมัครทุนวิจัยรอบใหม่แล้ววันนี้ โปรดตรวจสอบข้อมูลและยื่นคำขอภายในวันที่กำหนด', '2025-07-10', 1, '2026-01-06 19:12:29', '2026-01-06 19:12:29'),
(0, 'ประกาศรายชื่อผู้ได้รับทุนรอบที่ผ่านมา', 'ประกาศรายชื่อผู้ได้รับทุนรอบที่ผ่านมา <a href=\"#\" style=\"color:#007bff;text-decoration:underline;\">ดูรายละเอียด</a>', '2025-07-05', 1, '2026-01-06 19:12:29', '2026-01-06 19:12:29'),
(0, 'โปรดตรวจสอบข้อมูลส่วนตัวให้ถูกต้องก่อนยื่นขอทุน', 'โปรดตรวจสอบข้อมูลส่วนตัวให้ถูกต้องก่อนยื่นขอทุน เพื่อความรวดเร็วในการพิจารณา', '2025-07-01', 1, '2026-01-06 19:12:29', '2026-01-06 19:12:29'),
(0, 'ระบบเปิดรับสมัครทุนวิจัยรอบใหม่แล้ววันนี้', 'ระบบเปิดรับสมัครทุนวิจัยรอบใหม่แล้ววันนี้ โปรดตรวจสอบข้อมูลและยื่นคำขอภายในวันที่กำหนด', '2025-07-10', 1, '2026-01-06 19:12:29', '2026-01-06 19:12:29'),
(0, 'ประกาศรายชื่อผู้ได้รับทุนรอบที่ผ่านมา', 'ประกาศรายชื่อผู้ได้รับทุนรอบที่ผ่านมา <a href=\"#\" style=\"color:#007bff;text-decoration:underline;\">ดูรายละเอียด</a>', '2025-07-05', 1, '2026-01-06 19:12:29', '2026-01-06 19:12:29'),
(0, 'โปรดตรวจสอบข้อมูลส่วนตัวให้ถูกต้องก่อนยื่นขอทุน', 'โปรดตรวจสอบข้อมูลส่วนตัวให้ถูกต้องก่อนยื่นขอทุน เพื่อความรวดเร็วในการพิจารณา', '2025-07-01', 1, '2026-01-06 19:12:29', '2026-01-06 19:12:29');

-- --------------------------------------------------------

--
-- Table structure for table `research_personnel`
--

CREATE TABLE `research_personnel` (
  `id` int(11) NOT NULL,
  `project_th` varchar(500) NOT NULL,
  `project_en` varchar(500) DEFAULT NULL,
  `leader_firstname` varchar(255) NOT NULL,
  `leader_lastname` varchar(255) NOT NULL,
  `leader_position` varchar(255) DEFAULT NULL,
  `leader_department` varchar(255) DEFAULT NULL,
  `leader_phone` varchar(20) DEFAULT NULL,
  `leader_email` varchar(255) DEFAULT NULL,
  `leader_ratio` int(3) DEFAULT NULL,
  `co_researchers` text DEFAULT NULL,
  `msu_goals` varchar(255) DEFAULT NULL,
  `research_type` varchar(255) DEFAULT NULL,
  `learning_research` varchar(255) DEFAULT NULL,
  `activities` text DEFAULT NULL,
  `research_field` text DEFAULT NULL,
  `problem_importance` text DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `literature_review` text DEFAULT NULL,
  `methodology` text DEFAULT NULL,
  `research_schedule` text DEFAULT NULL,
  `success_indicators` text DEFAULT NULL,
  `budget_details` text DEFAULT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `proposal_file_path` varchar(255) DEFAULT NULL COMMENT 'ไฟล์เอกสารข้อเสนอโครงการวิจัย',
  `additional_file_path` varchar(255) DEFAULT NULL COMMENT 'ไฟล์เอกสารประกอบเพิ่มเติม',
  `fund_support` varchar(50) DEFAULT NULL COMMENT 'ประเภททุนสนับสนุน (FunName)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_proposals`
--

CREATE TABLE `research_proposals` (
  `id` int(11) NOT NULL,
  `project_th` varchar(255) DEFAULT NULL,
  `project_en` varchar(255) DEFAULT NULL,
  `student_firstname` varchar(100) DEFAULT NULL,
  `student_lastname` varchar(100) DEFAULT NULL,
  `student_level` varchar(50) DEFAULT NULL,
  `student_year` int(11) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `curriculum` varchar(255) DEFAULT NULL,
  `major` varchar(255) DEFAULT NULL,
  `faculty` varchar(255) DEFAULT NULL,
  `student_phone` varchar(20) DEFAULT NULL,
  `student_email` varchar(255) DEFAULT NULL,
  `student_ratio` decimal(5,2) DEFAULT NULL,
  `advisor_firstname` varchar(100) DEFAULT NULL,
  `advisor_lastname` varchar(100) DEFAULT NULL,
  `advisor_position` varchar(255) DEFAULT NULL,
  `advisor_department` varchar(255) DEFAULT NULL,
  `advisor_faculty` varchar(255) DEFAULT NULL,
  `advisor_phone` varchar(20) DEFAULT NULL,
  `advisor_email` varchar(255) DEFAULT NULL,
  `advisor_ratio` decimal(5,2) DEFAULT NULL,
  `advisor_student_count` int(11) DEFAULT NULL,
  `research_type` text DEFAULT NULL,
  `learning_type` text DEFAULT NULL,
  `activities` text DEFAULT NULL,
  `research_field` text DEFAULT NULL,
  `rationale` text DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `importance` text DEFAULT NULL,
  `literature` text DEFAULT NULL,
  `conceptual_framework` text DEFAULT NULL,
  `hypothesis` text DEFAULT NULL,
  `methodology` text DEFAULT NULL,
  `references_link` text DEFAULT NULL,
  `research_start` date DEFAULT NULL,
  `research_end` date DEFAULT NULL,
  `research_schedule` text DEFAULT NULL,
  `success_indicators` text DEFAULT NULL,
  `publication_title` varchar(255) DEFAULT NULL,
  `journal_name` varchar(255) DEFAULT NULL,
  `requested_budget` decimal(10,2) DEFAULT NULL,
  `budget_details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `proposal_file_path` varchar(255) DEFAULT NULL COMMENT 'ไฟล์เอกสารข้อเสนอโครงการวิจัย',
  `additional_file_path` varchar(255) DEFAULT NULL COMMENT 'ไฟล์เอกสารประกอบเพิ่มเติม',
  `fund_support` varchar(50) DEFAULT NULL COMMENT 'ประเภททุนสนับสนุน (FunName)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_requests_status`
--

CREATE TABLE `research_requests_status` (
  `request_id` int(11) NOT NULL,
  `original_table` varchar(50) NOT NULL COMMENT 'ระบุตารางต้นทาง (personnel, proposals, teacher)',
  `original_id` int(11) NOT NULL COMMENT 'ID ของรายการในตารางต้นทาง',
  `project_name` varchar(255) NOT NULL COMMENT 'ชื่อโครงการ',
  `submission_date` datetime NOT NULL COMMENT 'วันที่ยื่นคำขอ',
  `requesting_user_email` varchar(255) NOT NULL COMMENT 'อีเมลผู้ยื่นคำขอ',
  `requesting_user_name` varchar(255) NOT NULL COMMENT 'ชื่อผู้ยื่นคำขอ',
  `current_status` enum('รออนุมัติ','อนุมัติ','ปฏิเสธ','ยกเลิกแล้ว') NOT NULL DEFAULT 'รออนุมัติ',
  `approver_username` varchar(255) DEFAULT NULL COMMENT 'Username ผู้อนุมัติ/ปฏิเสธ',
  `action_date` datetime DEFAULT NULL COMMENT 'วันที่อนุมัติ/ปฏิเสธ',
  `comment` text DEFAULT NULL COMMENT 'ความคิดเห็น/ข้อเสนอแนะจากผู้อนุมัติ',
  `fund_disbursement_1st_status` enum('รอการจ่าย','จ่ายแล้ว','ไม่จ่าย') DEFAULT 'รอการจ่าย' COMMENT 'สถานะการจ่ายเงินงวดที่ 1',
  `fund_disbursement_1st_date` datetime DEFAULT NULL COMMENT 'วันที่จ่ายเงินงวดที่ 1',
  `fund_disbursement_1st_amount` decimal(10,2) DEFAULT NULL COMMENT 'จำนวนเงินงวดที่ 1',
  `fund_disbursement_1st_comment` text DEFAULT NULL COMMENT 'หมายเหตุการจ่ายเงินงวดที่ 1',
  `fund_disbursement_2nd_status` enum('รอการจ่าย','จ่ายแล้ว','ไม่จ่าย') DEFAULT 'รอการจ่าย' COMMENT 'สถานะการจ่ายเงินงวดที่ 2',
  `fund_disbursement_2nd_date` datetime DEFAULT NULL COMMENT 'วันที่จ่ายเงินงวดที่ 2',
  `fund_disbursement_2nd_amount` decimal(10,2) DEFAULT NULL COMMENT 'จำนวนเงินงวดที่ 2',
  `fund_disbursement_2nd_comment` text DEFAULT NULL COMMENT 'หมายเหตุการจ่ายเงินงวดที่ 2',
  `fund_disbursement_3rd_status` enum('รอการจ่าย','จ่ายแล้ว','ไม่จ่าย') DEFAULT 'รอการจ่าย' COMMENT 'สถานะการจ่ายเงินงวดที่ 3',
  `fund_disbursement_3rd_date` datetime DEFAULT NULL COMMENT 'วันที่จ่ายเงินงวดที่ 3',
  `fund_disbursement_3rd_amount` decimal(10,2) DEFAULT NULL COMMENT 'จำนวนเงินงวดที่ 3',
  `fund_disbursement_3rd_comment` text DEFAULT NULL COMMENT 'หมายเหตุการจ่ายเงินงวดที่ 3',
  `fund_disbursement_updated_by` varchar(255) DEFAULT NULL COMMENT 'ผู้อัปเดตสถานะการจ่ายเงิน',
  `fund_disbursement_updated_date` datetime DEFAULT NULL COMMENT 'วันที่อัปเดตสถานะการจ่ายเงินล่าสุด',
  `fund_disbursement_1st_proof_link` varchar(500) DEFAULT NULL COMMENT 'ลิงก์หลักฐานการจ่ายเงินงวดที่ 1',
  `fund_disbursement_2nd_proof_link` varchar(500) DEFAULT NULL COMMENT 'ลิงก์หลักฐานการจ่ายเงินงวดที่ 2',
  `fund_disbursement_3rd_proof_link` varchar(500) DEFAULT NULL COMMENT 'ลิงก์หลักฐานการจ่ายเงินงวดที่ 3'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_request_comments`
--

CREATE TABLE `research_request_comments` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `commenter_email` varchar(255) NOT NULL,
  `commenter_name` varchar(255) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_teacher`
--

CREATE TABLE `research_teacher` (
  `id` int(11) NOT NULL,
  `project_thai_name` text NOT NULL,
  `project_english_name` text NOT NULL,
  `teacher_prefix_name` varchar(50) DEFAULT NULL,
  `teacher_academic_position` varchar(255) DEFAULT NULL,
  `teacher_department` varchar(255) DEFAULT NULL,
  `teacher_faculty_unit` varchar(255) DEFAULT NULL,
  `teacher_mobile_phone` varchar(20) DEFAULT NULL,
  `teacher_email` varchar(255) DEFAULT NULL,
  `teacher_research_proportion` decimal(5,2) DEFAULT NULL,
  `teacher_expert_field` text DEFAULT NULL,
  `teacher_education_history` text DEFAULT NULL,
  `teacher_international_publications` text DEFAULT NULL,
  `co_researchers_details` text DEFAULT NULL,
  `student_co_researchers_details` text DEFAULT NULL,
  `research_type` varchar(255) DEFAULT NULL,
  `msu_goals` varchar(255) DEFAULT NULL,
  `ethics_related` varchar(255) DEFAULT NULL,
  `ethics_certification_number` varchar(255) DEFAULT NULL,
  `problem_significance` text DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `literature_review` text DEFAULT NULL,
  `methodology` text DEFAULT NULL,
  `research_period` varchar(255) DEFAULT NULL,
  `operation_plan` text DEFAULT NULL,
  `expected_outcomes` text DEFAULT NULL,
  `budget_details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `proposal_file_path` varchar(255) DEFAULT NULL COMMENT 'ไฟล์เอกสารข้อเสนอโครงการวิจัย',
  `additional_file_path` varchar(255) DEFAULT NULL COMMENT 'ไฟล์เอกสารประกอบเพิ่มเติม',
  `fund_support` varchar(50) DEFAULT NULL COMMENT 'ประเภททุนสนับสนุน (FunName)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `data`
--
ALTER TABLE `data`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uniq_email` (`Email`);

--
-- Indexes for table `disbursement_items`
--
ALTER TABLE `disbursement_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_disbursement_date` (`disbursement_date`);

--
-- Indexes for table `disbursement_summary`
--
ALTER TABLE `disbursement_summary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_fiscal_year` (`fiscal_year`);

--
-- Indexes for table `fund_disbursement_history`
--
ALTER TABLE `fund_disbursement_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `disbursement_phase` (`disbursement_phase`),
  ADD KEY `status` (`status`),
  ADD KEY `updated_date` (`updated_date`);

--
-- Indexes for table `fund_support`
--
ALTER TABLE `fund_support`
  ADD PRIMARY KEY (`FunID`);

--
-- Indexes for table `fund_type_selections`
--
ALTER TABLE `fund_type_selections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fund_name` (`fund_name`),
  ADD KEY `idx_table_source` (`table_source`),
  ADD KEY `idx_year` (`year`);

--
-- Indexes for table `research_personnel`
--
ALTER TABLE `research_personnel`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `research_proposals`
--
ALTER TABLE `research_proposals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `research_requests_status`
--
ALTER TABLE `research_requests_status`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `uniq_original` (`original_table`,`original_id`),
  ADD KEY `idx_original` (`original_table`,`original_id`),
  ADD KEY `idx_submission_date` (`submission_date`),
  ADD KEY `idx_current_status` (`current_status`),
  ADD KEY `idx_approver_username` (`approver_username`),
  ADD KEY `idx_requesting_user_email` (`requesting_user_email`),
  ADD KEY `idx_action_date` (`action_date`),
  ADD KEY `idx_project_name` (`project_name`),
  ADD KEY `idx_fund_disbursement_1st_status` (`fund_disbursement_1st_status`),
  ADD KEY `idx_fund_disbursement_2nd_status` (`fund_disbursement_2nd_status`),
  ADD KEY `idx_fund_disbursement_3rd_status` (`fund_disbursement_3rd_status`),
  ADD KEY `idx_fund_disbursement_updated_date` (`fund_disbursement_updated_date`);

--
-- Indexes for table `research_request_comments`
--
ALTER TABLE `research_request_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `research_teacher`
--
ALTER TABLE `research_teacher`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `data`
--
ALTER TABLE `data`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `disbursement_items`
--
ALTER TABLE `disbursement_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `disbursement_summary`
--
ALTER TABLE `disbursement_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fund_disbursement_history`
--
ALTER TABLE `fund_disbursement_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `fund_type_selections`
--
ALTER TABLE `fund_type_selections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `research_personnel`
--
ALTER TABLE `research_personnel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `research_proposals`
--
ALTER TABLE `research_proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `research_requests_status`
--
ALTER TABLE `research_requests_status`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `research_request_comments`
--
ALTER TABLE `research_request_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `research_teacher`
--
ALTER TABLE `research_teacher`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `fund_disbursement_history`
--
ALTER TABLE `fund_disbursement_history`
  ADD CONSTRAINT `fk_fund_disbursement_request_id` FOREIGN KEY (`request_id`) REFERENCES `research_requests_status` (`request_id`) ON DELETE CASCADE;

--
-- Constraints for table `research_request_comments`
--
ALTER TABLE `research_request_comments`
  ADD CONSTRAINT `fk_request_id` FOREIGN KEY (`request_id`) REFERENCES `research_requests_status` (`request_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
