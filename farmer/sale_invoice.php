<?php
/**
 * Farmer - Sale Invoice
 * Generates a printable sale invoice for a farmer's sale
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

// Check if user is logged in and is a farmer
if (!isLoggedIn() || !isFarmer()) {
    setFlashMessage('error', 'Please login as a farmer to access this page');
    redirect(BASE_URL . 'auth/login.php?role=farmer');
}

$farmerId = getCurrentUserId();
$orderId = (int)($_GET['id'] ?? 0);

if ($orderId <= 0) {
    redirect(BASE_URL . 'farmer/my_sales.php');
}

// Get order details with farmer's products only
$stmt = $conn->prepare("
    SELECT co.*,
           p.payment_method, p.payment_status, p.bkash_number,
           CONCAT(u.First_name, ' ', u.Last_name) as customer_name,
           u.Email as customer_email,
           u.Phone as customer_phone,
           c.address as customer_address
    FROM customer_order co
    JOIN user u ON co.customer_id = u.User_id
    LEFT JOIN customer c ON co.customer_id = c.customer_id
    LEFT JOIN payment p ON co.order_id = p.order_id
    JOIN order_items oi ON co.order_id = oi.order_id
    JOIN farm_product fp ON oi.product_id = fp.product_id
    WHERE co.order_id = ? AND fp.farmer_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $orderId, $farmerId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    setFlashMessage('error', 'Order not found or you do not have access to this sale');
    redirect(BASE_URL . 'farmer/my_sales.php');
}

// Get order items for this farmer only
$stmt = $conn->prepare("
    SELECT oi.*, fp.product_name, fp.unit
    FROM order_items oi
    JOIN farm_product fp ON oi.product_id = fp.product_id
    WHERE oi.order_id = ? AND fp.farmer_id = ?
");
$stmt->bind_param("ii", $orderId, $farmerId);
$stmt->execute();
$orderItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate farmer's total from this order
$farmerTotal = 0;
foreach ($orderItems as $item) {
    $farmerTotal += $item['price_per_unit'] * $item['quantity'];
}

// Get farmer info
$stmt = $conn->prepare("SELECT u.*, f.address, f.phone FROM user u LEFT JOIN farmer f ON u.User_id = f.farmer_id WHERE u.User_id = ?");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();

// Generate invoice number (Farmer Sale Invoice)
$invoiceNumber = 'FS-' . date('Ymd', strtotime($order['order_date'])) . '-' . str_pad($orderId, 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Invoice #<?php echo $invoiceNumber; ?> | AgriSphere</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #1f2937;
            background: #f3f4f6;
            padding: 20px;
        }

        .invoice {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 48px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 48px;
            padding-bottom: 24px;
            border-bottom: 2px solid #2F6B4F;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #2F6B4F 0%, #3d8b68 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .logo-text {
            font-size: 28px;
            font-weight: 700;
            color: #2F6B4F;
        }

        .invoice-info {
            text-align: right;
        }

        .invoice-title {
            font-size: 32px;
            font-weight: 700;
            color: #2F6B4F;
            margin-bottom: 8px;
        }

        .invoice-badge {
            display: inline-block;
            background: #FEF3C7;
            color: #92400E;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .invoice-number {
            font-size: 16px;
            color: #6b7280;
        }

        .invoice-date {
            font-size: 14px;
            color: #9ca3af;
        }

        .addresses {
            display: flex;
            justify-content: space-between;
            margin-bottom: 48px;
        }

        .address-block h4 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9ca3af;
            margin-bottom: 8px;
        }

        .address-block p {
            color: #374151;
        }

        .address-block strong {
            font-size: 16px;
            color: #1f2937;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 32px;
        }

        .items-table th {
            text-align: left;
            padding: 12px 16px;
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }

        .items-table td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table .product-name {
            font-weight: 600;
            color: #1f2937;
        }

        .summary {
            display: flex;
            justify-content: flex-end;
        }

        .summary-table {
            width: 280px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .summary-row.total {
            border-top: 2px solid #2F6B4F;
            margin-top: 8px;
            padding-top: 16px;
            font-size: 20px;
            font-weight: 700;
        }

        .summary-row.total .amount {
            color: #2F6B4F;
        }

        .payment-info {
            margin-top: 48px;
            padding: 24px;
            background: #f9fafb;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
        }

        .payment-info h4 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9ca3af;
            margin-bottom: 4px;
        }

        .payment-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }

        .payment-status.paid {
            background: #d1fae5;
            color: #059669;
        }

        .payment-status.pending {
            background: #fef3c7;
            color: #d97706;
        }

        .order-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }

        .order-status.delivered {
            background: #d1fae5;
            color: #059669;
        }

        .order-status.processing,
        .order-status.pending {
            background: #fef3c7;
            color: #d97706;
        }

        .order-status.shipped {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .order-status.cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        .footer {
            margin-top: 48px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
        }

        .footer p {
            margin-bottom: 4px;
        }

        .actions {
            margin-bottom: 20px;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: #2F6B4F;
            color: white;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
            margin-left: 8px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .invoice {
                box-shadow: none;
                padding: 0;
            }

            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button class="btn btn-primary" onclick="window.print()">
            Print Invoice
        </button>
        <a href="my_sales.php" class="btn btn-secondary">
            Back to Sales
        </a>
    </div>

    <div class="invoice">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <div class="logo-icon">
                    <span>&#127807;</span>
                </div>
                <span class="logo-text">AgriSphere</span>
            </div>
            <div class="invoice-info">
                <div class="invoice-badge">Farmer Sale Invoice</div>
                <div class="invoice-title">SALE INVOICE</div>
                <div class="invoice-number">#<?php echo $invoiceNumber; ?></div>
                <div class="invoice-date"><?php echo formatDate($order['order_date'], 'd M Y'); ?></div>
            </div>
        </div>

        <!-- Addresses -->
        <div class="addresses">
            <div class="address-block">
                <h4>Sold By (Farmer)</h4>
                <p><strong><?php echo sanitize($farmer['First_name'] . ' ' . $farmer['Last_name']); ?></strong></p>
                <p><?php echo nl2br(sanitize($farmer['address'] ?? '')); ?></p>
                <p><?php echo sanitize($farmer['phone'] ?? $farmer['Phone']); ?></p>
                <p><?php echo sanitize($farmer['Email']); ?></p>
            </div>
            <div class="address-block" style="text-align: right;">
                <h4>Sold To (Customer)</h4>
                <p><strong><?php echo sanitize($order['customer_name']); ?></strong></p>
                <p><?php echo nl2br(sanitize($order['customer_address'] ?? '')); ?></p>
                <p><?php echo sanitize($order['customer_phone']); ?></p>
                <p><?php echo sanitize($order['customer_email']); ?></p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Unit Price</th>
                    <th>Quantity</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td>
                            <div class="product-name"><?php echo sanitize($item['product_name']); ?></div>
                        </td>
                        <td><?php echo formatCurrency($item['price_per_unit']); ?>/<?php echo sanitize($item['unit']); ?></td>
                        <td><?php echo $item['quantity']; ?> <?php echo sanitize($item['unit']); ?></td>
                        <td><?php echo formatCurrency($item['price_per_unit'] * $item['quantity']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Summary -->
        <div class="summary">
            <div class="summary-table">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span><?php echo formatCurrency($farmerTotal); ?></span>
                </div>
                <div class="summary-row">
                    <span>Platform Fee (0%)</span>
                    <span style="color: #059669;">FREE</span>
                </div>
                <div class="summary-row total">
                    <span>Your Earnings</span>
                    <span class="amount"><?php echo formatCurrency($farmerTotal); ?></span>
                </div>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="payment-info">
            <div>
                <h4>Payment Method</h4>
                <p><strong><?php echo sanitize($order['payment_method'] ?? 'N/A'); ?></strong></p>
                <?php if (!empty($order['bkash_number'])): ?>
                    <p style="font-size: 12px; color: #6b7280;"><?php echo sanitize($order['bkash_number']); ?></p>
                <?php endif; ?>
            </div>
            <div>
                <h4>Payment Status</h4>
                <span class="payment-status <?php echo strtolower($order['payment_status'] ?? 'pending'); ?>">
                    <?php echo $order['payment_status'] ?? 'Pending'; ?>
                </span>
            </div>
            <div>
                <h4>Order Status</h4>
                <span class="order-status <?php echo strtolower($order['status'] ?? 'processing'); ?>">
                    <?php echo $order['status'] ?? 'Processing'; ?>
                </span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Thank you for selling with AgriSphere!</strong></p>
            <p>Fresh from Farm to Your Table</p>
            <p style="margin-top: 16px;">This is a computer-generated invoice. No signature required.</p>
            <p style="margin-top: 8px;">Order ID: #<?php echo $orderId; ?> | Generated on: <?php echo date('d M Y, h:i A'); ?></p>
        </div>
    </div>
</body>
</html>
