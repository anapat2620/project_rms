-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 17, 2025 at 08:35 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `msu`
--

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `budget_year` varchar(10) NOT NULL,
  `project_name_th` varchar(255) NOT NULL,
  `project_name_en` varchar(255) NOT NULL,
  `research_type` varchar(255) NOT NULL,
  `research_goal` text NOT NULL,
  `budget_request` decimal(10,2) NOT NULL,
  `project_type` varchar(255) NOT NULL DEFAULT 'ไม่ระบุ',
  `status` varchar(50) NOT NULL DEFAULT 'รอการอนุมัติ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `budget_year`, `project_name_th`, `project_name_en`, `research_type`, `research_goal`, `budget_request`, `project_type`, `status`) VALUES
(1, '1', 'asda', 'asdasd', 'asdasdasd', 'ฟหกฟหก', 0.00, 'ไม่ระบุ', 'ปฏิเสธ'),
(4, '1', 'ฟหกฟหก', 'ฟหกฟหก', 'ฟหกฟหก', 'ฟหกฟหก', 0.00, 'ไม่ระบุ', 'ปฏิเสธ'),
(5, '1', 'ฟหกฟหก', 'ฟหกฟหก', 'ฟหกฟหก', 'ฟหกฟหก', 0.00, 'ไม่ระบุ', 'ปฏิเสธ'),
(6, '3', 'ฟหกฟหก', 'ฟหกฟหก', 'ฟหกฟหก', 'ฟหกฟหก', 0.00, 'ไม่ระบุ', 'ปฏิเสธ'),
(7, '1', 'โครงการกู้เ', '', '', '', 0.00, 'ไม่ระบุ', 'ปฏิเสธ'),
(8, '1', 'โครงการกู้เงิน', 'โครงการกู้เงิน', 'เงิน', 'เงิน', 200000.00, 'ไม่ระบุ', 'ปฏิเสธ');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
