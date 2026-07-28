<?php
/**
 * Customer - Order Detail
 */

$pageTitle = 'Order Details';
$currentPage = 'orders';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is a customer
if (!isLoggedIn() || !isCustomer()) {
    setFlashMessage('error', 'Please login as a customer to access this page');
    redirect(BASE_URL . 'auth/login.php?role=customer');
}

$customerId = getCurrentUserId();
$orderId = (int)($_GET['id'] ?? 0);

if ($orderId <= 0) {
    redirect(BASE_URL . 'customer/orders.php');
}

// Get order details
$stmt = $conn->prepare("
    SELECT co.*, p.payment_method, p.payment_status, p.bkash_number
    FROM customer_order co
    LEFT JOIN payment p ON co.order_id = p.order_id
    WHERE co.order_id = ? AND co.customer_id = ?
");
if ($stmt === false) {
    setFlashMessage('error', 'Database error');
    redirect(BASE_URL . 'customer/orders.php');
}
$stmt->bind_param("ii", $orderId, $customerId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    setFlashMessage('error', 'Order not found');
    redirect(BASE_URL . 'customer/orders.php');
}

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, fp.product_name, fp.unit,
           CONCAT(u.First_name, ' ', u.Last_name) as farmer_name
    FROM order_items oi
    JOIN farm_product fp ON oi.product_id = fp.product_id
    JOIN user u ON fp.farmer_id = u.User_id
    WHERE oi.order_id = ?
");
if ($stmt === false) {
    $orderItems = [];
} else {
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $orderItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get customer info
$stmt = $conn->prepare("SELECT u.*, c.address, c.phone FROM user u LEFT JOIN customer c ON u.User_id = c.customer_id WHERE u.User_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
} else {
    $customer = ['First_name' => '', 'Last_name' => '', 'Phone' => '', 'address' => ''];
}

// Include sidebar
include __DIR__ . '/../includes/sidebar_customer.php';
?>

<!-- Page Header -->
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 class="page-title">Order #<?php echo $orderId; ?></h1>
        <p class="page-subtitle">Placed on <?php echo formatDateTime($order['order_date']); ?></p>
    </div>
    <div>
        <a href="download_invoice.php?id=<?php echo $orderId; ?>" class="btn btn-primary">
            <i class="fas fa-download"></i> Download Invoice
        </a>
        <a href="orders.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
    </div>
</div>

<!-- Order Status -->
<div class="card mb-4">
    <div class="card-body">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h4 style="margin-bottom: 8px;">Order Status</h4>
                <?php echo getOrderStatusBadge($order['status'] ?? 'Pending'); ?>
            </div>
            <div style="text-align: center;">
                <div class="text-muted" style="font-size: 0.875rem;">Payment Status</div>
                <?php
                $paymentStatus = $order['payment_status'] ?? 'Pending';
                ?>
                <span class="badge <?php echo $paymentStatus === 'Paid' ? 'badge-approved' : ($paymentStatus === 'Failed' ? 'badge-rejected' : 'badge-pending'); ?>" style="font-size: 1rem; padding: 8px 16px;">
                    <?php echo $paymentStatus; ?>
                </span>
            </div>
            <div style="text-align: right;">
                <div class="text-muted" style="font-size: 0.875rem;">Payment Method</div>
                <strong><?php echo sanitize($order['payment_method'] ?? 'N/A'); ?></strong>
            </div>
        </div>

        <!-- Progress Bar -->
        <div style="margin-top: 24px;">
            <?php
            $statuses = ['Pending', 'Processing', 'Shipped', 'Delivered'];
            $currentIndex = array_search($order['status'] ?? 'Pending', $statuses);
            if ($currentIndex === false) $currentIndex = 0;
            ?>
            <div style="display: flex; justify-content: space-between; position: relative;">
                <div style="position: absolute; top: 12px; left: 0; right: 0; height: 4px; background: var(--gray-200); z-index: 0;"></div>
                <div style="position: absolute; top: 12px; left: 0; width: <?php echo ($currentIndex / 3) * 100; ?>%; height: 4px; background: var(--primary); z-index: 1;"></div>
                <?php foreach ($statuses as $index => $status): ?>
                    <div style="text-align: center; z-index: 2;">
                        <div style="width: 28px; height: 28px; border-radius: 50%; background: <?php echo $index <= $currentIndex ? 'var(--primary)' : 'var(--gray-200)'; ?>; margin: 0 auto; display: flex; align-items: center; justify-content: center; color: white;">
                            <?php if ($index <= $currentIndex): ?>
                                <i class="fas fa-check" style="font-size: 12px;"></i>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 8px; font-size: 0.75rem; color: <?php echo $index <= $currentIndex ? 'var(--primary)' : 'var(--gray-400)'; ?>;">
                            <?php echo $status; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <!-- Order Items -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Order Items</h3>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Farmer</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td><strong><?php echo sanitize($item['product_name']); ?></strong></td>
                            <td><?php echo sanitize($item['farmer_name']); ?></td>
                            <td><?php echo formatCurrency($item['price_per_unit']); ?>/<?php echo sanitize($item['unit']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><strong><?php echo formatCurrency($item['price_per_unit'] * $item['quantity']); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Order Summary -->
    <div>
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Order Summary</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                    <span>Subtotal</span>
                    <span><?php echo formatCurrency($order['total_amount']); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; color: var(--success);">
                    <span>Delivery</span>
                    <span>FREE</span>
                </div>
                <hr style="margin: 16px 0; border: none; border-top: 1px solid var(--gray-200);">
                <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 700;">
                    <span>Total</span>
                    <span style="color: var(--primary);"><?php echo formatCurrency($order['total_amount']); ?></span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Delivery Address</h3>
            </div>
            <div class="card-body">
                <p><strong><?php echo sanitize($customer['First_name'] . ' ' . $customer['Last_name']); ?></strong></p>
                <p style="color: var(--gray-600);"><?php echo sanitize($customer['address']); ?></p>
                <p style="margin-top: 8px;"><i class="fas fa-phone"></i> <?php echo sanitize($customer['phone'] ?? $customer['Phone']); ?></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
