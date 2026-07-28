<?php
/**
 * Farmer - My Sales
 * View sales from approved products
 */

$pageTitle = 'My Sales';
$currentPage = 'my_sales';

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

// Get all sales for this farmer's products
$stmt = $conn->prepare("
    SELECT
        co.order_id, co.order_date, co.status as order_status,
        oi.quantity, oi.price_per_unit,
        (oi.quantity * oi.price_per_unit) as line_total,
        fp.product_name, fp.unit,
        CONCAT(u.First_name, ' ', u.Last_name) as customer_name,
        p.payment_status
    FROM order_items oi
    JOIN farm_product fp ON oi.product_id = fp.product_id
    JOIN customer_order co ON oi.order_id = co.order_id
    JOIN user u ON co.customer_id = u.User_id
    LEFT JOIN payment p ON co.order_id = p.order_id
    WHERE fp.farmer_id = ?
    ORDER BY co.order_date DESC
");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$totalSales = 0;
$pendingSales = 0;
$completedSales = 0;

foreach ($sales as $sale) {
    $totalSales += $sale['line_total'];
    if ($sale['order_status'] === 'Delivered') {
        $completedSales += $sale['line_total'];
    } else {
        $pendingSales += $sale['line_total'];
    }
}

// Include sidebar
include __DIR__ . '/../includes/sidebar_farmer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">My Sales</h1>
    <p class="page-subtitle">Track your product sales and earnings</p>
</div>

<!-- Sales Summary -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-coins"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatCurrency($totalSales); ?></div>
            <div class="stat-label">Total Sales</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatCurrency($completedSales); ?></div>
            <div class="stat-label">Completed Sales</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon yellow">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatCurrency($pendingSales); ?></div>
            <div class="stat-label">Pending Sales</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #F3E8FF; color: #7C3AED;">
            <i class="fas fa-receipt"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo count($sales); ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
    </div>
</div>

<!-- Sales Table -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h3 class="card-title">Sales History</h3>
    </div>

    <?php if (empty($sales)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h4 class="empty-state-title">No sales yet</h4>
                <p class="empty-state-text">When customers purchase your products, sales will appear here.</p>
                <a href="sell_products.php" class="btn btn-primary">Add Products</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Qty</th>
                        <th>Amount</th>
                        <th>Order Status</th>
                        <th>Payment</th>
                        <th>Invoice</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><strong>#<?php echo $sale['order_id']; ?></strong></td>
                            <td><?php echo formatDate($sale['order_date']); ?></td>
                            <td><?php echo sanitize($sale['product_name']); ?></td>
                            <td><?php echo sanitize($sale['customer_name']); ?></td>
                            <td><?php echo $sale['quantity']; ?> <?php echo sanitize($sale['unit']); ?></td>
                            <td><strong><?php echo formatCurrency($sale['line_total']); ?></strong></td>
                            <td><?php echo getOrderStatusBadge($sale['order_status']); ?></td>
                            <td>
                                <?php
                                $paymentStatus = $sale['payment_status'] ?? 'Pending';
                                ?>
                                <span class="badge <?php echo $paymentStatus === 'Paid' ? 'badge-approved' : ($paymentStatus === 'Failed' ? 'badge-rejected' : 'badge-pending'); ?>">
                                    <?php echo $paymentStatus; ?>
                                </span>
                            </td>
                            <td>
                                <a href="sale_invoice.php?id=<?php echo $sale['order_id']; ?>" class="btn btn-sm btn-outline" target="_blank">
                                    <i class="fas fa-file-invoice"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
