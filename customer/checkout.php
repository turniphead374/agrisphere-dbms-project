<?php
/**
 * Customer - Checkout
 */

$pageTitle = 'Checkout';
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

// Get cart items
$stmt = $conn->prepare("
    SELECT cc.*, fp.product_name, fp.price_per_unit, fp.unit, fp.product_image,
           COALESCE(fp.quantity, fi.quantity_available, 0) as stock,
           CONCAT(u.First_name, ' ', u.Last_name) as farmer_name
    FROM customer_cart cc
    JOIN farm_product fp ON cc.product_id = fp.product_id
    JOIN user u ON fp.farmer_id = u.User_id
    LEFT JOIN farm_inventory fi ON fp.product_id = fi.product_id
    WHERE cc.customer_id = ? AND fp.status = 'approved'
");
if ($stmt === false) {
    setFlashMessage('error', 'Database error. Please try again.');
    redirect(BASE_URL . 'customer/cart.php');
}
$stmt->bind_param("i", $customerId);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cartItems)) {
    setFlashMessage('error', 'Your cart is empty');
    redirect(BASE_URL . 'customer/cart.php');
}

// Calculate total
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price_per_unit'] * $item['quantity'];
}

// Get customer info
$stmt = $conn->prepare("SELECT u.*, c.address, c.phone as customer_phone FROM user u LEFT JOIN customer c ON u.User_id = c.customer_id WHERE u.User_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
} else {
    $customer = ['First_name' => '', 'Last_name' => '', 'Phone' => '', 'address' => ''];
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $deliveryAddress = sanitize($_POST['delivery_address']);
    $deliveryPhone = sanitize($_POST['delivery_phone']);
    $paymentMethod = sanitize($_POST['payment_method']);
    $bkashNumber = isset($_POST['bkash_number']) ? sanitize($_POST['bkash_number']) : '';

    if (empty($deliveryAddress) || empty($deliveryPhone)) {
        setFlashMessage('error', 'Please fill in all delivery information');
    } elseif ($paymentMethod === 'bkash' && empty($bkashNumber)) {
        setFlashMessage('error', 'Please enter your bKash number');
    } else {
        $conn->begin_transaction();

        try {
            // Verify stock and calculate total
            $total = 0;
            $orderItems = [];

            foreach ($cartItems as $item) {
                // Check stock - use COALESCE to handle missing quantity column
                $stmt = $conn->prepare("SELECT COALESCE(fp.quantity, fi.quantity_available, 0) as quantity FROM farm_product fp LEFT JOIN farm_inventory fi ON fp.product_id = fi.product_id WHERE fp.product_id = ? FOR UPDATE");
                if (!$stmt) {
                    throw new Exception("Database error");
                }
                $stmt->bind_param("i", $item['product_id']);
                $stmt->execute();
                $stock = $stmt->get_result()->fetch_assoc();

                if (!$stock || $stock['quantity'] < $item['quantity']) {
                    throw new Exception("Not enough stock for " . $item['product_name']);
                }

                $total += $item['price_per_unit'] * $item['quantity'];
                $orderItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price_per_unit' => $item['price_per_unit']
                ];
            }

            // Create order
            $stmt = $conn->prepare("INSERT INTO customer_order (customer_id, order_date, total_amount, status) VALUES (?, NOW(), ?, 'Processing')");
            $stmt->bind_param("id", $customerId, $total);
            $stmt->execute();
            $orderId = $conn->insert_id;

            // Insert order items and update stock
            foreach ($orderItems as $item) {
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_per_unit) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price_per_unit']);
                $stmt->execute();

                // Update product stock
                $stmt = $conn->prepare("UPDATE farm_product SET quantity = quantity - ? WHERE product_id = ?");
                $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $stmt->execute();

                // Update inventory if exists
                $stmt = $conn->prepare("UPDATE farm_inventory SET quantity_available = quantity_available - ? WHERE product_id = ?");
                $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $stmt->execute();
            }

            // Insert payment record
            $paymentStatus = ($paymentMethod === 'cod') ? 'Pending' : 'Paid';
            $stmt = $conn->prepare("INSERT INTO payment (order_id, payment_method, bkash_number, payment_status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $orderId, $paymentMethod, $bkashNumber, $paymentStatus);
            $stmt->execute();

            // Update customer address if provided
            $stmt = $conn->prepare("INSERT INTO customer (customer_id, address, phone) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE address = ?, phone = ?");
            $stmt->bind_param("issss", $customerId, $deliveryAddress, $deliveryPhone, $deliveryAddress, $deliveryPhone);
            $stmt->execute();

            // Clear cart
            $stmt = $conn->prepare("DELETE FROM customer_cart WHERE customer_id = ?");
            $stmt->bind_param("i", $customerId);
            $stmt->execute();

            $conn->commit();

            setFlashMessage('success', 'Order placed successfully! Order ID: #' . $orderId);
            redirect(BASE_URL . 'customer/order_detail.php?id=' . $orderId);

        } catch (Exception $e) {
            $conn->rollback();
            setFlashMessage('error', 'Checkout failed: ' . $e->getMessage());
        }
    }
}

// Include sidebar
include __DIR__ . '/../includes/sidebar_customer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Checkout</h1>
    <p class="page-subtitle">Complete your order</p>
</div>

<form method="POST" action="">
    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px;">
        <!-- Left Column - Delivery & Payment -->
        <div>
            <!-- Delivery Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-truck"></i> Delivery Information</h3>
                </div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label" for="delivery_name">Full Name</label>
                        <input type="text" id="delivery_name" class="form-control"
                               value="<?php echo sanitize($customer['First_name'] . ' ' . $customer['Last_name']); ?>" disabled style="background: var(--gray-100);">
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label" for="delivery_phone">Phone Number <span style="color: var(--error);">*</span></label>
                        <input type="tel" id="delivery_phone" name="delivery_phone" class="form-control"
                               value="<?php echo sanitize($customer['customer_phone'] ?? $customer['Phone'] ?? ''); ?>" required
                               placeholder="+880 1XXXXXXXXX">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="delivery_address">Delivery Address <span style="color: var(--error);">*</span></label>
                        <textarea id="delivery_address" name="delivery_address" class="form-control" rows="3" required
                                  placeholder="Enter your full delivery address"><?php echo sanitize($customer['address'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-credit-card"></i> Payment Method</h3>
                </div>
                <div class="card-body">
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="bkash" checked onchange="toggleBkashField()">
                            <div class="payment-option-content">
                                <div class="payment-icon" style="background: #E2136E;">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div>
                                    <strong>bKash</strong>
                                    <div class="text-muted text-sm">Pay with your bKash account</div>
                                </div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="nagad" onchange="toggleBkashField()">
                            <div class="payment-option-content">
                                <div class="payment-icon" style="background: #F6921E;">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div>
                                    <strong>Nagad</strong>
                                    <div class="text-muted text-sm">Pay with your Nagad account</div>
                                </div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cod" onchange="toggleBkashField()">
                            <div class="payment-option-content">
                                <div class="payment-icon" style="background: var(--success);">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <strong>Cash on Delivery</strong>
                                    <div class="text-muted text-sm">Pay when you receive your order</div>
                                </div>
                            </div>
                        </label>
                    </div>

                    <div id="bkashField" class="form-group" style="margin-top: 16px;">
                        <label class="form-label" for="bkash_number">Mobile Number</label>
                        <input type="tel" id="bkash_number" name="bkash_number" class="form-control"
                               placeholder="01XXXXXXXXX">
                        <small class="text-muted">Enter the number linked to your payment account</small>
                    </div>

                    <div class="alert alert-warning" style="margin-top: 16px;">
                        <i class="fas fa-info-circle"></i> This is a simulated payment for demonstration purposes.
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Order Summary -->
        <div>
            <div class="card" style="position: sticky; top: 100px;">
                <div class="card-header">
                    <h3 class="card-title">Order Summary</h3>
                </div>
                <div class="card-body">
                    <!-- Cart Items -->
                    <div class="checkout-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="checkout-item">
                                <div style="display: flex; gap: 12px;">
                                    <?php if ($item['product_image']): ?>
                                        <img src="<?php echo getProductImageUrl($item['product_image']); ?>" alt=""
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--radius-sm);">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: var(--gray-100); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 500;"><?php echo sanitize($item['product_name']); ?></div>
                                        <div class="text-muted text-sm">
                                            <?php echo $item['quantity']; ?> × <?php echo formatCurrency($item['price_per_unit']); ?>
                                        </div>
                                    </div>
                                    <div style="font-weight: 600;">
                                        <?php echo formatCurrency($item['price_per_unit'] * $item['quantity']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr style="margin: 16px 0; border: none; border-top: 1px solid var(--gray-200);">

                    <!-- Totals -->
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Subtotal</span>
                        <span><?php echo formatCurrency($subtotal); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: var(--success);">
                        <span>Delivery</span>
                        <span>FREE</span>
                    </div>

                    <hr style="margin: 16px 0; border: none; border-top: 2px solid var(--gray-200);">

                    <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 700;">
                        <span>Total</span>
                        <span style="color: var(--primary);"><?php echo formatCurrency($subtotal); ?></span>
                    </div>

                    <button type="submit" name="place_order" class="btn btn-primary" style="width: 100%; margin-top: 24px; padding: 14px;">
                        <i class="fas fa-check"></i> Place Order
                    </button>

                    <a href="cart.php" class="btn btn-outline" style="width: 100%; margin-top: 12px;">
                        <i class="fas fa-arrow-left"></i> Back to Cart
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.payment-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.payment-option {
    cursor: pointer;
}

.payment-option input {
    display: none;
}

.payment-option-content {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    border: 2px solid var(--gray-200);
    border-radius: var(--radius);
    transition: all var(--transition-fast);
}

.payment-option input:checked + .payment-option-content {
    border-color: var(--primary);
    background: var(--primary-50);
}

.payment-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.checkout-items {
    max-height: 300px;
    overflow-y: auto;
}

.checkout-item {
    padding: 12px 0;
    border-bottom: 1px solid var(--gray-100);
}

.checkout-item:last-child {
    border-bottom: none;
}
</style>

<script>
function toggleBkashField() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const bkashField = document.getElementById('bkashField');
    const bkashInput = document.getElementById('bkash_number');

    if (paymentMethod === 'cod') {
        bkashField.style.display = 'none';
        bkashInput.required = false;
    } else {
        bkashField.style.display = 'block';
        bkashInput.required = true;
    }
}

// Initialize on page load
toggleBkashField();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
