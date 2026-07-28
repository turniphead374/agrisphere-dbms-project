<?php
/**
 * Customer Sidebar Navigation
 */
$userName = getCurrentUserName();
$userInitials = strtoupper(substr($userName, 0, 1));
$cartCount = isLoggedIn() ? getCartCount($conn, getCurrentUserId()) : 0;
?>
<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo BASE_URL; ?>" class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <i class="fas fa-leaf"></i>
            </div>
            <span class="sidebar-logo-text"><?php echo SITE_NAME; ?></span>
        </a>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-avatar"><?php echo $userInitials; ?></div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?php echo sanitize($userName); ?></div>
            <div class="sidebar-user-role">Customer</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?php echo BASE_URL; ?>customer/dashboard.php"
           class="sidebar-nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?php echo BASE_URL; ?>customer/products.php"
           class="sidebar-nav-item <?php echo $currentPage === 'products' ? 'active' : ''; ?>">
            <i class="fas fa-store"></i>
            <span>Browse Products</span>
        </a>

        <a href="<?php echo BASE_URL; ?>customer/cart.php"
           class="sidebar-nav-item <?php echo $currentPage === 'cart' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i>
            <span>My Cart</span>
            <?php if ($cartCount > 0): ?>
                <span class="sidebar-nav-badge"><?php echo $cartCount; ?></span>
            <?php endif; ?>
        </a>

        <a href="<?php echo BASE_URL; ?>customer/orders.php"
           class="sidebar-nav-item <?php echo $currentPage === 'orders' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-list"></i>
            <span>My Orders</span>
        </a>

        <a href="<?php echo BASE_URL; ?>customer/profile.php"
           class="sidebar-nav-item <?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>My Profile</span>
        </a>

        <a href="<?php echo BASE_URL; ?>customer/ai_assistant.php"
           class="sidebar-nav-item <?php echo $currentPage === 'ai_assistant' ? 'active' : ''; ?>">
            <i class="fas fa-robot"></i>
            <span>AI Assistant</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="sidebar-logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

<!-- Top Bar -->
<header class="topbar">
    <div class="topbar-title"><?php echo sanitize($pageTitle); ?></div>
    <div class="topbar-actions">
        <div class="topbar-search">
            <i class="fas fa-search" style="color: var(--gray-400);"></i>
            <input type="text" placeholder="Search products...">
        </div>
        <a href="<?php echo BASE_URL; ?>customer/cart.php" class="btn btn-outline btn-sm">
            <i class="fas fa-shopping-cart"></i>
            Cart (<?php echo $cartCount; ?>)
        </a>
    </div>
</header>

<!-- Main Content Area -->
<main class="main-content">
    <?php displayFlashMessage(); ?>
