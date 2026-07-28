<?php
/**
 * Farmer Sidebar Navigation
 */
$userName = getCurrentUserName();
$userInitials = strtoupper(substr($userName, 0, 1));
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
            <div class="sidebar-user-role">Farmer</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?php echo BASE_URL; ?>farmer/dashboard.php"
           class="sidebar-nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?php echo BASE_URL; ?>farmer/sell_products.php"
           class="sidebar-nav-item <?php echo $currentPage === 'sell_products' ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i>
            <span>Sell Products</span>
        </a>

        <a href="<?php echo BASE_URL; ?>farmer/sales_history.php"
           class="sidebar-nav-item <?php echo $currentPage === 'sales_history' ? 'active' : ''; ?>">
            <i class="fas fa-history"></i>
            <span>Sales History</span>
        </a>

        <a href="<?php echo BASE_URL; ?>farmer/profile.php"
           class="sidebar-nav-item <?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>My Profile</span>
        </a>

        <a href="<?php echo BASE_URL; ?>farmer/ai_assistant.php"
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
            <input type="text" placeholder="Search...">
        </div>
    </div>
</header>

<!-- Main Content Area -->
<main class="main-content">
    <?php displayFlashMessage(); ?>
