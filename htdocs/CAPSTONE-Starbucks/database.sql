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
(1,	'John',	'Manager',	'admin@starbucks.com',	'+1234567890',	'SB001',	'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',	'super_admin',	'active',	'2025-09-02 00:57:27',	'2025-09-02 00:57:27',	NULL,	NULL),
(2,	'Sarah',	'Smith',	'sarah.smith@starbucks.com',	'+1234567891',	'SB002',	'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',	'admin',	'active',	'2025-09-02 00:57:27',	'2025-09-02 00:57:27',	NULL,	NULL),
(3,	'Mike',	'Johnson',	'mike.johnson@starbucks.com',	'+1234567892',	'SB003',	'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',	'manager',	'active',	'2025-09-02 00:57:27',	'2025-09-02 00:57:27',	NULL,	NULL),
(4,	'Jan Andrei',	'Libres',	'libres.janandrei@sti.edu.ph',	'123456789',	'Admin1',	'$2y$10$K9UGUS8a4ktl2JQ1dZ5Cz.vs5levixqRBGqlGyAoNSHBRZFgCdOt2',	'admin',	'active',	'2025-09-02 01:00:36',	'2025-09-03 01:25:51',	'2025-09-03 01:25:51',	NULL);

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
(3,	'admin',	4,	'admin_login',	NULL,	NULL,	NULL,	NULL,	'127.0.0.1',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0',	'2025-09-03 01:25:51');

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
(1,	'Alice',	'Brown',	'alice.brown@email.com',	'+1234567893',	'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',	'active',	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27',	NULL),
(2,	'Bob',	'Wilson',	'bob.wilson@email.com',	'+1234567894',	'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',	'active',	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27',	NULL),
(3,	'Carol',	'Davis',	'carol.davis@email.com',	'+1234567895',	'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',	'active',	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27',	NULL);

DROP VIEW IF EXISTS `customer_order_summary`;
CREATE TABLE `customer_order_summary` (`customer_id` int(11), `customer_name` varchar(101), `email` varchar(100), `total_orders` bigint(21), `total_spent` decimal(32,2), `average_order_value` decimal(14,6), `last_order_date` timestamp, `first_order_date` timestamp);


DROP VIEW IF EXISTS `daily_sales_summary`;
CREATE TABLE `daily_sales_summary` (`sale_date` date, `total_orders` bigint(21), `total_revenue` decimal(32,2), `average_order_value` decimal(14,6), `unique_customers` bigint(21));


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
  `payment_method` enum('cash','card','digital_wallet','gift_card') DEFAULT NULL,
  `order_type` enum('dine_in','takeaway','delivery') DEFAULT 'takeaway',
  `special_instructions` text DEFAULT NULL,
  `estimated_ready_time` datetime DEFAULT NULL,
  `actual_ready_time` datetime DEFAULT NULL,
  `served_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
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
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`served_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `orders` (`id`, `customer_id`, `order_number`, `total_amount`, `tax_amount`, `discount_amount`, `final_amount`, `status`, `payment_status`, `payment_method`, `order_type`, `special_instructions`, `estimated_ready_time`, `actual_ready_time`, `served_by`, `created_at`, `updated_at`) VALUES
(1,	1,	'SB2024001',	18.50,	1.48,	0.00,	19.98,	'pending',	'paid',	'card',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-02 00:57:27',	'2025-09-03 02:16:16'),
(2,	2,	'SB2024002',	12.25,	0.98,	0.00,	13.23,	'ready',	'paid',	'cash',	'dine_in',	NULL,	NULL,	NULL,	NULL,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(3,	3,	'SB2024003',	8.75,	0.70,	0.00,	9.45,	'preparing',	'paid',	'digital_wallet',	'takeaway',	NULL,	NULL,	NULL,	NULL,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(4,	1,	'SB2024004',	15.50,	1.24,	0.00,	16.74,	'pending',	'pending',	NULL,	'delivery',	NULL,	NULL,	NULL,	NULL,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27');

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
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_product_name` (`product_name`),
  KEY `idx_order_items_product_order` (`product_id`,`order_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_sku`, `quantity`, `unit_price`, `total_price`, `size`, `customizations`, `special_notes`, `created_at`) VALUES
(1,	1,	1,	'Pike Place Roast',	NULL,	2,	4.50,	9.00,	'Grande',	NULL,	NULL,	'2025-09-02 00:57:27'),
(2,	1,	5,	'Caramel Macchiato',	NULL,	1,	5.25,	5.25,	'Grande',	NULL,	NULL,	'2025-09-02 00:57:27'),
(3,	1,	16,	'Butter Croissant',	NULL,	1,	2.75,	2.75,	'Regular',	NULL,	NULL,	'2025-09-02 00:57:27'),
(4,	1,	17,	'Blueberry Muffin',	NULL,	1,	2.95,	2.95,	'Regular',	NULL,	NULL,	'2025-09-02 00:57:27'),
(5,	2,	3,	'Cappuccino',	NULL,	1,	4.25,	4.25,	'Grande',	NULL,	NULL,	'2025-09-02 00:57:27'),
(6,	2,	4,	'Caffè Latte',	NULL,	1,	4.75,	4.75,	'Venti',	NULL,	NULL,	'2025-09-02 00:57:27'),
(7,	2,	16,	'Butter Croissant',	NULL,	1,	2.75,	2.75,	'Regular',	NULL,	NULL,	'2025-09-02 00:57:27'),
(8,	3,	6,	'Iced Coffee',	NULL,	1,	3.25,	3.25,	'Grande',	NULL,	NULL,	'2025-09-02 00:57:27'),
(9,	3,	10,	'Caramel Frappuccino',	NULL,	1,	5.50,	5.50,	'Grande',	NULL,	NULL,	'2025-09-02 00:57:27'),
(10,	4,	9,	'Iced Caramel Macchiato',	NULL,	2,	5.00,	10.00,	'Venti',	NULL,	NULL,	'2025-09-02 00:57:27'),
(11,	4,	18,	'Turkey & Swiss Sandwich',	NULL,	1,	6.75,	6.75,	'Regular',	NULL,	NULL,	'2025-09-02 00:57:27');

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
  `stock_quantity` int(11) DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 5,
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
  KEY `idx_stock` (`stock_quantity`),
  FULLTEXT KEY `ft_search` (`name`,`description`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `cost_price`, `image_url`, `sku`, `stock_quantity`, `min_stock_level`, `calories`, `ingredients`, `allergens`, `size_options`, `customization_options`, `status`, `is_featured`, `sort_order`, `created_at`, `updated_at`) VALUES
(1,	1,	'Pike Place Roast',	'Our signature medium roast coffee',	4.50,	1.50,	NULL,	'HOT001',	100,	5,	5,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(2,	1,	'Caffè Americano',	'Espresso shots topped with hot water',	3.75,	1.25,	NULL,	'HOT002',	100,	5,	15,	NULL,	NULL,	NULL,	NULL,	'active',	0,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(3,	1,	'Cappuccino',	'Espresso with steamed milk and foam',	4.25,	1.40,	NULL,	'HOT003',	100,	5,	120,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(4,	1,	'Caffè Latte',	'Espresso with steamed milk',	4.75,	1.60,	NULL,	'HOT004',	100,	5,	190,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(5,	1,	'Caramel Macchiato',	'Steamed milk with vanilla syrup and caramel drizzle',	5.25,	1.75,	NULL,	'HOT005',	100,	5,	250,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(6,	2,	'Iced Coffee',	'Freshly brewed coffee served over ice',	3.25,	1.10,	NULL,	'COLD001',	100,	5,	5,	NULL,	NULL,	NULL,	NULL,	'active',	0,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(7,	2,	'Cold Brew Coffee',	'Slow-steeped, smooth cold coffee',	3.75,	1.25,	NULL,	'COLD002',	100,	5,	5,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(8,	2,	'Iced Caffè Americano',	'Espresso shots with cold water over ice',	3.50,	1.15,	NULL,	'COLD003',	100,	5,	15,	NULL,	NULL,	NULL,	NULL,	'active',	0,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(9,	2,	'Iced Caramel Macchiato',	'Vanilla syrup, milk, espresso, and caramel over ice',	5.00,	1.70,	NULL,	'COLD004',	100,	5,	250,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(10,	3,	'Caramel Frappuccino',	'Coffee blended with caramel syrup and ice',	5.50,	1.85,	NULL,	'FRAP001',	100,	5,	370,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(11,	3,	'Mocha Frappuccino',	'Coffee and chocolate blended with ice',	5.25,	1.75,	NULL,	'FRAP002',	100,	5,	350,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(12,	3,	'Vanilla Bean Frappuccino',	'Vanilla bean powder blended with ice',	4.75,	1.60,	NULL,	'FRAP003',	100,	5,	280,	NULL,	NULL,	NULL,	NULL,	'active',	0,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(13,	4,	'Earl Grey Tea',	'Classic bergamot-flavored black tea',	2.50,	0.85,	NULL,	'TEA001',	100,	5,	0,	NULL,	NULL,	NULL,	NULL,	'active',	0,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(14,	4,	'Green Tea',	'Premium jasmine green tea',	2.25,	0.75,	NULL,	'TEA002',	100,	5,	0,	NULL,	NULL,	NULL,	NULL,	'active',	0,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(15,	4,	'Chai Tea Latte',	'Spiced black tea with steamed milk',	4.25,	1.40,	NULL,	'TEA003',	100,	5,	240,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(16,	8,	'Butter Croissant',	'Flaky, buttery French croissant',	2.75,	1.00,	NULL,	'FOOD001',	50,	5,	280,	NULL,	NULL,	NULL,	NULL,	'active',	0,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(17,	8,	'Blueberry Muffin',	'Fresh blueberries in vanilla muffin',	2.95,	1.10,	NULL,	'FOOD002',	30,	5,	350,	NULL,	NULL,	NULL,	NULL,	'active',	1,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27'),
(18,	8,	'Turkey & Swiss Sandwich',	'Sliced turkey with Swiss cheese',	6.75,	2.50,	NULL,	'FOOD003',	25,	5,	520,	NULL,	NULL,	NULL,	NULL,	'active',	0,	0,	'2025-09-02 00:57:27',	'2025-09-02 00:57:27');

DROP VIEW IF EXISTS `product_sales_summary`;
CREATE TABLE `product_sales_summary` (`product_id` int(11), `product_name` varchar(100), `category_name` varchar(100), `total_quantity_sold` decimal(32,0), `total_revenue` decimal(32,2), `total_orders` bigint(21), `average_price` decimal(14,6));


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

DROP TABLE IF EXISTS `customer_order_summary`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `customer_order_summary` AS select `c`.`id` AS `customer_id`,concat(`c`.`first_name`,' ',`c`.`last_name`) AS `customer_name`,`c`.`email` AS `email`,count(`o`.`id`) AS `total_orders`,sum(`o`.`final_amount`) AS `total_spent`,avg(`o`.`final_amount`) AS `average_order_value`,max(`o`.`created_at`) AS `last_order_date`,min(`o`.`created_at`) AS `first_order_date` from (`customers` `c` left join `orders` `o` on(`c`.`id` = `o`.`customer_id` and `o`.`status` = 'completed')) group by `c`.`id`,concat(`c`.`first_name`,' ',`c`.`last_name`),`c`.`email` order by sum(`o`.`final_amount`) desc;

DROP TABLE IF EXISTS `daily_sales_summary`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `daily_sales_summary` AS select cast(`o`.`created_at` as date) AS `sale_date`,count(`o`.`id`) AS `total_orders`,sum(`o`.`final_amount`) AS `total_revenue`,avg(`o`.`final_amount`) AS `average_order_value`,count(distinct `o`.`customer_id`) AS `unique_customers` from `orders` `o` where `o`.`status` = 'completed' group by cast(`o`.`created_at` as date) order by cast(`o`.`created_at` as date) desc;

DROP TABLE IF EXISTS `product_sales_summary`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `product_sales_summary` AS select `p`.`id` AS `product_id`,`p`.`name` AS `product_name`,`c`.`name` AS `category_name`,sum(`oi`.`quantity`) AS `total_quantity_sold`,sum(`oi`.`total_price`) AS `total_revenue`,count(distinct `oi`.`order_id`) AS `total_orders`,avg(`oi`.`unit_price`) AS `average_price` from (((`products` `p` join `order_items` `oi` on(`p`.`id` = `oi`.`product_id`)) join `orders` `o` on(`oi`.`order_id` = `o`.`id`)) join `categories` `c` on(`p`.`category_id` = `c`.`id`)) where `o`.`status` = 'completed' group by `p`.`id`,`p`.`name`,`c`.`name` order by sum(`oi`.`quantity`) desc;

-- 2025-09-03 02:17:00