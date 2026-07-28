<?php
/**
 * AgriSphere Common Header
 * Include this at the top of all dashboard pages
 */

if (!defined('AGRISPHERE')) {
    define('AGRISPHERE', true);
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get page title (set before including header)
$pageTitle = $pageTitle ?? 'Dashboard';
$currentPage = $currentPage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AgriSphere - From Farm to Market, Simplified">
    <title><?php echo sanitize($pageTitle); ?> | <?php echo SITE_NAME; ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/main.css">

    <!-- Chart.js (for admin dashboard) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-layout">
