-- ============================================================
-- SCHEMA ADDITIONS FOR MULTIPLE CATEGORIES, PRODUCTS & CART
-- ============================================================

USE `vk_logistics`;

-- 1. Create categories table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create products table
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 14.99,
  `image_path` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create booking_items table
CREATE TABLE IF NOT EXISTS `booking_items` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` BIGINT NOT NULL,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Seed initial categories
INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Ganesh Statue')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `categories` (`id`, `name`) VALUES
(2, 'Rakhi')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 5. Seed initial products
INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image_path`) VALUES
(1, 1, 'Ganesh Statue / Vinayaka Vigraha', 'Handcrafted eco-friendly clay Ganesh statue with complete Mukut & ornaments kit.', 14.99, 'assets/images/ganesh_hero.png')
ON DUPLICATE KEY UPDATE `category_id` = VALUES(`category_id`), `name` = VALUES(`name`), `description` = VALUES(`description`), `price` = VALUES(`price`), `image_path` = VALUES(`image_path`);

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image_path`) VALUES
(2, 1, 'Premium Golden Ganesh Idol', 'Exquisite golden-painted eco-friendly clay idol with velvet base.', 24.99, 'assets/images/ganesh_product_2.png')
ON DUPLICATE KEY UPDATE `category_id` = VALUES(`category_id`), `name` = VALUES(`name`), `description` = VALUES(`description`), `price` = VALUES(`price`), `image_path` = VALUES(`image_path`);

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image_path`) VALUES
(3, 2, 'Designer Rudraksha Rakhi', 'Beautifully crafted pure Rudraksha Rakhi with gold-plated beads.', 4.99, 'assets/images/rakhi_rudraksha.png')
ON DUPLICATE KEY UPDATE `category_id` = VALUES(`category_id`), `name` = VALUES(`name`), `description` = VALUES(`description`), `price` = VALUES(`price`), `image_path` = VALUES(`image_path`);

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image_path`) VALUES
(4, 2, 'Silver Plated Peacock Rakhi', 'Elegant silver-plated peacock designer Rakhi with premium thread.', 6.99, 'assets/images/rakhi_peacock.png')
ON DUPLICATE KEY UPDATE `category_id` = VALUES(`category_id`), `name` = VALUES(`name`), `description` = VALUES(`description`), `price` = VALUES(`price`), `image_path` = VALUES(`image_path`);

-- 6. Migrate existing bookings to booking_items to ensure backward compatibility
INSERT INTO `booking_items` (`booking_id`, `product_id`, `product_name`, `quantity`, `price`)
SELECT `id`, 1, 'Ganesh Statue / Vinayaka Vigraha', `quantity`, `unit_price` FROM `bookings`
WHERE `id` NOT IN (SELECT DISTINCT `booking_id` FROM `booking_items`);
