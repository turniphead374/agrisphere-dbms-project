<?php
/**
 * Customer - Order History
 */

$pageTitle = 'My Orders';
$currentPage = 'orders';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is a customer
if (!isLoggedIn() || !isCustomer()) {
    setFlashMessage('error', 'Please login as a customer to access this page');
    redirect(BASE_URL . 'auth/login.php?role=customer');
}

$customerId = getCurrentUserId();

// Get orders with payment info
$stmt = $conn->prepare("
    SELECT co.*,
           p.payment_method, p.payment_status,
           (SELECT COUNT(*) FROM order_items WHERE order_id = co.order_id) as item_count
    FROM customer_order co
    LEFT JOIN payment p ON co.order_id = p.order_id
    WHERE co.customer_id = ?
    ORDER BY co.order_date DESC
");
if ($stmt === false) {
    $orders = [];
} else {
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Include sidebar
include __DIR__ . '/../includes/sidebar_customer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">My Orders</h1>
    <p class="page-subtitle">Track your order history and status.</p>
</div>

<?php if (empty($orders)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h4 class="empty-state-title">No orders yet</h4>
                <p class="empty-state-text">Start shopping to see your orders here!</p>
                <a href="products.php" class="btn btn-primary">Browse Products</a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Order Status</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo $order['order_id']; ?></strong>
                            </td>
                            <td><?php echo formatDateTime($order['order_date']); ?></td>
                            <td><?php echo $order['item_count']; ?> item(s)</td>
                            <td><strong><?php echo formatCurrency($order['total_amount']); ?></strong></td>
                            <td><?php echo getOrderStatusBadge($order['status'] ?? 'Pending'); ?></td>
                            <td>
                                <?php
                                $paymentStatus = $order['payment_status'] ?? 'Pending';
                                $paymentMethod = $order['payment_method'] ?? 'N/A';
                                ?>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <span class="badge <?php echo $paymentStatus === 'Paid' ? 'badge-approved' : ($paymentStatus === 'Failed' ? 'badge-rejected' : 'badge-pending'); ?>">
                                        <?php echo $paymentStatus; ?>
                                    </span>
                                    <span class="text-muted text-sm"><?php echo sanitize($paymentMethod); ?></span>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a href="order_detail.php?id=<?php echo $order['order_id']; ?>"
                                       class="btn btn-sm btn-outline">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="download_invoice.php?id=<?php echo $order['order_id']; ?>"
                                       class="btn btn-sm btn-secondary">
                                        <i class="fas fa-download"></i> Invoice
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
