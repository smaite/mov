-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2025 at 05:35 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `sasto_hub`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateProductPerformance` (IN `product_id_param` INT, IN `date_param` DATE)   BEGIN
    DECLARE vendor_id_val INT;
    
    SELECT vendor_id INTO vendor_id_val FROM products WHERE id = product_id_param;
    
    INSERT IGNORE INTO product_performance (vendor_id, product_id, date)
    VALUES (vendor_id_val, product_id_param, date_param);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateVendorDailyAnalytics` (IN `vendor_id_param` INT, IN `date_param` DATE)   BEGIN
    DECLARE total_orders INT DEFAULT 0;
    DECLARE total_revenue DECIMAL(12,2) DEFAULT 0.00;
    DECLARE unique_customers INT DEFAULT 0;
    
    -- Calculate daily metrics
    SELECT COUNT(DISTINCT oi.order_id), COALESCE(SUM(oi.total), 0)
    INTO total_orders, total_revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.vendor_id = vendor_id_param 
    AND DATE(o.created_at) = date_param
    AND o.payment_status = 'paid';
    
    -- Insert or update analytics
    INSERT INTO vendor_analytics (vendor_id, date, orders_count, revenue)
    VALUES (vendor_id_param, date_param, total_orders, total_revenue)
    ON DUPLICATE KEY UPDATE
        orders_count = total_orders,
        revenue = total_revenue,
        updated_at = CURRENT_TIMESTAMP;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `bulk_operations`
--

CREATE TABLE `bulk_operations` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `operation_type` enum('product_import','product_export','price_update','stock_update','status_update') NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `total_records` int(11) DEFAULT 0,
  `processed_records` int(11) DEFAULT 0,
  `success_records` int(11) DEFAULT 0,
  `failed_records` int(11) DEFAULT 0,
  `status` enum('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
  `error_log` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`, `parent_id`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'GTH', 'gps', '', NULL, NULL, 1, 1, '2025-11-17 16:28:27');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(8,2) NOT NULL,
  `minimum_amount` decimal(10,2) DEFAULT 0.00,
  `maximum_amount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hero_sections`
--

CREATE TABLE `hero_sections` (
  `id` int(11) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 1,
  `title` varchar(200) NOT NULL,
  `subtitle` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `button_link` varchar(500) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `background_color` varchar(20) DEFAULT '#ff6b35',
  `text_color` varchar(20) DEFAULT '#ffffff',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `shipping_amount` decimal(8,2) DEFAULT 0.00,
  `tax_amount` decimal(8,2) DEFAULT 0.00,
  `discount_amount` decimal(8,2) DEFAULT 0.00,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `shipping_address` text NOT NULL,
  `billing_address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tracking_number` varchar(100) DEFAULT NULL,
  `estimated_delivery_date` date DEFAULT NULL,
  `delivery_instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `slug` varchar(220) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `sku` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `tags` text DEFAULT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 5,
  `weight` decimal(8,2) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `status` enum('pending','active','inactive','rejected') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `total_sales` int(11) DEFAULT 0,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `seo_keywords` text DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `is_digital` tinyint(1) DEFAULT 0,
  `download_file` varchar(255) DEFAULT NULL,
  `license_key` varchar(100) DEFAULT NULL,
  `warranty_period` int(11) DEFAULT 0,
  `return_policy` text DEFAULT NULL,
  `shipping_class` enum('standard','express','overnight','digital') DEFAULT 'standard',
  `bulk_discount_min_qty` int(11) DEFAULT 0,
  `bulk_discount_percentage` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `vendor_id`, `category_id`, `name`, `brand`, `slug`, `description`, `short_description`, `sku`, `price`, `tags`, `sale_price`, `stock_quantity`, `min_stock_level`, `weight`, `dimensions`, `status`, `rejection_reason`, `featured`, `rating`, `total_reviews`, `total_sales`, `meta_title`, `meta_description`, `created_at`, `updated_at`, `seo_keywords`, `video_url`, `is_digital`, `download_file`, `license_key`, `warranty_period`, `return_policy`, `shipping_class`, `bulk_discount_min_qty`, `bulk_discount_percentage`) VALUES
(1, 1, 1, 'imou1231', 'SINTECH', '', 's', 's', 'SKU-1753980691763', 12.00, '12', 12.00, 12, 5, 12.00, '12', 'pending', NULL, 0, 0.00, 0, 0, NULL, NULL, '2025-11-17 11:48:57', '2025-11-17 16:33:57', NULL, NULL, 0, NULL, NULL, 0, NULL, 'standard', 0, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `product_attributes`
--

CREATE TABLE `product_attributes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `attribute_name` varchar(100) NOT NULL,
  `attribute_value` text NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `alt_text` varchar(200) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `alt_text`, `sort_order`, `is_primary`, `created_at`) VALUES
(1, 1, 'uploads/products/691b4e75210fa_1763397237.jpg', NULL, 1, 1, '2025-11-17 16:33:57');

-- --------------------------------------------------------

--
-- Table structure for table `product_performance`
--

CREATE TABLE `product_performance` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `views` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `orders` int(11) DEFAULT 0,
  `revenue` decimal(10,2) DEFAULT 0.00,
  `conversion_rate` decimal(5,2) DEFAULT 0.00,
  `bounce_rate` decimal(5,2) DEFAULT 0.00,
  `avg_time_on_page` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `title` varchar(200) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `helpful_count` int(11) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `vendor_response` text DEFAULT NULL,
  `vendor_response_date` timestamp NULL DEFAULT NULL,
  `vendor_response_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `movement_type` enum('purchase','sale','adjustment','return','damaged','expired') NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `previous_stock` int(11) NOT NULL,
  `new_stock` int(11) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `user_type` enum('customer','vendor','admin') DEFAULT 'customer',
  `status` enum('pending','active','inactive','rejected') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `phone`, `address`, `city`, `country`, `user_type`, `status`, `rejection_reason`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@sastohub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', '+977-9800000000', 'Kathmandu', 'Kathmandu', 'Nepal', 'admin', 'active', NULL, NULL, '2025-09-29 12:39:55', '2025-09-29 12:39:55'),
(2, 'customer1', 'customer@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', '+977-9841234567', 'Thamel, Kathmandu', 'Kathmandu', 'Nepal', 'customer', 'active', NULL, NULL, '2025-09-29 12:39:55', '2025-09-29 12:39:55'),
(4, 'vendor2', 'vendor2@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sita', 'Patel', '+977-9861234567', 'Lalitpur', 'Lalitpur', 'Nepal', 'vendor', 'active', NULL, NULL, '2025-09-29 12:39:55', '2025-09-29 12:39:55'),
(5, 'customer2', 'jane@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', '+977-9871234567', 'Pokhara', 'Pokhara', 'Nepal', 'customer', 'active', NULL, NULL, '2025-09-29 12:39:55', '2025-09-29 12:39:55'),
(6, 'vendor3', 'newvendor@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maya', 'Gurung', '+977-9876543210', 'Chitwan', 'Chitwan', 'Nepal', 'vendor', 'active', NULL, NULL, '2025-09-29 12:39:55', '2025-09-29 12:42:56'),
(7, '', 'glorioustradehub@gmail.com', '$2y$10$uTzLhevq1L0HWPqOPL9AmuWiwECAiUhYeUZINr4ERqdoOG.JVFLAS', 'Muhammad', 'Omar', NULL, NULL, NULL, NULL, 'vendor', 'active', NULL, NULL, '2025-10-03 12:10:44', '2025-10-03 12:12:25'),
(12, 'vendor', 'vendor@test.com', '$2y$10$3NbYXJBu8husx8NxCSq3du7AqkYmGDceJg2./kpN7I6bRVQw5r2RK', 'Muhammad', 'Omar', NULL, NULL, NULL, NULL, 'vendor', 'active', NULL, NULL, '2025-11-17 11:41:50', '2025-11-17 16:27:24');

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shop_name` varchar(100) NOT NULL,
  `shop_description` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `shop_logo` varchar(255) DEFAULT NULL,
  `shop_banner` varchar(255) DEFAULT NULL,
  `business_license` varchar(100) DEFAULT NULL,
  `business_license_file` varchar(255) DEFAULT NULL,
  `citizenship_file` varchar(255) DEFAULT NULL,
  `pan_card_file` varchar(255) DEFAULT NULL,
  `other_documents` varchar(500) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 10.00,
  `total_sales` decimal(12,2) DEFAULT 0.00,
  `rating` decimal(3,2) DEFAULT 0.00,
  `application_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `user_id`, `shop_name`, `shop_description`, `phone`, `address`, `shop_logo`, `shop_banner`, `business_license`, `business_license_file`, `citizenship_file`, `pan_card_file`, `other_documents`, `commission_rate`, `total_sales`, `rating`, `application_date`, `created_at`, `updated_at`) VALUES
(1, 12, 'Glorious Trade HuB', '', '+9779807540026', 'nepal', NULL, NULL, 'Glorious trade hub', NULL, NULL, NULL, NULL, 10.00, 0.00, 0.00, '2025-11-17 16:26:50', '2025-11-17 11:41:50', '2025-11-17 16:26:50');

-- --------------------------------------------------------

--
-- Table structure for table `vendor_activity_logs`
--

CREATE TABLE `vendor_activity_logs` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('login','logout','product_create','product_update','product_delete','order_update','profile_update','settings_change') NOT NULL,
  `activity_description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_analytics`
--

CREATE TABLE `vendor_analytics` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `total_views` int(11) DEFAULT 0,
  `product_views` int(11) DEFAULT 0,
  `profile_views` int(11) DEFAULT 0,
  `orders_count` int(11) DEFAULT 0,
  `revenue` decimal(12,2) DEFAULT 0.00,
  `unique_visitors` int(11) DEFAULT 0,
  `conversion_rate` decimal(5,2) DEFAULT 0.00,
  `avg_order_value` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_api_tokens`
--

CREATE TABLE `vendor_api_tokens` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `token_name` varchar(100) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `last_used_at` timestamp NULL DEFAULT NULL,
  `last_used_ip` varchar(45) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_inventory`
--

CREATE TABLE `vendor_inventory` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `reserved_stock` int(11) DEFAULT 0,
  `available_stock` int(11) GENERATED ALWAYS AS (`current_stock` - `reserved_stock`) STORED,
  `reorder_point` int(11) DEFAULT 0,
  `reorder_quantity` int(11) DEFAULT 0,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `supplier_info` text DEFAULT NULL,
  `last_restocked_at` timestamp NULL DEFAULT NULL,
  `next_restock_date` date DEFAULT NULL,
  `stock_alerts_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_notifications`
--

CREATE TABLE `vendor_notifications` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `notification_type` enum('order','stock','review','payment','system','promotion','security') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_performance`
--

CREATE TABLE `vendor_performance` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `month_year` varchar(7) NOT NULL,
  `total_products` int(11) DEFAULT 0,
  `active_products` int(11) DEFAULT 0,
  `total_orders` int(11) DEFAULT 0,
  `total_revenue` decimal(12,2) DEFAULT 0.00,
  `avg_order_value` decimal(10,2) DEFAULT 0.00,
  `customer_satisfaction` decimal(3,2) DEFAULT 0.00,
  `response_time_hours` decimal(5,2) DEFAULT 0.00,
  `return_rate` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_settings`
--

CREATE TABLE `vendor_settings` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`setting_value`)),
  `category` enum('dashboard','notifications','security','integrations','appearance') DEFAULT 'dashboard',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_subscriptions`
--

CREATE TABLE `vendor_subscriptions` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `plan_type` enum('basic','premium','enterprise') DEFAULT 'basic',
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `billing_cycle` enum('monthly','yearly') DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `auto_renew` tinyint(1) DEFAULT 1,
  `status` enum('active','expired','cancelled','suspended') DEFAULT 'active',
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bulk_operations`
--
ALTER TABLE `bulk_operations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_vendor_status` (`vendor_id`,`status`),
  ADD KEY `idx_operation_type` (`operation_type`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `hero_sections`
--
ALTER TABLE `hero_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_position_active` (`position`,`is_active`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_read` (`user_id`,`is_read`,`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_orders_status_date` (`status`,`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_order_items_vendor_date` (`vendor_id`,`created_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_products_vendor_status` (`vendor_id`,`status`,`created_at`);

--
-- Indexes for table `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_performance`
--
ALTER TABLE `product_performance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_date` (`product_id`,`date`),
  ADD KEY `idx_vendor_date` (`vendor_id`,`date`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product_order` (`user_id`,`product_id`,`order_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `vendor_response_by` (`vendor_response_by`),
  ADD KEY `idx_reviews_product_status` (`product_id`,`status`,`created_at`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_vendor_product_date` (`vendor_id`,`product_id`,`created_at`),
  ADD KEY `idx_movement_type` (`movement_type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_vendors_user_status` (`user_id`,`application_date`);

--
-- Indexes for table `vendor_activity_logs`
--
ALTER TABLE `vendor_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_vendor_activity` (`vendor_id`,`created_at`),
  ADD KEY `idx_activity_type` (`activity_type`);

--
-- Indexes for table `vendor_analytics`
--
ALTER TABLE `vendor_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vendor_date` (`vendor_id`,`date`);

--
-- Indexes for table `vendor_api_tokens`
--
ALTER TABLE `vendor_api_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token_hash` (`token_hash`),
  ADD KEY `idx_vendor_active` (`vendor_id`,`is_active`);

--
-- Indexes for table `vendor_inventory`
--
ALTER TABLE `vendor_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vendor_product` (`vendor_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `vendor_notifications`
--
ALTER TABLE `vendor_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_unread` (`vendor_id`,`is_read`,`created_at`),
  ADD KEY `idx_notification_type` (`notification_type`),
  ADD KEY `idx_priority` (`priority`);

--
-- Indexes for table `vendor_performance`
--
ALTER TABLE `vendor_performance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vendor_month` (`vendor_id`,`month_year`),
  ADD KEY `idx_month_year` (`month_year`);

--
-- Indexes for table `vendor_settings`
--
ALTER TABLE `vendor_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vendor_setting` (`vendor_id`,`setting_key`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `vendor_subscriptions`
--
ALTER TABLE `vendor_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_status` (`vendor_id`,`status`),
  ADD KEY `idx_end_date` (`end_date`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bulk_operations`
--
ALTER TABLE `bulk_operations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hero_sections`
--
ALTER TABLE `hero_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_attributes`
--
ALTER TABLE `product_attributes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `product_performance`
--
ALTER TABLE `product_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vendor_activity_logs`
--
ALTER TABLE `vendor_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_analytics`
--
ALTER TABLE `vendor_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_api_tokens`
--
ALTER TABLE `vendor_api_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_inventory`
--
ALTER TABLE `vendor_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_notifications`
--
ALTER TABLE `vendor_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_performance`
--
ALTER TABLE `vendor_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_settings`
--
ALTER TABLE `vendor_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_subscriptions`
--
ALTER TABLE `vendor_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bulk_operations`
--
ALTER TABLE `bulk_operations`
  ADD CONSTRAINT `bulk_operations_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bulk_operations_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD CONSTRAINT `product_attributes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_performance`
--
ALTER TABLE `product_performance`
  ADD CONSTRAINT `product_performance_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_performance_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reviews_ibfk_4` FOREIGN KEY (`vendor_response_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `vendors`
--
ALTER TABLE `vendors`
  ADD CONSTRAINT `vendors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_activity_logs`
--
ALTER TABLE `vendor_activity_logs`
  ADD CONSTRAINT `vendor_activity_logs_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vendor_activity_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_analytics`
--
ALTER TABLE `vendor_analytics`
  ADD CONSTRAINT `vendor_analytics_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_api_tokens`
--
ALTER TABLE `vendor_api_tokens`
  ADD CONSTRAINT `vendor_api_tokens_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_inventory`
--
ALTER TABLE `vendor_inventory`
  ADD CONSTRAINT `vendor_inventory_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vendor_inventory_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_notifications`
--
ALTER TABLE `vendor_notifications`
  ADD CONSTRAINT `vendor_notifications_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_performance`
--
ALTER TABLE `vendor_performance`
  ADD CONSTRAINT `vendor_performance_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_settings`
--
ALTER TABLE `vendor_settings`
  ADD CONSTRAINT `vendor_settings_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_subscriptions`
--
ALTER TABLE `vendor_subscriptions`
  ADD CONSTRAINT `vendor_subscriptions_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;
