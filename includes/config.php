<?php
/**
 * AgriSphere Configuration File
 * Central configuration for the entire application
 */

// Prevent direct access
if (!defined('AGRISPHERE')) {
    define('AGRISPHERE', true);
}

// =====================================================
// Site Configuration
// =====================================================
define('SITE_NAME', 'AgriSphere');
define('SITE_TAGLINE', 'From Farm to Market - Simplified');
define('BASE_URL', 'http://localhost/Agrisphere/');
define('CURRENCY', '৳');
define('CURRENCY_CODE', 'BDT');

// =====================================================
// Database Configuration
// =====================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cse311 lab project');

// =====================================================
// File Upload Configuration
// =====================================================
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('PRODUCT_IMAGE_PATH', UPLOAD_PATH . 'products/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// =====================================================
// AI Configuration (Gemini API - Using Gemini 2.5 Flash)
// =====================================================
define('GEMINI_API_KEY', '');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent');

// =====================================================
// Admin Credentials (Hardcoded for demo)
// =====================================================
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');

// =====================================================
// Session Configuration
// =====================================================
define('SESSION_LIFETIME', 3600); // 1 hour

// =====================================================
// Order Status Constants
// =====================================================
define('ORDER_STATUS_PENDING', 'Pending');
define('ORDER_STATUS_PROCESSING', 'Processing');
define('ORDER_STATUS_SHIPPED', 'Shipped');
define('ORDER_STATUS_DELIVERED', 'Delivered');
define('ORDER_STATUS_CANCELLED', 'Cancelled');

// =====================================================
// Product Status Constants
// =====================================================
define('PRODUCT_STATUS_PENDING', 'pending');
define('PRODUCT_STATUS_APPROVED', 'approved');
define('PRODUCT_STATUS_REJECTED', 'rejected');

// =====================================================
// User Types
// =====================================================
define('USER_TYPE_ADMIN', 'Admin');
define('USER_TYPE_FARMER', 'Farmer');
define('USER_TYPE_CUSTOMER', 'Customer');

// =====================================================
// Pagination
// =====================================================
define('ITEMS_PER_PAGE', 12);

// =====================================================
// Error Reporting (set to 0 in production)
// =====================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =====================================================
// Timezone
// =====================================================
date_default_timezone_set('Asia/Dhaka');
