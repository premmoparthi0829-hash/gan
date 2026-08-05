-- ============================================================
-- VK LOGISTICS - GANESH STATUE BOOKING DATABASE SCHEMA
-- Website: UK Ganesh Chaturthi Booking Platform
-- Currency: GBP (£)
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

-- Seed Settings Data
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('product_name', 'Ganesh Statue / Vinayaka Vigraha', 'Name of the festival product'),
('unit_price', '14.99', 'Base unit price per statue in GBP (£)'),
('shipping_charge', '3.99', 'Flat shipping fee within United Kingdom in GBP (£)'),
('currency_symbol', '£', 'Display currency symbol'),
('currency_code', 'GBP', 'Standard ISO currency code'),
('service_area', 'United Kingdom', 'Restricted delivery region'),
('bank_account_name', 'VK LOGISTICS LTD', 'Bank account holder name for direct transfers'),
('bank_name', 'Barclays Bank UK', 'Bank name for customer transfers'),
('bank_sort_code', '20-45-77', 'UK Bank Sort Code'),
('bank_account_number', '83920144', 'UK Bank Account Number'),
('paypal_client_id', 'sb', 'PayPal SDK Client ID (Default: sb for Sandbox)'),
('paypal_mode', 'sandbox', 'PayPal Mode: sandbox or live'),
('support_phone', '+44 7700 900888', 'UK Support Contact Line'),
('support_email', 'bappa@vklogistics.co.uk', 'Support Email Address'),
('website_status', 'active', 'Website status: active or maintenance');

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

-- Table sequence tracker for deterministic reference numbering (VKG-YYYY-XXXXXX)
DROP TABLE IF EXISTS `booking_sequence`;
CREATE TABLE `booking_sequence` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
