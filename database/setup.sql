-- AgriSphere Database Setup Script
-- Run this script in phpMyAdmin to create/update the database

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `cse311 lab project`
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE `cse311 lab project`;

-- =====================================================
-- User Table (Main user accounts)
-- =====================================================
CREATE TABLE IF NOT EXISTS `user` (
    `User_id` INT AUTO_INCREMENT PRIMARY KEY,
    `First_name` VARCHAR(100) NOT NULL,
    `Last_name` VARCHAR(100) NOT NULL,
    `Username` VARCHAR(100) UNIQUE NOT NULL,
    `Password` VARCHAR(255) NOT NULL,
    `User_type` ENUM('Admin', 'Farmer', 'Customer') NOT NULL,
    `Phone` VARCHAR(20),
    `Email` VARCHAR(100) UNIQUE,
    `NID` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Farmer Table (Farmer-specific info)
-- =====================================================
CREATE TABLE IF NOT EXISTS `farmer` (
    `farmer_id` INT PRIMARY KEY,
    `address` TEXT,
    `phone` VARCHAR(20),
    `bank_name` VARCHAR(100),
    `bank_account_number` VARCHAR(50),
    FOREIGN KEY (`farmer_id`) REFERENCES `user`(`User_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Customer Table (Customer-specific info)
-- =====================================================
CREATE TABLE IF NOT EXISTS `customer` (
    `customer_id` INT PRIMARY KEY,
    `address` TEXT,
    `phone` VARCHAR(20),
    FOREIGN KEY (`customer_id`) REFERENCES `user`(`User_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Category Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `category` (
    `category_id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(100) UNIQUE NOT NULL,
    `description` TEXT
) ENGINE=InnoDB;

-- =====================================================
-- Farm Product Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `farm_product` (
    `product_id` INT AUTO_INCREMENT PRIMARY KEY,
    `farmer_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `product_name` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `product_image` VARCHAR(255),
    `unit` VARCHAR(50) NOT NULL,
    `price_per_unit` DECIMAL(10,2) NOT NULL,
    `quantity` INT DEFAULT 0,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `rejection_reason` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`farmer_id`) REFERENCES `user`(`User_id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `category`(`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Add quantity column if it doesn't exist
ALTER TABLE `farm_product` ADD COLUMN IF NOT EXISTS `quantity` INT DEFAULT 0 AFTER `price_per_unit`;

-- =====================================================
-- Farm Inventory Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `farm_inventory` (
    `product_id` INT PRIMARY KEY,
    `quantity_available` INT DEFAULT 0,
    FOREIGN KEY (`product_id`) REFERENCES `farm_product`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Customer Cart Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `customer_cart` (
    `cart_id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `user`(`User_id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `farm_product`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Customer Order Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `customer_order` (
    `order_id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `order_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `status` ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    FOREIGN KEY (`customer_id`) REFERENCES `user`(`User_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Order Items Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `order_items` (
    `item_id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `price_per_unit` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `customer_order`(`order_id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `farm_product`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Payment Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `payment` (
    `payment_id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `bkash_number` VARCHAR(20),
    `payment_status` ENUM('Pending', 'Paid', 'Failed') DEFAULT 'Pending',
    `payment_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `customer_order`(`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Government Prices Table (Reference prices)
-- =====================================================
CREATE TABLE IF NOT EXISTS `government_prices` (
    `product_id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_name` VARCHAR(200) NOT NULL,
    `category_id` INT,
    `price_per_unit` DECIMAL(10,2) NOT NULL,
    `unit` VARCHAR(50) DEFAULT 'kg',
    FOREIGN KEY (`category_id`) REFERENCES `category`(`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Add unit column if it doesn't exist
ALTER TABLE `government_prices` ADD COLUMN IF NOT EXISTS `unit` VARCHAR(50) DEFAULT 'kg' AFTER `price_per_unit`;

-- =====================================================
-- User Block Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `user_block` (
    `user_id` INT PRIMARY KEY,
    `is_blocked` TINYINT(1) DEFAULT 0,
    `blocked_at` DATETIME,
    `reason` TEXT,
    FOREIGN KEY (`user_id`) REFERENCES `user`(`User_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- AI Chat Log Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `ai_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `user_type` VARCHAR(50) NOT NULL,
    `query` TEXT NOT NULL,
    `response` TEXT NOT NULL,
    `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_user_type` (`user_type`),
    INDEX `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB;

-- =====================================================
-- Insert Default Categories (if not exists)
-- =====================================================
INSERT IGNORE INTO `category` (`category_name`, `description`) VALUES
('Vegetables', 'Fresh vegetables from local farms'),
('Fruits', 'Fresh seasonal fruits'),
('Grains', 'Rice, wheat, and other grains'),
('Dairy', 'Milk, cheese, and dairy products'),
('Poultry', 'Eggs, chicken, and poultry products'),
('Fish', 'Fresh fish and seafood'),
('Spices', 'Fresh and dried spices');

-- =====================================================
-- Insert Sample Government Prices (if not exists)
-- =====================================================
INSERT IGNORE INTO `government_prices` (`product_name`, `category_id`, `price_per_unit`, `unit`) VALUES
('Fresh Potato', 1, 40.00, 'kg'),
('Onion', 1, 60.00, 'kg'),
('Tomato', 1, 80.00, 'kg'),
('Green Spinach', 1, 30.00, 'bundle'),
('Cabbage', 1, 50.00, 'kg'),
('Carrot', 1, 70.00, 'kg'),
('Cauliflower', 1, 60.00, 'kg'),
('Banana', 2, 80.00, 'dozen'),
('Mango', 2, 150.00, 'kg'),
('Apple', 2, 250.00, 'kg'),
('Orange', 2, 120.00, 'kg'),
('Miniket Rice', 3, 75.00, 'kg'),
('Basmati Rice', 3, 120.00, 'kg'),
('Wheat', 3, 45.00, 'kg'),
('Milk', 4, 80.00, 'liter'),
('Egg', 5, 12.00, 'piece'),
('Chicken', 5, 220.00, 'kg'),
('Tilapia Fish', 6, 180.00, 'kg'),
('Rohu Fish', 6, 350.00, 'kg');

-- =====================================================
-- Create Trigger to Sync Inventory with Product
-- =====================================================
DELIMITER //

DROP TRIGGER IF EXISTS `after_inventory_update`//
CREATE TRIGGER `after_inventory_update`
AFTER UPDATE ON `farm_inventory`
FOR EACH ROW
BEGIN
    UPDATE `farm_product`
    SET `quantity` = NEW.quantity_available
    WHERE `product_id` = NEW.product_id;
END//

DROP TRIGGER IF EXISTS `after_inventory_insert`//
CREATE TRIGGER `after_inventory_insert`
AFTER INSERT ON `farm_inventory`
FOR EACH ROW
BEGIN
    UPDATE `farm_product`
    SET `quantity` = NEW.quantity_available
    WHERE `product_id` = NEW.product_id;
END//

DELIMITER ;

-- =====================================================
-- Update existing products to sync quantity
-- =====================================================
UPDATE `farm_product` fp
LEFT JOIN `farm_inventory` fi ON fp.product_id = fi.product_id
SET fp.quantity = COALESCE(fi.quantity_available, 0);

-- =====================================================
-- Create Admin User (if not exists)
-- Password: admin123
-- =====================================================
INSERT IGNORE INTO `user` (`User_id`, `First_name`, `Last_name`, `Username`, `Password`, `User_type`, `Email`)
VALUES (1, 'System', 'Admin', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'admin@agrisphere.com');

SELECT 'Database setup completed successfully!' AS Status;
