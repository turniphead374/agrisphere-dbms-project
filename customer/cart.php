<?php
/**
 * Customer - Shopping Cart
 * View and manage cart items
 */

$pageTitle = 'Shopping Cart';
$currentPage = 'cart';

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

// Get cart items with product and farmer details
$stmt = $conn->prepare("
    SELECT cc.*, fp.product_name, fp.price_per_unit, fp.unit, fp.product_image,
           COALESCE(fp.quantity, 0) as stock, fp.description,
           CONCAT(u.First_name, ' ', u.Last_name) as farmer_name,
           c.category_name
    FROM customer_cart cc
    JOIN farm_product fp ON cc.product_id = fp.product_id
    JOIN user u ON fp.farmer_id = u.User_id
    JOIN category c ON fp.category_id = c.category_id
    WHERE cc.customer_id = ? AND fp.status = 'approved'
    ORDER BY cc.cart_id DESC
");
if ($stmt === false) {
    $cartItems = [];
} else {
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Calculate totals
$subtotal = 0;
$totalItems = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price_per_unit'] * $item['quantity'];
    $totalItems += $item['quantity'];
}

// Include sidebar
include __DIR__ . '/../includes/sidebar_customer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 class="page-title">Shopping Cart</h1>
            <p class="page-subtitle"><?php echo $totalItems; ?> item(s) in your cart</p>
        </div>
        <a href="products.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Continue Shopping
        </a>
    </div>
</div>

<?php if (empty($cartItems)): ?>
    <!-- Empty Cart -->
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h4 class="empty-state-title">Your cart is empty</h4>
                <p class="empty-state-text">Looks like you haven't added any products yet. Browse our fresh products from local farmers.</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-seedling"></i> Browse Products
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="cart-layout">
        <!-- Cart Items -->
        <div class="cart-items-section">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Cart Items</h3>
                    <form action="cart_actions.php" method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-sm btn-outline" onclick="return confirm('Are you sure you want to clear your cart?')">
                            <i class="fas fa-trash"></i> Clear Cart
                        </button>
                    </form>
                </div>
                <div class="cart-items">
                    <?php foreach ($cartItems as $item):
                        $lineTotal = $item['price_per_unit'] * $item['quantity'];
                        $maxStock = (int)$item['stock'];
                    ?>
                        <div class="cart-item">
                            <div class="cart-item-image">
                                <?php if ($item['product_image']): ?>
                                    <img src="<?php echo getProductImageUrl($item['product_image']); ?>" alt="">
                                <?php else: ?>
                                    <div class="cart-item-image-placeholder">
                                        <i class="fas fa-leaf"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="cart-item-details">
                                <div class="cart-item-header">
                                    <div>
                                        <h4 class="cart-item-name"><?php echo sanitize($item['product_name']); ?></h4>
                                        <p class="cart-item-meta">
                                            <span class="cart-item-category"><?php echo sanitize($item['category_name']); ?></span>
                                            <span class="cart-item-farmer"><i class="fas fa-user"></i> <?php echo sanitize($item['farmer_name']); ?></span>
                                        </p>
                                    </div>
                                    <form action="cart_actions.php" method="POST">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                        <button type="submit" class="btn-remove" title="Remove item">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                                <div class="cart-item-price-row">
                                    <div class="cart-item-unit-price">
                                        <?php echo formatCurrency($item['price_per_unit']); ?> / <?php echo sanitize($item['unit']); ?>
                                    </div>
                                    <form action="cart_actions.php" method="POST" class="cart-quantity-form">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                        <div class="quantity-selector">
                                            <button type="button" class="qty-btn minus" onclick="updateQuantity(this, -1, <?php echo $maxStock; ?>)">-</button>
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>"
                                                   min="1" max="<?php echo $maxStock; ?>" class="qty-input"
                                                   onchange="this.form.submit()">
                                            <button type="button" class="qty-btn plus" onclick="updateQuantity(this, 1, <?php echo $maxStock; ?>)">+</button>
                                        </div>
                                    </form>
                                    <div class="cart-item-total">
                                        <?php echo formatCurrency($lineTotal); ?>
                                    </div>
                                </div>
                                <?php if ($item['quantity'] > $maxStock): ?>
                                    <div class="cart-item-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Only <?php echo $maxStock; ?> available in stock
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="cart-summary-section">
            <div class="card cart-summary-card">
                <div class="card-header">
                    <h3 class="card-title">Order Summary</h3>
                </div>
                <div class="card-body">
                    <div class="summary-row">
                        <span>Subtotal (<?php echo $totalItems; ?> items)</span>
                        <span><?php echo formatCurrency($subtotal); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery</span>
                        <span class="text-success">FREE</span>
                    </div>
                    <hr style="margin: 16px 0; border: none; border-top: 1px solid var(--gray-200);">
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span><?php echo formatCurrency($subtotal); ?></span>
                    </div>

                    <a href="checkout.php" class="btn btn-primary btn-checkout">
                        <i class="fas fa-lock"></i> Proceed to Checkout
                    </a>

                    <div class="cart-benefits">
                        <div class="cart-benefit">
                            <i class="fas fa-truck"></i>
                            <span>Free delivery on all orders</span>
                        </div>
                        <div class="cart-benefit">
                            <i class="fas fa-leaf"></i>
                            <span>Fresh from local farmers</span>
                        </div>
                        <div class="cart-benefit">
                            <i class="fas fa-shield-alt"></i>
                            <span>Secure checkout</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.cart-layout {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 24px;
    align-items: start;
}

.cart-items {
    padding: 0;
}

.cart-item {
    display: flex;
    gap: 20px;
    padding: 20px;
    border-bottom: 1px solid var(--gray-100);
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item-image {
    width: 100px;
    height: 100px;
    border-radius: var(--radius);
    overflow: hidden;
    flex-shrink: 0;
    background: var(--gray-100);
}

.cart-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cart-item-image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--gray-300);
}

.cart-item-details {
    flex: 1;
    min-width: 0;
}

.cart-item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.cart-item-name {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 4px 0;
    color: var(--gray-800);
}

.cart-item-meta {
    display: flex;
    gap: 16px;
    font-size: 0.875rem;
    color: var(--gray-500);
    margin: 0;
}

.cart-item-category {
    background: var(--primary-50);
    color: var(--primary);
    padding: 2px 8px;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 500;
}

.cart-item-farmer i {
    margin-right: 4px;
}

.btn-remove {
    background: none;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-400);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.btn-remove:hover {
    background: var(--error-light);
    color: var(--error);
}

.cart-item-price-row {
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}

.cart-item-unit-price {
    color: var(--gray-600);
    font-size: 0.875rem;
    min-width: 120px;
}

.cart-quantity-form {
    display: inline-block;
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

.cart-item-total {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--primary);
    margin-left: auto;
}

.cart-item-warning {
    margin-top: 8px;
    padding: 8px 12px;
    background: var(--warning-light);
    color: var(--warning);
    border-radius: var(--radius-sm);
    font-size: 0.875rem;
}

.cart-summary-card {
    position: sticky;
    top: 100px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 0.9375rem;
}

.summary-total {
    font-size: 1.25rem;
    font-weight: 700;
}

.summary-total span:last-child {
    color: var(--primary);
}

.text-success {
    color: var(--success);
    font-weight: 600;
}

.btn-checkout {
    width: 100%;
    padding: 14px;
    font-size: 1rem;
    margin-top: 20px;
}

.cart-benefits {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid var(--gray-100);
}

.cart-benefit {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    font-size: 0.875rem;
    color: var(--gray-600);
}

.cart-benefit i {
    color: var(--primary);
    width: 20px;
    text-align: center;
}

@media (max-width: 992px) {
    .cart-layout {
        grid-template-columns: 1fr;
    }

    .cart-summary-card {
        position: static;
    }
}

@media (max-width: 576px) {
    .cart-item {
        flex-direction: column;
    }

    .cart-item-image {
        width: 100%;
        height: 150px;
    }

    .cart-item-price-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .cart-item-total {
        margin-left: 0;
    }
}
</style>

<script>
function updateQuantity(btn, change, max) {
    const form = btn.closest('form');
    const input = form.querySelector('.qty-input');
    let currentVal = parseInt(input.value) || 1;
    let newVal = currentVal + change;

    if (newVal < 1) newVal = 1;
    if (newVal > max) newVal = max;

    if (newVal !== currentVal) {
        input.value = newVal;
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
