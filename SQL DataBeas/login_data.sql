-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 24, 2025 at 10:43 AM
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
-- Database: `data_db`
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
  `Facuity` varchar(255) NOT NULL,
  `Position` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `data`
--

INSERT INTO `data` (`ID`, `Password`, `Username`, `Email`, `Facuity`, `Position`) VALUES
(1, '111A', 'ธีรชัย วัฒนาภิรมย์', '111A@msu.ac.th', 'คณะการบัญชี และการจัดการ', 'ปริญญาตรี '),
(2, '111B', 'วีรชัย ศรีสุข', '111B@msu.ac.th', 'คณะการบัญชี และการจัดการ', 'ปริญญาโท'),
(3, '111C', 'พงษ์ศักดิ์ บุญญาวัฒน์', '111C@msu.ac.th', 'คณะการบัญชี และการจัดการ', 'ปริญญาเอก'),
(4, '111D', 'อ.ดร.ชัยวัฒน์ ศิริกุล ', '111D@msu.ac.th', 'คณะการบัญชี และการจัดการ', 'บุคลากรวิชาการ'),
(5, '111E', 'รศ.ดร.ธรรมนูญ มณีรัตน์', '111E@msu.ac.th', 'คณะการบัญชี และการจัดการ', 'อธิการบดี'),
(6, '111F', 'รศ.ดร.ชัยวัฒน์ พรทิพย์', '111F@msu.ac.th', 'คณะการบัญชี และการจัดการ', 'รองอธิการบดี'),
(7, '111G', 'รศ.ดร.ชญาดา ธีรชัย', '111G@msu.ac.th', 'คณะการบัญชี และการจัดการ', 'ผู้ช่วยอธิการบดี');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `data`
--
ALTER TABLE `data`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `data`
--
ALTER TABLE `data`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
