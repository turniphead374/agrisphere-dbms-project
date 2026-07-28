<?php
/**
 * AgriSphere Database Connection
 */

if (!defined('AGRISPHERE')) {
    define('AGRISPHERE', true);
}

require_once __DIR__ . '/config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");
