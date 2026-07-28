<?php
/**
 * Farmer Dashboard
 */

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is a farmer
if (!isLoggedIn() || !isFarmer()) {
    setFlashMessage('error', 'Please login as a farmer to access this page');
    redirect(BASE_URL . 'auth/login.php?role=farmer');
}

// Check if user is blocked
if (isUserBlocked($conn, getCurrentUserId())) {
    session_destroy();
    setFlashMessage('error', 'Your account has been blocked. Please contact support.');
    redirect(BASE_URL . 'auth/login.php');
}

$farmerId = getCurrentUserId();

// Get farmer stats
$totalProducts = 0;
$approvedProducts = 0;
$pendingProducts = 0;
$totalRevenue = 0;

// Total products
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM farm_product WHERE farmer_id = ?");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$result = $stmt->get_result();
$totalProducts = $result->fetch_assoc()['count'];

// Approved products
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM farm_product WHERE farmer_id = ? AND status = 'approved'");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$result = $stmt->get_result();
$approvedProducts = $result->fetch_assoc()['count'];

// Pending products
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM farm_product WHERE farmer_id = ? AND status = 'pending'");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$result = $stmt->get_result();
$pendingProducts = $result->fetch_assoc()['count'];

// Total revenue from sales
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(oi.quantity * oi.price_per_unit), 0) as revenue
    FROM order_items oi
    JOIN farm_product fp ON oi.product_id = fp.product_id
    JOIN customer_order co ON oi.order_id = co.order_id
    WHERE fp.farmer_id = ? AND co.status = 'Delivered'
");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$result = $stmt->get_result();
$totalRevenue = $result->fetch_assoc()['revenue'];

// Get recent products
$stmt = $conn->prepare("
    SELECT fp.*, c.category_name
    FROM farm_product fp
    JOIN category c ON fp.category_id = c.category_id
    WHERE fp.farmer_id = ?
    ORDER BY fp.product_id DESC
    LIMIT 5
");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$recentProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Include sidebar
include __DIR__ . '/../includes/sidebar_farmer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Welcome, <?php echo sanitize(getCurrentUserName()); ?>!</h1>
    <p class="page-subtitle">Here's what's happening with your farm today.</p>
</div>

<!-- Subscription Status Banner -->
<div class="subscription-banner">
    <div class="subscription-info">
        <div class="subscription-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="subscription-details">
            <h4>Active Subscription</h4>
            <p>Your farmer subscription is active. You can sell products on AgriSphere.</p>
        </div>
    </div>
    <div class="subscription-amount">
        <span class="amount">৳1,000</span>
        <span class="period">/ year</span>
    </div>
</div>

<style>
.subscription-banner {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 2px solid var(--primary);
    border-radius: var(--radius-lg);
    padding: 20px 24px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}
.subscription-info {
    display: flex;
    align-items: center;
    gap: 16px;
}
.subscription-icon {
    width: 48px;
    height: 48px;
    background: var(--primary);
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}
.subscription-details h4 {
    margin: 0 0 4px;
    color: var(--primary-700);
    font-size: 1.125rem;
}
.subscription-details p {
    margin: 0;
    color: var(--gray-600);
    font-size: 0.875rem;
}
.subscription-amount {
    text-align: right;
}
.subscription-amount .amount {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--primary);
}
.subscription-amount .period {
    color: var(--gray-500);
    font-size: 0.875rem;
}
</style>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $totalProducts; ?></div>
            <div class="stat-label">Total Products</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $approvedProducts; ?></div>
            <div class="stat-label">Approved</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon yellow">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $pendingProducts; ?></div>
            <div class="stat-label">Pending Approval</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-coins"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatCurrency($totalRevenue); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Quick Actions</h3>
    </div>
    <div class="card-body" style="display: flex; gap: 16px; flex-wrap: wrap;">
        <a href="sell_products.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Product
        </a>
        <a href="sales_history.php" class="btn btn-secondary">
            <i class="fas fa-history"></i> View Sales History
        </a>
        <a href="ai_assistant.php" class="btn btn-outline">
            <i class="fas fa-robot"></i> Ask AI Assistant
        </a>
    </div>
</div>

<!-- Recent Products -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Recent Products</h3>
        <a href="sales_history.php" class="btn btn-sm btn-secondary">View All</a>
    </div>

    <?php if (empty($recentProducts)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-seedling"></i>
                </div>
                <h4 class="empty-state-title">No products yet</h4>
                <p class="empty-state-text">Start by adding your first product for sale.</p>
                <a href="sell_products.php" class="btn btn-primary">Add Product</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentProducts as $product): ?>
                        <tr>
                            <td>
                                <strong><?php echo sanitize($product['product_name']); ?></strong>
                            </td>
                            <td><?php echo sanitize($product['category_name']); ?></td>
                            <td><?php echo formatCurrency($product['price_per_unit']); ?>/<?php echo sanitize($product['unit']); ?></td>
                            <td><?php echo getStatusBadge($product['status'] ?? 'pending'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
