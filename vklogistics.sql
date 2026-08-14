-- ============================================================
-- VK LOGISTICS - GANESH STATUE BOOKING DATABASE SCHEMA
-- Website: UK Ganesh Chaturthi Booking Platform
-- Currency: GBP (£)
-- Consolidated & Ported from SQLite
-- ============================================================

CREATE DATABASE IF NOT EXISTS `vk_logistics` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vk_logistics`;

-- ------------------------------------------------------------
-- Table: settings
-- Purpose: Store dynamic product price, shipping fee, bank details & support info
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `description` VARCHAR(255) NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Settings Data with latest SQLite modifications
INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'product_name', 'Ganesh Statue / Vinayaka Vigraha', 'Name of the festival product', '2026-08-05 07:06:40'),
(2, 'unit_price', '14.99', 'Base unit price per statue in GBP (£)', '2026-08-05 07:06:40'),
(3, 'shipping_charge', '4.99', 'Flat shipping fee within United Kingdom in GBP (£)', '2026-08-05 07:06:40'),
(4, 'currency_symbol', '£', 'Display currency symbol', '2026-08-05 07:06:40'),
(5, 'currency_code', 'GBP', 'Standard ISO currency code', '2026-08-05 07:06:40'),
(6, 'service_area', 'United Kingdom', 'Restricted delivery region', '2026-08-05 07:06:40'),
(7, 'bank_account_name', 'VK LOGISTICS LTD', 'Bank account holder name for direct transfers', '2026-08-05 07:06:40'),
(8, 'bank_name', 'Barclays Bank UK', 'Bank name for customer transfers', '2026-08-05 07:06:40'),
(9, 'bank_sort_code', '20-45-77', 'UK Bank Sort Code', '2026-08-05 07:06:40'),
(10, 'bank_account_number', '83920144', 'UK Bank Account Number', '2026-08-05 07:06:40'),
(11, 'paypal_client_id', 'sb', 'PayPal SDK Client ID — replace with your Live Client ID from developer.paypal.com', '2026-08-05 07:06:40'),
(12, 'paypal_mode', 'sandbox', 'PayPal Mode: sandbox or live — change to live once you have live credentials', '2026-08-05 07:06:40'),
(13, 'paypal_client_secret', '', 'PayPal Live Client Secret — replace with your Live Secret from developer.paypal.com', '2026-08-05 07:06:40'),
(14, 'support_phone', '+44 7700 900888', 'UK Support Contact Line', '2026-08-05 07:06:40'),
(15, 'support_email', 'bappa@vklogistics.co.uk', 'Support Email Address', '2026-08-05 07:06:40'),
(16, 'website_status', 'active', 'Website status: active or maintenance', '2026-08-05 07:06:40'),
(25, 'admin_password', 'admin123', 'Admin access password', '2026-08-05 10:59:44'),
(52, 'paypal_email', 'premmoparthi0831@gmail.com', 'PayPal Merchant Email', '2026-08-05 11:49:06'),
(66, 'paypal_account_name', 'VK LOGISTICS LTD', 'PayPal Account Holder Name', '2026-08-05 12:14:52'),
(67, 'paypal_id', 'premmoparthi@paypal', 'PayPal ID for payments', '2026-08-05 12:14:52');

-- ------------------------------------------------------------
-- Table: bookings
-- Purpose: Store customer Ganesh Statue booking & payment records
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `booking_reference` VARCHAR(30) NOT NULL UNIQUE,
  `customer_name` VARCHAR(150) NOT NULL,
  `mobile` VARCHAR(30) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `address_line_1` VARCHAR(255) NOT NULL,
  `address_line_2` VARCHAR(255) NULL,
  `city` VARCHAR(100) NOT NULL,
  `county` VARCHAR(100) NULL,
  `postcode` VARCHAR(20) NOT NULL,
  `country` VARCHAR(50) NOT NULL DEFAULT 'United Kingdom',
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 14.99,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `shipping_charge` DECIMAL(10,2) NOT NULL DEFAULT 3.99,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('paypal', 'bank_transfer') NOT NULL,
  `payment_reference` VARCHAR(100) NULL COMMENT 'Bank Transfer User Reference or Txn Ref',
  `payment_proof_image` VARCHAR(255) NULL COMMENT 'Uploaded Payment Receipt Image File Path',
  `paypal_order_id` VARCHAR(100) NULL COMMENT 'PayPal SDK Order ID',
  `paypal_transaction_id` VARCHAR(100) NULL COMMENT 'PayPal Capture / Txn ID',
  `payment_status` ENUM('PAID', 'PAYMENT VERIFICATION PENDING', 'FAILED', 'CANCELLED') NOT NULL DEFAULT 'PAYMENT VERIFICATION PENDING',
  `booking_status` ENUM('CONFIRMED', 'PROCESSING', 'SHIPPED', 'DELIVERED', 'CANCELLED') NOT NULL DEFAULT 'CONFIRMED',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_booking_ref` (`booking_reference`),
  INDEX `idx_customer_email` (`email`),
  INDEX `idx_customer_mobile` (`mobile`),
  INDEX `idx_payment_status` (`payment_status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate Bookings Data
INSERT INTO `bookings` (
  `id`, `booking_reference`, `customer_name`, `mobile`, `email`,
  `address_line_1`, `address_line_2`, `city`, `county`, `postcode`, `country`,
  `quantity`, `unit_price`, `subtotal`, `shipping_charge`, `total_amount`,
  `payment_method`, `payment_reference`, `payment_proof_image`,
  `paypal_order_id`, `paypal_transaction_id`,
  `payment_status`, `booking_status`, `created_at`, `updated_at`
) VALUES
(1, 'VKG-2026-542061', 'Test Devotion User', '+447700900123', 'bappa@example.co.uk', '10 Downing Street', '', 'London', '', 'SW1A 1AA', 'United Kingdom', 2, 14.99, 29.98, 3.99, 33.97, 'bank_transfer', 'BARC-REF-998822', 'uploads/payment_receipts/sample_hd_receipt.svg', NULL, NULL, 'PAID', 'DELIVERED', '2026-08-05 07:06:57', '2026-08-05 07:06:57'),
(2, 'VKG-2026-1E4966', 'Ramesh Kumar', '+447700900111', 'ramesh@example.co.uk', '10 Downing Street', '', 'London', '', 'SW1A 1AA', 'United Kingdom', 2, 14.99, 29.98, 3.99, 33.97, 'bank_transfer', 'BANK-TXN-998877', 'uploads/payment_receipts/sample_hd_receipt.svg', NULL, NULL, 'PAYMENT VERIFICATION PENDING', 'CONFIRMED', '2026-08-05 08:21:20', '2026-08-05 08:21:20'),
(3, 'VKG-2026-52F7EF', 'Suresh Reddy', '+447700900222', 'suresh@example.co.uk', '22 Baker Street', '', 'London', '', 'NW1 6XE', 'United Kingdom', 1, 14.99, 14.99, 3.99, 18.98, 'bank_transfer', 'BANK-TXN-776655', 'uploads/payment_receipts/sample_hd_receipt.svg', NULL, NULL, 'PAYMENT VERIFICATION PENDING', 'CONFIRMED', '2026-08-05 08:22:11', '2026-08-05 08:22:11'),
(4, 'VKG-2026-5C52E5', 'Prem Moparthi', '+447123456789', 'premmoparthi0831@gmail.com', 'test', 'test', 'duhqiudg', 'wqdqefdcqe', 'SW1A 1AA', 'United Kingdom', 1, 14.99, 14.99, 3.99, 18.98, 'bank_transfer', 'BARC-REF-998822', 'uploads/payment_receipts/receipt_VKG-2026-5C52E5_1785921450_b72c7b.jpeg', NULL, NULL, 'PAYMENT VERIFICATION PENDING', 'CONFIRMED', '2026-08-05 09:17:30', '2026-08-05 09:17:30'),
(5, 'VKG-2026-292E8F', 'PayPal Customer Test', '+447700900555', 'paypal-test@vklogistics.co.uk', '10 Downing Street', 'London', 'London', 'London', 'SW1A 2AA', 'United Kingdom', 2, 14.99, 29.98, 4.99, 34.97, 'paypal', 'PAYID-MOCK9999', NULL, NULL, NULL, 'PAYMENT VERIFICATION PENDING', 'CONFIRMED', '2026-08-05 11:43:52', '2026-08-05 11:43:52'),
(6, 'VKG-2026-BF675F', 'Validation User 0', '+447700900888', 'valid-0@vklogistics.co.uk', '10 Downing Street', '', 'London', '', 'SW1A 2AA', 'United Kingdom', 1, 14.99, 14.99, 4.99, 19.98, 'bank_transfer', 'REF123', NULL, NULL, NULL, 'PAYMENT VERIFICATION PENDING', 'CONFIRMED', '2026-08-05 12:06:33', '2026-08-05 12:06:33'),
(7, 'VKG-2026-41A8E5', 'Validation User 1', '+447700900888', 'valid-1@vklogistics.co.uk', '10 Downing Street', '', 'London', '', 'SW1A 2AA', 'United Kingdom', 1, 14.99, 14.99, 4.99, 19.98, 'bank_transfer', 'REF123', NULL, NULL, NULL, 'PAYMENT VERIFICATION PENDING', 'CONFIRMED', '2026-08-05 12:06:33', '2026-08-05 12:06:33'),
(8, 'VKG-2026-DF7E62', 'Validation User 2', '+447700900888', 'valid-2@vklogistics.co.uk', '10 Downing Street', '', 'London', '', 'SW1A 2AA', 'United Kingdom', 1, 14.99, 14.99, 4.99, 19.98, 'bank_transfer', 'REF123', NULL, NULL, NULL, 'PAYMENT VERIFICATION PENDING', 'CONFIRMED', '2026-08-05 12:06:33', '2026-08-05 12:06:33'),
(9, 'VKG-2026-5E1B42', 'Prem Moparthi', '+447700900777', 'premmoparthi0831@gmail.com', 'test', 'test', 'Gannavaram', 'wqdqefdcqe', 'SW1A 1AA', 'United Kingdom', 1, 14.99, 14.99, 4.99, 19.98, 'paypal', '78876253564256', 'uploads/payment_receipts/receipt_VKG-2026-5E1B42_1785931666_1e45b2.jpeg', NULL, NULL, 'PAYMENT VERIFICATION PENDING', 'CONFIRMED', '2026-08-05 12:07:46', '2026-08-05 12:07:46'),
(10, 'VKG-2026-05905F', 'Prem Moparthi', '+447700900777', 'premmoparthi0831@gmail.com', 'test', 'test', 'Gannavaram', 'wqdqefdcqe', 'SW1A 1AA', 'United Kingdom', 1, 14.99, 14.99, 4.99, 19.98, 'paypal', '565656565', 'uploads/payment_receipts/receipt_VKG-2026-05905F_1785932469_b9d2b1.jpeg', NULL, NULL, 'PAYMENT VERIFICATION PENDING', 'CONFIRMED', '2026-08-05 12:21:09', '2026-08-05 12:21:09');

-- Set auto-increment offset for future bookings to start at 11
ALTER TABLE `bookings` AUTO_INCREMENT = 11;

-- ------------------------------------------------------------
-- Table: booking_sequence
-- Purpose: Deterministic reference numbering (VKG-YYYY-XXXXXX)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `booking_sequence`;
CREATE TABLE `booking_sequence` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed the sequence table so the next ID is 11
INSERT INTO `booking_sequence` (`id`, `created_at`) VALUES
(1, '2026-08-05 07:06:57'),
(2, '2026-08-05 08:21:20'),
(3, '2026-08-05 08:22:11'),
(4, '2026-08-05 09:17:30'),
(5, '2026-08-05 11:43:52'),
(6, '2026-08-05 12:06:33'),
(7, '2026-08-05 12:06:33'),
(8, '2026-08-05 12:06:33'),
(9, '2026-08-05 12:07:46'),
(10, '2026-08-05 12:21:09');

ALTER TABLE `booking_sequence` AUTO_INCREMENT = 11;

-- ============================================================
-- SCHEMA ADDITIONS: CATEGORIES, PRODUCTS & BOOKING ITEMS
-- (Previously in schema_migration.sql — now merged here)
-- ============================================================

-- ------------------------------------------------------------
-- Table: categories
-- Purpose: Product category grouping (Ganesh Statues, Rakhi, etc.)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `image_path` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: products
-- Purpose: Individual products tied to a category
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 14.99,
  `image_path` VARCHAR(255) NULL,
  `image_path_2` VARCHAR(255) NULL,
  `image_path_3` VARCHAR(255) NULL,
  `gallery_images` LONGTEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: booking_items
-- Purpose: Line items linking bookings to specific products
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `booking_items` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` BIGINT NOT NULL,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed initial categories
INSERT INTO `categories` (`id`, `name`, `description`, `image_path`) VALUES
(1, 'Ganesh Statue', 'Handcrafted eco-friendly clay Ganesh statues with complete Mukut & ornament accessories delivered across the UK.', 'assets/images/ganesh_hero.png')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`), `image_path` = VALUES(`image_path`);

INSERT INTO `categories` (`id`, `name`, `description`, `image_path`) VALUES
(2, 'Rakhi', 'Designer Rudraksha & Silver-Plated Peacock Rakhi sets handcrafted for festive celebrations.', 'assets/images/rakhi_peacock.png')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`), `image_path` = VALUES(`image_path`);

-- Seed initial products (with 3 photos each)
INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image_path`, `image_path_2`, `image_path_3`) VALUES
(1, 1, 'Ganesh Statue / Vinayaka Vigraha', 'Handcrafted eco-friendly clay Ganesh statue with complete Mukut & ornaments kit.', 14.99, 'assets/images/ganesh_hero.png', 'assets/images/ganesh_product_2.png', 'assets/images/ganesh_product_3.png')
ON DUPLICATE KEY UPDATE `category_id` = VALUES(`category_id`), `name` = VALUES(`name`), `description` = VALUES(`description`), `price` = VALUES(`price`), `image_path` = VALUES(`image_path`), `image_path_2` = VALUES(`image_path_2`), `image_path_3` = VALUES(`image_path_3`);

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image_path`, `image_path_2`, `image_path_3`) VALUES
(2, 1, 'Premium Golden Ganesh Idol', 'Exquisite golden-painted eco-friendly clay idol with velvet base.', 24.99, 'assets/images/ganesh_product_2.png', 'assets/images/ganesh_hero.png', 'assets/images/ganesh_product_4.png')
ON DUPLICATE KEY UPDATE `category_id` = VALUES(`category_id`), `name` = VALUES(`name`), `description` = VALUES(`description`), `price` = VALUES(`price`), `image_path` = VALUES(`image_path`), `image_path_2` = VALUES(`image_path_2`), `image_path_3` = VALUES(`image_path_3`);

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image_path`, `image_path_2`, `image_path_3`) VALUES
(3, 2, 'Designer Rudraksha Rakhi', 'Beautifully crafted pure Rudraksha Rakhi with gold-plated beads.', 4.99, 'assets/images/rakhi_rudraksha.png', 'assets/images/rakhi_peacock.png', 'assets/images/prod_1786450092_4a247638.png')
ON DUPLICATE KEY UPDATE `category_id` = VALUES(`category_id`), `name` = VALUES(`name`), `description` = VALUES(`description`), `price` = VALUES(`price`), `image_path` = VALUES(`image_path`), `image_path_2` = VALUES(`image_path_2`), `image_path_3` = VALUES(`image_path_3`);

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image_path`, `image_path_2`, `image_path_3`) VALUES
(4, 2, 'Silver Plated Peacock Rakhi', 'Elegant silver-plated peacock designer Rakhi with premium thread.', 6.99, 'assets/images/rakhi_peacock.png', 'assets/images/rakhi_rudraksha.png', 'assets/images/prod_1786450274_3733079c.png')
ON DUPLICATE KEY UPDATE `category_id` = VALUES(`category_id`), `name` = VALUES(`name`), `description` = VALUES(`description`), `price` = VALUES(`price`), `image_path` = VALUES(`image_path`), `image_path_2` = VALUES(`image_path_2`), `image_path_3` = VALUES(`image_path_3`);

-- Migrate existing bookings to booking_items (backward compatibility)
INSERT INTO `booking_items` (`booking_id`, `product_id`, `product_name`, `quantity`, `price`)
SELECT `id`, 1, 'Ganesh Statue / Vinayaka Vigraha', `quantity`, `unit_price` FROM `bookings`
WHERE `id` NOT IN (SELECT DISTINCT `booking_id` FROM `booking_items`);
