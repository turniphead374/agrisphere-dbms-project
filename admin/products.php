<?php
/**
 * Admin Products Management
 * View, approve, and reject products
 */

$pageTitle = 'Products';
$currentPage = 'products';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Please login as admin to access this page');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

// Get filter parameters
$filterStatus = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$filterCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$reviewProductId = isset($_GET['review']) ? (int)$_GET['review'] : 0;

// Build query
$whereConditions = ["1=1"];
$params = [];
$types = '';

if ($filterStatus && in_array($filterStatus, ['pending', 'approved', 'rejected'])) {
    $whereConditions[] = "fp.status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

if ($filterCategory) {
    $whereConditions[] = "fp.category_id = ?";
    $params[] = $filterCategory;
    $types .= 'i';
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
    SELECT fp.*, c.category_name, u.First_name, u.Last_name, u.Email, u.Phone
    FROM farm_product fp
    JOIN category c ON fp.category_id = c.category_id
    JOIN user u ON fp.farmer_id = u.User_id
    WHERE $whereClause
    ORDER BY
        CASE fp.status
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'rejected' THEN 3
        END,
        fp.product_id DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $products = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
}

// Get product for review modal if specified
$reviewProduct = null;
if ($reviewProductId) {
    $stmt = $conn->prepare("
        SELECT fp.*, c.category_name, u.First_name, u.Last_name, u.Email, u.Phone,
               gp.price_per_unit as govt_price
        FROM farm_product fp
        JOIN category c ON fp.category_id = c.category_id
        JOIN user u ON fp.farmer_id = u.User_id
        LEFT JOIN government_prices gp ON LOWER(fp.product_name) = LOWER(gp.product_name)
        WHERE fp.product_id = ?
    ");
    $stmt->bind_param("i", $reviewProductId);
    $stmt->execute();
    $reviewProduct = $stmt->get_result()->fetch_assoc();
}

// Get counts for filter badges
$totalResult = $conn->query("SELECT COUNT(*) as count FROM farm_product");
$totalProducts = $totalResult ? $totalResult->fetch_assoc()['count'] : 0;
$pendingResult = $conn->query("SELECT COUNT(*) as count FROM farm_product WHERE status = 'pending'");
$pendingProducts = $pendingResult ? $pendingResult->fetch_assoc()['count'] : 0;
$approvedResult = $conn->query("SELECT COUNT(*) as count FROM farm_product WHERE status = 'approved'");
$approvedProducts = $approvedResult ? $approvedResult->fetch_assoc()['count'] : 0;
$rejectedResult = $conn->query("SELECT COUNT(*) as count FROM farm_product WHERE status = 'rejected'");
$rejectedProducts = $rejectedResult ? $rejectedResult->fetch_assoc()['count'] : 0;

// Get categories for filter dropdown
$categories = getAllCategories($conn);

// Include sidebar
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 class="page-title">Product Management</h1>
            <p class="page-subtitle">Review and manage product listings</p>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="card mb-4">
    <div class="card-body" style="padding: 12px 16px;">
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <a href="products.php" class="filter-tab <?php echo !$filterStatus ? 'active' : ''; ?>">
                All <span class="filter-count"><?php echo $totalProducts; ?></span>
            </a>
            <a href="products.php?status=pending" class="filter-tab pending <?php echo $filterStatus === 'pending' ? 'active' : ''; ?>">
                Pending <span class="filter-count"><?php echo $pendingProducts; ?></span>
            </a>
            <a href="products.php?status=approved" class="filter-tab approved <?php echo $filterStatus === 'approved' ? 'active' : ''; ?>">
                Approved <span class="filter-count"><?php echo $approvedProducts; ?></span>
            </a>
            <a href="products.php?status=rejected" class="filter-tab rejected <?php echo $filterStatus === 'rejected' ? 'active' : ''; ?>">
                Rejected <span class="filter-count"><?php echo $rejectedProducts; ?></span>
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

                <form method="GET" action="products.php" style="display: flex; gap: 8px;">
                    <?php if ($filterStatus): ?>
                        <input type="hidden" name="status" value="<?php echo $filterStatus; ?>">
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

<!-- Products Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <?php
            if ($filterStatus === 'pending') echo 'Pending Products';
            elseif ($filterStatus === 'approved') echo 'Approved Products';
            elseif ($filterStatus === 'rejected') echo 'Rejected Products';
            else echo 'All Products';
            ?>
        </h3>
        <span class="text-muted"><?php echo count($products); ?> products found</span>
    </div>

    <?php if (empty($products)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-box"></i>
                </div>
                <h4 class="empty-state-title">No products found</h4>
                <p class="empty-state-text">
                    <?php if ($filterStatus === 'pending'): ?>
                        No products are waiting for approval.
                    <?php else: ?>
                        No products match your filter criteria.
                    <?php endif; ?>
                </p>
                <a href="products.php" class="btn btn-primary">View All Products</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Farmer</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if ($product['product_image']): ?>
                                        <img src="<?php echo getProductImageUrl($product['product_image']); ?>" alt="" style="width: 48px; height: 48px; object-fit: cover; border-radius: var(--radius);">
                                    <?php else: ?>
                                        <div style="width: 48px; height: 48px; background: var(--gray-100); border-radius: var(--radius); display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo sanitize($product['product_name']); ?></strong>
                                        <div class="text-muted text-sm">ID: #<?php echo $product['product_id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php echo sanitize($product['First_name'] . ' ' . $product['Last_name']); ?>
                            </td>
                            <td>
                                <span class="badge badge-category"><?php echo sanitize($product['category_name']); ?></span>
                            </td>
                            <td>
                                <?php echo formatCurrency($product['price_per_unit']); ?>/<?php echo sanitize($product['unit']); ?>
                            </td>
                            <td>
                                <?php echo number_format($product['quantity'] ?? 0); ?> <?php echo sanitize($product['unit'] ?? ''); ?>
                            </td>
                            <td>
                                <?php echo getStatusBadge($product['status']); ?>
                                <?php if ($product['status'] === 'rejected' && $product['rejection_reason']): ?>
                                    <div class="text-muted text-sm" title="<?php echo sanitize($product['rejection_reason']); ?>">
                                        <i class="fas fa-info-circle"></i> <?php echo truncateText($product['rejection_reason'], 20); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a href="products.php?review=<?php echo $product['product_id']; ?><?php echo $filterStatus ? '&status='.$filterStatus : ''; ?>" class="btn btn-sm btn-outline" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($product['status'] === 'pending'): ?>
                                        <form method="POST" action="product_action.php" style="display: inline;">
                                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-danger" title="Reject" onclick="showRejectModal(<?php echo $product['product_id']; ?>, '<?php echo sanitize($product['product_name']); ?>')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php elseif ($product['status'] === 'approved'): ?>
                                        <form method="POST" action="product_action.php" style="display: inline;">
                                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                            <input type="hidden" name="action" value="pending">
                                            <button type="submit" class="btn btn-sm btn-warning" title="Set Pending">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </form>
                                    <?php elseif ($product['status'] === 'rejected'): ?>
                                        <form method="POST" action="product_action.php" style="display: inline;">
                                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Review Modal -->
<?php if ($reviewProduct): ?>
<div id="reviewModal" class="modal" style="display: flex;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3 class="modal-title">Product Review</h3>
            <a href="products.php<?php echo $filterStatus ? '?status='.$filterStatus : ''; ?>" class="modal-close">&times;</a>
        </div>
        <div class="modal-body">
            <div style="display: grid; grid-template-columns: 200px 1fr; gap: 24px;">
                <!-- Product Image -->
                <div>
                    <?php if ($reviewProduct['product_image']): ?>
                        <img src="<?php echo getProductImageUrl($reviewProduct['product_image']); ?>" alt="" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: var(--radius);">
                    <?php else: ?>
                        <div style="width: 100%; aspect-ratio: 1; background: var(--gray-100); border-radius: var(--radius); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-image fa-3x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Product Details -->
                <div>
                    <div style="margin-bottom: 16px;">
                        <h2 style="margin: 0 0 8px;"><?php echo sanitize($reviewProduct['product_name']); ?></h2>
                        <span class="badge badge-category"><?php echo sanitize($reviewProduct['category_name']); ?></span>
                        <?php echo getStatusBadge($reviewProduct['status']); ?>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Price</span>
                        <span class="detail-value">
                            <?php echo formatCurrency($reviewProduct['price_per_unit']); ?>/<?php echo sanitize($reviewProduct['unit']); ?>
                            <?php if ($reviewProduct['govt_price']): ?>
                                <span class="text-muted text-sm">(Govt: <?php echo formatCurrency($reviewProduct['govt_price']); ?>)</span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Quantity</span>
                        <span class="detail-value"><?php echo number_format($reviewProduct['quantity']); ?> <?php echo sanitize($reviewProduct['unit']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Farmer</span>
                        <span class="detail-value">
                            <?php echo sanitize($reviewProduct['First_name'] . ' ' . $reviewProduct['Last_name']); ?>
                            <div class="text-muted text-sm"><?php echo sanitize($reviewProduct['Email']); ?></div>
                        </span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Product ID</span>
                        <span class="detail-value">#<?php echo $reviewProduct['product_id']; ?></span>
                    </div>

                    <?php if ($reviewProduct['description']): ?>
                        <div style="margin-top: 16px;">
                            <strong>Description:</strong>
                            <p class="text-muted" style="margin-top: 8px;"><?php echo nl2br(sanitize($reviewProduct['description'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($reviewProduct['status'] === 'rejected' && $reviewProduct['rejection_reason']): ?>
                        <div class="alert alert-error" style="margin-top: 16px;">
                            <strong>Rejection Reason:</strong> <?php echo sanitize($reviewProduct['rejection_reason']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--gray-200); display: flex; gap: 12px; justify-content: flex-end;">
                <a href="products.php<?php echo $filterStatus ? '?status='.$filterStatus : ''; ?>" class="btn btn-secondary">Close</a>
                <?php if ($reviewProduct['status'] === 'pending'): ?>
                    <form method="POST" action="product_action.php" style="display: inline;">
                        <input type="hidden" name="product_id" value="<?php echo $reviewProduct['product_id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="redirect" value="products.php<?php echo $filterStatus ? '?status='.$filterStatus : ''; ?>">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Approve Product
                        </button>
                    </form>
                    <button type="button" class="btn btn-danger" onclick="showRejectModal(<?php echo $reviewProduct['product_id']; ?>, '<?php echo sanitize($reviewProduct['product_name']); ?>')">
                        <i class="fas fa-times"></i> Reject Product
                    </button>
                <?php elseif ($reviewProduct['status'] === 'rejected'): ?>
                    <form method="POST" action="product_action.php" style="display: inline;">
                        <input type="hidden" name="product_id" value="<?php echo $reviewProduct['product_id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="redirect" value="products.php<?php echo $filterStatus ? '?status='.$filterStatus : ''; ?>">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Approve Product
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Reject Modal -->
<div id="rejectModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title">Reject Product</h3>
            <button type="button" class="modal-close" onclick="closeRejectModal()">&times;</button>
        </div>
        <form method="POST" action="product_action.php">
            <div class="modal-body">
                <p>You are about to reject: <strong id="rejectProductName"></strong></p>
                <input type="hidden" name="product_id" id="rejectProductId">
                <input type="hidden" name="action" value="reject">

                <div class="form-group" style="margin-top: 16px;">
                    <label for="rejection_reason" class="form-label">Reason for rejection <span class="text-error">*</span></label>
                    <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="4" required placeholder="Please provide a reason for rejecting this product..."></textarea>
                </div>
            </div>
            <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid var(--gray-200); display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-times"></i> Reject Product
                </button>
            </div>
        </form>
    </div>
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

.filter-tab.pending.active {
    background: var(--warning);
}

.filter-tab.approved.active {
    background: var(--success);
}

.filter-tab.rejected.active {
    background: var(--error);
}

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

.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: var(--radius-lg);
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: var(--shadow-xl);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    border-bottom: 1px solid var(--gray-200);
}

.modal-title {
    margin: 0;
    font-size: 1.25rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--gray-500);
    text-decoration: none;
}

.modal-body {
    padding: 24px;
    overflow-y: auto;
    max-height: calc(90vh - 130px);
}

.detail-row {
    display: flex;
    padding: 12px 0;
    border-bottom: 1px solid var(--gray-100);
}

.detail-label {
    width: 100px;
    color: var(--gray-500);
    font-size: 0.875rem;
}

.detail-value {
    flex: 1;
    font-weight: 500;
}

.btn-success {
    background: var(--success);
    color: white;
    border: none;
}

.btn-success:hover {
    background: #059669;
}

.btn-danger {
    background: var(--error);
    color: white;
    border: none;
}

.btn-danger:hover {
    background: #DC2626;
}

.btn-warning {
    background: var(--warning);
    color: white;
    border: none;
}

.btn-warning:hover {
    background: #D97706;
}
</style>

<script>
function filterByCategory(categoryId) {
    let url = 'products.php';
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

function showRejectModal(productId, productName) {
    document.getElementById('rejectProductId').value = productId;
    document.getElementById('rejectProductName').textContent = productName;
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

// Close modals when clicking outside
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            // If it's the review modal, redirect
            if (this.id === 'reviewModal') {
                window.location.href = 'products.php<?php echo $filterStatus ? "?status=$filterStatus" : ""; ?>';
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
