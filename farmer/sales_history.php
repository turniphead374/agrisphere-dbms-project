<?php
/**
 * Farmer - Sales History (Products List)
 */

$pageTitle = 'Sales History';
$currentPage = 'sales_history';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is a farmer
if (!isLoggedIn() || !isFarmer()) {
    setFlashMessage('error', 'Please login as a farmer to access this page');
    redirect(BASE_URL . 'auth/login.php?role=farmer');
}

$farmerId = getCurrentUserId();

// Get filter
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Build query
$query = "
    SELECT fp.*, c.category_name, fi.quantity_available
    FROM farm_product fp
    JOIN category c ON fp.category_id = c.category_id
    LEFT JOIN farm_inventory fi ON fp.product_id = fi.product_id
    WHERE fp.farmer_id = ?
";
$params = [$farmerId];
$types = "i";

if (!empty($statusFilter)) {
    $query .= " AND fp.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$query .= " ORDER BY fp.product_id DESC";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    $products = [];
} else {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Count by status
$statusCounts = [
    'all' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

$countQuery = $conn->prepare("
    SELECT status, COUNT(*) as count
    FROM farm_product
    WHERE farmer_id = ?
    GROUP BY status
");
if ($countQuery) {
    $countQuery->bind_param("i", $farmerId);
    $countQuery->execute();
    $countResult = $countQuery->get_result();
    while ($row = $countResult->fetch_assoc()) {
        $statusCounts[$row['status']] = $row['count'];
        $statusCounts['all'] += $row['count'];
    }
}

// Include sidebar
include __DIR__ . '/../includes/sidebar_farmer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Sales History</h1>
    <p class="page-subtitle">View all your products and their approval status.</p>
</div>

<!-- Status Filters -->
<div class="category-pills">
    <a href="?status=" class="category-pill <?php echo empty($statusFilter) ? 'active' : ''; ?>">
        All (<?php echo $statusCounts['all']; ?>)
    </a>
    <a href="?status=pending" class="category-pill <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
        Pending (<?php echo $statusCounts['pending']; ?>)
    </a>
    <a href="?status=approved" class="category-pill <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">
        Approved (<?php echo $statusCounts['approved']; ?>)
    </a>
    <a href="?status=rejected" class="category-pill <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">
        Rejected (<?php echo $statusCounts['rejected']; ?>)
    </a>
</div>

<!-- Products Table -->
<div class="card">
    <?php if (empty($products)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-box-open"></i>
                </div>
                <h4 class="empty-state-title">No products found</h4>
                <p class="empty-state-text">
                    <?php if (!empty($statusFilter)): ?>
                        No products with status "<?php echo ucfirst($statusFilter); ?>".
                    <?php else: ?>
                        You haven't added any products yet.
                    <?php endif; ?>
                </p>
                <a href="sell_products.php" class="btn btn-primary">Add New Product</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if (!empty($product['product_image'])): ?>
                                        <img src="<?php echo getProductImageUrl($product['product_image']); ?>"
                                             alt="" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; background: var(--gray-100); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--gray-400);">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo sanitize($product['product_name']); ?></strong>
                                        <?php if (!empty($product['description'])): ?>
                                            <div style="font-size: 0.8125rem; color: var(--gray-500);">
                                                <?php echo truncateText(sanitize($product['description']), 50); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo sanitize($product['category_name']); ?></td>
                            <td><?php echo $product['quantity_available'] ?? 0; ?> <?php echo sanitize($product['unit']); ?></td>
                            <td><?php echo formatCurrency($product['price_per_unit']); ?>/<?php echo sanitize($product['unit']); ?></td>
                            <td><?php echo getStatusBadge($product['status'] ?? 'pending'); ?></td>
                            <td>
                                <?php if ($product['status'] === 'rejected' && !empty($product['rejection_reason'])): ?>
                                    <span style="color: var(--error); font-size: 0.8125rem;">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo sanitize($product['rejection_reason']); ?>
                                    </span>
                                <?php elseif ($product['status'] === 'pending'): ?>
                                    <span style="color: var(--gray-400); font-size: 0.8125rem;">
                                        Awaiting review
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--success); font-size: 0.8125rem;">
                                        <i class="fas fa-check"></i> Live
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div style="margin-top: 24px;">
    <a href="sell_products.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Product
    </a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
