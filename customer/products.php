<?php
/**
 * Customer - Browse Products
 * View and add products to cart
 */

$pageTitle = 'Browse Products';
$currentPage = 'products';

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

// Get filter parameters
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'newest';

// Build query
$whereConditions = ["fp.status = 'approved'"];
$params = [];
$types = '';

if ($category > 0) {
    $whereConditions[] = "fp.category_id = ?";
    $params[] = $category;
    $types .= 'i';
}

if ($search) {
    $whereConditions[] = "(fp.product_name LIKE ? OR fp.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

$whereClause = implode(' AND ', $whereConditions);

// Sort options
$orderBy = match($sort) {
    'price_low' => 'fp.price_per_unit ASC',
    'price_high' => 'fp.price_per_unit DESC',
    'name' => 'fp.product_name ASC',
    default => 'fp.product_id DESC'
};

$query = "
    SELECT fp.*, c.category_name,
           CONCAT(u.First_name, ' ', u.Last_name) as farmer_name,
           COALESCE(fi.quantity_available, fp.quantity, 0) as stock
    FROM farm_product fp
    JOIN category c ON c.category_id = fp.category_id
    JOIN user u ON fp.farmer_id = u.User_id
    LEFT JOIN farm_inventory fi ON fi.product_id = fp.product_id
    WHERE $whereClause
    ORDER BY $orderBy
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

// Get categories for filter
$categoriesResult = $conn->query("SELECT * FROM category ORDER BY category_name");
$categories = $categoriesResult ? $categoriesResult->fetch_all(MYSQLI_ASSOC) : [];

// Get cart count
$cartCount = getCartCount($conn, $customerId);

// Include sidebar
include __DIR__ . '/../includes/sidebar_customer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 class="page-title">Browse Products</h1>
            <p class="page-subtitle">Fresh products directly from local farmers</p>
        </div>
        <a href="cart.php" class="btn btn-primary">
            <i class="fas fa-shopping-cart"></i> Cart
            <?php if ($cartCount > 0): ?>
                <span class="cart-badge"><?php echo $cartCount; ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body" style="padding: 16px;">
        <form method="GET" action="" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 200px;">
                <input type="text" name="search" placeholder="Search products..."
                       value="<?php echo $search; ?>" class="form-control">
            </div>
            <select name="category" class="form-control" style="width: auto; min-width: 150px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>" <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                        <?php echo sanitize($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="sort" class="form-control" style="width: auto; min-width: 150px;">
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
            <?php if ($search || $category || $sort !== 'newest'): ?>
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Products Grid -->
<?php if (empty($products)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-seedling"></i>
                </div>
                <h4 class="empty-state-title">No products found</h4>
                <p class="empty-state-text">
                    <?php if ($search): ?>
                        No products match your search "<?php echo $search; ?>".
                    <?php else: ?>
                        There are no products available in this category.
                    <?php endif; ?>
                </p>
                <a href="products.php" class="btn btn-primary">View All Products</a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="products-grid">
        <?php foreach ($products as $product):
            $stock = (int)$product['stock'];
            $inStock = $stock > 0;
        ?>
            <div class="product-card">
                <div class="product-image">
                    <?php if ($product['product_image']): ?>
                        <img src="<?php echo getProductImageUrl($product['product_image']); ?>" alt="<?php echo sanitize($product['product_name']); ?>">
                    <?php else: ?>
                        <div class="product-image-placeholder">
                            <i class="fas fa-leaf"></i>
                        </div>
                    <?php endif; ?>
                    <span class="product-category"><?php echo sanitize($product['category_name']); ?></span>
                    <?php if (!$inStock): ?>
                        <div class="out-of-stock-overlay">Out of Stock</div>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h3 class="product-name"><?php echo sanitize($product['product_name']); ?></h3>
                    <p class="product-farmer">
                        <i class="fas fa-user"></i> <?php echo sanitize($product['farmer_name']); ?>
                    </p>
                    <?php if ($product['description']): ?>
                        <p class="product-description"><?php echo sanitize($product['description']); ?></p>
                    <?php endif; ?>
                    <div class="product-meta">
                        <span class="product-price"><?php echo formatCurrency($product['price_per_unit']); ?></span>
                        <span class="product-unit">/ <?php echo sanitize($product['unit']); ?></span>
                    </div>
                    <div class="product-stock <?php echo $inStock ? 'in-stock' : 'no-stock'; ?>">
                        <?php if ($inStock): ?>
                            <i class="fas fa-check-circle"></i> <?php echo $stock; ?> in stock
                        <?php else: ?>
                            <i class="fas fa-times-circle"></i> Out of stock
                        <?php endif; ?>
                    </div>
                    <?php if ($inStock): ?>
                        <form action="cart_actions.php" method="POST" class="add-to-cart-form">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                            <div class="quantity-selector">
                                <button type="button" class="qty-btn minus" onclick="decrementQty(this)">-</button>
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $stock; ?>" class="qty-input">
                                <button type="button" class="qty-btn plus" onclick="incrementQty(this, <?php echo $stock; ?>)">+</button>
                            </div>
                            <button type="submit" class="btn btn-primary btn-add-cart">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.cart-badge {
    background: white;
    color: var(--primary);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: var(--radius-full);
    margin-left: 8px;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}

.product-card {
    background: white;
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: all var(--transition);
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.product-image {
    position: relative;
    height: 200px;
    background: var(--gray-100);
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: var(--gray-300);
}

.product-category {
    position: absolute;
    top: 12px;
    left: 12px;
    background: white;
    padding: 4px 12px;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--primary);
    box-shadow: var(--shadow-sm);
}

.out-of-stock-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.25rem;
}

.product-info {
    padding: 20px;
}

.product-name {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--gray-800);
}

.product-farmer {
    font-size: 0.875rem;
    color: var(--gray-500);
    margin-bottom: 8px;
}

.product-farmer i {
    margin-right: 4px;
}

.product-description {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 12px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-meta {
    margin-bottom: 8px;
}

.product-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
}

.product-unit {
    font-size: 0.875rem;
    color: var(--gray-500);
}

.product-stock {
    font-size: 0.875rem;
    margin-bottom: 16px;
}

.product-stock.in-stock {
    color: var(--success);
}

.product-stock.no-stock {
    color: var(--error);
}

.add-to-cart-form {
    display: flex;
    gap: 12px;
    align-items: center;
}

.quantity-selector {
    display: flex;
    align-items: center;
    background: var(--gray-100);
    border-radius: var(--radius);
    overflow: hidden;
}

.qty-btn {
    width: 36px;
    height: 36px;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 1.25rem;
    color: var(--gray-600);
    transition: all var(--transition-fast);
}

.qty-btn:hover {
    background: var(--gray-200);
    color: var(--gray-800);
}

.qty-input {
    width: 50px;
    text-align: center;
    border: none;
    background: none;
    font-size: 1rem;
    font-weight: 600;
}

.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.btn-add-cart {
    flex: 1;
    padding: 10px 16px;
}

@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    }

    .add-to-cart-form {
        flex-direction: column;
    }

    .btn-add-cart {
        width: 100%;
    }
}
</style>

<script>
function decrementQty(btn) {
    const input = btn.parentElement.querySelector('.qty-input');
    const currentVal = parseInt(input.value) || 1;
    if (currentVal > 1) {
        input.value = currentVal - 1;
    }
}

function incrementQty(btn, max) {
    const input = btn.parentElement.querySelector('.qty-input');
    const currentVal = parseInt(input.value) || 1;
    if (currentVal < max) {
        input.value = currentVal + 1;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
