<?php
/**
 * Admin Order Actions
 * Handle order status and payment status updates
 */

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Unauthorized access');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'admin/orders.php');
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';

if (!$orderId) {
    setFlashMessage('error', 'Invalid request');
    redirect(BASE_URL . 'admin/orders.php');
}

// Verify order exists and get payment info
$stmt = $conn->prepare("
    SELECT co.order_id, co.status, p.payment_method, p.payment_status
    FROM customer_order co
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

// Handle different actions
if ($action === 'update_status') {
    $newStatus = isset($_POST['new_status']) ? sanitize($_POST['new_status']) : '';

    $validStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        setFlashMessage('error', 'Invalid status');
        redirect(BASE_URL . 'admin/orders.php');
    }

    // Update order status
    $stmt = $conn->prepare("UPDATE customer_order SET status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $newStatus, $orderId);

    if ($stmt->execute()) {
        // Auto-update payment status to "Paid" when delivered and payment method is COD
        if ($newStatus === 'Delivered') {
            $paymentMethod = strtolower($order['payment_method'] ?? '');
            if ($paymentMethod === 'cod' || $paymentMethod === 'cash on delivery') {
                $stmt = $conn->prepare("UPDATE payment SET payment_status = 'Paid', payment_time = NOW() WHERE order_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $orderId);
                    $stmt->execute();
                }
                setFlashMessage('success', "Order #$orderId marked as Delivered. Payment status auto-updated to Paid (COD).");
            } else {
                setFlashMessage('success', "Order #$orderId status updated to $newStatus");
            }
        } else {
            setFlashMessage('success', "Order #$orderId status updated to $newStatus");
        }
    } else {
        setFlashMessage('error', 'Failed to update order status');
    }

} elseif ($action === 'update_payment') {
    $newPaymentStatus = isset($_POST['payment_status']) ? sanitize($_POST['payment_status']) : '';

    $validPaymentStatuses = ['Pending', 'Paid', 'Failed'];
    if (!in_array($newPaymentStatus, $validPaymentStatuses)) {
        setFlashMessage('error', 'Invalid payment status');
        redirect(BASE_URL . 'admin/orders.php');
    }

    // Check if payment record exists
    $stmt = $conn->prepare("SELECT order_id FROM payment WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $paymentExists = $stmt->get_result()->num_rows > 0;

    if ($paymentExists) {
        // Update existing payment record
        if ($newPaymentStatus === 'Paid') {
            $stmt = $conn->prepare("UPDATE payment SET payment_status = ?, payment_time = NOW() WHERE order_id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE payment SET payment_status = ? WHERE order_id = ?");
        }
        $stmt->bind_param("si", $newPaymentStatus, $orderId);
    } else {
        // Create payment record if it doesn't exist
        $stmt = $conn->prepare("INSERT INTO payment (order_id, payment_method, payment_status, payment_time) VALUES (?, 'Unknown', ?, NOW())");
        $stmt->bind_param("is", $orderId, $newPaymentStatus);
    }

    if ($stmt->execute()) {
        setFlashMessage('success', "Order #$orderId payment status updated to $newPaymentStatus");
    } else {
        setFlashMessage('error', 'Failed to update payment status');
    }

} else {
    setFlashMessage('error', 'Invalid action');
}

// Redirect back to referring page or orders list
$referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . 'admin/orders.php';
redirect($referer);
