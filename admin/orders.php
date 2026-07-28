<?php
/**
 * Admin Orders Management
 * View and manage all orders
 */

$pageTitle = 'Orders';
$currentPage = 'orders';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Please login as admin to access this page');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

// Get filter parameters
$filterStatus = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$viewOrderId = isset($_GET['view']) ? (int)$_GET['view'] : 0;

// Build query
$whereConditions = ["1=1"];
$params = [];
$types = '';

$validStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
if ($filterStatus && in_array($filterStatus, $validStatuses)) {
    $whereConditions[] = "co.status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

if ($search) {
    $whereConditions[] = "(co.order_id LIKE ? OR u.First_name LIKE ? OR u.Last_name LIKE ? OR u.Email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ssss';
}

$whereClause = implode(' AND ', $whereConditions);

$query = "
    SELECT co.*, u.First_name, u.Last_name, u.Email, u.Phone,
           c.address as customer_address,
           p.payment_method, p.payment_status, p.bkash_number,
           (SELECT COUNT(*) FROM order_items WHERE order_id = co.order_id) as item_count
    FROM customer_order co
    JOIN user u ON co.customer_id = u.User_id
    LEFT JOIN customer c ON co.customer_id = c.customer_id
    LEFT JOIN payment p ON co.order_id = p.order_id
    WHERE $whereClause
    ORDER BY co.order_date DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        $orders = [];
    } else {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
} else {
    $result = $conn->query($query);
    $orders = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get order details if viewing
$viewOrder = null;
$orderItems = [];
$payment = null;

if ($viewOrderId) {
    $stmt = $conn->prepare("
        SELECT co.*, u.First_name, u.Last_name, u.Email, u.Phone,
               c.address as customer_address
        FROM customer_order co
        JOIN user u ON co.customer_id = u.User_id
        LEFT JOIN customer c ON co.customer_id = c.customer_id
        WHERE co.order_id = ?
    ");
    if ($stmt) {
        $stmt->bind_param("i", $viewOrderId);
        $stmt->execute();
        $viewOrder = $stmt->get_result()->fetch_assoc();
    }

    if ($viewOrder) {
        // Get order items
        $stmt = $conn->prepare("
            SELECT oi.*, fp.product_name, fp.product_image, fp.unit,
                   fu.First_name as farmer_first, fu.Last_name as farmer_last
            FROM order_items oi
            JOIN farm_product fp ON oi.product_id = fp.product_id
            JOIN user fu ON fp.farmer_id = fu.User_id
            WHERE oi.order_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param("i", $viewOrderId);
            $stmt->execute();
            $orderItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        // Get payment info
        $stmt = $conn->prepare("SELECT * FROM payment WHERE order_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $viewOrderId);
            $stmt->execute();
            $payment = $stmt->get_result()->fetch_assoc();
        }
    }
}

// Get counts for filter badges with error handling
$totalResult = $conn->query("SELECT COUNT(*) as count FROM customer_order");
$totalOrders = $totalResult ? ($totalResult->fetch_assoc()['count'] ?? 0) : 0;
$pendingResult = $conn->query("SELECT COUNT(*) as count FROM customer_order WHERE status = 'Pending'");
$pendingOrders = $pendingResult ? ($pendingResult->fetch_assoc()['count'] ?? 0) : 0;
$processingResult = $conn->query("SELECT COUNT(*) as count FROM customer_order WHERE status = 'Processing'");
$processingOrders = $processingResult ? ($processingResult->fetch_assoc()['count'] ?? 0) : 0;
$shippedResult = $conn->query("SELECT COUNT(*) as count FROM customer_order WHERE status = 'Shipped'");
$shippedOrders = $shippedResult ? ($shippedResult->fetch_assoc()['count'] ?? 0) : 0;
$deliveredResult = $conn->query("SELECT COUNT(*) as count FROM customer_order WHERE status = 'Delivered'");
$deliveredOrders = $deliveredResult ? ($deliveredResult->fetch_assoc()['count'] ?? 0) : 0;

// Include sidebar
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 class="page-title">Order Management</h1>
            <p class="page-subtitle">Track and manage customer orders</p>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="card mb-4">
    <div class="card-body" style="padding: 12px 16px;">
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <a href="orders.php" class="filter-tab <?php echo !$filterStatus ? 'active' : ''; ?>">
                All <span class="filter-count"><?php echo $totalOrders; ?></span>
            </a>
            <a href="orders.php?status=Pending" class="filter-tab pending <?php echo $filterStatus === 'Pending' ? 'active' : ''; ?>">
                Pending <span class="filter-count"><?php echo $pendingOrders; ?></span>
            </a>
            <a href="orders.php?status=Processing" class="filter-tab processing <?php echo $filterStatus === 'Processing' ? 'active' : ''; ?>">
                Processing <span class="filter-count"><?php echo $processingOrders; ?></span>
            </a>
            <a href="orders.php?status=Shipped" class="filter-tab shipped <?php echo $filterStatus === 'Shipped' ? 'active' : ''; ?>">
                Shipped <span class="filter-count"><?php echo $shippedOrders; ?></span>
            </a>
            <a href="orders.php?status=Delivered" class="filter-tab delivered <?php echo $filterStatus === 'Delivered' ? 'active' : ''; ?>">
                Delivered <span class="filter-count"><?php echo $deliveredOrders; ?></span>
            </a>

            <div style="margin-left: auto;">
                <form method="GET" action="orders.php" style="display: flex; gap: 8px;">
                    <?php if ($filterStatus): ?>
                        <input type="hidden" name="status" value="<?php echo $filterStatus; ?>">
                    <?php endif; ?>
                    <input type="text" name="search" placeholder="Search orders..." value="<?php echo $search; ?>" class="form-control" style="width: 200px;">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <?php
            if ($filterStatus) echo $filterStatus . ' Orders';
            else echo 'All Orders';
            ?>
        </h3>
        <span class="text-muted"><?php echo count($orders); ?> orders found</span>
    </div>

    <?php if (empty($orders)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h4 class="empty-state-title">No orders found</h4>
                <p class="empty-state-text">No orders match your filter criteria.</p>
                <a href="orders.php" class="btn btn-primary">View All Orders</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Date</th>
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
                            <td>
                                <div><?php echo sanitize($order['First_name'] . ' ' . $order['Last_name']); ?></div>
                                <div class="text-muted text-sm"><?php echo sanitize($order['Email']); ?></div>
                            </td>
                            <td>
                                <?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?>
                            </td>
                            <td>
                                <strong><?php echo formatCurrency($order['total_amount']); ?></strong>
                            </td>
                            <td>
                                <?php echo formatDateTime($order['order_date']); ?>
                            </td>
                            <td>
                                <?php echo getOrderStatusBadge($order['status']); ?>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <?php
                                    $paymentStatus = $order['payment_status'] ?? 'Pending';
                                    $paymentMethod = $order['payment_method'] ?? 'N/A';
                                    ?>
                                    <span class="badge <?php echo $paymentStatus === 'Paid' ? 'badge-approved' : ($paymentStatus === 'Failed' ? 'badge-rejected' : 'badge-pending'); ?>">
                                        <?php echo $paymentStatus; ?>
                                    </span>
                                    <span class="text-muted text-sm"><?php echo sanitize($paymentMethod); ?></span>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a href="orders.php?view=<?php echo $order['order_id']; ?><?php echo $filterStatus ? '&status='.$filterStatus : ''; ?>" class="btn btn-sm btn-outline" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($order['status'] !== 'Delivered' && $order['status'] !== 'Cancelled'): ?>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="showStatusModal(<?php echo $order['order_id']; ?>, '<?php echo $order['status']; ?>')" title="Update Order Status">
                                            <i class="fas fa-truck"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($paymentStatus !== 'Paid'): ?>
                                        <button type="button" class="btn btn-sm btn-success" onclick="showPaymentModal(<?php echo $order['order_id']; ?>, '<?php echo $paymentStatus; ?>', '<?php echo $paymentMethod; ?>')" title="Update Payment Status">
                                            <i class="fas fa-dollar-sign"></i>
                                        </button>
                                    <?php endif; ?>
                                    <a href="farmer_invoice.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-secondary" title="View Farmer Invoice" target="_blank">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Order Details Modal -->
<?php if ($viewOrder): ?>
<div id="orderModal" class="modal" style="display: flex;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 class="modal-title">Order #<?php echo $viewOrder['order_id']; ?></h3>
            <a href="orders.php<?php echo $filterStatus ? '?status='.$filterStatus : ''; ?>" class="modal-close">&times;</a>
        </div>
        <div class="modal-body">
            <!-- Order Status -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--gray-200);">
                <div>
                    <span class="text-muted">Order Status</span>
                    <div style="margin-top: 8px;">
                        <?php echo getOrderStatusBadge($viewOrder['status']); ?>
                    </div>
                </div>
                <div style="text-align: right;">
                    <span class="text-muted">Order Date</span>
                    <div style="margin-top: 4px; font-weight: 500;">
                        <?php echo formatDateTime($viewOrder['order_date']); ?>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <!-- Customer Info -->
                <div>
                    <h4 style="margin-bottom: 12px;">Customer Details</h4>
                    <div class="detail-row">
                        <span class="detail-label">Name</span>
                        <span class="detail-value"><?php echo sanitize($viewOrder['First_name'] . ' ' . $viewOrder['Last_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?php echo sanitize($viewOrder['Email']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value"><?php echo sanitize($viewOrder['Phone'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Address</span>
                        <span class="detail-value"><?php echo sanitize($viewOrder['customer_address'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <!-- Payment Info -->
                <div>
                    <h4 style="margin-bottom: 12px;">Payment Details</h4>
                    <?php if ($payment): ?>
                        <div class="detail-row">
                            <span class="detail-label">Method</span>
                            <span class="detail-value"><?php echo sanitize($payment['payment_method']); ?></span>
                        </div>
                        <?php if ($payment['bkash_number']): ?>
                            <div class="detail-row">
                                <span class="detail-label">bKash No.</span>
                                <span class="detail-value"><?php echo sanitize($payment['bkash_number']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">
                                <span class="badge <?php echo $payment['payment_status'] === 'Paid' ? 'badge-approved' : 'badge-pending'; ?>">
                                    <?php echo $payment['payment_status']; ?>
                                </span>
                            </span>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No payment information available</p>
                    <?php endif; ?>
                    <div class="detail-row" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--gray-200);">
                        <span class="detail-label" style="font-weight: 600;">Total</span>
                        <span class="detail-value" style="font-size: 1.25rem; color: var(--primary);">
                            <?php echo formatCurrency($viewOrder['total_amount']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div style="margin-top: 24px;">
                <h4 style="margin-bottom: 12px;">Order Items</h4>
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
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <?php if ($item['product_image']): ?>
                                                <img src="<?php echo getProductImageUrl($item['product_image']); ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: var(--radius-sm);">
                                            <?php endif; ?>
                                            <span><?php echo sanitize($item['product_name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo sanitize($item['farmer_first'] . ' ' . $item['farmer_last']); ?></td>
                                    <td><?php echo formatCurrency($item['price_per_unit']); ?>/<?php echo sanitize($item['unit']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><strong><?php echo formatCurrency($item['price_per_unit'] * $item['quantity']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--gray-200); display: flex; gap: 12px; justify-content: space-between;">
                <a href="download_invoice.php?id=<?php echo $viewOrder['order_id']; ?>" class="btn btn-outline" target="_blank">
                    <i class="fas fa-file-invoice"></i> Download Invoice
                </a>
                <div style="display: flex; gap: 12px;">
                    <a href="orders.php<?php echo $filterStatus ? '?status='.$filterStatus : ''; ?>" class="btn btn-secondary">Close</a>
                    <?php if ($viewOrder['status'] !== 'Delivered' && $viewOrder['status'] !== 'Cancelled'): ?>
                        <button type="button" class="btn btn-primary" onclick="showStatusModal(<?php echo $viewOrder['order_id']; ?>, '<?php echo $viewOrder['status']; ?>')">
                            <i class="fas fa-edit"></i> Update Status
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Status Update Modal -->
<div id="statusModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Update Order Status</h3>
            <button type="button" class="modal-close" onclick="closeStatusModal()">&times;</button>
        </div>
        <form method="POST" action="order_action.php">
            <div class="modal-body">
                <input type="hidden" name="order_id" id="statusOrderId">
                <input type="hidden" name="action" value="update_status">

                <div class="form-group">
                    <label for="new_status" class="form-label">New Status</label>
                    <select name="new_status" id="new_status" class="form-control" required>
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Shipped">Shipped</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="status-flow" style="margin-top: 16px;">
                    <div class="status-flow-item">
                        <div class="status-flow-icon pending"><i class="fas fa-clock"></i></div>
                        <span>Pending</span>
                    </div>
                    <div class="status-flow-arrow"><i class="fas fa-arrow-right"></i></div>
                    <div class="status-flow-item">
                        <div class="status-flow-icon processing"><i class="fas fa-cog"></i></div>
                        <span>Processing</span>
                    </div>
                    <div class="status-flow-arrow"><i class="fas fa-arrow-right"></i></div>
                    <div class="status-flow-item">
                        <div class="status-flow-icon shipped"><i class="fas fa-truck"></i></div>
                        <span>Shipped</span>
                    </div>
                    <div class="status-flow-arrow"><i class="fas fa-arrow-right"></i></div>
                    <div class="status-flow-item">
                        <div class="status-flow-icon delivered"><i class="fas fa-check-circle"></i></div>
                        <span>Delivered</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid var(--gray-200); display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Payment Status Modal -->
<div id="paymentModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h3 class="modal-title">Update Payment Status</h3>
            <button type="button" class="modal-close" onclick="closePaymentModal()">&times;</button>
        </div>
        <form method="POST" action="order_action.php">
            <div class="modal-body">
                <input type="hidden" name="order_id" id="paymentOrderId">
                <input type="hidden" name="action" value="update_payment">

                <div class="payment-info-display" style="background: var(--gray-50); padding: 16px; border-radius: var(--radius); margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span class="text-muted">Payment Method:</span>
                        <strong id="displayPaymentMethod">-</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span class="text-muted">Current Status:</span>
                        <span id="displayCurrentStatus" class="badge badge-pending">-</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="payment_status" class="form-label">New Payment Status</label>
                    <select name="payment_status" id="payment_status" class="form-control" required>
                        <option value="Pending">Pending</option>
                        <option value="Paid">Paid</option>
                        <option value="Failed">Failed</option>
                    </select>
                </div>

                <div class="alert alert-info" style="margin-top: 16px; padding: 12px; background: var(--info-light); border-radius: var(--radius); font-size: 0.875rem;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note:</strong> For Cash on Delivery orders, payment is automatically marked as "Paid" when order is delivered.
                </div>
            </div>
            <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid var(--gray-200); display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">Cancel</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Update Payment
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

.filter-tab.pending.active { background: var(--warning); }
.filter-tab.processing.active { background: #F59E0B; }
.filter-tab.shipped.active { background: var(--info); }
.filter-tab.delivered.active { background: var(--success); }

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
    padding: 8px 0;
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

.status-flow {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background: var(--gray-50);
    border-radius: var(--radius);
}

.status-flow-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    font-size: 0.75rem;
    color: var(--gray-600);
}

.status-flow-icon {
    width: 36px;
    height: 36px;
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
}

.status-flow-icon.pending { background: var(--warning-light); color: #B45309; }
.status-flow-icon.processing { background: #FEF3C7; color: #D97706; }
.status-flow-icon.shipped { background: var(--info-light); color: #2563EB; }
.status-flow-icon.delivered { background: var(--success-light); color: #059669; }

.status-flow-arrow {
    color: var(--gray-400);
    font-size: 0.75rem;
}
</style>

<script>
function showStatusModal(orderId, currentStatus) {
    document.getElementById('statusOrderId').value = orderId;
    document.getElementById('new_status').value = currentStatus;
    document.getElementById('statusModal').style.display = 'flex';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

function showPaymentModal(orderId, currentStatus, paymentMethod) {
    document.getElementById('paymentOrderId').value = orderId;
    document.getElementById('payment_status').value = currentStatus;
    document.getElementById('displayPaymentMethod').textContent = paymentMethod || 'N/A';

    const statusDisplay = document.getElementById('displayCurrentStatus');
    statusDisplay.textContent = currentStatus;
    statusDisplay.className = 'badge ' + (currentStatus === 'Paid' ? 'badge-approved' : (currentStatus === 'Failed' ? 'badge-rejected' : 'badge-pending'));

    document.getElementById('paymentModal').style.display = 'flex';
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

// Close modals when clicking outside
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            if (this.id === 'orderModal') {
                window.location.href = 'orders.php<?php echo $filterStatus ? "?status=$filterStatus" : ""; ?>';
            } else {
                this.style.display = 'none';
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
