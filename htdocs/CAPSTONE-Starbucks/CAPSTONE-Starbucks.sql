-- Adminer 4.8.1 MySQL 10.4.34-MariaDB-1:10.4.34+maria~ubu2004 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','manager','staff') DEFAULT 'admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  UNIQUE KEY `unique_employee_id` (`employee_id`),
  KEY `idx_email` (`email`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` (`id`, `first_name`, `last_name`, `email`, `phone`, `employee_id`, `password`, `role`, `status`, `created_at`, `updated_at`, `last_login`, `created_by`) VALUES
(5,	'(Admin) Jan Andrei',	'Libres',	'libres.janandrei@sti.edu.ph',	'123456789',	'ADMIN1',	'$2y$10$hcijwiWUPzmvCA00nDd3N.wxcfGWqmbjQUuXw5ujvmfMZLZtIYi02',	'admin',	'active',	'2025-09-06 03:44:02',	'2025-09-13 01:58:38',	'2025-09-13 01:58:38',	NULL);

DROP TABLE IF EXISTS `admin_remember_tokens`;
CREATE TABLE `admin_remember_tokens` (
  `admin_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`admin_id`),
  CONSTRAINT `admin_remember_tokens_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_type` enum('admin','customer') NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_type`,`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_table` (`table_name`,`record_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `audit_logs` (`id`, `user_type`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1,	'admin',	4,	'admin_registered',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0',	'2025-09-02 01:00:36'),
(2,	'admin',	4,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0',	'2025-09-03 00:34:39'),
(3,	'admin',	4,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0',	'2025-09-03 01:25:51'),
(4,	'admin',	4,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0',	'2025-09-04 03:39:18'),
(5,	'admin',	4,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0',	'2025-09-05 23:41:43'),
(6,	'admin',	4,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0',	'2025-09-06 00:24:09'),
(7,	'admin',	5,	'admin_registered',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 Edg/132.0.0.0',	'2025-09-06 03:44:02'),
(8,	'admin',	5,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 Edg/132.0.0.0',	'2025-09-06 03:44:16'),
(9,	'admin',	5,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0',	'2025-09-08 23:45:19'),
(10,	'customer',	4,	'logout',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0',	'2025-09-09 00:38:37'),
(11,	'admin',	5,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0',	'2025-09-09 00:38:55'),
(12,	'admin',	5,	'logout',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0',	'2025-09-09 00:38:58'),
(13,	'admin',	5,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0',	'2025-09-09 00:39:04'),
(14,	'customer',	4,	'logout',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0',	'2025-09-09 01:58:24'),
(15,	'admin',	5,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0',	'2025-09-10 04:57:21'),
(16,	'admin',	5,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 Edg/132.0.0.0',	'2025-09-10 06:05:22'),
(17,	'customer',	4,	'logout',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 Edg/132.0.0.0',	'2025-09-10 06:27:35'),
(18,	'admin',	5,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 Edg/132.0.0.0',	'2025-09-10 06:28:14'),
(19,	'admin',	5,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0',	'2025-09-12 01:20:38'),
(20,	'customer',	4,	'logout',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0',	'2025-09-12 01:30:13'),
(21,	'admin',	5,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0',	'2025-09-12 23:52:56'),
(22,	'admin',	5,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 Edg/132.0.0.0',	'2025-09-13 01:52:21'),
(23,	'customer',	4,	'logout',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 Edg/132.0.0.0',	'2025-09-13 01:55:03'),
(24,	'admin',	5,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 Edg/132.0.0.0',	'2025-09-13 01:58:38');

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `name`, `description`, `image_url`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES
(1,	'Hot Coffees',	'Traditional hot coffee beverages',	NULL,	'active',	1,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(2,	'Cold Coffees',	'Iced and blended coffee drinks',	NULL,	'active',	2,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(3,	'Frappuccinos',	'Blended ice beverages',	NULL,	'active',	3,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(4,	'Hot Teas',	'Premium hot tea selections',	NULL,	'active',	4,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(5,	'Cold Teas',	'Refreshing iced tea beverages',	NULL,	'active',	5,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(6,	'Hot Drinks',	'Non-coffee hot beverages',	NULL,	'active',	6,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(7,	'Cold Drinks',	'Refreshing cold beverages',	NULL,	'active',	7,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(8,	'Food',	'Sandwiches, pastries, and snacks',	NULL,	'active',	8,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27');

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive','blocked') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customers` (`id`, `first_name`, `last_name`, `email`, `phone`, `password`, `status`, `email_verified`, `created_at`, `updated_at`, `last_login`) VALUES
(4,	'Jan Andrei',	'Libres',	'libres.janandrei@sti.edu.ph',	'123456789',	'$2y$10$I3EloUvks4aG3583Vq1xEO6R4qHAMCGHj8zndy6b9rgGIZmAF3A9S',	'active',	0,	'2025-09-03 02:17:35',	'2025-09-13 01:52:44',	'2025-09-13 01:52:44'),
(5,	'Denissa',	'Joseco',	'joseco@sti.edu.ph',	'1234567989',	'$2y$10$TJuE0pdDF2c/qqaMCxicx.zKC6bZKoUFXZBbOWCTTO1f5LlNAPeMO',	'active',	0,	'2025-09-13 01:55:45',	'2025-09-13 01:55:55',	'2025-09-13 01:55:55');

DROP VIEW IF EXISTS `customer_order_summary`;
CREATE TABLE `customer_order_summary` (`customer_id` int(11), `customer_name` varchar(101), `email` varchar(100), `total_orders` bigint(21), `total_spent` decimal(32,2), `average_order_value` decimal(14,6), `last_order_date` timestamp, `first_order_date` timestamp);


DROP VIEW IF EXISTS `daily_sales_summary`;
CREATE TABLE `daily_sales_summary` (`sale_date` date, `total_orders` bigint(21), `total_revenue` decimal(32,2), `average_order_value` decimal(14,6), `unique_customers` bigint(21));


DROP TABLE IF EXISTS `digital_wallet`;
CREATE TABLE `digital_wallet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_customer_wallet` (`customer_id`),
  CONSTRAINT `digital_wallet_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `digital_wallet` (`id`, `customer_id`, `balance`, `created_at`, `updated_at`) VALUES
(1,	4,	9908.84,	'2025-09-10 05:38:23',	'2025-09-13 00:02:56'),
(2,	5,	49651.43,	'2025-09-13 01:56:21',	'2025-09-13 01:58:06');

DROP TABLE IF EXISTS `inventory_transactions`;
CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `transaction_type` enum('stock_in','stock_out','adjustment','waste') NOT NULL,
  `quantity` int(11) NOT NULL,
  `previous_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `reference_type` enum('order','purchase','adjustment','waste') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_product` (`product_id`),
  KEY `idx_type` (`transaction_type`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `inventory_transactions` (`id`, `product_id`, `transaction_type`, `quantity`, `previous_quantity`, `new_quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES
(1,	7,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'Kupal',	5,	'2025-09-09 00:02:53'),
(2,	7,	'stock_in',	20,	20,	40,	'adjustment',	NULL,	'Kupal',	5,	'2025-09-09 00:03:58'),
(3,	7,	'stock_in',	20,	40,	60,	'adjustment',	NULL,	'Kupal',	5,	'2025-09-09 00:04:46'),
(4,	8,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:08:42'),
(5,	9,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:08:48'),
(6,	6,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:08:52'),
(7,	16,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:08:58'),
(8,	17,	'stock_in',	20,	10,	30,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:09:02'),
(9,	18,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:09:09'),
(10,	10,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:09:17'),
(11,	11,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:09:22'),
(12,	12,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:09:26'),
(13,	2,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:09:31'),
(14,	4,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:09:36'),
(15,	3,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:09:40'),
(16,	5,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:09:46'),
(17,	19,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:09:50'),
(18,	1,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:09:55'),
(19,	15,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:10:05'),
(20,	13,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:10:10'),
(21,	14,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-09 00:10:13'),
(22,	16,	'stock_in',	20,	1,	21,	'adjustment',	NULL,	'',	5,	'2025-09-10 04:58:10'),
(23,	4,	'stock_in',	20,	5,	25,	'adjustment',	NULL,	'',	5,	'2025-09-10 04:58:38'),
(24,	17,	'stock_in',	20,	0,	20,	'adjustment',	NULL,	'',	5,	'2025-09-10 06:26:41'),
(25,	16,	'stock_in',	20,	5,	25,	'adjustment',	NULL,	'',	5,	'2025-09-10 06:27:00');

DROP TABLE IF EXISTS `loyalty_points`;
CREATE TABLE `loyalty_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `transaction_type` enum('earned','redeemed','expired','adjusted') NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `expires_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_type` (`transaction_type`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_loyalty_customer_type` (`customer_id`,`transaction_type`),
  CONSTRAINT `loyalty_points_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loyalty_points_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `loyalty_points` (`id`, `customer_id`, `points`, `transaction_type`, `order_id`, `description`, `expires_at`, `created_at`) VALUES
(1,	4,	8,	'earned',	22,	'Points earned for order',	NULL,	'2025-09-10 07:46:30'),
(2,	4,	2,	'earned',	23,	'Points earned for order',	NULL,	'2025-09-10 07:46:43'),
(3,	4,	2,	'earned',	24,	'Points earned for order',	NULL,	'2025-09-10 07:48:39'),
(4,	4,	5,	'earned',	25,	'Points earned for order',	NULL,	'2025-09-10 07:54:54'),
(5,	4,	2,	'earned',	26,	'Points earned for order',	NULL,	'2025-09-12 01:21:36'),
(6,	4,	5,	'earned',	27,	'Points earned for order',	NULL,	'2025-09-12 23:58:43'),
(7,	4,	2,	'earned',	28,	'Points earned for order',	NULL,	'2025-09-13 00:02:56'),
(8,	5,	348,	'earned',	29,	'Points earned for order',	NULL,	'2025-09-13 01:58:06');

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','preparing','ready','completed','cancelled','refunded') DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `payment_method` enum('cash','card','digital_wallet','gcash','gift_card') DEFAULT NULL,
  `order_type` enum('dine_in','takeaway','delivery') DEFAULT 'takeaway',
  `special_instructions` text DEFAULT NULL,
  `estimated_ready_time` datetime DEFAULT NULL,
  `actual_ready_time` datetime DEFAULT NULL,
  `served_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `wallet_payment` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_number` (`order_number`),
  KEY `served_by` (`served_by`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_order_number` (`order_number`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_order_type` (`order_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_ready_time` (`estimated_ready_time`),
  KEY `idx_orders_date_status` (`created_at`,`status`),
  KEY `idx_orders_payment_method` (`payment_method`),
  KEY `idx_orders_status_payment` (`status`,`payment_method`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`served_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_payment_method` CHECK (`payment_method` in ('cash','card','digital_wallet','gcash','gift_card') or `payment_method` is null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `orders` (`id`, `customer_id`, `order_number`, `total_amount`, `tax_amount`, `discount_amount`, `final_amount`, `status`, `payment_status`, `payment_method`, `order_type`, `special_instructions`, `estimated_ready_time`, `actual_ready_time`, `served_by`, `created_at`, `updated_at`, `wallet_payment`) VALUES
(5,	4,	'SB20255927',	5.90,	0.47,	0.00,	6.37,	'completed',	'pending',	'cash',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-05 23:49:07',	'2025-09-09 00:48:35',	0),
(6,	4,	'SB20252498',	31.00,	2.48,	0.00,	33.48,	'completed',	'pending',	'cash',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-06 01:14:59',	'2025-09-09 00:48:35',	0),
(7,	4,	'SB20250682',	9.50,	0.76,	0.00,	10.26,	'completed',	'pending',	'cash',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-06 03:05:54',	'2025-09-09 00:48:35',	0),
(8,	4,	'SB20256263',	2.75,	0.22,	0.00,	2.97,	'completed',	'pending',	'cash',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-06 04:53:16',	'2025-09-09 00:48:35',	0),
(9,	4,	'SB20258960',	163.25,	13.06,	0.00,	176.31,	'completed',	'pending',	'cash',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-06 05:12:10',	'2025-09-09 00:48:35',	0),
(10,	4,	'SB20258045',	94.00,	7.52,	0.00,	101.52,	'completed',	'pending',	'cash',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-09 00:22:37',	'2025-09-09 00:48:35',	0),
(11,	4,	'SB20259871',	2.75,	0.22,	0.00,	2.97,	'preparing',	'pending',	'card',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-09 01:17:03',	'2025-09-10 05:19:29',	0),
(12,	4,	'SB20259357',	13.75,	1.10,	0.00,	14.85,	'preparing',	'pending',	'card',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-09 01:35:29',	'2025-09-10 05:19:29',	0),
(13,	4,	'SB20251163',	5.50,	0.44,	0.00,	5.94,	'preparing',	'pending',	'digital_wallet',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-09 01:35:50',	'2025-09-10 05:19:29',	0),
(14,	4,	'SB20259818',	45.75,	3.66,	0.00,	49.41,	'preparing',	'pending',	'card',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-09 01:54:19',	'2025-09-10 05:19:29',	0),
(15,	4,	'SB20257542',	2.75,	0.22,	0.00,	2.97,	'preparing',	'pending',	'card',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-09 01:58:02',	'2025-09-10 05:19:29',	0),
(16,	4,	'SB20255134',	11.00,	0.88,	0.00,	11.88,	'completed',	'pending',	NULL,	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-10 05:26:32',	'2025-09-12 01:20:53',	0),
(17,	4,	'SB20257062',	101.25,	8.10,	0.00,	109.35,	'completed',	'pending',	'digital_wallet',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-10 05:51:54',	'2025-09-12 01:20:53',	1),
(18,	4,	'SB20254333',	2.75,	0.22,	0.00,	2.97,	'completed',	'pending',	'card',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-10 05:52:28',	'2025-09-12 01:20:53',	0),
(19,	4,	'SB20250739',	8.50,	0.68,	0.00,	9.18,	'completed',	'pending',	'digital_wallet',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-10 06:21:35',	'2025-09-12 01:21:18',	1),
(20,	4,	'SB20255402',	37.50,	3.00,	0.00,	40.50,	'completed',	'pending',	'digital_wallet',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-10 06:29:11',	'2025-09-12 01:20:53',	1),
(21,	4,	'SB20251490',	5.25,	0.42,	0.00,	5.67,	'completed',	'pending',	'digital_wallet',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-10 07:34:29',	'2025-09-12 01:20:53',	1),
(22,	4,	'SB20250210',	8.00,	0.64,	0.00,	8.64,	'completed',	'pending',	'digital_wallet',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-10 07:46:30',	'2025-09-12 01:20:53',	1),
(23,	4,	'SB20251281',	2.75,	0.22,	0.00,	2.97,	'completed',	'pending',	'digital_wallet',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-10 07:46:43',	'2025-09-12 01:20:53',	1),
(24,	4,	'SB20250410',	2.75,	0.22,	0.00,	2.97,	'completed',	'pending',	'digital_wallet',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-10 07:48:39',	'2025-09-12 01:20:53',	1),
(25,	4,	'SB20252196',	5.50,	0.44,	0.00,	5.94,	'completed',	'pending',	'digital_wallet',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-10 07:54:54',	'2025-09-12 01:20:53',	1),
(26,	4,	'SB20255348',	2.75,	0.22,	0.00,	2.97,	'ready',	'pending',	'digital_wallet',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-12 01:21:36',	'2025-09-13 00:01:35',	1),
(27,	4,	'SB20257117',	4.75,	0.38,	0.00,	5.13,	'preparing',	'pending',	'card',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-12 23:58:43',	'2025-09-13 00:01:26',	0),
(28,	4,	'SB20258521',	2.75,	0.22,	0.00,	2.97,	'pending',	'pending',	'digital_wallet',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-13 00:02:56',	'2025-09-13 00:02:56',	1),
(29,	5,	'SB20252998',	322.75,	25.82,	0.00,	348.57,	'refunded',	'pending',	'digital_wallet',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-13 01:58:06',	'2025-09-13 02:01:12',	1);

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `product_sku` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `size` varchar(20) DEFAULT NULL,
  `customizations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`customizations`)),
  `special_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `selected_size` varchar(20) DEFAULT 'Grande',
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_product_name` (`product_name`),
  KEY `idx_order_items_product_order` (`product_id`,`order_id`),
  KEY `idx_order_items_size` (`selected_size`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Order items with size selection support';

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_sku`, `quantity`, `unit_price`, `total_price`, `size`, `customizations`, `special_notes`, `created_at`, `selected_size`) VALUES
(12,	5,	17,	'Blueberry Muffin',	NULL,	2,	2.95,	5.00,	NULL,	NULL,	NULL,	'2025-09-05 23:49:07',	'Grande'),
(13,	6,	10,	'Caramel Frappuccino',	NULL,	1,	5.50,	5.00,	NULL,	NULL,	NULL,	'2025-09-06 01:14:59',	'Grande'),
(14,	6,	18,	'Turkey & Swiss Sandwich',	NULL,	3,	6.75,	20.00,	NULL,	NULL,	NULL,	'2025-09-06 01:14:59',	'Grande'),
(15,	6,	5,	'Caramel Macchiato',	NULL,	1,	5.25,	5.00,	NULL,	NULL,	NULL,	'2025-09-06 01:14:59',	'Grande'),
(16,	7,	4,	'Caffè Latte',	NULL,	2,	4.75,	9.00,	NULL,	NULL,	NULL,	'2025-09-06 03:05:54',	'Grande'),
(17,	8,	16,	'Butter Croissant',	NULL,	1,	2.75,	2.00,	NULL,	NULL,	NULL,	'2025-09-06 04:53:16',	'Grande'),
(18,	9,	17,	'Blueberry Muffin',	NULL,	50,	2.95,	147.00,	NULL,	NULL,	NULL,	'2025-09-06 05:12:10',	'Grande'),
(19,	9,	4,	'Caffè Latte',	NULL,	1,	4.75,	4.00,	NULL,	NULL,	NULL,	'2025-09-06 05:12:10',	'Grande'),
(20,	9,	10,	'Caramel Frappuccino',	NULL,	2,	5.50,	11.00,	NULL,	NULL,	NULL,	'2025-09-06 05:12:10',	'Grande'),
(21,	10,	16,	'Butter Croissant',	NULL,	2,	2.75,	5.00,	NULL,	NULL,	NULL,	'2025-09-09 00:22:37',	'Grande'),
(22,	10,	17,	'Blueberry Muffin',	NULL,	5,	2.95,	14.00,	NULL,	NULL,	NULL,	'2025-09-09 00:22:37',	'Grande'),
(23,	10,	4,	'Caffè Latte',	NULL,	10,	4.75,	47.00,	NULL,	NULL,	NULL,	'2025-09-09 00:22:37',	'Grande'),
(24,	10,	5,	'Caramel Macchiato',	NULL,	5,	5.25,	26.00,	NULL,	NULL,	NULL,	'2025-09-09 00:22:37',	'Grande'),
(25,	11,	16,	'Butter Croissant',	NULL,	1,	2.75,	2.00,	NULL,	NULL,	NULL,	'2025-09-09 01:17:03',	'Grande'),
(26,	12,	16,	'Butter Croissant',	NULL,	5,	2.75,	13.00,	NULL,	NULL,	NULL,	'2025-09-09 01:35:29',	'Grande'),
(27,	13,	16,	'Butter Croissant',	NULL,	2,	2.75,	5.00,	NULL,	NULL,	NULL,	'2025-09-09 01:35:50',	'Grande'),
(28,	14,	16,	'Butter Croissant',	NULL,	8,	2.75,	22.00,	NULL,	NULL,	NULL,	'2025-09-09 01:54:19',	'Grande'),
(29,	14,	4,	'Caffè Latte',	NULL,	5,	4.75,	23.00,	NULL,	NULL,	NULL,	'2025-09-09 01:54:19',	'Grande'),
(30,	15,	16,	'Butter Croissant',	NULL,	1,	2.75,	2.00,	NULL,	NULL,	NULL,	'2025-09-09 01:58:02',	'Grande'),
(31,	16,	16,	'Butter Croissant',	NULL,	4,	2.75,	11.00,	NULL,	NULL,	NULL,	'2025-09-10 05:26:32',	'Grande'),
(32,	17,	16,	'Butter Croissant',	NULL,	10,	2.75,	27.00,	NULL,	NULL,	NULL,	'2025-09-10 05:51:54',	'Grande'),
(33,	17,	17,	'Blueberry Muffin',	NULL,	25,	2.95,	73.00,	NULL,	NULL,	NULL,	'2025-09-10 05:51:54',	'Grande'),
(34,	18,	16,	'Butter Croissant',	NULL,	1,	2.75,	2.00,	NULL,	NULL,	NULL,	'2025-09-10 05:52:28',	'Grande'),
(35,	19,	16,	'Butter Croissant',	NULL,	1,	2.75,	2.00,	NULL,	NULL,	NULL,	'2025-09-10 06:21:35',	'0'),
(36,	19,	4,	'Caffè Latte',	NULL,	1,	5.75,	5.00,	NULL,	NULL,	NULL,	'2025-09-10 06:21:35',	'0'),
(37,	20,	16,	'Butter Croissant',	NULL,	5,	2.75,	13.00,	NULL,	NULL,	NULL,	'2025-09-10 06:29:11',	'0'),
(38,	20,	2,	'Caffè Americano',	NULL,	5,	4.75,	23.00,	NULL,	NULL,	NULL,	'2025-09-10 06:29:11',	'0'),
(39,	21,	4,	'Caffè Latte',	NULL,	1,	5.25,	5.00,	NULL,	NULL,	NULL,	'2025-09-10 07:34:29',	'0'),
(40,	22,	16,	'Butter Croissant',	NULL,	1,	2.75,	2.00,	NULL,	NULL,	NULL,	'2025-09-10 07:46:30',	'0'),
(41,	22,	4,	'Caffè Latte',	NULL,	1,	5.25,	5.00,	NULL,	NULL,	NULL,	'2025-09-10 07:46:30',	'0'),
(42,	23,	16,	'Butter Croissant',	NULL,	1,	2.75,	2.00,	NULL,	NULL,	NULL,	'2025-09-10 07:46:43',	'0'),
(43,	24,	16,	'Butter Croissant',	NULL,	1,	2.75,	2.00,	NULL,	NULL,	NULL,	'2025-09-10 07:48:39',	'0'),
(44,	25,	16,	'Butter Croissant',	NULL,	2,	2.75,	5.00,	NULL,	NULL,	NULL,	'2025-09-10 07:54:54',	'0'),
(45,	26,	16,	'Butter Croissant',	NULL,	1,	2.75,	2.00,	NULL,	NULL,	NULL,	'2025-09-12 01:21:36',	'0'),
(46,	27,	4,	'Caffè Latte',	NULL,	1,	4.75,	4.00,	NULL,	NULL,	NULL,	'2025-09-12 23:58:43',	'0'),
(47,	28,	16,	'Butter Croissant',	NULL,	1,	2.75,	2.00,	NULL,	NULL,	NULL,	'2025-09-13 00:02:56',	'0'),
(48,	29,	17,	'Blueberry Muffin',	NULL,	20,	2.95,	59.00,	NULL,	NULL,	NULL,	'2025-09-13 01:58:06',	'0'),
(49,	29,	10,	'Caramel Frappuccino',	NULL,	20,	11.50,	230.00,	NULL,	NULL,	NULL,	'2025-09-13 01:58:06',	'0'),
(50,	29,	18,	'Turkey & Swiss Sandwich',	NULL,	5,	6.75,	33.00,	NULL,	NULL,	NULL,	'2025-09-13 01:58:06',	'0');

DROP TABLE IF EXISTS `order_promotions`;
CREATE TABLE `order_promotions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `promotion_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_promotion` (`promotion_id`),
  CONSTRAINT `order_promotions_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_promotions_ibfk_2` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `image_url` varchar(255) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `min_stock_level` int(11) DEFAULT 5,
  `stock_quantity` int(11) NOT NULL DEFAULT 0 COMMENT 'Current stock quantity for the product',
  `calories` int(11) DEFAULT NULL,
  `ingredients` text DEFAULT NULL,
  `allergens` varchar(255) DEFAULT NULL,
  `size_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`size_options`)),
  `customization_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`customization_options`)),
  `status` enum('active','inactive','out_of_stock') DEFAULT 'active',
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sku` (`sku`),
  KEY `idx_category` (`category_id`),
  KEY `idx_name` (`name`),
  KEY `idx_price` (`price`),
  KEY `idx_status` (`status`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_stock_quantity` (`stock_quantity`),
  FULLTEXT KEY `ft_search` (`name`,`description`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `chk_stock_positive` CHECK (`stock_quantity` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `cost_price`, `image_url`, `sku`, `min_stock_level`, `stock_quantity`, `calories`, `ingredients`, `allergens`, `size_options`, `customization_options`, `status`, `is_featured`, `sort_order`, `created_at`, `updated_at`) VALUES
(1,	1,	'Pike Place Roast',	'Our signature medium roast coffee',	4.50,	1.50,	'PikePlaceRoast.jpg',	'HOT001',	5,	20,	5,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-09 00:09:55'),
(2,	1,	'Caffè Americano',	'Espresso shots topped with hot water',	3.75,	1.25,	'CaffeAmericano.jpg',	'HOT002',	5,	15,	15,	NULL,	NULL,	NULL,	NULL,	'active',	0,	0,	'2025-09-02 00:57:27',	'2025-09-10 06:29:11'),
(3,	1,	'Cappuccino',	'Espresso with steamed milk and foam',	4.25,	1.40,	'Cappuccino.jpg',	'HOT003',	5,	20,	120,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-09 00:09:40'),
(4,	1,	'Caffè Latte',	'Espresso with steamed milk',	4.75,	1.60,	'CaffeLatte.jpg',	'HOT004',	5,	21,	190,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-12 23:58:43'),
(5,	1,	'Caramel Macchiato',	'Steamed milk with vanilla syrup and caramel drizzle',	5.25,	1.75,	'CaramelMacchiato.jpg',	'HOT005',	5,	15,	250,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-09 00:22:37'),
(6,	2,	'Iced Coffee',	'Freshly brewed coffee served over ice',	3.25,	1.10,	'Iced_Coffee.jpg',	'COLD001',	5,	20,	5,	NULL,	NULL,	NULL,	NULL,	'active',	0,	0,	'2025-09-02 00:57:27',	'2025-09-09 00:08:52'),
(7,	2,	'Cold Brew Coffee',	'Slow-steeped, smooth cold coffee',	3.75,	1.25,	'ColdBrew.jpg',	'COLD002',	5,	60,	5,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-09 00:04:46'),
(8,	2,	'Iced Caffè Americano',	'Espresso shots with cold water over ice',	3.50,	1.15,	'Iced_Caffe_Americano.jpg',	'COLD003',	5,	20,	15,	NULL,	NULL,	NULL,	NULL,	'active',	0,	0,	'2025-09-02 00:57:27',	'2025-09-09 00:08:42'),
(9,	2,	'Iced Caramel Macchiato',	'Vanilla syrup, milk, espresso, and caramel over ice',	5.00,	1.70,	'Iced-Caramel-Macchiato.jpg',	'COLD004',	5,	20,	250,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-09 00:08:48'),
(10,	3,	'Caramel Frappuccino',	'Coffee blended with caramel syrup and ice',	5.50,	1.85,	'Caramel_Frappuccino.jpg',	'FRAP001',	5,	0,	370,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-13 01:58:06'),
(11,	3,	'Mocha Frappuccino',	'Coffee and chocolate blended with ice',	5.25,	1.75,	'MochaFrappuccino.jpg',	'FRAP002',	5,	20,	350,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-09 00:09:22'),
(12,	3,	'Vanilla Bean Frappuccino',	'Vanilla bean powder blended with ice',	4.75,	1.60,	'Vanilla_Bean_Creme_Frappuccino.jpg',	'FRAP003',	5,	20,	280,	NULL,	NULL,	NULL,	NULL,	'active',	0,	0,	'2025-09-02 00:57:27',	'2025-09-09 00:09:26'),
(13,	4,	'Earl Grey Tea',	'Classic bergamot-flavored black tea',	2.50,	0.85,	'EarlGreyBlackTea.jpg',	'TEA001',	5,	20,	0,	NULL,	NULL,	NULL,	NULL,	'active',	0,	0,	'2025-09-02 00:57:27',	'2025-09-09 00:10:10'),
(14,	4,	'Green Tea',	'Premium jasmine green tea',	2.25,	0.75,	'Green_Tea.jpg',	'TEA002',	5,	20,	0,	NULL,	NULL,	NULL,	NULL,	'active',	0,	0,	'2025-09-02 00:57:27',	'2025-09-09 00:10:13'),
(15,	4,	'Chai Tea Latte',	'Spiced black tea with steamed milk',	4.25,	1.40,	'IcedChaiTeaLatte.jpg',	'TEA003',	5,	20,	240,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-09 00:10:05'),
(16,	8,	'Butter Croissant',	'Flaky, buttery French croissant',	2.75,	1.00,	'Butter_Croissant.jpg',	'FOOD001',	5,	13,	280,	NULL,	NULL,	NULL,	NULL,	'active',	0,	0,	'2025-09-02 00:57:27',	'2025-09-13 00:02:56'),
(17,	8,	'Blueberry Muffin',	'Fresh blueberries in vanilla muffin',	2.95,	1.10,	'Blueberry_Muffin.jpg',	'FOOD002',	5,	0,	350,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-13 01:58:06'),
(18,	8,	'Turkey & Swiss Sandwich',	'Sliced turkey with Swiss cheese',	6.75,	2.50,	'Turkey&Swiss Sandwich.jpg',	'FOOD003',	5,	15,	520,	NULL,	NULL,	NULL,	NULL,	'active',	0,	0,	'2025-09-02 00:57:27',	'2025-09-13 01:58:06'),
(19,	1,	'Nescafe Black Coffee',	'KUPAL KABA BOSS?',	22.00,	2.00,	'uploads/products/68bba40ea01ef_1757127694.jpg',	'NBC1',	5,	20,	100,	'Sekwet',	'Cocoa',	NULL,	NULL,	'active',	1,	1,	'2025-09-06 03:01:34',	'2025-09-09 00:10:55');

DROP VIEW IF EXISTS `product_sales_summary`;
CREATE TABLE `product_sales_summary` (`product_id` int(11), `product_name` varchar(100), `category_name` varchar(100), `total_quantity_sold` decimal(32,0), `total_revenue` decimal(32,2), `total_orders` bigint(21), `average_price` decimal(14,6));


DROP TABLE IF EXISTS `product_sizes`;
CREATE TABLE `product_sizes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `size_name` varchar(20) NOT NULL,
  `size_price_modifier` decimal(5,2) DEFAULT 0.00,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_size` (`product_id`,`size_name`),
  KEY `idx_product_sizes_product` (`product_id`),
  CONSTRAINT `product_sizes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Product size configurations and pricing modifiers';

INSERT INTO `product_sizes` (`id`, `product_id`, `size_name`, `size_price_modifier`, `is_available`, `created_at`) VALUES
(1,	1,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(2,	2,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(3,	3,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(4,	4,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(5,	5,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(6,	6,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(7,	7,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(8,	8,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(9,	9,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(10,	10,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(11,	11,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(12,	12,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(13,	13,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(14,	14,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(15,	15,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(16,	16,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(17,	17,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(18,	18,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(19,	19,	'Small',	0.00,	1,	'2025-09-10 06:18:03'),
(32,	1,	'Medium',	0.50,	1,	'2025-09-10 06:18:03'),
(33,	2,	'Medium',	0.50,	1,	'2025-09-10 06:18:03'),
(34,	3,	'Medium',	0.50,	1,	'2025-09-10 06:18:03'),
(35,	4,	'Medium',	0.50,	1,	'2025-09-10 06:18:03'),
(36,	5,	'Medium',	0.50,	1,	'2025-09-10 06:18:03'),
(37,	6,	'Medium',	0.50,	1,	'2025-09-10 06:18:03'),
(38,	7,	'Medium',	0.50,	1,	'2025-09-10 06:18:03'),
(39,	8,	'Medium',	0.50,	1,	'2025-09-10 06:18:03'),
(40,	9,	'Medium',	0.50,	1,	'2025-09-10 06:18:03'),
(41,	10,	'Medium',	0.50,	1,	'2025-09-10 06:18:03'),
(42,	11,	'Medium',	0.50,	1,	'2025-09-10 06:18:03'),
(43,	12,	'Medium',	0.50,	1,	'2025-09-10 06:18:03'),
(44,	13,	'Medium',	0.50,	1,	'2025-09-10 06:18:03'),
(45,	14,	'Medium',	0.50,	1,	'2025-09-10 06:18:03'),
(46,	15,	'Medium',	0.50,	1,	'2025-09-10 06:18:03'),
(47,	16,	'Medium',	0.00,	1,	'2025-09-10 06:18:03'),
(48,	17,	'Medium',	0.00,	1,	'2025-09-10 06:18:03'),
(49,	18,	'Medium',	0.00,	1,	'2025-09-10 06:18:03'),
(50,	19,	'Medium',	0.50,	1,	'2025-09-10 06:18:03'),
(63,	1,	'Large',	1.00,	1,	'2025-09-10 06:18:03'),
(64,	2,	'Large',	1.00,	1,	'2025-09-10 06:18:03'),
(65,	3,	'Large',	1.00,	1,	'2025-09-10 06:18:03'),
(66,	4,	'Large',	1.00,	1,	'2025-09-10 06:18:03'),
(67,	5,	'Large',	1.00,	1,	'2025-09-10 06:18:03'),
(68,	6,	'Large',	1.00,	1,	'2025-09-10 06:18:03'),
(69,	7,	'Large',	1.00,	1,	'2025-09-10 06:18:03'),
(70,	8,	'Large',	1.00,	1,	'2025-09-10 06:18:03'),
(71,	9,	'Large',	1.00,	1,	'2025-09-10 06:18:03'),
(72,	10,	'Large',	1.00,	1,	'2025-09-10 06:18:03'),
(73,	11,	'Large',	1.00,	1,	'2025-09-10 06:18:03'),
(74,	12,	'Large',	1.00,	1,	'2025-09-10 06:18:03'),
(75,	13,	'Large',	1.00,	1,	'2025-09-10 06:18:03'),
(76,	14,	'Large',	1.00,	1,	'2025-09-10 06:18:03'),
(77,	15,	'Large',	1.00,	1,	'2025-09-10 06:18:03'),
(78,	16,	'Large',	0.00,	1,	'2025-09-10 06:18:03'),
(79,	17,	'Large',	0.00,	1,	'2025-09-10 06:18:03'),
(80,	18,	'Large',	0.00,	1,	'2025-09-10 06:18:03'),
(81,	19,	'Large',	1.00,	1,	'2025-09-10 06:18:03'),
(82,	1,	'XL',	6.00,	1,	'2025-09-12 23:59:13'),
(83,	2,	'XL',	6.00,	1,	'2025-09-12 23:59:13'),
(84,	3,	'XL',	6.00,	1,	'2025-09-12 23:59:13'),
(85,	4,	'XL',	6.00,	1,	'2025-09-12 23:59:13'),
(86,	5,	'XL',	6.00,	1,	'2025-09-12 23:59:13'),
(87,	6,	'XL',	6.00,	1,	'2025-09-12 23:59:13'),
(88,	7,	'XL',	6.00,	1,	'2025-09-12 23:59:13'),
(89,	8,	'XL',	6.00,	1,	'2025-09-12 23:59:13'),
(90,	9,	'XL',	6.00,	1,	'2025-09-12 23:59:13'),
(91,	10,	'XL',	6.00,	1,	'2025-09-12 23:59:13'),
(92,	11,	'XL',	6.00,	1,	'2025-09-12 23:59:13'),
(93,	12,	'XL',	6.00,	1,	'2025-09-12 23:59:13'),
(94,	13,	'XL',	6.00,	1,	'2025-09-12 23:59:13'),
(95,	14,	'XL',	6.00,	1,	'2025-09-12 23:59:13'),
(96,	15,	'XL',	6.00,	1,	'2025-09-12 23:59:13'),
(97,	16,	'XL',	5.00,	1,	'2025-09-12 23:59:13'),
(98,	17,	'XL',	5.00,	1,	'2025-09-12 23:59:13'),
(99,	18,	'XL',	5.00,	1,	'2025-09-12 23:59:13'),
(100,	19,	'XL',	6.00,	1,	'2025-09-12 23:59:13');

DROP TABLE IF EXISTS `promotions`;
CREATE TABLE `promotions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `discount_type` enum('percentage','fixed_amount','buy_x_get_y') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `customer_usage_limit` int(11) DEFAULT 1,
  `applicable_products` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_products`)),
  `applicable_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_categories`)),
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_code` (`code`),
  KEY `created_by` (`created_by`),
  KEY `idx_code` (`code`),
  KEY `idx_dates` (`start_date`,`end_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `promotions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_public`, `updated_by`, `updated_at`) VALUES
(1,	'store_name',	'Starbucks Coffee Shop',	'string',	'Store name for receipts and displays',	1,	NULL,	'2025-09-02 00:57:27'),
(2,	'store_address',	'123 Coffee Street, Bean City, BC 12345',	'string',	'Store address',	1,	NULL,	'2025-09-02 00:57:27'),
(3,	'store_phone',	'+1-555-COFFEE',	'string',	'Store contact number',	1,	NULL,	'2025-09-02 00:57:27'),
(4,	'tax_rate',	'8.0',	'number',	'Tax rate percentage',	0,	NULL,	'2025-09-02 00:57:27'),
(5,	'loyalty_points_rate',	'1',	'number',	'Points earned per dollar spent',	0,	NULL,	'2025-09-02 00:57:27'),
(6,	'min_order_delivery',	'15.00',	'number',	'Minimum order amount for delivery',	1,	NULL,	'2025-09-02 00:57:27'),
(7,	'store_hours',	'{\"monday\":\"6:00-22:00\",\"tuesday\":\"6:00-22:00\",\"wednesday\":\"6:00-22:00\",\"thursday\":\"6:00-22:00\",\"friday\":\"6:00-23:00\",\"saturday\":\"6:00-23:00\",\"sunday\":\"7:00-21:00\"}',	'json',	'Store operating hours',	1,	NULL,	'2025-09-02 00:57:27');

DROP TABLE IF EXISTS `wallet_transactions`;
CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `transaction_type` enum('cash_in','payment','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `wallet_id` (`wallet_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wallet_transactions_ibfk_2` FOREIGN KEY (`wallet_id`) REFERENCES `digital_wallet` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wallet_transactions_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `wallet_transactions` (`id`, `customer_id`, `wallet_id`, `transaction_type`, `amount`, `balance_before`, `balance_after`, `description`, `order_id`, `created_at`) VALUES
(1,	4,	1,	'payment',	109.35,	10100.00,	9990.65,	'Payment for Order #SB20257062',	17,	'2025-09-10 05:51:54'),
(2,	4,	1,	'payment',	9.18,	9990.65,	9981.47,	'Payment for Order #SB20250739',	19,	'2025-09-10 06:21:35'),
(3,	4,	1,	'payment',	40.50,	9981.47,	9940.97,	'Payment for Order #SB20255402',	20,	'2025-09-10 06:29:11'),
(4,	4,	1,	'payment',	5.67,	9940.97,	9935.30,	'Payment for Order #SB20251490',	21,	'2025-09-10 07:34:29'),
(5,	4,	1,	'payment',	8.64,	9935.30,	9926.66,	'Payment for Order #SB20250210',	22,	'2025-09-10 07:46:30'),
(6,	4,	1,	'payment',	2.97,	9926.66,	9923.69,	'Payment for Order #SB20251281',	23,	'2025-09-10 07:46:43'),
(7,	4,	1,	'payment',	2.97,	9923.69,	9920.72,	'Payment for Order #SB20250410',	24,	'2025-09-10 07:48:39'),
(8,	4,	1,	'payment',	5.94,	9920.72,	9914.78,	'Payment for Order #SB20252196',	25,	'2025-09-10 07:54:54'),
(9,	4,	1,	'payment',	2.97,	9914.78,	9911.81,	'Payment for Order #SB20255348',	26,	'2025-09-12 01:21:36'),
(10,	4,	1,	'payment',	2.97,	9911.81,	9908.84,	'Payment for Order #SB20258521',	28,	'2025-09-13 00:02:56'),
(11,	5,	2,	'payment',	348.57,	50000.00,	49651.43,	'Payment for Order #SB20252998',	29,	'2025-09-13 01:58:06');

DROP TABLE IF EXISTS `customer_order_summary`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `customer_order_summary` AS select `c`.`id` AS `customer_id`,concat(`c`.`first_name`,' ',`c`.`last_name`) AS `customer_name`,`c`.`email` AS `email`,count(`o`.`id`) AS `total_orders`,sum(`o`.`final_amount`) AS `total_spent`,avg(`o`.`final_amount`) AS `average_order_value`,max(`o`.`created_at`) AS `last_order_date`,min(`o`.`created_at`) AS `first_order_date` from (`customers` `c` left join `orders` `o` on(`c`.`id` = `o`.`customer_id` and `o`.`status` = 'completed')) group by `c`.`id`,concat(`c`.`first_name`,' ',`c`.`last_name`),`c`.`email` order by sum(`o`.`final_amount`) desc;

DROP TABLE IF EXISTS `daily_sales_summary`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `daily_sales_summary` AS select cast(`o`.`created_at` as date) AS `sale_date`,count(`o`.`id`) AS `total_orders`,sum(`o`.`final_amount`) AS `total_revenue`,avg(`o`.`final_amount`) AS `average_order_value`,count(distinct `o`.`customer_id`) AS `unique_customers` from `orders` `o` where `o`.`status` = 'completed' group by cast(`o`.`created_at` as date) order by cast(`o`.`created_at` as date) desc;

DROP TABLE IF EXISTS `product_sales_summary`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `product_sales_summary` AS select `p`.`id` AS `product_id`,`p`.`name` AS `product_name`,`c`.`name` AS `category_name`,sum(`oi`.`quantity`) AS `total_quantity_sold`,sum(`oi`.`total_price`) AS `total_revenue`,count(distinct `oi`.`order_id`) AS `total_orders`,avg(`oi`.`unit_price`) AS `average_price` from (((`products` `p` join `order_items` `oi` on(`p`.`id` = `oi`.`product_id`)) join `orders` `o` on(`oi`.`order_id` = `o`.`id`)) join `categories` `c` on(`p`.`category_id` = `c`.`id`)) where `o`.`status` = 'completed' group by `p`.`id`,`p`.`name`,`c`.`name` order by sum(`oi`.`quantity`) desc;

-- 2025-09-13 02:03:15