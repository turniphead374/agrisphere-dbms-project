<?php
/**
 * Admin Inventory Overview
 * Monitor stock levels across all products
 */

$pageTitle = 'Inventory';
$currentPage = 'inventory';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Please login as admin to access this page');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

// Get filter parameters
$filterCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$filterStock = isset($_GET['stock']) ? sanitize($_GET['stock']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$whereConditions = ["fp.status = 'approved'"];
$params = [];
$types = '';

if ($filterCategory) {
    $whereConditions[] = "fp.category_id = ?";
    $params[] = $filterCategory;
    $types .= 'i';
}

if ($filterStock === 'low') {
    $whereConditions[] = "fp.quantity <= 10 AND fp.quantity > 0";
} elseif ($filterStock === 'out') {
    $whereConditions[] = "fp.quantity = 0";
} elseif ($filterStock === 'in') {
    $whereConditions[] = "fp.quantity > 10";
}

if ($search) {
    $whereConditions[] = "(fp.product_name LIKE ? OR u.First_name LIKE ? OR u.Last_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

$whereClause = implode(' AND ', $whereConditions);

$query = "
    SELECT fp.*, c.category_name, u.First_name, u.Last_name,
           COALESCE(fi.quantity_available, fp.quantity) as stock_quantity,
           (SELECT SUM(oi.quantity) FROM order_items oi
            JOIN customer_order co ON oi.order_id = co.order_id
            WHERE oi.product_id = fp.product_id AND co.status != 'Cancelled') as total_sold
    FROM farm_product fp
    JOIN category c ON fp.category_id = c.category_id
    JOIN user u ON fp.farmer_id = u.User_id
    LEFT JOIN farm_inventory fi ON fp.product_id = fi.product_id
    WHERE $whereClause
    ORDER BY fp.quantity ASC, fp.product_name ASC
";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        $products = [];
    } else {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
} else {
    $result = $conn->query($query);
    $products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get summary stats with error handling
$totalResult = $conn->query("SELECT COUNT(*) as count FROM farm_product WHERE status = 'approved'");
$totalProducts = $totalResult ? ($totalResult->fetch_assoc()['count'] ?? 0) : 0;

$lowStockResult = $conn->query("SELECT COUNT(*) as count FROM farm_product WHERE status = 'approved' AND COALESCE(quantity, 0) <= 10 AND COALESCE(quantity, 0) > 0");
$lowStockCount = $lowStockResult ? ($lowStockResult->fetch_assoc()['count'] ?? 0) : 0;

$outOfStockResult = $conn->query("SELECT COUNT(*) as count FROM farm_product WHERE status = 'approved' AND COALESCE(quantity, 0) = 0");
$outOfStockCount = $outOfStockResult ? ($outOfStockResult->fetch_assoc()['count'] ?? 0) : 0;

$inStockResult = $conn->query("SELECT COUNT(*) as count FROM farm_product WHERE status = 'approved' AND COALESCE(quantity, 0) > 10");
$inStockCount = $inStockResult ? ($inStockResult->fetch_assoc()['count'] ?? 0) : 0;

// Get total inventory value
$valueResult = $conn->query("SELECT SUM(COALESCE(quantity, 0) * price_per_unit) as total FROM farm_product WHERE status = 'approved'");
$totalValue = $valueResult ? ($valueResult->fetch_assoc()['total'] ?? 0) : 0;

// Get categories for filter dropdown
$categories = getAllCategories($conn);

// Get inventory by category for chart
$categoryResult = $conn->query("
    SELECT c.category_name,
           COUNT(fp.product_id) as product_count,
           SUM(COALESCE(fp.quantity, 0)) as total_quantity,
           SUM(COALESCE(fp.quantity, 0) * fp.price_per_unit) as total_value
    FROM category c
    LEFT JOIN farm_product fp ON c.category_id = fp.category_id AND fp.status = 'approved'
    GROUP BY c.category_id, c.category_name
    ORDER BY total_value DESC
");
$categoryInventory = $categoryResult ? $categoryResult->fetch_all(MYSQLI_ASSOC) : [];

// Include sidebar
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 class="page-title">Inventory Management</h1>
            <p class="page-subtitle">Monitor and track product stock levels</p>
        </div>
    </div>
</div>

<!-- Summary Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-boxes"></i>
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
            <div class="stat-value"><?php echo $inStockCount; ?></div>
            <div class="stat-label">In Stock</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon yellow">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $lowStockCount; ?></div>
            <div class="stat-label">Low Stock</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #FEE2E2; color: #991B1B;">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $outOfStockCount; ?></div>
            <div class="stat-label">Out of Stock</div>
        </div>
    </div>
</div>

<!-- Total Value Card -->
<div class="card mb-4" style="margin-top: 16px;">
    <div class="card-body" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <span class="text-muted">Total Inventory Value</span>
            <div style="font-size: 1.75rem; font-weight: 700; color: var(--primary);">
                <?php echo formatCurrency($totalValue); ?>
            </div>
        </div>
        <div style="display: flex; gap: 24px;">
            <?php foreach (array_slice($categoryInventory, 0, 4) as $cat): ?>
                <?php if ($cat['total_value'] > 0): ?>
                    <div style="text-align: center;">
                        <div class="text-muted text-sm"><?php echo sanitize($cat['category_name']); ?></div>
                        <div style="font-weight: 600;"><?php echo formatCurrency($cat['total_value']); ?></div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="card mb-4">
    <div class="card-body" style="padding: 12px 16px;">
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <a href="inventory.php" class="filter-tab <?php echo !$filterStock ? 'active' : ''; ?>">
                All <span class="filter-count"><?php echo $totalProducts; ?></span>
            </a>
            <a href="inventory.php?stock=in" class="filter-tab in <?php echo $filterStock === 'in' ? 'active' : ''; ?>">
                In Stock <span class="filter-count"><?php echo $inStockCount; ?></span>
            </a>
            <a href="inventory.php?stock=low" class="filter-tab low <?php echo $filterStock === 'low' ? 'active' : ''; ?>">
                Low Stock <span class="filter-count"><?php echo $lowStockCount; ?></span>
            </a>
            <a href="inventory.php?stock=out" class="filter-tab out <?php echo $filterStock === 'out' ? 'active' : ''; ?>">
                Out of Stock <span class="filter-count"><?php echo $outOfStockCount; ?></span>
            </a>

            <div style="margin-left: auto; display: flex; gap: 8px;">
                <select onchange="filterByCategory(this.value)" class="form-control" style="width: 150px;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo $filterCategory == $cat['category_id'] ? 'selected' : ''; ?>>
                            <?php echo sanitize($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <form method="GET" action="inventory.php" style="display: flex; gap: 8px;">
                    <?php if ($filterStock): ?>
                        <input type="hidden" name="stock" value="<?php echo $filterStock; ?>">
                    <?php endif; ?>
                    <?php if ($filterCategory): ?>
                        <input type="hidden" name="category" value="<?php echo $filterCategory; ?>">
                    <?php endif; ?>
                    <input type="text" name="search" placeholder="Search products..." value="<?php echo $search; ?>" class="form-control" style="width: 200px;">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Product Inventory</h3>
        <span class="text-muted"><?php echo count($products); ?> products</span>
    </div>

    <?php if (empty($products)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-box-open"></i>
                </div>
                <h4 class="empty-state-title">No products found</h4>
                <p class="empty-state-text">No products match your filter criteria.</p>
                <a href="inventory.php" class="btn btn-primary">View All</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Farmer</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Sold</th>
                        <th>Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $stockLevel = $product['quantity'];
                        $stockClass = '';
                        $stockIcon = '';
                        if ($stockLevel == 0) {
                            $stockClass = 'stock-out';
                            $stockIcon = 'fa-times-circle';
                        } elseif ($stockLevel <= 10) {
                            $stockClass = 'stock-low';
                            $stockIcon = 'fa-exclamation-triangle';
                        } else {
                            $stockClass = 'stock-in';
                            $stockIcon = 'fa-check-circle';
                        }
                        ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if ($product['product_image']): ?>
                                        <img src="<?php echo getProductImageUrl($product['product_image']); ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: var(--radius-sm);">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; background: var(--gray-100); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <strong><?php echo sanitize($product['product_name']); ?></strong>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-category"><?php echo sanitize($product['category_name']); ?></span>
                            </td>
                            <td><?php echo sanitize($product['First_name'] . ' ' . $product['Last_name']); ?></td>
                            <td><?php echo formatCurrency($product['price_per_unit']); ?>/<?php echo sanitize($product['unit']); ?></td>
                            <td>
                                <span class="stock-badge <?php echo $stockClass; ?>">
                                    <i class="fas <?php echo $stockIcon; ?>"></i>
                                    <?php echo number_format($stockLevel); ?> <?php echo sanitize($product['unit']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($product['total_sold'] ?? 0); ?></td>
                            <td><?php echo formatCurrency($product['quantity'] * $product['price_per_unit']); ?></td>
                            <td>
                                <?php if ($stockLevel == 0): ?>
                                    <span class="badge badge-rejected">Out of Stock</span>
                                <?php elseif ($stockLevel <= 10): ?>
                                    <span class="badge badge-pending">Low Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-approved">In Stock</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
.filter-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: var(--gray-100);
    border-radius: var(--radius-full);
    color: var(--gray-600);
    font-size: 0.875rem;
    font-weight: 500;
    transition: all var(--transition-fast);
}

.filter-tab:hover {
    background: var(--gray-200);
    color: var(--gray-700);
}

.filter-tab.active {
    background: var(--primary);
    color: white;
}

.filter-tab.in.active { background: var(--success); }
.filter-tab.low.active { background: var(--warning); }
.filter-tab.out.active { background: var(--error); }

.filter-tab.active .filter-count {
    background: rgba(255,255,255,0.2);
    color: white;
}

.filter-count {
    background: var(--gray-200);
    padding: 2px 8px;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
}

.badge-category {
    background: var(--primary-100);
    color: var(--primary-700);
}

.stock-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: var(--radius-full);
    font-size: 0.875rem;
    font-weight: 500;
}

.stock-badge.stock-in {
    background: var(--success-light);
    color: #059669;
}

.stock-badge.stock-low {
    background: var(--warning-light);
    color: #B45309;
}

.stock-badge.stock-out {
    background: var(--error-light);
    color: #DC2626;
}
</style>

<script>
function filterByCategory(categoryId) {
    let url = 'inventory.php';
    const params = new URLSearchParams(window.location.search);

    if (categoryId) {
        params.set('category', categoryId);
    } else {
        params.delete('category');
    }

    if (params.toString()) {
        url += '?' + params.toString();
    }

    window.location.href = url;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
