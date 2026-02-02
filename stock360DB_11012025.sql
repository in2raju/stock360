-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 11, 2026 at 09:36 AM
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
-- Database: `stock360`
--

-- --------------------------------------------------------

--
-- Table structure for table `branch_info`
--

CREATE TABLE `branch_info` (
  `BR_CODE` varchar(20) NOT NULL,
  `BRANCH_NAME` varchar(200) NOT NULL,
  `BRANCH_ADDRESS` varchar(200) DEFAULT NULL,
  `BRANCH_CONTACT` varchar(500) DEFAULT NULL,
  `ORG_CODE` varchar(20) NOT NULL,
  `AUTHORIZED_STATUS` char(1) DEFAULT NULL,
  `AUTHORIZED_USER` varchar(100) DEFAULT NULL,
  `AUTHORIZED_DATE` date DEFAULT NULL,
  `ENTRY_DATE` date DEFAULT NULL,
  `ENTRY_USER` varchar(200) DEFAULT NULL,
  `EDIT_DATE` date DEFAULT NULL,
  `EDIT_USER` varchar(200) DEFAULT NULL,
  `DELETE_USER` varchar(200) DEFAULT NULL,
  `DELETE_DATE` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branch_info`
--

INSERT INTO `branch_info` (`BR_CODE`, `BRANCH_NAME`, `BRANCH_ADDRESS`, `BRANCH_CONTACT`, `ORG_CODE`, `AUTHORIZED_STATUS`, `AUTHORIZED_USER`, `AUTHORIZED_DATE`, `ENTRY_DATE`, `ENTRY_USER`, `EDIT_DATE`, `EDIT_USER`, `DELETE_USER`, `DELETE_DATE`) VALUES
('100-100', 'HEAD OFFICE', NULL, NULL, '100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('100-101', 'Regional Office', 'Dhaka', '', '100', NULL, NULL, NULL, '2025-10-13', 'raju', '2025-10-14', 'raju', NULL, NULL),
('101-101', 'Diginala', 'Diginala, Khagrachori', '', '101', NULL, NULL, NULL, '2025-10-15', 'softadmin', '2025-10-15', 'softadmin', NULL, NULL),
('101-102', 'Khagrachori', '', '', '101', NULL, NULL, NULL, '2025-10-15', 'softadmin', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer_due_installment`
--

CREATE TABLE `customer_due_installment` (
  `installment_id` varchar(50) NOT NULL,
  `customer_id` varchar(100) NOT NULL,
  `sales_mst_id` varchar(50) DEFAULT NULL,
  `installment_amount` decimal(18,2) NOT NULL,
  `installment_date` date NOT NULL,
  `payment_mode` varchar(50) DEFAULT 'Cash',
  `remarks` varchar(500) DEFAULT NULL,
  `entry_user` varchar(200) DEFAULT NULL,
  `entry_date` datetime DEFAULT NULL,
  `edit_user` varchar(200) DEFAULT NULL,
  `edit_date` datetime DEFAULT NULL,
  `delete_user` varchar(200) DEFAULT NULL,
  `delete_date` datetime DEFAULT NULL,
  `authorized_status` char(1) DEFAULT 'N',
  `authorized_user` varchar(100) DEFAULT NULL,
  `authorized_date` datetime DEFAULT NULL,
  `org_code` varchar(20) NOT NULL,
  `br_code` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_due_installment`
--

INSERT INTO `customer_due_installment` (`installment_id`, `customer_id`, `sales_mst_id`, `installment_amount`, `installment_date`, `payment_mode`, `remarks`, `entry_user`, `entry_date`, `edit_user`, `edit_date`, `delete_user`, `delete_date`, `authorized_status`, `authorized_user`, `authorized_date`, `org_code`, `br_code`) VALUES
('DIN-101-101-25122411473791', 'C-20251224113801545', '101-101-20251224-762032', 50000.00, '2025-12-24', 'Cash', NULL, 'riton', '2025-12-24 16:47:37', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101'),
('DIN-101-101-25122411485724', 'C-20251224113801545', '101-101-20251224-762032', 5000.00, '2025-12-24', 'Cash', NULL, 'riton', '2025-12-24 16:48:57', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101'),
('DIN-101-101-25122411491754', 'C-20251224113801545', '101-101-20251224-594584', 50000.00, '2025-12-24', 'Cash', NULL, 'riton', '2025-12-24 16:49:17', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101'),
('DIN-101-101-26010609221621', 'C-20251224113801545', NULL, 500.00, '2026-01-06', 'Cash', NULL, 'riton', '2026-01-06 14:22:16', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101'),
('DIN-101-101-26010609352365', 'C-101-101-260105112410', NULL, 2000.00, '2026-01-06', 'Cash', NULL, 'riton', '2026-01-06 14:35:23', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101'),
('DIN-101-101-26010809340669', 'C-20251224113801545', NULL, 4000.00, '2026-01-08', 'Cash', NULL, 'riton', '2026-01-08 14:34:06', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101'),
('DIN-101-101-26010809565495', 'C-20251224113801545', NULL, 500.00, '2026-01-08', 'Cash', NULL, 'rasel', '2026-01-08 14:56:54', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101');

-- --------------------------------------------------------

--
-- Table structure for table `customer_info`
--

CREATE TABLE `customer_info` (
  `customer_id` varchar(100) NOT NULL,
  `customer_name` varchar(200) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `customer_phone` varchar(15) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `reg_date` date DEFAULT NULL,
  `next_payment_date` date DEFAULT NULL,
  `gurantor_name` varchar(200) DEFAULT NULL,
  `gurantor_phone` varchar(15) DEFAULT NULL,
  `nid` varchar(50) DEFAULT NULL,
  `entry_date` datetime DEFAULT NULL,
  `entry_user` varchar(200) DEFAULT NULL,
  `edit_date` datetime DEFAULT NULL,
  `edit_user` varchar(200) DEFAULT NULL,
  `delete_user` varchar(200) DEFAULT NULL,
  `delete_date` datetime DEFAULT NULL,
  `delete_status` varchar(10) DEFAULT NULL,
  `org_code` varchar(20) NOT NULL,
  `br_code` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_info`
--

INSERT INTO `customer_info` (`customer_id`, `customer_name`, `address`, `customer_phone`, `email`, `reg_date`, `next_payment_date`, `gurantor_name`, `gurantor_phone`, `nid`, `entry_date`, `entry_user`, `edit_date`, `edit_user`, `delete_user`, `delete_date`, `delete_status`, `org_code`, `br_code`) VALUES
('C-101-101-260105112410', 'raju das', 'Motijheel', '01815572760', '', '2026-01-05', '0000-00-00', '', '', '', '2026-01-05 16:24:10', 'riton', NULL, NULL, NULL, NULL, 'N', '101', '101-101'),
('C-20251224113801545', 'বর্ষা সরকার', NULL, '01810639977', NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-24 16:38:01', 'riton', NULL, NULL, NULL, NULL, NULL, '101', '101-101');

-- --------------------------------------------------------

--
-- Table structure for table `customer_previous_due`
--

CREATE TABLE `customer_previous_due` (
  `prev_due_id` varchar(50) NOT NULL,
  `customer_id` varchar(100) NOT NULL,
  `previous_due_amount` decimal(18,2) NOT NULL,
  `entry_user` varchar(200) DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `edit_user` varchar(200) DEFAULT NULL,
  `edit_date` datetime DEFAULT NULL,
  `delete_user` varchar(200) DEFAULT NULL,
  `delete_date` datetime DEFAULT NULL,
  `authorized_status` char(1) DEFAULT 'N',
  `authorized_user` varchar(100) DEFAULT NULL,
  `authorized_date` datetime DEFAULT NULL,
  `org_code` varchar(20) NOT NULL,
  `br_code` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_previous_due`
--

INSERT INTO `customer_previous_due` (`prev_due_id`, `customer_id`, `previous_due_amount`, `entry_user`, `entry_date`, `edit_user`, `edit_date`, `delete_user`, `delete_date`, `authorized_status`, `authorized_user`, `authorized_date`, `org_code`, `br_code`) VALUES
('PDU-101-101-25122411500064', 'C-20251224113801545', 10000.00, 'riton', '2025-12-24', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101'),
('PDU-101-101-26010511242436', 'C-101-101-260105112410', 2000.00, 'riton', '2026-01-05', 'riton', '2026-01-06 15:03:31', NULL, NULL, 'N', NULL, NULL, '101', '101-101');

-- --------------------------------------------------------

--
-- Table structure for table `distributor`
--

CREATE TABLE `distributor` (
  `DISTRIBUTOR_CODE` varchar(50) NOT NULL,
  `DISTRIBUTOR_NAME` varchar(200) NOT NULL,
  `DISTRIBUTOR_ADDRESS` varchar(200) DEFAULT NULL,
  `DISTRIBUTOR_CONTACT` varchar(20) DEFAULT NULL,
  `AUTHORIZED_STATUS` char(1) DEFAULT NULL,
  `AUTHORIZED_USER` varchar(100) DEFAULT NULL,
  `AUTHORIZED_DATE` datetime DEFAULT NULL,
  `ENTRY_DATE` datetime NOT NULL DEFAULT current_timestamp(),
  `ENTRY_USER` varchar(200) NOT NULL,
  `EDIT_DATE` datetime DEFAULT NULL,
  `EDIT_USER` varchar(200) DEFAULT NULL,
  `DELETE_USER` varchar(200) DEFAULT NULL,
  `DELETE_DATE` datetime DEFAULT NULL,
  `ORG_CODE` varchar(20) NOT NULL,
  `BR_CODE` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `distributor`
--

INSERT INTO `distributor` (`DISTRIBUTOR_CODE`, `DISTRIBUTOR_NAME`, `DISTRIBUTOR_ADDRESS`, `DISTRIBUTOR_CONTACT`, `AUTHORIZED_STATUS`, `AUTHORIZED_USER`, `AUTHORIZED_DATE`, `ENTRY_DATE`, `ENTRY_USER`, `EDIT_DATE`, `EDIT_USER`, `DELETE_USER`, `DELETE_DATE`, `ORG_CODE`, `BR_CODE`) VALUES
('1', 'WALTON', 'Dhaka', '', 'Y', NULL, NULL, '2026-01-06 14:57:46', 'riton', '2026-01-06 14:57:55', 'riton', NULL, NULL, '101', '101-101'),
('101-101-20251023-104629', 'KK ELECTRONICS', 'DHAKA', '', 'Y', NULL, NULL, '2025-10-23 10:46:29', 'riton', '2025-11-17 14:51:17', 'riton', NULL, NULL, '101', '101-101'),
('101-101-20251117-161115', 'RR TECH', '', '', 'N', NULL, NULL, '2025-11-17 16:11:15', 'riton', NULL, NULL, NULL, NULL, '101', '101-101');

--
-- Triggers `distributor`
--
DELIMITER $$
CREATE TRIGGER `TRG_GENERATE_DISTRIBUTOR_ID` BEFORE INSERT ON `distributor` FOR EACH ROW BEGIN
    DECLARE v_timestamp VARCHAR(20);
    SET v_timestamp = DATE_FORMAT(NOW(), '%Y%m%d-%H%i%s');
    SET NEW.DISTRIBUTOR_CODE = CONCAT(NEW.BR_CODE, '-', v_timestamp);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `distributor_due_installment`
--

CREATE TABLE `distributor_due_installment` (
  `installment_id` varchar(50) NOT NULL,
  `distributor_code` varchar(100) NOT NULL,
  `stock_mst_id` varchar(50) DEFAULT NULL,
  `installment_amount` decimal(18,2) NOT NULL,
  `installment_date` date NOT NULL,
  `payment_mode` varchar(50) DEFAULT 'Cash',
  `remarks` varchar(500) DEFAULT NULL,
  `entry_user` varchar(200) DEFAULT NULL,
  `entry_date` datetime DEFAULT current_timestamp(),
  `edit_user` varchar(200) DEFAULT NULL,
  `edit_date` datetime DEFAULT NULL,
  `delete_user` varchar(200) DEFAULT NULL,
  `delete_date` datetime DEFAULT NULL,
  `authorized_status` char(1) DEFAULT 'N',
  `authorized_user` varchar(100) DEFAULT NULL,
  `authorized_date` datetime DEFAULT NULL,
  `org_code` varchar(20) NOT NULL,
  `br_code` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `distributor_due_installment`
--

INSERT INTO `distributor_due_installment` (`installment_id`, `distributor_code`, `stock_mst_id`, `installment_amount`, `installment_date`, `payment_mode`, `remarks`, `entry_user`, `entry_date`, `edit_user`, `edit_date`, `delete_user`, `delete_date`, `authorized_status`, `authorized_user`, `authorized_date`, `org_code`, `br_code`) VALUES
('DDI-101-101-26010710532965', '1', NULL, 250000.00, '2026-01-07', 'Cash', NULL, 'riton', '2026-01-07 15:53:29', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101'),
('DDI-101-101-26010710543613', '101-101-20251023-104629', '101-101-STK-20251223-233020', 20000.00, '2026-01-07', 'Cash', NULL, 'riton', '2026-01-07 15:54:36', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101'),
('DDI-101-101-26010710554836', '1', NULL, 4500.00, '2026-01-07', 'Cash', NULL, 'riton', '2026-01-07 15:55:48', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101'),
('DDI-101-101-26010805460256', '101-101-20251023-104629', '101-101-STK-20251216-711627', 18500.00, '2026-01-08', 'Cash', NULL, 'riton', '2026-01-08 10:46:02', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101'),
('DDI-101-101-26010805462476', '101-101-20251023-104629', '101-101-STK-20251216-711627', 2000.00, '2026-01-08', 'Cash', NULL, 'riton', '2026-01-08 10:46:24', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101'),
('DDI-101-101-26010805464512', '101-101-20251023-104629', NULL, 10200.00, '2026-01-08', 'Cash', NULL, 'riton', '2026-01-08 10:46:45', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101'),
('DDI-101-101-26010807492428', '101-101-20251023-104629', '101-101-STK-20251216-191790', 2000.00, '2026-01-08', 'Cash', NULL, 'riton', '2026-01-08 12:49:24', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101'),
('DDI-101-101-26010807570117', '101-101-20251023-104629', NULL, 1200.00, '2026-01-08', 'Cash', NULL, 'rasel', '2026-01-08 12:57:01', NULL, NULL, NULL, NULL, 'Y', NULL, NULL, '101', '101-101'),
('DDI-101-101-26010808261655', '1', NULL, 8000.00, '2026-01-08', 'Cash', NULL, 'riton', '2026-01-08 13:26:16', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101'),
('DDI-101-101-26010809594934', '1', NULL, 500.00, '2026-01-08', 'Cash', NULL, 'rasel', '2026-01-08 14:59:49', NULL, NULL, NULL, NULL, 'N', NULL, NULL, '101', '101-101');

-- --------------------------------------------------------

--
-- Table structure for table `distributor_previous_due`
--

CREATE TABLE `distributor_previous_due` (
  `distributor_due_id` varchar(50) NOT NULL,
  `distributor_code` varchar(50) NOT NULL,
  `br_code` varchar(10) NOT NULL,
  `org_code` varchar(10) NOT NULL,
  `due_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_date` date NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `entry_user` varchar(50) NOT NULL,
  `entry_date` datetime DEFAULT current_timestamp(),
  `edit_user` varchar(50) DEFAULT NULL,
  `edit_date` datetime DEFAULT NULL,
  `authorized_status` char(1) DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `distributor_previous_due`
--

INSERT INTO `distributor_previous_due` (`distributor_due_id`, `distributor_code`, `br_code`, `org_code`, `due_amount`, `due_date`, `remarks`, `entry_user`, `entry_date`, `edit_user`, `edit_date`, `authorized_status`) VALUES
('DPD-101-101-26010609544795', '101-101-20251023-104629', '101-101', '101', 150000.00, '2026-01-06', NULL, 'riton', '2026-01-06 14:54:47', 'riton', '2026-01-06 14:55:24', 'N'),
('DPD-101-101-26010609582678', '1', '101-101', '101', 265000.00, '2026-01-06', NULL, 'riton', '2026-01-06 14:58:26', 'riton', '2026-01-06 15:01:56', 'N');

-- --------------------------------------------------------

--
-- Table structure for table `menu_info`
--

CREATE TABLE `menu_info` (
  `MENU_ID` int(11) NOT NULL,
  `MENU_NAME` varchar(100) NOT NULL,
  `MENU_LINK` varchar(255) DEFAULT NULL,
  `PARENT_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_info`
--

INSERT INTO `menu_info` (`MENU_ID`, `MENU_NAME`, `MENU_LINK`, `PARENT_ID`) VALUES
(1, 'CONFIGURATION', 'dashboard.php', 0),
(2, 'SETUP', NULL, NULL),
(4, 'Reports', 'reports.php', 0),
(5, 'Monthly Report', 'monthly_report.php', 4),
(26, 'SUPPLIER', 'supplier.php', 2),
(27, 'product catagory', 'product_category.php', 2),
(28, 'USER ADMINISTRATION', '', NULL),
(30, 'ADD PERMISSION', 'add_permission.php', 28),
(31, 'PERMISSION', 'menu_permission.php', 4),
(32, 'USER TYPE', 'user_type.php', 1),
(33, 'ADD MENU', 'add_menu.php', 1),
(34, 'ADD ORGANIZATION', 'add_org.php', 1),
(35, 'USER MENU PERMISSION', 'user_menu_permission.php', 1),
(37, 'ADD BRANCH', 'add_branch.php', 1),
(40, 'USER CREATE', 'create_user.php', 1),
(41, 'Add product model', 'product_model.php', 2),
(42, 'ENTRY FORM', NULL, NULL),
(43, 'STOCK ENTRY', 'stock_entry.php', 42),
(44, 'Add Distributor', 'distributor.php', 2),
(46, 'USER ACTION PERMISSION', 'user_action_permission.php', 1),
(47, 'SALES ENTRY', 'sales_entry.php', 42),
(48, 'Add Customer', 'add_customer.php', 2),
(49, 'Customer Previous Due', 'previous_due.php', 2),
(50, 'Customer Due Collection', 'due_collection.php', 2),
(51, 'Distributor Previous Due', 'distributor_previous_due.php', 2),
(52, 'Distributor Due Collection', 'distributor_due_collection.php', 2);

-- --------------------------------------------------------

--
-- Table structure for table `organization_info`
--

CREATE TABLE `organization_info` (
  `ORG_CODE` varchar(20) NOT NULL,
  `ORGANIZATION_NAME` varchar(200) NOT NULL,
  `ORGANIZATION_ADDRESS` varchar(200) DEFAULT NULL,
  `ORGANIZATION_CONTACT` varchar(500) DEFAULT NULL,
  `AUTHORIZED_STARUS` varchar(1) DEFAULT NULL,
  `AUTHORIZED_USER` varchar(100) DEFAULT NULL,
  `AUTHORIZED_DATE` date DEFAULT NULL,
  `ENTRY_DATE` date DEFAULT NULL,
  `ENTRY_USER` varchar(200) DEFAULT NULL,
  `EDIT_DATE` date DEFAULT NULL,
  `EDIT_USER` varchar(200) DEFAULT NULL,
  `DELETE_USER` varchar(200) DEFAULT NULL,
  `DELETE_DATE` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organization_info`
--

INSERT INTO `organization_info` (`ORG_CODE`, `ORGANIZATION_NAME`, `ORGANIZATION_ADDRESS`, `ORGANIZATION_CONTACT`, `AUTHORIZED_STARUS`, `AUTHORIZED_USER`, `AUTHORIZED_DATE`, `ENTRY_DATE`, `ENTRY_USER`, `EDIT_DATE`, `EDIT_USER`, `DELETE_USER`, `DELETE_DATE`) VALUES
('0001', 'BRAC', 'DHAKA', '', 'Y', NULL, NULL, '2025-10-12', 'raju', '2025-10-12', 'raju', 'softadmin', '2025-10-15'),
('100', 'Software Admin Org', 'DHAKA', '', 'Y', NULL, NULL, NULL, NULL, '2025-10-14', 'raju', NULL, NULL),
('101', 'RS ELECTRONICS', 'CHITTAGONG', '', 'Y', NULL, NULL, NULL, NULL, '2025-10-15', 'softadmin', NULL, NULL);

--
-- Triggers `organization_info`
--
DELIMITER $$
CREATE TRIGGER `before_insert_organization` BEFORE INSERT ON `organization_info` FOR EACH ROW BEGIN
    DECLARE nextCode INT;
    DECLARE newOrgCode VARCHAR(20);

    -- Only generate if not provided manually
    IF NEW.ORG_CODE IS NULL OR NEW.ORG_CODE = '' THEN
        SELECT IFNULL(MAX(CAST(SUBSTRING(ORG_CODE, 4) AS UNSIGNED)), 0) + 1
        INTO nextCode
        FROM organization_info;

        SET newOrgCode = CONCAT(LPAD(nextCode, 4, '0'));
        SET NEW.ORG_CODE = newOrgCode;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `product_category`
--

CREATE TABLE `product_category` (
  `product_category_id` varchar(50) NOT NULL,
  `product_category_name` varchar(200) NOT NULL,
  `supplier_id` varchar(50) DEFAULT NULL,
  `authorized_status` varchar(1) DEFAULT NULL,
  `authorized_user` varchar(100) DEFAULT NULL,
  `authorized_date` datetime DEFAULT NULL,
  `entry_date` datetime DEFAULT current_timestamp(),
  `entry_user` varchar(200) DEFAULT NULL,
  `edit_date` datetime DEFAULT NULL,
  `edit_user` varchar(200) DEFAULT NULL,
  `delete_user` varchar(200) DEFAULT NULL,
  `delete_date` datetime DEFAULT NULL,
  `org_code` varchar(20) DEFAULT NULL,
  `br_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_category`
--

INSERT INTO `product_category` (`product_category_id`, `product_category_name`, `supplier_id`, `authorized_status`, `authorized_user`, `authorized_date`, `entry_date`, `entry_user`, `edit_date`, `edit_user`, `delete_user`, `delete_date`, `org_code`, `br_code`) VALUES
('1', 'Direct Cool Ref', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('10', 'Washing Machine', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('11', 'Room Heater', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('12', 'Microwave Oven', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('13', 'Electric Oven', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('14', 'Mosquito Bat', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('15', 'MOP', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('16', 'Fri fan', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('17', 'Rice Cooker', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('18', 'Gas Stove (LPG)', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('19', 'Kitchen Hood', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('2', 'Non Frost Refrigerator', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('20', 'Vacuum Cleaner', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('21', 'Multi Curry Cooker', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('22', 'Dish Dryer Machine', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('23', 'Electric Lunch Box', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('24', 'Air Purifier', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('25', 'Pressure Cooker', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('26', 'Infrared Cooker', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('27', 'Blender', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('28', 'Iron', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('29', 'Kitchen Cookware', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('3', 'Freezer(Deep)', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('30', 'Rechargeable Portable Lamp & Torch', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('31', 'Hair Dryer', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('32', 'Hair Straightener', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('33', 'Hair Clipper', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('34', 'Cake Maker', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('35', 'Electric Trimmer & Shaver', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('36', 'Electric Kettle', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('37', 'Hand Mixer', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('38', 'Stand Mixer', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('39', 'Hot Plate', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('4', 'Beverage Cooler', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('40', 'Electric Geyser', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('41', 'Sewing Machine', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('42', 'Donut Plate-Accessories', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('43', 'Body Weight Scale', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('44', 'Price Computing Weight Scale', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('45', 'Toaster', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('46', 'Sandwich Maker', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('47', 'Solar Street Light', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('48', 'Infrared Cooker', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('49', 'Induction Cooker', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('5', 'LED Television', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('50', 'Ruti Maker', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('51', 'Coffee Maker', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('52', 'Protector', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('53', 'Water Heater', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('54', 'Generator', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('55', 'Water Purifier', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('56', 'Water Dispenser', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('57', 'Water Pump', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('58', 'Vacuum Flask', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('59', 'Auto Voltage Stabilizer', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('6', 'Air-Conditioner', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('60', 'Automatic Voltage Protector', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('61', 'Pedestal Fan', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('62', 'Tornado Fan', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('63', 'Exhaust Fan', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('64', 'Net Fan', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('65', 'Sealed Lead Acid Recharge Battery', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('66', 'Ceiling Fan', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('67', 'Table Fan', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('68', 'Wall Fan', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('69', 'Rechargeable Wall Fan', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('7', 'Generator', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('70', 'Rechargeable Table Fan', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('71', 'Ceiling Fan Capacitor', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('72', 'Fan Hook Box Plate', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, '101', NULL, NULL, NULL, NULL, '101', '101-101'),
('73-040537', 'D09', '1', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101'),
('74-141512', 'SULAR NAT FAN', '1', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101'),
('75-141542', 'SULAR FAN', '1', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101'),
('76-141732', 'WG Crystal 30L', '1', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101'),
('77-141750', 'WG C45L', '1', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101'),
('78-141820', 'WG C30L', '1', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101'),
('79-062701', 'Multi plag', '1', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101'),
('8', 'Air-Cooler', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, '2025-10-16 14:37:05', 'riton', NULL, NULL, '101', '101-101'),
('80-063702', 'LED BULP', '1', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101'),
('81-064546', 'Rimote', '1', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101'),
('82-064608', 'Regulator', '1', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101'),
('83-064625', 'Inverter', '1', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101'),
('84-065632', 'Akash', '1', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101'),
('85-070119', 'Socket', '1', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101'),
('86-022842', 'Mobile', '2-022740', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101'),
('87-022858', 'Walton mobile', '1', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101'),
('88-050106', 'Batari', '1', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101'),
('89-090741', 'TEST-DUE', '1', NULL, NULL, NULL, '2025-10-16 14:47:35', 'TEST', NULL, NULL, NULL, NULL, '101', '101-101'),
('9', 'Air-Fryer', '1', NULL, NULL, '2024-12-22 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101'),
('90-105306', 'RS-DUE', '3-105205', NULL, NULL, NULL, '2025-10-16 14:47:35', 'RITON', NULL, NULL, NULL, NULL, '101', '101-101');

--
-- Triggers `product_category`
--
DELIMITER $$
CREATE TRIGGER `trg_generate_product_category_id` BEFORE INSERT ON `product_category` FOR EACH ROW BEGIN
    DECLARE v_category_count INT;
    DECLARE v_time VARCHAR(12);

    IF NEW.product_category_id IS NULL OR NEW.product_category_id = '' THEN
        -- Count total categories for this branch + 1
        SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(product_category_id, '-', 1) AS UNSIGNED)), 0) + 1
        INTO v_category_count
        FROM product_category
        WHERE br_code = NEW.br_code;

        -- Current timestamp DDMMYYHHMMSS
        SET v_time = DATE_FORMAT(NOW(), '%d%m%y%H%i%s');

        -- Assign ID as COUNT-TIMESTAMP
        SET NEW.product_category_id = CONCAT('PC-', LPAD(FLOOR(RAND()*9999),4,'0'), '-', DATE_FORMAT(NOW(), '%d%m%y%H%i%s'));

    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `product_model`
--

CREATE TABLE `product_model` (
  `MODEL_ID` varchar(50) NOT NULL,
  `MODEL_NAME` varchar(200) NOT NULL,
  `PRODUCT_CATEGORY_ID` varchar(20) NOT NULL,
  `SUPPLIER_ID` varchar(20) NOT NULL,
  `AUTHORIZED_STATUS` varchar(1) DEFAULT NULL,
  `AUTHORIZED_USER` varchar(100) DEFAULT NULL,
  `AUTHORIZED_DATE` date DEFAULT NULL,
  `ENTRY_DATE` date DEFAULT NULL,
  `ENTRY_USER` varchar(200) DEFAULT NULL,
  `EDIT_DATE` date DEFAULT NULL,
  `EDIT_USER` varchar(200) DEFAULT NULL,
  `DELETE_USER` varchar(200) DEFAULT NULL,
  `DELETE_DATE` date DEFAULT NULL,
  `ORG_CODE` varchar(3) DEFAULT NULL,
  `BR_CODE` varchar(7) DEFAULT NULL,
  `PRICE` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_model`
--

INSERT INTO `product_model` (`MODEL_ID`, `MODEL_NAME`, `PRODUCT_CATEGORY_ID`, `SUPPLIER_ID`, `AUTHORIZED_STATUS`, `AUTHORIZED_USER`, `AUTHORIZED_DATE`, `ENTRY_DATE`, `ENTRY_USER`, `EDIT_DATE`, `EDIT_USER`, `DELETE_USER`, `DELETE_DATE`, `ORG_CODE`, `BR_CODE`, `PRICE`) VALUES
('1002-060831', 'WK Ljss180n', '36', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1090.00),
('1003-060912', 'WK Ljss180p', '36', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1350.00),
('1004-044904', 'WIR DO9', '28', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1230.00),
('1013-025049', 'WFB 1G7GDXX', '1', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 35690.00),
('1014-032850', 'WFE 3E8 GDEN DD', '1', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 57190.00),
('1015-033927', 'WFB 2E4 GDXX inverter', '1', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 44890.00),
('1017-034134', 'WFB 2E4 GDSH inverter', '1', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 46190.00),
('1022-055242', 'WFC 3A7 GDNE', '1', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 47390.00),
('1023-055419', 'WFC 3F5 GDEH DD', '1', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 57690.00),
('1026-060451', 'WFS TG2 RBXX', '1', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 16290.00),
('1027-060813', 'WCG 2E5 GDEL Inverter', '3', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 43490.00),
('1030-064835', 'W24T23CS', '5', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 15990.00),
('1031-064919', 'W32R30', '5', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 15490.00),
('1034-070355', 'W32D210H11GT', '5', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 24490.00),
('1035-070425', 'W32S3EG GOGLkE', '5', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 25490.00),
('1036-070506', 'WD43R', '5', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 32900.00),
('1037-070555', 'WD AF39 120 BX', '5', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 28900.00),
('1038-070753', 'W43D210EG1', '5', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 36990.00),
('1039-070836', 'W43S2FG GOGLE', '5', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 46900.00),
('1042-071253', 'WE MX43EG1', '5', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 39990.00),
('1043-071347', 'W50S3BG', '5', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 62990.00),
('1044-071445', 'W43S3EG EMI', '5', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 41490.00),
('1049-083735', 'W17OA MS', '70', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 6590.00),
('1050-084144', 'WCF5601EM', '66', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3530.00),
('1051-084204', 'WCF5605', '66', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3610.00),
('1053-092147', 'WWM ATG80', '10', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 33950.00),
('1054-092235', 'WWM ATG70', '10', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 30900.00),
('1060-104827', 'DS 60AS', '44', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 5900.00),
('1062-105558', 'WRC SGAE 180', '17', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2920.00),
('1063-110102', 'WRC SGAE 300', '17', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3710.00),
('1064-110344', 'WRC SDE 280', '17', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3990.00),
('1071-132152', 'WBL 15GM55N', '27', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4390.00),
('1074-132355', 'WBL 15GM85N', '27', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 5290.00),
('1075-132440', 'OBL 15GM60', '27', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3700.00),
('1077-133847', 'WCW SFC1600', '16', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 750.00),
('1079-134036', 'WRC SFGC2600', '16', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1550.00),
('1080-134644', 'WCW PPC2600', '16', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1090.00),
('1081-135923', 'COOK MASTER ELITE', '48', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4090.00),
('1082-140033', 'COOKMASTER', '49', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4090.00),
('1087-060901', 'WSI INVERNA(super saver)24H plasma', '6', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 92490.00),
('1088-061024', 'WSI RIVERINE 12F', '6', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 50000.00),
('1091-063734', '10 imagency', '80-063702', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 780.00),
('1092-063811', '15 watt', '80-063702', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 345.00),
('1093-063842', '20watt', '80-063702', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 430.00),
('1094-064828', 'Mini 22', '82-064608', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 385.00),
('1095-064949', 'Aksh', '81-064546', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 300.00),
('1101-070139', '8pin', '85-070119', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 200.00),
('1103-081027', 'OGS SSH90', '18', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1490.00),
('1104-081105', 'WGS Sweety Single(LPG)', '18', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2650.00),
('1105-081140', 'WGS D G', '18', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4350.00),
('1106-081221', 'Orgin D G D', '18', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4290.00),
('1107-081243', 'Siliva', '18', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 9700.00),
('1108-084200', '1316', '75-141542', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 880.00),
('1109-035515', 'WBL VK10N', '27', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 5990.00),
('1110-035900', 'WRC star Deluxe 1.8', '17', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3050.00),
('1111-040203', 'WRC star Deluxe 2.2', '17', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3550.00),
('1112-040253', 'WRC star Deluxe 2.8', '17', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3750.00),
('1115-041534', 'WCW CGC2600', '16', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2190.00),
('1116-041622', 'WCW CGC2400', '16', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1850.00),
('1119-041909', 'WCW CGC2800', '16', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2350.00),
('1122-085734', 'WCW WGC2600', '16', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1790.00),
('1123-092824', 'WCW WGC2800', '16', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1890.00),
('1125-023027', 'Primo GH 10', '86-022842', '2-022740', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 8390.00),
('1126-023058', 'Primo R10', '86-022842', '2-022740', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 11499.00),
('1127-023131', 'Primo GH10i', '86-022842', '2-022740', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 8290.00),
('1128-023205', 'Primo X4', '86-022842', '2-022740', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 15999.00),
('1130-023304', 'Zenx 1', '86-022842', '2-022740', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 11500.00),
('1134-134940', 'Primo GH10i', '86-022842', '2-022740', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 8290.00),
('1136-135108', 'Primo x4', '86-022842', '2-022740', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 15999.00),
('1139-082226', '6 pin', '85-070119', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 690.00),
('1141-042339', 'NEXG N74', '86-022842', '2-022740', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 13500.00),
('1144-052946', 'WRC Gloria deluxe 2.2', '17', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3490.00),
('1146-062023', 'WBL VK85N', '27', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 5590.00),
('1149-105737', 'WSI ACC (digital display)18H', '6', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 81900.00),
('1150-111309', 'WFF9S5', '62', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1320.00),
('1151-082646', 'WSI INVERNA(super saver)12J smart plasma', '6', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 59900.00),
('1152-082839', 'WBA 2FO GCXB', '4', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 59490.00),
('1153-123213', 'WCF5601 WR', '66', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4390.00),
('1155-124950', 'WRTF12B', '70', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4190.00),
('1156-125106', 'W17OA AS', '70', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 6990.00),
('1158-133043', 'WRSF16B', '70', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 6890.00),
('1161-095008', 'WRSF16B RMC', '70', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 6890.00),
('1163-065233', '5 watt', '80-063702', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 179.00),
('1165-103535', 'WFA 2A3 GDEL SC', '1', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 38490.00),
('1166-103923', 'WFB 2B6 GDEH SC', '1', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 43990.00),
('1167-111352', 'WSI INVERNA(super saver)18H PLASMA', '6', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 76990.00),
('1168-111553', 'WSI INVERNA(super saver)18H SMART PLASMA', '6', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 78990.00),
('1170-050224', '1245', '88-050106', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1050.00),
('1173-040314', 'WBL 15G312', '27', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 6990.00),
('1175-040836', 'WPC MSC350', '25', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1550.00),
('1176-041032', 'WGS Camellia', '18', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4890.00),
('1177-041120', 'WGH 21GS', '18', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 7990.00),
('1178-043552', 'WRC Gloria 1.8', '17', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2990.00),
('1180-125329', 'XANON X21', '86-022842', '2-022740', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 23400.00),
('1181-090826', 'TEST-DUE', '89-090741', '1', NULL, 'TEST', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 0.00),
('1182-105338', 'RS-PRE-DUE', '90-105306', '3-105205', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 0.00),
('1184-031609', 'WCF 2AO GSRE', '3', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 38290.00),
('1185-140105', 'NEXG N72', '86-022842', '2-022740', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 11990.00),
('1187-052032', 'WD55E11RUG1', '5', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 75990.00),
('1190-080412', 'WSI INVERNA (SUPER SAVER)', '6', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 59900.00),
('1193-141005', 'WFD 1B6GDEH', '1', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 28590.00),
('1195-040021', 'WCW CGC3000', '16', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2550.00),
('1198-042216', 'WGS GDC11', '18', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4690.00),
('1200-032810', 'WNI 5FO GETE DD', '1', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 117900.00),
('1201-032920', 'WNI 6E2GSRE CX', '1', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 138990.00),
('1203-133406', 'Appolo', '59', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3800.00),
('1204-121127', 'WNI 6A9GDSD DD', '1', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 119990.00),
('1206-122934', 'WBL 15G275', '27', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4490.00),
('1207-125404', 'WBL 15G275', '27', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4490.00),
('1208-130612', 'WIR DO2', '28', '1', NULL, 'RITON', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1220.00),
('607-180206', 'WGS-AT150-LPG-Brass Burner Cover', '18', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1980.00),
('608-180206', 'WGS-SC1701S-LPG', '18', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1800.00),
('609-180206', 'WGS-SSB3', '18', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1600.00),
('610-180206', 'WGS-SSB2', '18', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1600.00),
('611-180206', 'WGS-SSB1-LPG-Stainless Steel', '18', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1600.00),
('612-180206', 'WGS-SSC2', '18', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1450.00),
('613-180206', 'WGS-SSC1-LPG-Stainless Steel', '18', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1450.00),
('614-180206', 'WGS-SCE1', '18', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1350.00),
('615-180206', 'WGS-SSC3', '18', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1300.00),
('616-180206', 'WGS-SSH2', '18', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1200.00),
('617-180206', 'WGS-SS1', '18', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1340.00),
('618-180206', 'WGS-SSH90', '18', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1650.00),
('619-180206', 'WGS-SSH3', '18', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1150.00),
('620-180206', 'WGS-SSH1-Stainless Steel', '18', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1100.00),
('621-180206', 'WKH-CBSH60', '19', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 8500.00),
('622-180206', 'WKH-CBGH75', '19', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 37000.00),
('623-180206', 'WAVC-LS06', '20', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3300.00),
('624-180206', 'WAVC-F153', '20', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 5500.00),
('625-180206', 'WMC-P0718-HIL', '21', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 5200.00),
('626-180206', 'WMC-P0715-HIL', '21', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4750.00),
('627-180206', 'WMC-GCS712', '21', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3900.00),
('628-180206', 'WMC-GCA712', '21', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3600.00),
('629-180206', 'WDD-CP10', '22', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 7700.00),
('630-180206', 'WELB-RB02', '23', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 590.00),
('631-180206', 'WELB-VB10', '23', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 790.00),
('632-180206', 'WELB-V121', '23', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 750.00),
('633-180206', 'WELB-V959', '23', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 900.00),
('634-180206', 'WAP-OL06', '24', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 20500.00),
('635-180206', 'WEPC-K06A7', '25', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 5500.00),
('636-180206', 'WEPC-K05A10', '25', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4990.00),
('637-180206', 'WMPC-P03L(Manual)', '25', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1950.00),
('638-180206', 'WPC-M055(Induction)', '25', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1870.00),
('639-180206', 'WMPC-LR05(5.Lt)-Manual', '25', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1850.00),
('640-180206', 'WPC-MS55-Manual(MTC)', '25', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1550.00),
('641-180206', 'WPC-MO55-Oval(MTC)', '25', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1750.00),
('642-180206', 'WPC-M045(Induction)', '25', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1720.00),
('643-180206', 'WPC-MS55(Induction)', '25', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1680.00),
('644-180206', 'WPC-MO45-Oval(MTC)', '25', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1650.00),
('645-180206', 'WPC-MS45(Induction)', '25', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1500.00),
('646-180206', 'WPC-M035(Induction)', '25', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1460.00),
('647-180206', 'WPC-MS45-Manual(MTC)', '25', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1400.00),
('648-180206', 'WPC-MO35-Oval(MTC)', '25', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1350.00),
('649-180206', 'WPC-MS35(Induction)', '25', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1230.00),
('650-180206', 'WPC-MS35-Manual(MTC)', '25', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1120.00),
('651-180206', 'WIR-BS20', '26', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2890.00),
('652-180206', 'WBL-15GM75', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4800.00),
('653-180206', 'WBL-VK01-Mixer Grinder', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4590.00),
('654-180206', 'WBL-15GM65-Mixer Grider', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4400.00),
('655-180206', 'WBL-15SMG6', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4390.00),
('656-180206', 'WBL-15GM55', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4200.00),
('657-180206', 'WBL-10GX65-Mixer Grinder', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3500.00),
('658-180206', 'WBL-12M330', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3300.00),
('659-180206', 'WBL-12TCG5', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3300.00),
('660-180206', 'WBL-JYL22', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3280.00),
('661-180206', 'WBL-10G140', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3150.00),
('662-180206', 'WBL-6TCG30', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3100.00),
('663-180206', 'WBL-13M230', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3000.00),
('664-180206', 'WBL-13MC40N', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2550.00),
('665-180206', 'WBL-13PC40PN', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2500.00),
('666-180206', 'WBL-15GC40N', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2450.00),
('667-180206', 'WBL-13MX35N', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2300.00),
('668-180206', 'WBL-15PC40N', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2550.00),
('669-180206', 'WBL-15G35N', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2150.00),
('670-180206', 'WBL-13PC40N', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2450.00),
('671-180206', 'WBL-15PX35N', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2250.00),
('672-180206', 'WBL-13PX35N', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2180.00),
('673-180206', 'WBL-13EC25N', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2200.00),
('674-180206', 'WBL-13EX25N', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2020.00),
('675-180206', 'WBL-13CC25N', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2070.00),
('676-180206', 'WBL-13C325N', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1950.00),
('677-180206', 'WBL-13C225N', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1990.00),
('678-180206', 'WBL-13CX25N', '27', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1720.00),
('679-180206', 'WIR-SSI 01(Stand Steam)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 9300.00),
('680-180206', 'WIR-SST 02(Steam)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 5850.00),
('681-180206', 'WIR-SST 01(Steam)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 5400.00),
('682-180206', 'WIR-SC03(Steam)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1850.00),
('683-180206', 'WIR-SC01(Cordless Steam)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2190.00),
('684-180206', 'WIR-S07(Steam)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1800.00),
('685-180206', 'WIR-S08(Steam)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1550.00),
('686-180206', 'WIR-HD01(Dry)-Heavy', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1950.00),
('687-180206', 'WIR-S01(Steam)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1500.00),
('688-180206', 'WIR-S06(Steam)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1470.00),
('689-180206', 'WIR-S04(Steam)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1470.00),
('690-180206', 'WIR-HD03', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1790.00),
('691-180206', 'WIR-SC02(Cordless Steam)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1750.00),
('692-180206', 'WIR-S02(Steam)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1380.00),
('693-180206', 'WIR-S05(Steam)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1320.00),
('694-180206', 'WIR-HD02(Dri)-Heavy', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1550.00),
('695-180206', 'WIR-D08(Dry)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1150.00),
('696-180206', 'WIR-S10', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1100.00),
('697-180206', 'WIR-D07(Dry)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1100.00),
('698-180206', 'WIR-S03(Steam)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1050.00),
('699-180206', 'WIR-D06(Dry)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1150.00),
('700-180206', 'WIR-D03(Dry)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 860.00),
('701-180206', 'WIR-S09(Steam)-HIL', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 800.00),
('702-180206', 'WIR-D04(Dry)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 780.00),
('703-180206', 'WIR-D01A(Dry)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1160.00),
('704-180206', 'WIR-D05(Dry)', '28', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1150.00),
('705-180206', 'WCW-F2001(20cm)', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 720.00),
('706-180206', 'WCW-FS2001', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 740.00),
('707-180206', 'WEF-HM09(Frying Pan-Electric)', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 750.00),
('708-180206', 'WCW-F2202(22cm)', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 760.00),
('709-180206', 'WCW-TPS2601-Ruti Tawa Pan 26CM', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 780.00),
('710-180206', 'WCW-FS2201', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 840.00),
('711-180206', 'WCW-MSL1801', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 850.00),
('712-180206', 'WCW-TPS2801-Ruti Tawa Pan 28CM', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 850.00),
('713-180206', 'WCW-FSL2001(With Glass Lid)', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 850.00),
('714-180206', 'WCW-FS2201 (induction)', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 870.00),
('715-180206', 'WCW-F2002 Glass Lid(20cm)', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 880.00),
('716-180206', 'WCW-FSL2201', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 900.00),
('717-180206', 'WCW-MSL1801 (Induction)', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 900.00),
('718-180206', 'WCW-F2201-Glass Lid (22cm)', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 930.00),
('719-180206', 'WCW-FS2401 (Induction)', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 940.00),
('720-180206', 'WCW-T2601Without Indu.base (26cm)', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 980.00),
('721-180206', 'WCW-FS2601-Fry Pan', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 980.00),
('722-180206', 'WCW-FSL2201 (induction)', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1000.00),
('723-180206', 'WCW-F2404(24cm)', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1000.00),
('724-180206', 'WCW-FSL2401-Fry Pan with Lid', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1000.00),
('725-180206', 'WCW-F2403-Glass Lid (24cm)', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1020.00),
('726-180206', 'WCW-FS2601 (induction)', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1070.00),
('727-180206', 'WCW-F2605(26cm)', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1080.00),
('728-180206', 'WCW-FS2801-Fry Pan', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1090.00),
('729-180206', 'WCW-FSL2601-Fry Pan with Lid', '29', '1', NULL, NULL, NULL, '2024-12-22', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1100.00),
('730-180206', 'WCW-CSL2001-Casserole Pan with Lid', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1100.00),
('731-180206', 'WCW-FSL2401 (Induction)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1150.00),
('732-180206', 'WCW-FSL2801-Fry Pan with Glass Lid', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1250.00),
('733-180206', 'WCW-FS201I', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 800.00),
('734-180206', 'WCW-FS2801 (induction)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1150.00),
('735-180206', 'WCW-FSL2601 (induction)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1200.00),
('736-180206', 'WCW-WSL2401-Wok Pan with Lid', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1250.00),
('737-180206', 'WCW-S2001 Glass Lid(20cm)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1300.00),
('738-180206', 'WCW-F2604-Glass Lid (26cm)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1300.00),
('739-180206', 'WCW-WSL2601-Wok Pan with Lid', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1380.00),
('740-180206', 'WCW-FSL2801 (induction)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1400.00),
('741-180206', 'WCW-WSL2601 (induction)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1420.00),
('742-180206', 'WCW-CSL2401-Casserole Pan with Lid', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1420.00),
('743-180206', 'WCW-F2804-Glass Lid (28cm)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1450.00),
('744-180206', 'WCW-WSL2801-Wok Pan with Lid', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1480.00),
('745-180206', 'WCW-F3002-Glass Lid (30cm)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1550.00),
('746-180206', 'WCW-CSL2601-Casserole Pan with Lid', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1570.00),
('747-180206', 'WCW-CSL2601 (induction)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1600.00),
('748-180206', 'WCW-WSL3001-Wok Pan with Lid', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1650.00),
('749-180206', 'WCW-WSL3001 (induction)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1690.00),
('750-180206', 'WCW-W2801 Glass Lid(28cm)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1720.00),
('751-180206', 'WCW-CSL2801-Casserole Pan with Lid', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1750.00),
('752-180206', 'WCW-C2602-With Glass Lid(26cm)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1800.00),
('753-180206', 'WCW-CSL2801(induction)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1800.00),
('754-180206', 'WCW-W3003 Glass Lid(30cm)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1920.00),
('755-180206', 'WCW-SF240 Glass Lid(24cm)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2000.00),
('756-180206', 'WCW-CSL2001(Induction)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1170.00),
('757-180206', 'WCW-CSL2401(Induction)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1490.00),
('758-180206', 'WCW-FS2401(Induction)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 940.00),
('759-180206', 'WCW-WSL2401(Induction)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1330.00),
('760-180206', 'WCW-WSL2801(Induction)', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1530.00),
('761-180206', 'WCW-COM80', '29', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4990.00),
('762-180206', 'WRL-L77-Two Color', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1450.00),
('763-180206', 'WRL-L99', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1180.00),
('764-180206', 'WRL-MT50(Metal Torch)', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1030.00),
('765-180206', 'WRL-L88', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1050.00),
('766-180206', 'WRL-L98', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 950.00),
('767-180206', 'WRL-L104', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 850.00),
('768-180206', 'WRL-L69', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 840.00),
('769-180206', 'WRL-L55', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 800.00),
('770-180206', 'WRL-L95B', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 780.00),
('771-180206', 'WRL-DL07', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 750.00),
('772-180206', 'WRL-L66-Two Color', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 730.00),
('773-180206', 'WRL-LT100', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 690.00),
('774-180206', 'WRL-L44', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 650.00),
('775-180206', 'WRL-L95S', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 600.00),
('776-180206', 'WRL-L16C', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 420.00),
('777-180206', 'WRL-HT01(Head Torch)', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 410.00),
('778-180206', 'WRL-T80(Torch)', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 375.00),
('779-180206', 'WRL-L30', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 330.00),
('780-180206', 'WRL-L18', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 330.00),
('781-180206', 'WRL-LT101', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 320.00),
('782-180206', 'WRL-T50(Torch)', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 350.00),
('783-180206', 'WRL-T40(Torch)', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 295.00),
('784-180206', 'WRL-L20', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 300.00),
('785-180206', 'WRL-T20(Torch)9”length', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 230.00),
('786-180206', 'WRL-T103', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 225.00),
('787-180206', 'WRL-L10', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 220.00),
('788-180206', 'WRL-T10(Torch)', '30', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 205.00),
('789-180206', 'WHD-PRO 07', '31', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1900.00),
('790-180206', 'WHD-P06', '31', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1190.00),
('791-180206', 'WHD-P05', '31', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 990.00),
('792-180206', 'WHD-Rapunzel 08', '31', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 730.00),
('793-180206', 'WHS-TL01', '32', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 850.00),
('794-180206', 'WHSC-SZ19T(Curler)', '32', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1350.00),
('795-180206', 'ELITE-HP01', '33', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2000.00),
('796-180206', 'ELITE-HP02', '33', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2100.00),
('797-180206', 'ELITE-HP03', '33', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2200.00),
('798-180206', 'WCM-AK03', '34', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 950.00),
('799-180206', 'WCM-AK01', '34', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1150.00),
('800-180206', 'WCM-AK04(Waffle Maker)', '34', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1200.00),
('801-180206', 'WCM-AK05(Waffle Bowl Maker)', '34', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1200.00),
('802-180206', 'Gentry', '35', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3400.00),
('803-180206', 'Falchion', '35', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3200.00),
('804-180206', 'Grace-Shaver', '35', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2300.00),
('805-180206', 'Stark', '35', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1350.00),
('806-180206', 'Sleek', '35', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1020.00),
('807-180206', 'Shaver', '35', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1140.00),
('808-180206', 'Knight', '35', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 890.00),
('809-180206', 'WK-LDW17B', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2890.00),
('810-180206', 'WK-GDW17D', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2500.00),
('811-180206', 'WK-FYCK12', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2360.00),
('812-180206', 'WK-GDW17C', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2400.00),
('813-180206', 'WK-DW155(1.5L)Double layer', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1900.00),
('814-180206', 'WK-LSS250', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1850.00),
('815-180206', 'WK-GLDW170', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1800.00),
('816-180206', 'WK-DW175(Double Wall)', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1800.00),
('817-180206', 'WK-PGL20', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1700.00),
('818-180206', 'WK-LDW17A', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1650.00),
('819-180206', 'WK-DW171(Double Wall)', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1590.00),
('820-180206', 'WK-YDW18A', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1550.00),
('821-180206', 'WK-DW170(Double Wall)', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1530.00),
('822-180206', 'WK-DW150(Double Wall)', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1500.00),
('823-180206', 'WK-DW200(2L)Double Wall', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1490.00),
('824-180206', 'WK-DW201(2.0L)Double Wall', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1650.00),
('825-180206', 'WK-DW173(Double Wall)', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1350.00),
('826-180206', 'WK-DW151(1.5L)Double Wall', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1250.00),
('827-180206', 'WK-DLP100', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1250.00),
('828-180206', 'WK-SS1202(1.2L)Double Wall', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1280.00),
('829-180206', 'WK-HQDW150', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1250.00),
('830-180206', 'WK-LJSS170C', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1025.00),
('831-180206', 'WK-P1703(1.7L)', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 980.00),
('832-180206', 'WK-LJSS170', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 950.00),
('833-180206', 'WK-LJSS170', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 950.00),
('834-180206', 'WK-LJSS150C', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 940.00),
('835-180206', 'WK-LJSS150(1.5L)', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 900.00),
('836-180206', 'WK-P1001(1.0L)', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 900.00),
('837-180206', 'WK-LJSS150', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 900.00),
('838-180206', 'WK-LJSS120', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 875.00),
('839-180206', 'WK-LJSS120', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 875.00),
('840-180206', 'WK-P0801(0.8L)', '36', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 850.00),
('841-180206', 'WMIX-E200', '37', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 1800.00),
('842-180206', 'WMIX-KF13', '38', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 4450.00),
('843-180206', 'WHP-DAMH22', '39', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 3000.00),
('844-180206', 'WHP-SMH15', '39', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 2000.00),
('845-180206', 'WG-67L', '40', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 8500.00),
('846-180206', 'WG-C45L', '40', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 7800.00),
('847-180206', 'WG-C30L', '40', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 6800.00),
('848-180206', 'WS-AE565', '41', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 9000.00),
('849-180206', 'WS-AE588', '41', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 8500.00),
('850-180206', 'WSM-AK01(A)', '42', '1', NULL, NULL, NULL, '0000-00-00', NULL, NULL, NULL, NULL, NULL, '101', '101-101', 300.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales_dtl`
--

CREATE TABLE `sales_dtl` (
  `sales_dtl_id` varchar(50) NOT NULL,
  `sales_mst_id` varchar(50) NOT NULL,
  `model_id` varchar(20) DEFAULT NULL,
  `product_category_id` varchar(20) DEFAULT NULL,
  `supplier_id` varchar(20) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `total` decimal(18,2) DEFAULT NULL,
  `sub_total` decimal(18,2) DEFAULT NULL,
  `original_price` decimal(18,2) DEFAULT NULL,
  `commission_pct` decimal(5,2) DEFAULT NULL,
  `commission_type` varchar(50) NOT NULL,
  `org_code` varchar(20) DEFAULT NULL,
  `br_code` varchar(20) DEFAULT NULL,
  `distributor_code` varchar(50) DEFAULT NULL,
  `tds` decimal(18,2) DEFAULT NULL,
  `grade` varchar(50) DEFAULT NULL,
  `sales_voucher_ref` varchar(50) NOT NULL,
  `entry_user` varchar(200) DEFAULT NULL,
  `entry_date` datetime DEFAULT NULL,
  `edit_user` varchar(200) DEFAULT NULL,
  `edit_date` datetime DEFAULT NULL,
  `delete_user` varchar(200) DEFAULT NULL,
  `delete_date` datetime DEFAULT NULL,
  `authorized_status` char(1) DEFAULT 'N',
  `authorized_user` varchar(100) DEFAULT NULL,
  `authorized_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_dtl`
--

INSERT INTO `sales_dtl` (`sales_dtl_id`, `sales_mst_id`, `model_id`, `product_category_id`, `supplier_id`, `price`, `quantity`, `total`, `sub_total`, `original_price`, `commission_pct`, `commission_type`, `org_code`, `br_code`, `distributor_code`, `tds`, `grade`, `sales_voucher_ref`, `entry_user`, `entry_date`, `edit_user`, `edit_date`, `delete_user`, `delete_date`, `authorized_status`, `authorized_user`, `authorized_date`) VALUES
('101-101-D-20251224113801-2806', '101-101-20251224-762032', '1149-105737', '6', '1', 81900.00, 1.00, 81700.00, NULL, 81900.00, 200.00, 'AMT', '101', '101-101', NULL, NULL, NULL, '101-101-V-20251224-113716.240', 'riton', '2025-12-24 16:38:01', NULL, NULL, NULL, NULL, 'N', NULL, NULL),
('101-101-D-20251224113801-6540', '101-101-20251224-762032', '634-180206', '24', '1', 20500.00, 1.00, 19475.00, NULL, 20500.00, 5.00, 'PCT', '101', '101-101', NULL, NULL, NULL, '101-101-V-20251224-113716.240', 'riton', '2025-12-24 16:38:01', NULL, NULL, NULL, NULL, 'N', NULL, NULL),
('101-101-D-20251224114825-3271', '101-101-20251224-594584', '1141-042339', '86-022842', '2-022740', 13500.00, 1.00, 13500.00, NULL, 13500.00, 0.00, 'PCT', '101', '101-101', NULL, NULL, NULL, '101-101-V-20251224-114807.024', 'riton', '2025-12-24 16:48:25', NULL, NULL, NULL, NULL, 'N', NULL, NULL),
('101-101-D-20260105112859-1804', '101-101-20260105-622800', '1203-133406', '59', '1', 3800.00, 1.00, 3800.00, NULL, 3800.00, 0.00, 'PCT', '101', '101-101', NULL, NULL, NULL, '101-101-V-20260105-112824.012', 'riton', '2026-01-05 16:28:59', NULL, NULL, NULL, NULL, 'N', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sales_mst`
--

CREATE TABLE `sales_mst` (
  `sales_mst_id` varchar(50) NOT NULL,
  `sales_voucher_ref` varchar(50) DEFAULT NULL,
  `sales_entry_date` date DEFAULT NULL,
  `org_code` varchar(20) DEFAULT NULL,
  `br_code` varchar(20) DEFAULT NULL,
  `sub_total` decimal(10,0) NOT NULL,
  `discount` decimal(10,0) NOT NULL,
  `total_amount` decimal(18,2) DEFAULT NULL,
  `payment` decimal(18,2) DEFAULT NULL,
  `due_amount` decimal(18,2) DEFAULT NULL,
  `entry_user` varchar(200) DEFAULT NULL,
  `entry_date` datetime DEFAULT NULL,
  `edit_user` varchar(200) DEFAULT NULL,
  `edit_date` datetime DEFAULT NULL,
  `authorized_status` char(1) DEFAULT 'N',
  `authorized_user` varchar(100) DEFAULT NULL,
  `authorized_date` datetime DEFAULT NULL,
  `customer_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_mst`
--

INSERT INTO `sales_mst` (`sales_mst_id`, `sales_voucher_ref`, `sales_entry_date`, `org_code`, `br_code`, `sub_total`, `discount`, `total_amount`, `payment`, `due_amount`, `entry_user`, `entry_date`, `edit_user`, `edit_date`, `authorized_status`, `authorized_user`, `authorized_date`, `customer_id`) VALUES
('101-101-20251224-594584', '101-101-V-20251224-114807.024', '2025-12-24', '101', '101-101', 13500, 0, 13500.00, 5000.00, 8500.00, 'riton', '2025-12-24 16:48:25', NULL, NULL, 'Y', 'riton', '2025-12-24 16:48:28', 'C-20251224113801545'),
('101-101-20251224-762032', '101-101-V-20251224-113716.240', '2025-12-24', '101', '101-101', 101175, 0, 101175.00, 0.00, 101175.00, 'riton', '2025-12-24 16:38:01', NULL, NULL, 'Y', 'riton', '2025-12-24 16:38:36', 'C-20251224113801545'),
('101-101-20260105-622800', '101-101-V-20260105-112824.012', '2026-01-05', '101', '101-101', 3800, 0, 3800.00, 2000.00, 1800.00, 'riton', '2026-01-05 16:28:59', NULL, NULL, 'N', 'riton', '2026-01-05 16:29:02', 'C-101-101-260105112410');

-- --------------------------------------------------------

--
-- Table structure for table `stock_dtl`
--

CREATE TABLE `stock_dtl` (
  `stock_dtl_id` varchar(50) NOT NULL,
  `stock_mst_id` varchar(50) NOT NULL,
  `model_id` varchar(20) DEFAULT NULL,
  `product_category_id` varchar(20) DEFAULT NULL,
  `supplier_id` varchar(20) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `total` decimal(18,2) DEFAULT NULL,
  `sub_total` decimal(18,2) DEFAULT NULL,
  `original_price` decimal(18,2) DEFAULT NULL,
  `commission_pct` decimal(5,2) DEFAULT NULL,
  `commission_type` varchar(50) NOT NULL,
  `org_code` varchar(20) DEFAULT NULL,
  `br_code` varchar(20) DEFAULT NULL,
  `tds` decimal(18,2) DEFAULT NULL,
  `grade` varchar(50) DEFAULT NULL,
  `stock_voucher_ref` varchar(50) NOT NULL,
  `entry_user` varchar(200) DEFAULT NULL,
  `entry_date` datetime DEFAULT NULL,
  `edit_user` varchar(200) DEFAULT NULL,
  `edit_date` datetime DEFAULT NULL,
  `delete_user` varchar(200) DEFAULT NULL,
  `delete_date` datetime DEFAULT NULL,
  `authorized_status` char(1) DEFAULT 'N',
  `authorized_user` varchar(100) DEFAULT NULL,
  `authorized_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_dtl`
--

INSERT INTO `stock_dtl` (`stock_dtl_id`, `stock_mst_id`, `model_id`, `product_category_id`, `supplier_id`, `price`, `quantity`, `total`, `sub_total`, `original_price`, `commission_pct`, `commission_type`, `org_code`, `br_code`, `tds`, `grade`, `stock_voucher_ref`, `entry_user`, `entry_date`, `edit_user`, `edit_date`, `delete_user`, `delete_date`, `authorized_status`, `authorized_user`, `authorized_date`) VALUES
('101-101-DTL-20251216195321581', '101-101-STK-20251216-711627', '634-180206', '24', '1', 20500.00, 1.00, 20500.00, 20500.00, NULL, 0.00, 'PCT', '101', '101-101', NULL, NULL, '', 'riton', '2025-12-17 00:53:21', NULL, NULL, NULL, NULL, 'N', NULL, NULL),
('101-101-DTL-20251216195416557', '101-101-STK-20251216-191790', '1064-110344', '17', '1', 3990.00, 3.00, 11970.00, 11970.00, NULL, 0.00, 'PCT', '101', '101-101', NULL, NULL, '', 'riton', '2025-12-17 00:54:16', NULL, NULL, NULL, NULL, 'N', NULL, NULL),
('101-101-DTL-20251216195416618', '101-101-STK-20251216-191790', '634-180206', '24', '1', 20500.00, 1.00, 20500.00, 20500.00, NULL, 0.00, 'PCT', '101', '101-101', NULL, NULL, '', 'riton', '2025-12-17 00:54:16', NULL, NULL, NULL, NULL, 'N', NULL, NULL),
('101-101-DTL-20251223094842848', '101-101-STK-20251223-233020', '1126-023058', '86-022842', '2-022740', 11499.00, 3.00, 34497.00, 34497.00, NULL, 0.00, 'PCT', '101', '101-101', NULL, NULL, '', 'riton', '2025-12-23 14:48:42', NULL, NULL, NULL, NULL, 'N', NULL, NULL),
('101-101-DTL-20260108063316679', '101-101-STK-20260108-237368', '1026-060451', '1', '1', 16290.00, 12.00, 179841.60, 195480.00, NULL, 8.00, 'PCT', '101', '101-101', NULL, NULL, '', 'riton', '2026-01-08 11:33:16', NULL, NULL, NULL, NULL, 'N', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `stock_mst`
--

CREATE TABLE `stock_mst` (
  `stock_mst_id` varchar(50) NOT NULL,
  `stock_voucher_ref` varchar(50) DEFAULT NULL,
  `stock_entry_date` date DEFAULT NULL,
  `org_code` varchar(20) DEFAULT NULL,
  `br_code` varchar(20) DEFAULT NULL,
  `sub_total` decimal(10,0) NOT NULL,
  `discount` decimal(10,0) NOT NULL,
  `total_amount` decimal(18,2) DEFAULT NULL,
  `payment` decimal(18,2) DEFAULT NULL,
  `due_amount` decimal(18,2) DEFAULT NULL,
  `entry_user` varchar(200) DEFAULT NULL,
  `entry_date` datetime DEFAULT NULL,
  `edit_user` varchar(200) DEFAULT NULL,
  `edit_date` datetime DEFAULT NULL,
  `authorized_status` char(1) DEFAULT 'N',
  `authorized_user` varchar(100) DEFAULT NULL,
  `authorized_date` datetime DEFAULT NULL,
  `distributor_code` varchar(50) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_mst`
--

INSERT INTO `stock_mst` (`stock_mst_id`, `stock_voucher_ref`, `stock_entry_date`, `org_code`, `br_code`, `sub_total`, `discount`, `total_amount`, `payment`, `due_amount`, `entry_user`, `entry_date`, `edit_user`, `edit_date`, `authorized_status`, `authorized_user`, `authorized_date`, `distributor_code`) VALUES
('101-101-STK-20251216-191790', '979798', '2025-12-17', '101', '101-101', 32470, 0, 32470.00, 10000.00, 22470.00, 'riton', '2025-12-17 00:54:16', NULL, NULL, 'Y', 'riton', '2025-12-23 12:43:59', '101-101-20251023-104629'),
('101-101-STK-20251216-711627', '9878', '2025-12-17', '101', '101-101', 20500, 0, 20500.00, 0.00, 20500.00, 'riton', '2025-12-17 00:53:21', NULL, NULL, 'Y', 'riton', '2025-12-23 12:43:54', '101-101-20251023-104629'),
('101-101-STK-20251223-233020', '101-1019898', '2025-12-23', '101', '101-101', 34497, 0, 34497.00, 0.00, 34497.00, 'riton', '2025-12-23 14:48:31', 'riton', '2025-12-23 14:48:42', 'Y', 'riton', '2025-12-23 14:48:49', '101-101-20251023-104629'),
('101-101-STK-20260108-237368', '101-101-VCH-20251215-211958.888', '2026-01-08', '101', '101-101', 179842, 0, 179841.60, 0.00, 179841.60, 'riton', '2026-01-08 11:33:16', NULL, NULL, 'Y', 'riton', '2026-01-08 11:33:25', '1');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `supplier_id` varchar(50) NOT NULL,
  `supplier_name` varchar(200) NOT NULL,
  `supplier_address` varchar(200) DEFAULT NULL,
  `supplier_contact` varchar(500) DEFAULT NULL,
  `authorized_status` varchar(1) DEFAULT NULL,
  `authorized_user` varchar(100) DEFAULT NULL,
  `authorized_date` date DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `entry_user` varchar(200) DEFAULT NULL,
  `edit_date` date DEFAULT NULL,
  `edit_user` varchar(200) DEFAULT NULL,
  `delete_user` varchar(200) DEFAULT NULL,
  `delete_date` date DEFAULT NULL,
  `org_code` varchar(20) DEFAULT NULL,
  `br_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`supplier_id`, `supplier_name`, `supplier_address`, `supplier_contact`, `authorized_status`, `authorized_user`, `authorized_date`, `entry_date`, `entry_user`, `edit_date`, `edit_user`, `delete_user`, `delete_date`, `org_code`, `br_code`) VALUES
('1', 'WALTON', '', '', NULL, NULL, NULL, '2025-08-20', 'raju', NULL, NULL, NULL, NULL, '101', '101-101'),
('2-022740', 'Ma Telecom', 'Khagrachori', '', NULL, NULL, NULL, '2025-08-21', 'admin', '2025-10-16', 'riton', NULL, NULL, '101', '101-101'),
('3-105205', 'RS E', '', '', NULL, NULL, NULL, '2025-10-14', 'raju', '2025-11-17', 'riton', NULL, NULL, '101', '101-101'),
('4-122638', 'WALTON', '', '', NULL, NULL, NULL, '2025-10-15', 'test', NULL, NULL, NULL, NULL, '100', '100-100');

--
-- Triggers `supplier`
--
DELIMITER $$
CREATE TRIGGER `trg_generate_supplier_id` BEFORE INSERT ON `supplier` FOR EACH ROW BEGIN
    DECLARE v_supplier_count INT;
    DECLARE v_time VARCHAR(6);

    -- Only generate supplier_id if it is NULL or empty
    IF NEW.supplier_id IS NULL OR NEW.supplier_id = '' THEN
        -- Count total suppliers + 1 (use MAX numeric part to handle deletes)
        SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(supplier_id, '-', 1) AS UNSIGNED)), 0) + 1
        INTO v_supplier_count
        FROM supplier;

        -- Get current time HHMMSS
        SET v_time = DATE_FORMAT(NOW(), '%H%i%s');

        -- Assign supplier_id
        SET NEW.supplier_id = CONCAT(v_supplier_count, '-', v_time);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_action_permission`
--

CREATE TABLE `user_action_permission` (
  `permission_id` int(11) NOT NULL,
  `user_type_id` int(11) NOT NULL,
  `can_insert` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `can_approve` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_action_permission`
--

INSERT INTO `user_action_permission` (`permission_id`, `user_type_id`, `can_insert`, `can_edit`, `can_delete`, `can_approve`) VALUES
(3, 1, 1, 1, 1, 1),
(6, 2, 1, 1, 1, 1),
(7, 3, 0, 0, 0, 0),
(8, 4, 1, 1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_login_info`
--

CREATE TABLE `user_login_info` (
  `USER_ID` varchar(200) NOT NULL,
  `USER_PASSWORD` varchar(200) NOT NULL,
  `EMAIL` varchar(100) DEFAULT NULL,
  `PHONE` varchar(20) DEFAULT NULL,
  `ENTRY_DATE` date DEFAULT NULL,
  `AUTHORIZED_STATUS` char(1) DEFAULT 'N',
  `USER_NAME` varchar(200) DEFAULT NULL,
  `USER_TYPE_ID` int(10) DEFAULT NULL,
  `BR_CODE` varchar(20) DEFAULT NULL,
  `ORG_CODE` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_login_info`
--

INSERT INTO `user_login_info` (`USER_ID`, `USER_PASSWORD`, `EMAIL`, `PHONE`, `ENTRY_DATE`, `AUTHORIZED_STATUS`, `USER_NAME`, `USER_TYPE_ID`, `BR_CODE`, `ORG_CODE`) VALUES
('raju', '$2y$10$l5soIuG1p9yJbJcBrJuTAuIxMwF.i.v1eheXHxvYI5RpBQTxdvCrG', 'rajucsecu@hotmail.com', '', '2025-10-15', 'Y', 'Raju Das', 1, '100-100', '100'),
('rasel', '$2y$10$h7e9mNQchs0dcLizEtpM/ejVAENkiiFKXljRBj.gv0DLOoTRj04gm', '', '', '2025-10-15', 'Y', 'rasel', 4, '101-101', '101'),
('riton', '$2y$10$629v4JKsD2Koe9QAYzGKeu.RZoo7HJv4AmePphdXyUlLe79Lz68u6', 'rs0011@gmail.com', '', '2025-10-15', 'Y', 'Riton Das', 2, '101-101', '101'),
('sales', '$2y$10$aJK.RXRBtWI2UjGgJcH06OmIIG6/WOmZQgTIiWvvhyGHImFgZ0yp.', '', '', '2025-10-28', 'Y', 'sales', 4, '101-101', '101'),
('softadmin', '$2y$10$ry0hs3xBOzo/bji9oNQNqe9w84cvxONkZSGW/M4lewdhJUbDzX.Ma', '', '', '2025-10-15', 'Y', 'software Admin', 1, '100-100', '100');

-- --------------------------------------------------------

--
-- Table structure for table `user_menu_view_permission`
--

CREATE TABLE `user_menu_view_permission` (
  `PERMISSION_ID` varchar(200) NOT NULL,
  `USER_TYPE_ID` int(11) NOT NULL,
  `MENU_ID` int(11) NOT NULL,
  `CAN_VIEW` tinyint(1) DEFAULT 0,
  `AUTHORIZED_STATUS` varchar(1) DEFAULT NULL,
  `AUTHORIZED_USER` varchar(100) DEFAULT NULL,
  `AUTHORIZED_DATE` date DEFAULT NULL,
  `ENTRY_USER` varchar(200) NOT NULL,
  `ENTRY_DATE` date NOT NULL DEFAULT curdate(),
  `EDIT_USER` varchar(200) DEFAULT NULL,
  `EDIT_DATE` date DEFAULT NULL,
  `DELETE_USER` varchar(200) DEFAULT NULL,
  `DELETE_DATE` date DEFAULT NULL,
  `ORG_CODE` varchar(20) DEFAULT NULL,
  `BR_CODE` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_menu_view_permission`
--

INSERT INTO `user_menu_view_permission` (`PERMISSION_ID`, `USER_TYPE_ID`, `MENU_ID`, `CAN_VIEW`, `AUTHORIZED_STATUS`, `AUTHORIZED_USER`, `AUTHORIZED_DATE`, `ENTRY_USER`, `ENTRY_DATE`, `EDIT_USER`, `EDIT_DATE`, `DELETE_USER`, `DELETE_DATE`, `ORG_CODE`, `BR_CODE`) VALUES
('r001', 1, 35, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r002', 1, 34, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r003', 1, 33, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r004', 1, 1, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r007', 1, 4, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r008', 1, 5, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r009', 1, 32, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r010', 1, 37, 1, 'Y', NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r012', 1, 2, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r013', 3, 37, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r014', 3, 33, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r015', 3, 34, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r016', 3, 30, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r017', 3, 1, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r018', 3, 5, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r019', 3, 31, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r020', 3, 27, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r021', 3, 4, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r022', 3, 2, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r023', 3, 26, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r025', 3, 28, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r026', 3, 35, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r027', 3, 32, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r031', 1, 30, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r034', 1, 31, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r035', 1, 27, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r038', 1, 26, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r040', 1, 28, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r043', 2, 37, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r044', 2, 33, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r045', 2, 34, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r046', 2, 30, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r047', 2, 1, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r048', 2, 5, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r049', 2, 31, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r050', 2, 27, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r051', 2, 4, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r052', 2, 2, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r053', 2, 26, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r055', 2, 28, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r056', 2, 35, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r057', 2, 32, 0, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r060', 1, 40, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r062', 2, 40, 1, NULL, NULL, NULL, 'raju', '2025-10-14', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r063', 3, 40, 0, NULL, NULL, NULL, 'raju', '2025-10-15', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r064', 4, 37, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r065', 4, 33, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r066', 4, 34, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r067', 4, 30, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r068', 4, 1, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r069', 4, 5, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r070', 4, 31, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r071', 4, 27, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r072', 4, 4, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r073', 4, 2, 1, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r074', 4, 26, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r076', 4, 28, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r077', 4, 40, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r078', 4, 35, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r079', 4, 32, 0, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r080', 1, 41, 1, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r081', 2, 41, 1, NULL, NULL, NULL, 'softadmin', '2025-10-16', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r082', 1, 42, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100'),
('r083', 1, 43, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2025-10-22', NULL, NULL, '100', '100-100'),
('r084', 2, 42, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r085', 2, 43, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2026-01-06', NULL, NULL, '101', '101-101'),
('r086', 3, 41, 0, NULL, NULL, NULL, 'softadmin', '2025-10-23', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r087', 3, 42, 1, NULL, NULL, NULL, 'softadmin', '2025-10-23', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r088', 3, 43, 1, NULL, NULL, NULL, 'softadmin', '2025-10-23', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r089', 1, 44, 1, NULL, NULL, NULL, '', '0000-00-00', NULL, NULL, NULL, NULL, '100', '100-100'),
('r090', 2, 44, 1, NULL, NULL, NULL, '', '0000-00-00', 'softadmin', '2026-01-06', NULL, NULL, '101', '101-101'),
('r091', 4, 44, 0, NULL, NULL, NULL, 'softadmin', '2025-10-28', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r092', 4, 41, 0, NULL, NULL, NULL, 'softadmin', '2025-10-28', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r093', 4, 42, 1, NULL, NULL, NULL, 'softadmin', '2025-10-28', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r094', 4, 43, 1, NULL, NULL, NULL, 'softadmin', '2025-10-28', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r095', 3, 44, 0, NULL, NULL, NULL, 'softadmin', '2025-10-28', 'softadmin', '2025-10-28', NULL, NULL, '100', '100-100'),
('r100', 1, 46, 1, NULL, NULL, NULL, '', '0000-00-00', NULL, NULL, NULL, NULL, '100', '100'),
('r101', 2, 46, 0, NULL, NULL, NULL, 'softadmin', '2025-10-28', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r102', 4, 46, 0, NULL, NULL, NULL, 'softadmin', '2025-10-28', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r103', 3, 46, 0, NULL, NULL, NULL, 'softadmin', '2025-10-28', NULL, NULL, NULL, NULL, '100', '100-100'),
('r104', 1, 47, 1, NULL, NULL, NULL, '', '0000-00-00', NULL, NULL, NULL, NULL, '100', '100-100'),
('r105', 2, 47, 1, NULL, NULL, NULL, 'softadmin', '2025-12-04', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r106', 1, 48, 1, NULL, NULL, NULL, '', '0000-00-00', NULL, NULL, NULL, NULL, '100', '100-100'),
('r107', 2, 48, 1, NULL, NULL, NULL, 'softadmin', '2025-12-23', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r108', 1, 49, 1, NULL, NULL, NULL, '', '0000-00-00', NULL, NULL, NULL, NULL, '100', '100-100'),
('r109', 1, 50, 1, NULL, NULL, NULL, '', '0000-00-00', NULL, NULL, NULL, NULL, '100', '100-100'),
('r110', 2, 49, 1, NULL, NULL, NULL, 'softadmin', '2025-12-24', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r111', 2, 50, 1, NULL, NULL, NULL, 'softadmin', '2025-12-24', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r112', 1, 51, 1, NULL, NULL, NULL, '', '0000-00-00', NULL, NULL, NULL, NULL, '100', '100-100'),
('r113', 2, 51, 1, NULL, NULL, NULL, 'softadmin', '2026-01-06', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r114', 1, 52, 1, NULL, NULL, NULL, '', '0000-00-00', NULL, NULL, NULL, NULL, '100', '100-100'),
('r115', 2, 52, 1, NULL, NULL, NULL, 'softadmin', '2026-01-06', 'softadmin', '2026-01-06', NULL, NULL, '100', '100-100'),
('r116', 4, 48, 0, NULL, NULL, NULL, 'softadmin', '2026-01-08', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r117', 4, 49, 1, NULL, NULL, NULL, 'softadmin', '2026-01-08', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r118', 4, 50, 1, NULL, NULL, NULL, 'softadmin', '2026-01-08', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r119', 4, 52, 1, NULL, NULL, NULL, 'softadmin', '2026-01-08', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r120', 4, 51, 1, NULL, NULL, NULL, 'softadmin', '2026-01-08', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100'),
('r121', 4, 47, 1, NULL, NULL, NULL, 'softadmin', '2026-01-08', 'softadmin', '2026-01-08', NULL, NULL, '100', '100-100');

--
-- Triggers `user_menu_view_permission`
--
DELIMITER $$
CREATE TRIGGER `trg_permission_autogen` BEFORE INSERT ON `user_menu_view_permission` FOR EACH ROW BEGIN
    DECLARE last_id VARCHAR(10);
    DECLARE new_num INT;

    -- Only generate if PERMISSION_ID is NULL
    IF NEW.PERMISSION_ID IS NULL OR NEW.PERMISSION_ID = '' THEN
        -- Get the last inserted PERMISSION_ID
        SELECT PERMISSION_ID 
        INTO last_id
        FROM user_menu_view_permission
        WHERE PERMISSION_ID LIKE 'r%' 
        ORDER BY PERMISSION_ID DESC
        LIMIT 1;

        -- Generate new number
        IF last_id IS NOT NULL THEN
            SET new_num = CAST(SUBSTRING(last_id,2) AS UNSIGNED) + 1;
        ELSE
            SET new_num = 1;
        END IF;

        -- Set new PERMISSION_ID with R prefix
        SET NEW.PERMISSION_ID = CONCAT('r', LPAD(new_num, 3, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_type_info`
--

CREATE TABLE `user_type_info` (
  `USER_TYPE_ID` int(11) NOT NULL,
  `USER_TYPE_NAME` varchar(100) NOT NULL,
  `USER_TYPE_CODE` varchar(50) NOT NULL,
  `ROLE_LEVEL` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_type_info`
--

INSERT INTO `user_type_info` (`USER_TYPE_ID`, `USER_TYPE_NAME`, `USER_TYPE_CODE`, `ROLE_LEVEL`) VALUES
(1, 'SUPER ADMIN', 'SUPER_ADMIN', 1),
(2, 'ADMIN USER', 'ADMIN', 2),
(3, 'GENERAL USER', 'USER', 3),
(4, 'END USER', 'END USER', 4);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branch_info`
--
ALTER TABLE `branch_info`
  ADD PRIMARY KEY (`BR_CODE`),
  ADD KEY `FK_BRANCH_ORG` (`ORG_CODE`);

--
-- Indexes for table `customer_due_installment`
--
ALTER TABLE `customer_due_installment`
  ADD PRIMARY KEY (`installment_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `sales_mst_id` (`sales_mst_id`);

--
-- Indexes for table `customer_info`
--
ALTER TABLE `customer_info`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `customer_previous_due`
--
ALTER TABLE `customer_previous_due`
  ADD PRIMARY KEY (`prev_due_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `distributor`
--
ALTER TABLE `distributor`
  ADD PRIMARY KEY (`DISTRIBUTOR_CODE`);

--
-- Indexes for table `distributor_due_installment`
--
ALTER TABLE `distributor_due_installment`
  ADD PRIMARY KEY (`installment_id`),
  ADD KEY `idx_distributor_code` (`distributor_code`),
  ADD KEY `idx_stock_mst_id` (`stock_mst_id`),
  ADD KEY `idx_org_br` (`org_code`,`br_code`);

--
-- Indexes for table `distributor_previous_due`
--
ALTER TABLE `distributor_previous_due`
  ADD PRIMARY KEY (`distributor_due_id`),
  ADD UNIQUE KEY `uk_distributor_due` (`distributor_code`,`br_code`,`org_code`,`due_date`);

--
-- Indexes for table `menu_info`
--
ALTER TABLE `menu_info`
  ADD PRIMARY KEY (`MENU_ID`);

--
-- Indexes for table `organization_info`
--
ALTER TABLE `organization_info`
  ADD PRIMARY KEY (`ORG_CODE`);

--
-- Indexes for table `product_model`
--
ALTER TABLE `product_model`
  ADD PRIMARY KEY (`MODEL_ID`),
  ADD KEY `PRODUCT_CATEGORY_ID` (`PRODUCT_CATEGORY_ID`),
  ADD KEY `SUPPLIER_ID` (`SUPPLIER_ID`);

--
-- Indexes for table `sales_dtl`
--
ALTER TABLE `sales_dtl`
  ADD PRIMARY KEY (`sales_dtl_id`),
  ADD KEY `idx_sales_mst` (`sales_mst_id`);

--
-- Indexes for table `sales_mst`
--
ALTER TABLE `sales_mst`
  ADD PRIMARY KEY (`sales_mst_id`),
  ADD KEY `idx_org_code` (`org_code`),
  ADD KEY `idx_br_code` (`br_code`),
  ADD KEY `fk_sales_mst_customer` (`customer_id`);

--
-- Indexes for table `stock_dtl`
--
ALTER TABLE `stock_dtl`
  ADD PRIMARY KEY (`stock_dtl_id`),
  ADD KEY `fk_stock_mst` (`stock_mst_id`),
  ADD KEY `fk_dtl_model` (`model_id`),
  ADD KEY `fk_dtl_category` (`product_category_id`),
  ADD KEY `fk_dtl_supplier` (`supplier_id`),
  ADD KEY `fk_dtl_org` (`org_code`),
  ADD KEY `fk_dtl_br` (`br_code`);

--
-- Indexes for table `stock_mst`
--
ALTER TABLE `stock_mst`
  ADD PRIMARY KEY (`stock_mst_id`),
  ADD KEY `fk_mst_org` (`org_code`),
  ADD KEY `fk_mst_br` (`br_code`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `user_action_permission`
--
ALTER TABLE `user_action_permission`
  ADD PRIMARY KEY (`permission_id`),
  ADD KEY `fk_action_perm_user_type` (`user_type_id`);

--
-- Indexes for table `user_login_info`
--
ALTER TABLE `user_login_info`
  ADD PRIMARY KEY (`USER_ID`),
  ADD KEY `fk_user_login_user_type` (`USER_TYPE_ID`);

--
-- Indexes for table `user_menu_view_permission`
--
ALTER TABLE `user_menu_view_permission`
  ADD PRIMARY KEY (`PERMISSION_ID`),
  ADD KEY `fk_menu_perm_user_type` (`USER_TYPE_ID`),
  ADD KEY `fk_menu` (`MENU_ID`);

--
-- Indexes for table `user_type_info`
--
ALTER TABLE `user_type_info`
  ADD PRIMARY KEY (`USER_TYPE_ID`),
  ADD UNIQUE KEY `USER_TYPE_CODE` (`USER_TYPE_CODE`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `menu_info`
--
ALTER TABLE `menu_info`
  MODIFY `MENU_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `user_action_permission`
--
ALTER TABLE `user_action_permission`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_type_info`
--
ALTER TABLE `user_type_info`
  MODIFY `USER_TYPE_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `branch_info`
--
ALTER TABLE `branch_info`
  ADD CONSTRAINT `FK_BRANCH_ORG` FOREIGN KEY (`ORG_CODE`) REFERENCES `organization_info` (`ORG_CODE`);

--
-- Constraints for table `customer_due_installment`
--
ALTER TABLE `customer_due_installment`
  ADD CONSTRAINT `customer_due_installment_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer_info` (`customer_id`),
  ADD CONSTRAINT `customer_due_installment_ibfk_2` FOREIGN KEY (`sales_mst_id`) REFERENCES `sales_mst` (`sales_mst_id`);

--
-- Constraints for table `customer_previous_due`
--
ALTER TABLE `customer_previous_due`
  ADD CONSTRAINT `customer_previous_due_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer_info` (`customer_id`);

--
-- Constraints for table `distributor_due_installment`
--
ALTER TABLE `distributor_due_installment`
  ADD CONSTRAINT `fk_d_inst_distributor` FOREIGN KEY (`distributor_code`) REFERENCES `distributor` (`DISTRIBUTOR_CODE`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_d_inst_stock` FOREIGN KEY (`stock_mst_id`) REFERENCES `stock_mst` (`stock_mst_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `distributor_previous_due`
--
ALTER TABLE `distributor_previous_due`
  ADD CONSTRAINT `fk_distributor_prev_due` FOREIGN KEY (`distributor_code`) REFERENCES `distributor` (`DISTRIBUTOR_CODE`) ON UPDATE CASCADE;

--
-- Constraints for table `sales_mst`
--
ALTER TABLE `sales_mst`
  ADD CONSTRAINT `fk_sales_mst_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer_info` (`customer_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
