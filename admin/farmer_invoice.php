<?php
/**
 * Admin - View Farmer Invoice
 * Admin can view invoices for farmer sales
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Unauthorized access');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

$orderId = (int)($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
    redirect(BASE_URL . 'admin/orders.php');
}

// Get order details
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
    WHERE co.order_id = ?
");
if ($stmt === false) {
    setFlashMessage('error', 'Database error');
    redirect(BASE_URL . 'admin/orders.php');
}
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    setFlashMessage('error', 'Order not found');
    redirect(BASE_URL . 'admin/orders.php');
}

// Get all order items grouped by farmer
$stmt = $conn->prepare("
    SELECT oi.*, fp.product_name, fp.unit, fp.farmer_id,
           CONCAT(fu.First_name, ' ', fu.Last_name) as farmer_name,
           fu.Email as farmer_email,
           fu.Phone as farmer_phone,
           f.address as farmer_address
    FROM order_items oi
    JOIN farm_product fp ON oi.product_id = fp.product_id
    JOIN user fu ON fp.farmer_id = fu.User_id
    LEFT JOIN farmer f ON fp.farmer_id = f.farmer_id
    WHERE oi.order_id = ?
    ORDER BY fp.farmer_id, oi.product_id
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$allItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group items by farmer
$farmerItems = [];
foreach ($allItems as $item) {
    $farmerId = $item['farmer_id'];
    if (!isset($farmerItems[$farmerId])) {
        $farmerItems[$farmerId] = [
            'farmer_name' => $item['farmer_name'],
            'farmer_email' => $item['farmer_email'],
            'farmer_phone' => $item['farmer_phone'],
            'farmer_address' => $item['farmer_address'],
            'items' => [],
            'total' => 0
        ];
    }
    $farmerItems[$farmerId]['items'][] = $item;
    $farmerItems[$farmerId]['total'] += $item['price_per_unit'] * $item['quantity'];
}

// Generate invoice number
$invoiceNumber = 'ADM-' . date('Ymd', strtotime($order['order_date'])) . '-' . str_pad($orderId, 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Invoice #<?php echo $invoiceNumber; ?> | AgriSphere Admin</title>
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

        .actions {
            max-width: 900px;
            margin: 0 auto 20px;
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

        .invoice {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 48px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 3px solid #2F6B4F;
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
            font-size: 28px;
            font-weight: 700;
            color: #2F6B4F;
            margin-bottom: 8px;
        }

        .admin-badge {
            display: inline-block;
            background: #7C3AED;
            color: white;
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

        .order-summary {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 32px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .summary-item h4 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9ca3af;
            margin-bottom: 4px;
        }

        .summary-item p {
            font-weight: 600;
            color: #1f2937;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.paid { background: #d1fae5; color: #059669; }
        .status-badge.pending { background: #fef3c7; color: #d97706; }
        .status-badge.delivered { background: #d1fae5; color: #059669; }
        .status-badge.processing { background: #dbeafe; color: #1d4ed8; }
        .status-badge.shipped { background: #e0e7ff; color: #4f46e5; }

        .farmer-section {
            margin-bottom: 32px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .farmer-header {
            background: #f9fafb;
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .farmer-header h3 {
            font-size: 16px;
            color: #1f2937;
        }

        .farmer-header .farmer-total {
            font-size: 18px;
            font-weight: 700;
            color: #2F6B4F;
        }

        .farmer-details {
            padding: 16px 20px;
            background: white;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            font-size: 13px;
            border-bottom: 1px solid #e5e7eb;
        }

        .farmer-details p {
            color: #6b7280;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th {
            text-align: left;
            padding: 12px 20px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
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
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table .product-name {
            font-weight: 600;
            color: #1f2937;
        }

        .grand-total {
            background: #2F6B4F;
            color: white;
            padding: 24px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 20px;
            font-weight: 700;
        }

        .footer {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .invoice {
                box-shadow: none;
                padding: 20px;
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
        <a href="orders.php" class="btn btn-secondary">
            Back to Orders
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
                <div class="admin-badge">Admin View - Farmer Invoice</div>
                <div class="invoice-title">ORDER INVOICE</div>
                <div class="invoice-number">#<?php echo $invoiceNumber; ?></div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="order-summary">
            <div class="summary-item">
                <h4>Order ID</h4>
                <p>#<?php echo $orderId; ?></p>
            </div>
            <div class="summary-item">
                <h4>Order Date</h4>
                <p><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></p>
            </div>
            <div class="summary-item">
                <h4>Order Status</h4>
                <span class="status-badge <?php echo strtolower($order['status']); ?>">
                    <?php echo $order['status']; ?>
                </span>
            </div>
            <div class="summary-item">
                <h4>Payment Status</h4>
                <span class="status-badge <?php echo strtolower($order['payment_status'] ?? 'pending'); ?>">
                    <?php echo $order['payment_status'] ?? 'Pending'; ?>
                </span>
            </div>
        </div>

        <!-- Customer Info -->
        <div style="margin-bottom: 32px; padding: 20px; background: #fef3c7; border-radius: 8px;">
            <h4 style="font-size: 14px; color: #92400E; margin-bottom: 8px;">Customer Information</h4>
            <p style="font-weight: 600; color: #1f2937;"><?php echo sanitize($order['customer_name']); ?></p>
            <p style="color: #6b7280; font-size: 13px;"><?php echo sanitize($order['customer_email']); ?> | <?php echo sanitize($order['customer_phone']); ?></p>
            <p style="color: #6b7280; font-size: 13px;"><?php echo sanitize($order['customer_address'] ?? 'No address provided'); ?></p>
        </div>

        <!-- Farmer Sections -->
        <?php foreach ($farmerItems as $farmerId => $farmerData): ?>
        <div class="farmer-section">
            <div class="farmer-header">
                <h3><i class="fas fa-user-tie"></i> Farmer: <?php echo sanitize($farmerData['farmer_name']); ?></h3>
                <span class="farmer-total"><?php echo formatCurrency($farmerData['total']); ?></span>
            </div>
            <div class="farmer-details">
                <p><strong>Email:</strong> <?php echo sanitize($farmerData['farmer_email']); ?></p>
                <p><strong>Phone:</strong> <?php echo sanitize($farmerData['farmer_phone'] ?? 'N/A'); ?></p>
                <p style="grid-column: span 2;"><strong>Address:</strong> <?php echo sanitize($farmerData['farmer_address'] ?? 'N/A'); ?></p>
            </div>
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
                    <?php foreach ($farmerData['items'] as $item): ?>
                        <tr>
                            <td class="product-name"><?php echo sanitize($item['product_name']); ?></td>
                            <td><?php echo formatCurrency($item['price_per_unit']); ?>/<?php echo sanitize($item['unit']); ?></td>
                            <td><?php echo $item['quantity']; ?> <?php echo sanitize($item['unit']); ?></td>
                            <td><?php echo formatCurrency($item['price_per_unit'] * $item['quantity']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>

        <!-- Grand Total -->
        <div class="grand-total">
            <span>Total Order Amount</span>
            <span><?php echo formatCurrency($order['total_amount']); ?></span>
        </div>

        <!-- Payment Info -->
        <div style="margin-top: 24px; padding: 20px; background: #f9fafb; border-radius: 8px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <div>
                <h4 style="font-size: 12px; color: #9ca3af; text-transform: uppercase; margin-bottom: 4px;">Payment Method</h4>
                <p style="font-weight: 600;"><?php echo sanitize($order['payment_method'] ?? 'N/A'); ?></p>
            </div>
            <?php if (!empty($order['bkash_number'])): ?>
            <div>
                <h4 style="font-size: 12px; color: #9ca3af; text-transform: uppercase; margin-bottom: 4px;">bKash Number</h4>
                <p style="font-weight: 600;"><?php echo sanitize($order['bkash_number']); ?></p>
            </div>
            <?php endif; ?>
            <div>
                <h4 style="font-size: 12px; color: #9ca3af; text-transform: uppercase; margin-bottom: 4px;">Total Farmers</h4>
                <p style="font-weight: 600;"><?php echo count($farmerItems); ?> Farmer(s)</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>AgriSphere - From Farm to Market, Simplified</strong></p>
            <p>This is an administrative invoice for record purposes.</p>
            <p style="margin-top: 8px;">Generated on: <?php echo date('d M Y, h:i A'); ?></p>
        </div>
    </div>
</body>
</html>
