<?php
/**
 * Admin Dashboard
 * Overview with statistics and charts
 */

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Please login as admin to access this page');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

// Check if user is blocked
if (isUserBlocked($conn, getCurrentUserId())) {
    session_destroy();
    setFlashMessage('error', 'Your account has been blocked.');
    redirect(BASE_URL . 'auth/login.php');
}

// Get statistics
$totalUsers = 0;
$totalFarmers = 0;
$totalCustomers = 0;
$totalProducts = 0;
$pendingProducts = 0;
$approvedProducts = 0;
$totalOrders = 0;
$pendingOrders = 0;
$salesRevenue = 0;

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM user WHERE User_type != 'Admin'");
$totalUsers = $result->fetch_assoc()['count'];

// Total farmers
$result = $conn->query("SELECT COUNT(*) as count FROM user WHERE User_type = 'Farmer'");
$totalFarmers = $result->fetch_assoc()['count'];

// Total customers
$result = $conn->query("SELECT COUNT(*) as count FROM user WHERE User_type = 'Customer'");
$totalCustomers = $result->fetch_assoc()['count'];

// Total products
$result = $conn->query("SELECT COUNT(*) as count FROM farm_product");
$totalProducts = $result->fetch_assoc()['count'];

// Pending products
$result = $conn->query("SELECT COUNT(*) as count FROM farm_product WHERE status = 'pending'");
$pendingProducts = $result->fetch_assoc()['count'];

// Approved products
$result = $conn->query("SELECT COUNT(*) as count FROM farm_product WHERE status = 'approved'");
$approvedProducts = $result->fetch_assoc()['count'];

// Total orders
$result = $conn->query("SELECT COUNT(*) as count FROM customer_order");
$totalOrders = $result->fetch_assoc()['count'];

// Pending/Processing orders
$result = $conn->query("SELECT COUNT(*) as count FROM customer_order WHERE status IN ('Pending', 'Processing')");
$pendingOrders = $result->fetch_assoc()['count'];

// Sales revenue (delivered orders)
$result = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM customer_order WHERE status = 'Delivered'");
$salesRevenue = $result->fetch_assoc()['revenue'];

// Subscription Revenue (Each farmer pays ৳1,000 subscription fee)
$subscriptionFee = 1000;
$subscriptionRevenue = $totalFarmers * $subscriptionFee;

// Total Revenue = Subscription + Sales Commission (10% of sales)
$salesCommission = $salesRevenue * 0.10;
$totalRevenue = $subscriptionRevenue + $salesCommission;

// Mock Operational Costs
$operationalCosts = [
    'server_hosting' => 5000,
    'payment_gateway' => 3000,
    'marketing' => 8000,
    'support_staff' => 15000,
    'maintenance' => 4000,
    'logistics' => 10000
];
$totalCosts = array_sum($operationalCosts);

// Net Profit
$netProfit = $totalRevenue - $totalCosts;

// Get recent orders
$recentOrders = $conn->query("
    SELECT co.*, u.First_name, u.Last_name
    FROM customer_order co
    JOIN user u ON co.customer_id = u.User_id
    ORDER BY co.order_date DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Get pending products for review
$pendingProductsList = $conn->query("
    SELECT fp.*, c.category_name, u.First_name, u.Last_name
    FROM farm_product fp
    JOIN category c ON fp.category_id = c.category_id
    JOIN user u ON fp.farmer_id = u.User_id
    WHERE fp.status = 'pending'
    ORDER BY fp.product_id DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Get monthly revenue data for chart (last 6 months)
$monthlyRevenue = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('M', strtotime("-$i months"));
    $result = $conn->query("
        SELECT COALESCE(SUM(total_amount), 0) as revenue
        FROM customer_order
        WHERE status = 'Delivered'
        AND DATE_FORMAT(order_date, '%Y-%m') = '$month'
    ");
    $monthlyRevenue[] = [
        'month' => $monthName,
        'revenue' => (float)$result->fetch_assoc()['revenue']
    ];
}

// Get products by category for chart
$categoryData = $conn->query("
    SELECT c.category_name, COUNT(fp.product_id) as count
    FROM category c
    LEFT JOIN farm_product fp ON c.category_id = fp.category_id AND fp.status = 'approved'
    GROUP BY c.category_id, c.category_name
    ORDER BY count DESC
")->fetch_all(MYSQLI_ASSOC);

// Include sidebar
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Admin Dashboard</h1>
    <p class="page-subtitle">Overview of your agricultural marketplace</p>
</div>

<!-- Revenue Summary Cards -->
<div class="stats-grid">
    <div class="stat-card highlight">
        <div class="stat-icon" style="background: #D1FAE5; color: #059669;">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatCurrency($totalRevenue); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon yellow">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatCurrency($subscriptionRevenue); ?></div>
            <div class="stat-label">Subscription Revenue (<?php echo $totalFarmers; ?> × ৳1,000)</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-percentage"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo formatCurrency($salesCommission); ?></div>
            <div class="stat-label">Sales Commission (10%)</div>
        </div>
    </div>

    <div class="stat-card <?php echo $netProfit >= 0 ? '' : 'loss'; ?>">
        <div class="stat-icon" style="background: <?php echo $netProfit >= 0 ? '#D1FAE5' : '#FEE2E2'; ?>; color: <?php echo $netProfit >= 0 ? '#059669' : '#DC2626'; ?>;">
            <i class="fas fa-<?php echo $netProfit >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value" style="color: <?php echo $netProfit >= 0 ? 'var(--success)' : 'var(--error)'; ?>;"><?php echo formatCurrency(abs($netProfit)); ?></div>
            <div class="stat-label"><?php echo $netProfit >= 0 ? 'Net Profit' : 'Net Loss'; ?></div>
        </div>
    </div>
</div>

<!-- User & Order Stats -->
<div class="stats-grid" style="margin-top: 16px;">
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-tractor"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $totalFarmers; ?></div>
            <div class="stat-label">Active Farmers</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #F3E8FF; color: #7C3AED;">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $totalCustomers; ?></div>
            <div class="stat-label">Customers</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-shopping-bag"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $totalOrders; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon yellow">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $approvedProducts; ?></div>
            <div class="stat-label">Active Products</div>
        </div>
    </div>
</div>

<!-- Operational Costs Summary -->
<div class="card mb-4" style="margin-top: 16px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calculator"></i> Operational Costs Breakdown</h3>
        <span class="badge badge-rejected">Total: <?php echo formatCurrency($totalCosts); ?></span>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
            <div class="cost-item">
                <i class="fas fa-server"></i>
                <span class="cost-label">Server Hosting</span>
                <span class="cost-value"><?php echo formatCurrency($operationalCosts['server_hosting']); ?></span>
            </div>
            <div class="cost-item">
                <i class="fas fa-credit-card"></i>
                <span class="cost-label">Payment Gateway</span>
                <span class="cost-value"><?php echo formatCurrency($operationalCosts['payment_gateway']); ?></span>
            </div>
            <div class="cost-item">
                <i class="fas fa-bullhorn"></i>
                <span class="cost-label">Marketing</span>
                <span class="cost-value"><?php echo formatCurrency($operationalCosts['marketing']); ?></span>
            </div>
            <div class="cost-item">
                <i class="fas fa-headset"></i>
                <span class="cost-label">Support Staff</span>
                <span class="cost-value"><?php echo formatCurrency($operationalCosts['support_staff']); ?></span>
            </div>
            <div class="cost-item">
                <i class="fas fa-tools"></i>
                <span class="cost-label">Maintenance</span>
                <span class="cost-value"><?php echo formatCurrency($operationalCosts['maintenance']); ?></span>
            </div>
            <div class="cost-item">
                <i class="fas fa-truck"></i>
                <span class="cost-label">Logistics</span>
                <span class="cost-value"><?php echo formatCurrency($operationalCosts['logistics']); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid-2" style="margin-top: 24px;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Revenue Breakdown</h3>
        </div>
        <div class="card-body">
            <canvas id="revenueBreakdownChart" height="200"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Operational Costs</h3>
        </div>
        <div class="card-body">
            <canvas id="costsChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Second Charts Row -->
<div class="grid-2" style="margin-top: 24px;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Monthly Sales Revenue</h3>
        </div>
        <div class="card-body">
            <canvas id="revenueChart" height="200"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Products by Category</h3>
        </div>
        <div class="card-body">
            <canvas id="categoryChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Tables Row -->
<div class="grid-2" style="margin-top: 24px;">
    <!-- Pending Products -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pending Products</h3>
            <a href="products.php?status=pending" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <?php if (empty($pendingProductsList)): ?>
            <div class="card-body">
                <div class="empty-state-small">
                    <i class="fas fa-check-circle text-success"></i>
                    <p>No pending products to review</p>
                </div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Farmer</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingProductsList as $product): ?>
                            <tr>
                                <td>
                                    <strong><?php echo sanitize($product['product_name']); ?></strong>
                                    <div class="text-muted text-sm"><?php echo sanitize($product['category_name']); ?></div>
                                </td>
                                <td><?php echo sanitize($product['First_name'] . ' ' . $product['Last_name']); ?></td>
                                <td><?php echo formatCurrency($product['price_per_unit']); ?>/<?php echo sanitize($product['unit']); ?></td>
                                <td>
                                    <a href="products.php?review=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-primary">Review</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Orders</h3>
            <a href="orders.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <?php if (empty($recentOrders)): ?>
            <div class="card-body">
                <div class="empty-state-small">
                    <i class="fas fa-shopping-bag text-muted"></i>
                    <p>No orders yet</p>
                </div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                <td><?php echo sanitize($order['First_name'] . ' ' . $order['Last_name']); ?></td>
                                <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                <td><?php echo getOrderStatusBadge($order['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h3 class="card-title">Quick Actions</h3>
    </div>
    <div class="card-body" style="display: flex; gap: 16px; flex-wrap: wrap;">
        <a href="products.php?status=pending" class="btn btn-primary">
            <i class="fas fa-check"></i> Review Products (<?php echo $pendingProducts; ?>)
        </a>
        <a href="orders.php?status=Processing" class="btn btn-secondary">
            <i class="fas fa-truck"></i> Process Orders (<?php echo $pendingOrders; ?>)
        </a>
        <a href="users.php" class="btn btn-outline">
            <i class="fas fa-users"></i> Manage Users
        </a>
        <a href="ai_assistant.php" class="btn btn-outline">
            <i class="fas fa-robot"></i> AI Assistant
        </a>
    </div>
</div>

<style>
.cost-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px;
    background: var(--gray-50);
    border-radius: var(--radius);
    text-align: center;
}
.cost-item i {
    font-size: 1.5rem;
    color: var(--primary);
    margin-bottom: 8px;
}
.cost-label {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-bottom: 4px;
}
.cost-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-800);
}
.stat-card.highlight {
    border: 2px solid var(--primary);
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}
</style>

<?php
$extraScripts = "
<script>
// Revenue Breakdown Chart
const revenueBreakdownCtx = document.getElementById('revenueBreakdownChart').getContext('2d');
new Chart(revenueBreakdownCtx, {
    type: 'doughnut',
    data: {
        labels: ['Subscription Revenue', 'Sales Commission'],
        datasets: [{
            data: [$subscriptionRevenue, $salesCommission],
            backgroundColor: ['#2F6B4F', '#A8D5BA'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { boxWidth: 12, padding: 15 }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ৳' + context.raw.toLocaleString();
                    }
                }
            }
        }
    }
});

// Operational Costs Chart
const costsCtx = document.getElementById('costsChart').getContext('2d');
new Chart(costsCtx, {
    type: 'bar',
    data: {
        labels: ['Server', 'Payment', 'Marketing', 'Staff', 'Maintenance', 'Logistics'],
        datasets: [{
            label: 'Costs (BDT)',
            data: [5000, 3000, 8000, 15000, 4000, 10000],
            backgroundColor: ['#EF4444', '#F97316', '#EAB308', '#22C55E', '#3B82F6', '#8B5CF6'],
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) { return '৳' + value.toLocaleString(); }
                }
            }
        }
    }
});

// Monthly Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: " . json_encode(array_column($monthlyRevenue, 'month')) . ",
        datasets: [{
            label: 'Revenue (BDT)',
            data: " . json_encode(array_column($monthlyRevenue, 'revenue')) . ",
            borderColor: '#2F6B4F',
            backgroundColor: 'rgba(47, 107, 79, 0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#2F6B4F',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) { return '৳' + value.toLocaleString(); }
                }
            }
        }
    }
});

// Category Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: " . json_encode(array_column($categoryData, 'category_name')) . ",
        datasets: [{
            data: " . json_encode(array_column($categoryData, 'count')) . ",
            backgroundColor: ['#2F6B4F','#A8D5BA','#34D399','#10B981','#059669','#047857','#065F46','#064E3B'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: { boxWidth: 12, padding: 15 }
            }
        }
    }
});
</script>
";
?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
