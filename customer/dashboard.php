<?php
/**
 * Customer Dashboard
 */

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is a customer
if (!isLoggedIn() || !isCustomer()) {
    setFlashMessage('error', 'Please login as a customer to access this page');
    redirect(BASE_URL . 'auth/login.php?role=customer');
}

// Check if user is blocked
if (isUserBlocked($conn, getCurrentUserId())) {
    session_destroy();
    setFlashMessage('error', 'Your account has been blocked. Please contact support.');
    redirect(BASE_URL . 'auth/login.php');
}

$customerId = getCurrentUserId();

// Get customer stats
$totalOrders = 0;
$cartItems = 0;

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM customer_order WHERE customer_id = ?");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$totalOrders = $stmt->get_result()->fetch_assoc()['count'];

$cartItems = getCartCount($conn, $customerId);

// Get featured/recent products (approved only)
$featuredProducts = $conn->query("
    SELECT fp.*, c.category_name, fi.quantity_available,
           CONCAT(u.First_name, ' ', u.Last_name) as farmer_name
    FROM farm_product fp
    JOIN category c ON fp.category_id = c.category_id
    JOIN farmer f ON fp.farmer_id = f.farmer_id
    JOIN user u ON f.farmer_id = u.User_id
    LEFT JOIN farm_inventory fi ON fp.product_id = fi.product_id
    WHERE fp.status = 'approved' AND (fi.quantity_available > 0 OR fi.quantity_available IS NULL)
    ORDER BY fp.product_id DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// Get categories
$categories = getAllCategories($conn);

// Include sidebar
include __DIR__ . '/../includes/sidebar_customer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Welcome, <?php echo sanitize(getCurrentUserName()); ?>!</h1>
    <p class="page-subtitle">Browse fresh products directly from local farmers.</p>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
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
        <div class="stat-icon green">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $cartItems; ?></div>
            <div class="stat-label">Items in Cart</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Quick Actions</h3>
    </div>
    <div class="card-body" style="display: flex; gap: 16px; flex-wrap: wrap;">
        <a href="products.php" class="btn btn-primary">
            <i class="fas fa-store"></i> Browse All Products
        </a>
        <a href="cart.php" class="btn btn-secondary">
            <i class="fas fa-shopping-cart"></i> View Cart (<?php echo $cartItems; ?>)
        </a>
        <a href="orders.php" class="btn btn-outline">
            <i class="fas fa-clipboard-list"></i> My Orders
        </a>
    </div>
</div>

<!-- Category Pills -->
<div class="category-pills">
    <a href="products.php" class="category-pill active">All Products</a>
    <?php foreach ($categories as $category): ?>
        <a href="products.php?category=<?php echo $category['category_id']; ?>" class="category-pill">
            <?php echo sanitize($category['category_name']); ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Featured Products -->
<h3 style="margin-bottom: 20px; color: var(--gray-800);">Fresh Products</h3>

<?php if (empty($featuredProducts)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-store"></i>
                </div>
                <h4 class="empty-state-title">No products available</h4>
                <p class="empty-state-text">Check back later for fresh products from our farmers.</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="products-grid">
        <?php foreach ($featuredProducts as $product): ?>
            <div class="product-card">
                <?php if (!empty($product['product_image'])): ?>
                    <img src="<?php echo getProductImageUrl($product['product_image']); ?>" alt="<?php echo sanitize($product['product_name']); ?>" class="product-image">
                <?php else: ?>
                    <div class="product-image" style="display: flex; align-items: center; justify-content: center; background: var(--gray-100); color: var(--gray-400);">
                        <i class="fas fa-seedling" style="font-size: 3rem;"></i>
                    </div>
                <?php endif; ?>

                <div class="product-body">
                    <div class="product-category"><?php echo sanitize($product['category_name']); ?></div>
                    <h4 class="product-name"><?php echo sanitize($product['product_name']); ?></h4>
                    <div class="product-price"><?php echo formatCurrency($product['price_per_unit']); ?>/<?php echo sanitize($product['unit']); ?></div>
                    <div class="product-farmer">
                        <i class="fas fa-user"></i> <?php echo sanitize($product['farmer_name']); ?>
                    </div>
                    <div class="product-footer">
                        <form action="cart_actions.php" method="POST" style="width: 100%;">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="margin-top: 24px; text-align: center;">
        <a href="products.php" class="btn btn-outline btn-lg">View All Products</a>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
