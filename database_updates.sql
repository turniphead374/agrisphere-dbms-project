-- AgriSphere Database Modifications
-- Run this after importing cse311_lab_project.sql

-- =====================================================
-- 1. Add new columns to farm_product for approval workflow
-- =====================================================

ALTER TABLE `farm_product`
ADD COLUMN IF NOT EXISTS `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER `price_per_unit`,
ADD COLUMN IF NOT EXISTS `rejection_reason` VARCHAR(255) DEFAULT NULL AFTER `status`,
ADD COLUMN IF NOT EXISTS `product_image` VARCHAR(255) DEFAULT NULL AFTER `description`,
ADD COLUMN IF NOT EXISTS `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP AFTER `rejection_reason`;

-- Update existing products to approved status
UPDATE `farm_product` SET `status` = 'approved' WHERE `status` IS NULL OR `status` = '';

-- =====================================================
-- 2. Create government_prices table
-- =====================================================

CREATE TABLE IF NOT EXISTS `government_prices` (
  `price_id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_name` VARCHAR(150) NOT NULL,
  `category_id` INT(11) NOT NULL,
  `price_per_unit` DECIMAL(10,2) NOT NULL,
  `unit` VARCHAR(50) NOT NULL,
  `effective_date` DATE DEFAULT (CURRENT_DATE),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`price_id`),
  KEY `fk_govprice_category` (`category_id`),
  CONSTRAINT `fk_govprice_category` FOREIGN KEY (`category_id`)
    REFERENCES `category` (`category_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed Government Prices (29 products across all categories)
INSERT INTO `government_prices` (`product_name`, `category_id`, `price_per_unit`, `unit`) VALUES
-- Vegetables (category_id: 1)
('Potato', 1, 30.00, 'kg'),
('Tomato', 1, 50.00, 'kg'),
('Onion', 1, 45.00, 'kg'),
('Spinach', 1, 25.00, 'bundle'),
('Cauliflower', 1, 40.00, 'piece'),
('Cabbage', 1, 35.00, 'piece'),
('Carrot', 1, 55.00, 'kg'),
('Brinjal', 1, 40.00, 'kg'),
('Cucumber', 1, 35.00, 'kg'),
('Green Chili', 1, 80.00, 'kg'),
-- Fruits (category_id: 2)
('Mango', 2, 120.00, 'kg'),
('Banana', 2, 60.00, 'dozen'),
('Apple', 2, 200.00, 'kg'),
('Orange', 2, 100.00, 'kg'),
('Papaya', 2, 50.00, 'kg'),
('Guava', 2, 80.00, 'kg'),
-- Grains (category_id: 3)
('Miniket Rice', 3, 68.00, 'kg'),
('Nazirshail Rice', 3, 75.00, 'kg'),
('BR28 Rice', 3, 55.00, 'kg'),
('Wheat', 3, 45.00, 'kg'),
-- Spices (category_id: 4)
('Turmeric', 4, 200.00, 'kg'),
('Chili Powder', 4, 150.00, 'kg'),
('Cumin', 4, 400.00, 'kg'),
('Coriander', 4, 120.00, 'kg'),
-- Dairy (category_id: 5)
('Milk', 5, 70.00, 'liter'),
('Ghee', 5, 800.00, 'kg'),
('Yogurt', 5, 80.00, 'kg'),
-- Fish (category_id: 6)
('Hilsha', 6, 1200.00, 'kg'),
('Rohu', 6, 300.00, 'kg'),
('Tilapia', 6, 180.00, 'kg'),
('Catla', 6, 280.00, 'kg'),
-- Meat (category_id: 7)
('Beef', 7, 650.00, 'kg'),
('Mutton', 7, 1100.00, 'kg'),
-- Poultry (category_id: 8)
('Chicken', 8, 200.00, 'kg'),
('Duck', 8, 350.00, 'kg'),
('Egg', 8, 12.00, 'piece');

-- =====================================================
-- 3. Modify ai_log for all user types
-- =====================================================

ALTER TABLE `ai_log`
CHANGE COLUMN `farmer_id` `user_id` INT(11) NOT NULL;

ALTER TABLE `ai_log`
ADD COLUMN IF NOT EXISTS `user_type` VARCHAR(20) DEFAULT NULL AFTER `user_id`;

-- =====================================================
-- 4. Add admin user with standard credentials if not exists
-- =====================================================

INSERT INTO `user` (`User_id`, `First_name`, `Last_name`, `Username`, `Password`, `User_type`, `Phone`, `Email`, `NID`)
SELECT 9999999, 'System', 'Admin', 'admin', 'admin123', 'Admin', '0000000000', 'admin@agrisphere.com', '0000000000'
WHERE NOT EXISTS (SELECT 1 FROM `user` WHERE `Username` = 'admin');

-- =====================================================
-- 5. Ensure user_block table has proper structure
-- =====================================================

-- Already exists in original schema

-- =====================================================
-- 6. Add description column to category table
-- =====================================================

ALTER TABLE `category`
ADD COLUMN IF NOT EXISTS `description` VARCHAR(255) DEFAULT NULL AFTER `category_name`;

-- =====================================================
-- Done! Database is ready for AgriSphere
-- =====================================================
