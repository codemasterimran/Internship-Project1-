-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 16, 2025 at 09:14 AM
-- Server version: 11.5.2-MariaDB
-- PHP Version: 8.2.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `test`
--

-- --------------------------------------------------------

--
-- Table structure for table `emp_ctcdetails_info`
--

CREATE TABLE `emp_ctcdetails_info` (
  `indo_code` varchar(10) NOT NULL,
  `stateName` int(11) DEFAULT 1 COMMENT 'id from master_circles_info table ',
  `category` enum('ABE','fcm','outpar','other','contract') DEFAULT NULL,
  `skill_status` varchar(100) DEFAULT NULL,
  `basic` varchar(10) DEFAULT NULL,
  `daily_allowance` float DEFAULT NULL,
  `hra` varchar(10) DEFAULT NULL,
  `laptopOwner` varchar(255) DEFAULT 'N/A',
  `laptopRent` varchar(10) DEFAULT '0',
  `lapSecurityIns` varchar(11) DEFAULT NULL,
  `lapSecurityTotalAmt` varchar(11) DEFAULT NULL,
  `lapsecurityDedcution` tinyint(1) DEFAULT NULL,
  `tkOwner` varchar(255) DEFAULT 'N/A',
  `tkRent` varchar(10) DEFAULT NULL,
  `tkSecurityIns` varchar(11) DEFAULT NULL,
  `tkSecurityTotalAmt` varchar(11) DEFAULT NULL,
  `tksecurityDedcution` tinyint(1) DEFAULT NULL,
  `sktOwner` varchar(255) DEFAULT 'N/A',
  `sktRent` varchar(10) DEFAULT NULL,
  `sktSecurityIns` varchar(11) DEFAULT NULL,
  `sktSecurityTotalAmt` varchar(11) DEFAULT NULL,
  `sktsecurityDedcution` tinyint(1) DEFAULT NULL,
  `mobileOwner` varchar(255) DEFAULT 'N/A',
  `mobileRent` varchar(11) DEFAULT NULL,
  `mobileSecurityIns` varchar(11) DEFAULT NULL,
  `mobileSecurityTotalAmt` varchar(11) DEFAULT NULL,
  `mobilesecurityDedcution` tinyint(1) DEFAULT NULL,
  `conveyance_allowance` float DEFAULT NULL,
  `books_periodicals` varchar(11) DEFAULT NULL,
  `washing_allowance` float DEFAULT NULL,
  `food_allowance` float DEFAULT NULL,
  `transportation_charges` float(10,2) DEFAULT NULL,
  `vehicle_remb` varchar(11) DEFAULT NULL,
  `other_allowance` varchar(11) DEFAULT NULL,
  `bonus` varchar(10) DEFAULT NULL,
  `attendance_bonus` float DEFAULT NULL,
  `cityCompensatoryAllowance` varchar(10) DEFAULT 'N/A',
  `medical_allowance` varchar(10) DEFAULT NULL,
  `mobile_allowance` float DEFAULT NULL,
  `Lta` varchar(10) DEFAULT NULL,
  `specialAllowance` varchar(10) DEFAULT 'N/A',
  `lwa` float NOT NULL DEFAULT 0,
  `gross_salary` float DEFAULT NULL,
  `lwf` varchar(11) DEFAULT NULL,
  `ESIC` varchar(10) DEFAULT 'N/A',
  `mediclaim` varchar(10) DEFAULT 'N/A',
  `canteen` float NOT NULL DEFAULT 0,
  `uniform` float DEFAULT NULL,
  `emp_provident_fund` float DEFAULT NULL,
  `PF_flag` tinyint(1) DEFAULT NULL COMMENT '0=Fixed , 1=variable,2=new fixed,3=new variable',
  `professional_tax` varchar(10) DEFAULT 'N/A',
  `transaction_charge` varchar(10) DEFAULT 'N/A',
  `tds` float DEFAULT NULL,
  `other_deduction` float DEFAULT NULL,
  `admin_charges` float DEFAULT NULL,
  `netSalary` varchar(11) DEFAULT NULL,
  `employer_ESIC` varchar(10) DEFAULT 'N/A',
  `employer_lwf` float DEFAULT 0,
  `employer_providentFund` varchar(10) DEFAULT NULL,
  `leave_encashment` float DEFAULT NULL,
  `gratuity` int(11) DEFAULT NULL,
  `annual_bonus` float DEFAULT NULL,
  `yearly_variable` float DEFAULT 0,
  `monthly_ctc` float DEFAULT NULL,
  `poValue` varchar(10) DEFAULT '0',
  `remarks` text DEFAULT NULL,
  `increment_applicable_from` date DEFAULT NULL,
  `status` enum('Confirmed','Pending','Rejected') DEFAULT NULL,
  `offeredBy` varchar(255) DEFAULT NULL,
  `offeredDate` date DEFAULT NULL,
  `confirmedBy` varchar(255) DEFAULT NULL,
  `confirmedDate` varchar(15) DEFAULT NULL,
  `lastUpdate` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `lastUpdatedBy` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `emp_ctcdetails_info`
--

INSERT INTO `emp_ctcdetails_info` (`indo_code`, `stateName`, `category`, `skill_status`, `basic`, `daily_allowance`, `hra`, `laptopOwner`, `laptopRent`, `lapSecurityIns`, `lapSecurityTotalAmt`, `lapsecurityDedcution`, `tkOwner`, `tkRent`, `tkSecurityIns`, `tkSecurityTotalAmt`, `tksecurityDedcution`, `sktOwner`, `sktRent`, `sktSecurityIns`, `sktSecurityTotalAmt`, `sktsecurityDedcution`, `mobileOwner`, `mobileRent`, `mobileSecurityIns`, `mobileSecurityTotalAmt`, `mobilesecurityDedcution`, `conveyance_allowance`, `books_periodicals`, `washing_allowance`, `food_allowance`, `transportation_charges`, `vehicle_remb`, `other_allowance`, `bonus`, `attendance_bonus`, `cityCompensatoryAllowance`, `medical_allowance`, `mobile_allowance`, `Lta`, `specialAllowance`, `lwa`, `gross_salary`, `lwf`, `ESIC`, `mediclaim`, `canteen`, `uniform`, `emp_provident_fund`, `PF_flag`, `professional_tax`, `transaction_charge`, `tds`, `other_deduction`, `admin_charges`, `netSalary`, `employer_ESIC`, `employer_lwf`, `employer_providentFund`, `leave_encashment`, `gratuity`, `annual_bonus`, `yearly_variable`, `monthly_ctc`, `poValue`, `remarks`, `increment_applicable_from`, `status`, `offeredBy`, `offeredDate`, `confirmedBy`, `confirmedDate`, `lastUpdate`, `lastUpdatedBy`) VALUES
('AMS-003', 11, 'fcm', NULL, '12646', NULL, '4246', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '1108', 0, 18000, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 18000, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-004', 11, 'fcm', NULL, '12646', NULL, '2500', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '4992', 0, 20138, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 20138, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-005', 11, 'fcm', NULL, '14000', NULL, '4000', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 1320, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '4500', 0, 23820, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 23820, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-006', 11, 'fcm', NULL, '13000', NULL, '3000', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 2000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '3000', 0, 21000, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 21000, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-007', 11, 'fcm', NULL, '12646', NULL, '3750', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '1354', 0, 17750, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 17750, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-008', 11, 'fcm', NULL, '12646', NULL, '2700', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 2408, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '1196', 0, 18950, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 18950, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-009', 11, 'fcm', NULL, '10924', NULL, '4683', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '1738', 0, 17345, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 17345, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-010', 11, 'fcm', NULL, '13504', NULL, '2496', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '2000', 0, 18000, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 18000, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-011', 11, 'fcm', NULL, '15000', NULL, '7500', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 553, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '3450', 0, 26503, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 26503, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-012', 11, 'fcm', NULL, '12000', NULL, '6500', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '1500', 0, 20000, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 20000, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-013', 11, 'fcm', NULL, '12930', NULL, '6465', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 553, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '2042', 0, 21990, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 21990, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-014', 11, 'fcm', NULL, '19000', NULL, '8500', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 2805, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '14695', 0, 45000, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 45000, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-015', 11, 'fcm', NULL, '15000', NULL, '7000', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '5325', 0, 27325, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 27325, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-016', 11, 'fcm', NULL, '19800', NULL, '9900', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 1600, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '1700', 0, 33000, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 33000, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-017', 11, 'fcm', NULL, '10200', NULL, '0', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '1000', 0, 11200, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 11200, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-018', 11, 'fcm', NULL, '15500', NULL, '10000', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 10000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '8500', 0, 44000, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 44000, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-019', 11, 'fcm', NULL, '13699', NULL, '6850', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 1600, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '682', 0, 22831, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 22831, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-020', 11, 'fcm', NULL, '13508', NULL, '6754', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 1600, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '651', 0, 22513, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 22513, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-021', 11, 'fcm', NULL, '12930', NULL, '6465', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 553, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '4202', 0, 24150, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 24150, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-022', 11, 'fcm', NULL, '13498', NULL, '6749', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 1600, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '2470', 0, 24317, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 24317, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-023', 11, 'fcm', NULL, '13498', NULL, '6749', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 1600, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '4600', 0, 26447, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 26447, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-024', 11, 'fcm', NULL, '13498', NULL, '6749', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 1600, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '653', 0, 22500, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 22500, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-025', 11, 'fcm', NULL, '12930', NULL, '6465', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 1600, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '5336', 0, 26331, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 26331, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-026', 11, 'fcm', NULL, '11000', NULL, '3000', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '2500', 0, 16500, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 16500, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-027', 11, 'fcm', NULL, '15500', NULL, '10000', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 9500, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '1831', 0, 36831, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 36831, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-028', 11, 'fcm', NULL, '12044', NULL, '2131', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 1519, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '0', 0, 15694, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 15694, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-029', 11, 'fcm', NULL, '12646', NULL, '4246', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '1108', 0, 18000, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 18000, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-030', 11, 'fcm', NULL, '10946', NULL, '1054', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '1000', 0, 13000, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 13000, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-031', 11, 'fcm', NULL, '11000', NULL, '3000', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '1500', 0, 15500, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 15500, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-032', 11, 'fcm', NULL, '14140', NULL, '0', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '0', 0, 14140, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 14140, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-033', 11, 'fcm', NULL, '14620', NULL, '0', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '0', 0, 14620, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 14620, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-034', 11, 'fcm', NULL, '11500', NULL, '4950', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 4500, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '4050', 0, 25000, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 25000, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-035', 11, 'fcm', NULL, '50000', NULL, '30000', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 8500, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '9700', 0, 98200, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 98200, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-036', 11, 'fcm', NULL, '10000', NULL, '2400', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '1000', 0, 13400, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 13400, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-037', 11, 'fcm', NULL, '10000', NULL, '3400', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '1000', 0, 14400, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 14400, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL),
('AMS-038', 11, 'fcm', NULL, '16200', NULL, '7200', 'N/A', '0', NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 2500, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, '2450', 0, 28350, NULL, 'N/A', 'N/A', 0, NULL, NULL, NULL, 'N/A', 'N/A', NULL, NULL, NULL, NULL, 'N/A', 0, NULL, NULL, NULL, NULL, 0, 28350, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00 00:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `emp_personal_info`
--

CREATE TABLE `emp_personal_info` (
  `emp_id` int(11) NOT NULL,
  `indo_code` varchar(50) NOT NULL,
  `rom_empcode` varchar(50) DEFAULT NULL,
  `old_indocode` varchar(50) DEFAULT NULL,
  `empl_id` int(11) NOT NULL COMMENT 'employer id from emp_employer_info',
  `reg_id` int(11) DEFAULT NULL COMMENT 'workmen registered id from table emp_workmenRegister_info',
  `agencyid` int(11) NOT NULL DEFAULT 1 COMMENT 'use primary of master_agencies_info',
  `companyName` int(11) NOT NULL COMMENT 'company ids from master_companies_info',
  `working_shift_id` int(11) DEFAULT NULL,
  `verticalName` int(11) NOT NULL COMMENT 'get department id from master_departments_info',
  `empType` enum('Joined','Offered','OfferRejected','Pending','Resign','disabled') NOT NULL DEFAULT 'Offered',
  `position_code` varchar(255) DEFAULT NULL,
  `new_code` varchar(255) DEFAULT NULL,
  `resource_name` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `positionRole` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `prime_department` varchar(255) DEFAULT NULL,
  `national_circle` varchar(255) DEFAULT NULL,
  `circle_name` int(11) NOT NULL COMMENT 'get circle id from master_circles_info',
  `city_name` varchar(255) DEFAULT NULL,
  `pincode` int(11) DEFAULT NULL,
  `base_location` varchar(255) DEFAULT NULL,
  `offered_date` varchar(20) NOT NULL,
  `offered_by` varchar(255) DEFAULT NULL,
  `expected_joining_date` varchar(20) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `contract_probation_days` int(11) DEFAULT NULL,
  `contract_probation_completed` enum('Yes','No','Review') DEFAULT NULL,
  `old_joiningdate` varchar(255) DEFAULT NULL,
  `mobile_number` varchar(255) DEFAULT NULL,
  `email_id` varchar(255) DEFAULT NULL,
  `official_email_id` varchar(255) DEFAULT NULL,
  `married_status` tinyint(1) NOT NULL DEFAULT 0,
  `fatherName` varchar(255) DEFAULT NULL,
  `esic_number` varchar(255) DEFAULT NULL,
  `pf_number` varchar(255) DEFAULT NULL,
  `uan_number` varchar(255) DEFAULT NULL,
  `pancard` varchar(255) DEFAULT NULL,
  `pancard_number` varchar(255) DEFAULT NULL,
  `aadharcard` varchar(255) DEFAULT NULL,
  `passport` varchar(255) DEFAULT NULL,
  `passport_back` varchar(255) DEFAULT NULL,
  `mediclaimdoc` varchar(255) DEFAULT NULL,
  `esicdoc` varchar(255) DEFAULT NULL,
  `aadhar_number` varchar(255) DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `current_address` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `blood_group` enum('A+','A-','B+','B-','O+','O-','AB+','AB-') DEFAULT NULL,
  `pledgedoc` varchar(255) DEFAULT NULL,
  `another_mobile` varchar(255) DEFAULT NULL,
  `reportingMgrName` varchar(255) DEFAULT NULL,
  `reportingMgrContact` varchar(255) DEFAULT NULL,
  `reportingMgrEmail` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `infoCompleted` tinyint(1) NOT NULL DEFAULT 0,
  `punchType` int(11) NOT NULL DEFAULT 1 COMMENT '1=> Single Location, 2=> Three Location, 3=> No Limit',
  `remark` text DEFAULT NULL,
  `updateDatesAr` text DEFAULT NULL,
  `updateUsersAr` text DEFAULT NULL,
  `lastUpdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lastUpdatedBy` varchar(255) DEFAULT NULL,
  `geo_tracking_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=geo tracking active,0=geo tracking inactive',
  `office_location_id` int(11) DEFAULT NULL,
  `working_location_id` int(11) DEFAULT NULL,
  `working_location_address` text DEFAULT NULL,
  `contact_person_name` varchar(255) DEFAULT NULL,
  `contact_person_email` varchar(255) DEFAULT NULL,
  `contact_person_mobile` varchar(255) DEFAULT NULL,
  `terms_condition` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `emp_personal_info`
--

INSERT INTO `emp_personal_info` (`emp_id`, `indo_code`, `rom_empcode`, `old_indocode`, `empl_id`, `reg_id`, `agencyid`, `companyName`, `working_shift_id`, `verticalName`, `empType`, `position_code`, `new_code`, `resource_name`, `gender`, `dob`, `designation`, `positionRole`, `department`, `prime_department`, `national_circle`, `circle_name`, `city_name`, `pincode`, `base_location`, `offered_date`, `offered_by`, `expected_joining_date`, `joining_date`, `contract_probation_days`, `contract_probation_completed`, `old_joiningdate`, `mobile_number`, `email_id`, `official_email_id`, `married_status`, `fatherName`, `esic_number`, `pf_number`, `uan_number`, `pancard`, `pancard_number`, `aadharcard`, `passport`, `passport_back`, `mediclaimdoc`, `esicdoc`, `aadhar_number`, `permanent_address`, `current_address`, `photo`, `blood_group`, `pledgedoc`, `another_mobile`, `reportingMgrName`, `reportingMgrContact`, `reportingMgrEmail`, `description`, `infoCompleted`, `punchType`, `remark`, `updateDatesAr`, `updateUsersAr`, `lastUpdate`, `lastUpdatedBy`, `geo_tracking_status`, `office_location_id`, `working_location_id`, `working_location_address`, `contact_person_name`, `contact_person_email`, `contact_person_mobile`, `terms_condition`) VALUES
(5578, 'AMS-001', 'AMS-001', NULL, 3, NULL, 4, 72, NULL, 4, 'Joined', 'NULL', 'NULL', 'Manish Kumar', 'male', '1983-04-05', 'Director', 'Director', 'fcm', 'NULL', 'Global ', 11, NULL, NULL, 'Gurgaon', '23-12-2024', 'manish@theams.in', '23-12-2024', '2024-12-23', 0, '', NULL, '8178266050', 'info@theams.in', 'info@theams.in', 1, 'Sh Lal Chand', 'N/A', 'N/A', 'N/A', 'AMS-001-pancard-23-12-2024-06-45-19-595481312.jpg', 'APVPK8913J', 'AMS-001-aadharcard-23-12-2024-06-45-19-237448051.jpg', '', '', '', '', '96938385742', 'NULL', 'plot no 357d Sector 37 Gurgaon', 'AMS-001-photo-23-12-2024-06-45-19-1635687784.jpg', 'B+', NULL, '9818221773', 'Manish', 'NULL', 'manish@theams.in', 'NULL', 0, 1, '|', ',23-12_2024,23-12-2024,23-12-2024', ',manish@theams.in,manish@theams.in,manish@theams.in', '2024-12-23 13:15:57', 'manish@theams.in', 0, 4, 13, 'Plot No 357D sector 37', 'Manish', 'manish@theams.in', '9818221773', ''),
(5579, 'AMS-5579', 'AMS-5579', NULL, 3, NULL, 4, 72, NULL, 3, 'Joined', 'Manager', 'NULL', 'Shivam Gupta', 'male', '1994-03-03', 'Manager', 'Manager - SCM', 'fcm', 'Purchase', 'Gurgaon', 11, NULL, NULL, 'Gurgaon', '24-12-2024', 'manish@theams.in', '23-10-2020', '2020-10-23', 180, 'No', NULL, '8077658885', 'shivam.gupta@theams.in', 'shivam.gupta@theams.in', 1, 'Sh. Dinesh Kumar Gupta', 'N/A', 'N/A', '101901102561', 'AMS-5579-pancard-24-12-2024-10-19-04-1807963041.jpeg', 'BWDPG2644E', 'AMS-5579-aadharcard-24-12-2024-10-19-04-1205793777.jpeg', '', '', '', '', '710299069272', 'House No 9 , Ashok Vihar Ph 2 Extn 122001', 'House No 9 , Ashok Vihar Ph 2 Extn 122001', 'AMS-5579-photo-24-12-2024-10-19-04-2081850588.jpeg', 'AB+', NULL, '8860316264', 'Manish', '9818221773', 'info@theams.in', 'NULL', 0, 1, '|', ',24-12_2024,24-12-2024', ',manish@theams.in,manish@theams.in', '2024-12-24 04:49:04', 'manish@theams.in', 0, 4, 13, 'Plot NO 357d Sector 37', 'Manish', 'manish@theams.in', '9818221773', '');

-- --------------------------------------------------------

--
-- Table structure for table `generate_urn3`
--

CREATE TABLE `generate_urn3` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `reference_number` varchar(55) DEFAULT NULL,
  `approver_status` varchar(55) NOT NULL DEFAULT '3' COMMENT '1=Approved,2=Rejected,3=Pending,4=Uploaded',
  `third_party_approver_status` int(11) DEFAULT NULL,
  `document_locked_status` varchar(55) NOT NULL DEFAULT 'Unlocked',
  `year` varchar(20) DEFAULT NULL,
  `project_code` varchar(4) NOT NULL,
  `document_name` text DEFAULT NULL,
  `purpose` text NOT NULL,
  `approver_name` varchar(44) NOT NULL,
  `third_party_approver_name` varchar(55) NOT NULL,
  `third_party_approver_id` int(11) NOT NULL,
  `approver_type` int(11) NOT NULL,
  `approver_code` varchar(55) NOT NULL,
  `output_type_id` int(11) NOT NULL,
  `remarks` tinytext DEFAULT NULL,
  `third_party_remarks` tinytext DEFAULT NULL,
  `emp_code` varchar(55) NOT NULL,
  `emp_name` varchar(55) NOT NULL,
  `document_type` varchar(255) DEFAULT NULL,
  `create_datetime` datetime NOT NULL,
  `third_party_action_date` datetime DEFAULT NULL,
  `created_by` varchar(44) NOT NULL,
  `action_by` varchar(55) NOT NULL,
  `action_date` datetime DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `generate_urn3`
--

INSERT INTO `generate_urn3` (`id`, `company_id`, `department_id`, `reference_number`, `approver_status`, `third_party_approver_status`, `document_locked_status`, `year`, `project_code`, `document_name`, `purpose`, `approver_name`, `third_party_approver_name`, `third_party_approver_id`, `approver_type`, `approver_code`, `output_type_id`, `remarks`, `third_party_remarks`, `emp_code`, `emp_name`, `document_type`, `create_datetime`, `third_party_action_date`, `created_by`, `action_by`, `action_date`, `status`) VALUES
(1, 53, 11, '2024/DHR/IL/206', '1', 0, 'Unlocked', '2024', 'IL', NULL, 'Increment Letter-AIR-4653_Nikhil Yadav', 'Vikas Barak', '', 0, 3, 'INDO-S4124', 1, 'Approved', NULL, 'INDO-S3717', 'Shilpi Kishor', NULL, '2024-12-17 00:00:00', NULL, 'INDO-S3717', 'INDO-S4124', '2024-12-17 00:00:00', 1),
(2, 53, 11, '2024/DHR/IL/207', '1', 0, 'Unlocked', '2024', 'IL', NULL, 'Increment Letter-AIR-4718_Ram Krishna Gupta', 'Vikas Barak', '', 0, 3, 'INDO-S4124', 1, 'Approved', NULL, 'INDO-S3717', 'Shilpi Kishor', NULL, '2024-12-17 00:00:00', NULL, 'INDO-S3717', 'INDO-S4124', '2024-12-17 00:00:00', 1),
(3, 53, 11, '2024/DHR/IL/208', '1', 0, 'Unlocked', '2024', 'IL', NULL, 'Increment Letter-AIR-4777_Alifsha Masood', 'Vikas Barak', '', 0, 3, 'INDO-S4124', 1, 'Approved', NULL, 'INDO-S3717', 'Shilpi Kishor', NULL, '2024-12-17 00:00:00', NULL, 'INDO-S3717', 'INDO-S4124', '2024-12-17 00:00:00', 1),
(4, 53, 11, '2024/DHR/IL/209', '1', 0, 'Unlocked', '2024', 'IL', NULL, 'Increment Letter-AIR-5754_Narayan Dev Mishra', 'Vikas Barak', '', 0, 3, 'INDO-S4124', 1, 'Approved', NULL, 'INDO-S3717', 'Shilpi Kishor', NULL, '2024-12-17 00:00:00', NULL, 'INDO-S3717', 'INDO-S4124', '2024-12-17 00:00:00', 1),
(5, 53, 11, '2024/DHR/IL/210', '1', 0, 'Unlocked', '2024', 'IL', NULL, 'Increment Letter-AIR-5899_Avita', 'Vikas Barak', '', 0, 3, 'INDO-S4124', 1, 'Approved', NULL, 'INDO-S3717', 'Shilpi Kishor', NULL, '2024-12-17 00:00:00', NULL, 'INDO-S3717', 'INDO-S4124', '2024-12-17 00:00:00', 1),
(6, 53, 11, '2024/DHR/IL/211', '1', 0, 'Unlocked', '2024', 'IL', NULL, 'Increment Letter-AIR-5903_Amit Kumar', 'Vikas Barak', '', 0, 3, 'INDO-S4124', 1, 'Approved', NULL, 'INDO-S3717', 'Shilpi Kishor', NULL, '2024-12-17 00:00:00', NULL, 'INDO-S3717', 'INDO-S4124', '2024-12-17 00:00:00', 1),
(7, 53, 11, '2024/DHR/IL/212', '1', 0, 'Unlocked', '2024', 'IL', NULL, 'Increment Letter-AIR-5939_Sparsh Kharoo', 'Vikas Barak', '', 0, 3, 'INDO-S4124', 1, 'Approved', NULL, 'INDO-S3717', 'Shilpi Kishor', NULL, '2024-12-17 00:00:00', NULL, 'INDO-S3717', 'INDO-S4124', '2024-12-17 00:00:00', 1),
(8, 53, 11, '2024/DHR/IL/213', '1', 0, 'Unlocked', '2024', 'IL', NULL, 'Increment Letter-AIR-5943_Shahran Hashan', 'Vikas Barak', '', 0, 3, 'INDO-S4124', 1, 'Approved', NULL, 'INDO-S3717', 'Shilpi Kishor', NULL, '2024-12-17 00:00:00', NULL, 'INDO-S3717', 'INDO-S4124', '2024-12-17 00:00:00', 1),
(9, 53, 11, '2024/DHR/IL/214', '1', 0, 'Unlocked', '2024', 'IL', NULL, 'Increment Letter-AIR-5953_Sunil', 'Vikas Barak', '', 0, 3, 'INDO-S4124', 1, 'Approved', NULL, 'INDO-S3717', 'Shilpi Kishor', NULL, '2024-12-17 00:00:00', NULL, 'INDO-S3717', 'INDO-S4124', '2024-12-17 00:00:00', 1),
(10, 53, 11, '2024/DHR/IL/215', '1', 0, 'Unlocked', '2024', 'IL', NULL, 'Increment Letter-AIR-5972_Badal Jain', 'Vikas Barak', '', 0, 3, 'INDO-S4124', 1, 'Approved', NULL, 'INDO-S3717', 'Shilpi Kishor', NULL, '2024-12-17 00:00:00', NULL, 'INDO-S3717', 'INDO-S4124', '2024-12-17 00:00:00', 1),
(11, 53, 11, '2024/DHR/IL/216', '1', 0, 'Unlocked', '2024', 'IL', NULL, 'Increment Letter-AIR-5988_Mukesh Kumar', 'Vikas Barak', '', 0, 3, 'INDO-S4124', 1, 'Approved', NULL, 'INDO-S3717', 'Shilpi Kishor', NULL, '2024-12-17 00:00:00', NULL, 'INDO-S3717', 'INDO-S4124', '2024-12-17 00:00:00', 1),
(12, 53, 11, '2024/DHR/IL/217', '1', 0, 'Unlocked', '2024', 'IL', NULL, 'Increment Letter-AIR-6019_Suraj Pratap Singh', 'Vikas Barak', '', 0, 3, 'INDO-S4124', 1, 'Approved', NULL, 'INDO-S3717', 'Shilpi Kishor', NULL, '2024-12-17 00:00:00', NULL, 'INDO-S3717', 'INDO-S4124', '2024-12-17 00:00:00', 1),
(13, 53, 11, '2024/DHR/IL/218', '1', 0, 'Unlocked', '2024', 'IL', NULL, 'Increment Letter-AIR-6030_Anupam Verma', 'Vikas Barak', '', 0, 3, 'INDO-S4124', 1, 'Approved', NULL, 'INDO-S3717', 'Shilpi Kishor', NULL, '2024-12-17 00:00:00', NULL, 'INDO-S3717', 'INDO-S4124', '2024-12-17 00:00:00', 1),
(14, 53, 11, '2024/DHR/IL/219', '1', 0, 'Unlocked', '2024', 'IL', NULL, 'Increment Letter-AIR-6030_Gaurav Kumar Kesharwani', 'Vikas Barak', '', 0, 3, 'INDO-S4124', 1, 'Approved', NULL, 'INDO-S3717', 'Shilpi Kishor', NULL, '2024-12-17 00:00:00', NULL, 'INDO-S3717', 'INDO-S4124', '2024-12-17 00:00:00', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `emp_ctcdetails_info`
--
ALTER TABLE `emp_ctcdetails_info`
  ADD UNIQUE KEY `indo_code` (`indo_code`),
  ADD KEY `stateName` (`stateName`);

--
-- Indexes for table `emp_personal_info`
--
ALTER TABLE `emp_personal_info`
  ADD PRIMARY KEY (`emp_id`),
  ADD UNIQUE KEY `indo_code` (`indo_code`),
  ADD KEY `empl_id` (`empl_id`),
  ADD KEY `companyName` (`companyName`) COMMENT 'use primary key of master_companies_info',
  ADD KEY `agencyid` (`agencyid`) COMMENT 'agencyid from master_agencies_info',
  ADD KEY `circle_name` (`circle_name`),
  ADD KEY `verticalName` (`verticalName`);

--
-- Indexes for table `generate_urn3`
--
ALTER TABLE `generate_urn3`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `emp_personal_info`
--
ALTER TABLE `emp_personal_info`
  MODIFY `emp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5580;

--
-- AUTO_INCREMENT for table `generate_urn3`
--
ALTER TABLE `generate_urn3`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
