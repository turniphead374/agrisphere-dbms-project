<?php
/**
 * AgriSphere Landing Page
 * The main entry point of the application
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Start session
session_start();

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(BASE_URL . 'admin/dashboard.php');
    } elseif (isFarmer()) {
        redirect(BASE_URL . 'farmer/dashboard.php');
    } else {
        redirect(BASE_URL . 'customer/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AgriSphere - Connecting farmers directly with customers. Fresh produce at fair prices.">
    <title><?php echo SITE_NAME; ?> - From Farm to Market, Simplified</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/main.css">
</head>
<body class="landing-page">

    <!-- Navigation -->
    <nav class="landing-nav">
        <a href="<?php echo BASE_URL; ?>" class="landing-logo">
            <div class="landing-logo-icon">
                <i class="fas fa-leaf"></i>
            </div>
            <span class="landing-logo-text"><?php echo SITE_NAME; ?></span>
        </a>

        <div class="landing-nav-links">
            <a href="#features" class="landing-nav-link">Features</a>
            <a href="#how-it-works" class="landing-nav-link">How It Works</a>
            <a href="<?php echo BASE_URL; ?>auth/login.php" class="btn btn-outline btn-sm">Login</a>
            <a href="<?php echo BASE_URL; ?>auth/register.php" class="btn btn-primary btn-sm">Get Started</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-seedling"></i>
                <span>100% Fresh from Farm</span>
            </div>

            <h1 class="hero-title">
                Welcome to <span><?php echo SITE_NAME; ?></span>
            </h1>

            <p class="hero-subtitle">
                <?php echo SITE_TAGLINE; ?>. We connect farmers directly with customers,
                ensuring fresh produce at government-regulated prices with zero delivery charges.
            </p>

            <div class="hero-buttons">
                <a href="<?php echo BASE_URL; ?>auth/register.php?role=customer" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-basket"></i>
                    Start Shopping
                </a>
                <a href="<?php echo BASE_URL; ?>auth/register.php?role=farmer" class="btn btn-outline btn-lg">
                    <i class="fas fa-tractor"></i>
                    Join as Farmer
                </a>
            </div>
        </div>
    </section>

    <!-- User Types Section -->
    <section class="user-types" id="how-it-works">
        <h2 class="user-types-title">Choose Your Portal</h2>

        <div class="user-types-grid">
            <!-- Admin Card -->
            <a href="<?php echo BASE_URL; ?>auth/login.php?role=admin" class="user-type-card admin">
                <div class="user-type-icon">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <h3 class="user-type-title">Admin</h3>
                <p class="user-type-desc">
                    Manage the platform, approve products, handle users, and oversee all operations.
                </p>
                <span class="btn btn-outline btn-sm">Admin Login</span>
            </a>

            <!-- Farmer Card -->
            <a href="<?php echo BASE_URL; ?>auth/login.php?role=farmer" class="user-type-card farmer">
                <div class="user-type-icon">
                    <i class="fas fa-tractor"></i>
                </div>
                <h3 class="user-type-title">Farmer</h3>
                <p class="user-type-desc">
                    List your products, manage sales, and get fair prices directly from customers.
                </p>
                <span class="btn btn-outline btn-sm">Farmer Portal</span>
            </a>

            <!-- Customer Card -->
            <a href="<?php echo BASE_URL; ?>auth/login.php?role=customer" class="user-type-card customer">
                <div class="user-type-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 class="user-type-title">Customer</h3>
                <p class="user-type-desc">
                    Browse fresh farm products, shop at fair prices, and enjoy free delivery.
                </p>
                <span class="btn btn-outline btn-sm">Start Shopping</span>
            </a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="user-types" id="features" style="background: var(--gray-50);">
        <h2 class="user-types-title">Why Choose AgriSphere?</h2>

        <div class="user-types-grid">
            <div class="user-type-card" style="cursor: default;">
                <div class="user-type-icon" style="background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%); color: #059669;">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <h3 class="user-type-title">Fair Prices</h3>
                <p class="user-type-desc">
                    Government-regulated prices ensure farmers get fair compensation and customers pay reasonable rates.
                </p>
            </div>

            <div class="user-type-card" style="cursor: default;">
                <div class="user-type-icon" style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); color: #D97706;">
                    <i class="fas fa-truck"></i>
                </div>
                <h3 class="user-type-title">Free Delivery</h3>
                <p class="user-type-desc">
                    Enjoy zero delivery charges on all orders. We believe fresh produce should be accessible to everyone.
                </p>
            </div>

            <div class="user-type-card" style="cursor: default;">
                <div class="user-type-icon" style="background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%); color: #2563EB;">
                    <i class="fas fa-robot"></i>
                </div>
                <h3 class="user-type-title">AI Assistant</h3>
                <p class="user-type-desc">
                    Get smart recommendations, farming tips, and instant support from our AI-powered assistant.
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background: var(--gray-800); color: var(--gray-400); padding: 40px 48px; text-align: center;">
        <div class="landing-logo" style="justify-content: center; margin-bottom: 24px;">
            <div class="landing-logo-icon" style="width: 36px; height: 36px; font-size: 1.125rem;">
                <i class="fas fa-leaf"></i>
            </div>
            <span class="landing-logo-text" style="color: var(--white);"><?php echo SITE_NAME; ?></span>
        </div>
        <p style="margin-bottom: 16px;">Connecting Farmers with Customers Since 2024</p>
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
    </footer>

</body>
</html>
