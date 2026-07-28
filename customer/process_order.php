<?php
/**
 * Customer - Process Order
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

// Check if user is logged in and is a customer
if (!isLoggedIn() || !isCustomer()) {
    redirect(BASE_URL . 'auth/login.php?role=customer');
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'customer/checkout.php');
}

$customerId = getCurrentUserId();

// Get form data
$name = sanitize($_POST['name'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$instructions = sanitize($_POST['instructions'] ?? '');
$paymentMethod = sanitize($_POST['payment_method'] ?? '');
$bkashNumber = sanitize($_POST['bkash_number'] ?? '');

// Validate
if (empty($name) || empty($phone) || empty($address) || empty($paymentMethod)) {
    setFlashMessage('error', 'Please fill in all required fields');
    redirect(BASE_URL . 'customer/checkout.php');
}

// Get cart items
$stmt = $conn->prepare("
    SELECT cc.*, fp.product_name, fp.price_per_unit, fp.unit
    FROM customer_cart cc
    JOIN farm_product fp ON cc.product_id = fp.product_id
    WHERE cc.customer_id = ?
");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cartItems)) {
    setFlashMessage('error', 'Your cart is empty');
    redirect(BASE_URL . 'customer/products.php');
}

// Calculate total
$totalAmount = 0;
foreach ($cartItems as $item) {
    $totalAmount += $item['price_per_unit'] * $item['quantity'];
}

// Start transaction
$conn->begin_transaction();

try {
    // Create order
    $stmt = $conn->prepare("
        INSERT INTO customer_order (customer_id, total_amount, order_date, status)
        VALUES (?, ?, NOW(), 'Pending')
    ");
    $stmt->bind_param("id", $customerId, $totalAmount);
    $stmt->execute();
    $orderId = $conn->insert_id;

    // Create order items and update inventory
    foreach ($cartItems as $item) {
        // Add order item
        $stmt = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price_per_unit)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price_per_unit']);
        $stmt->execute();

        // Update inventory
        $stmt = $conn->prepare("
            UPDATE farm_inventory
            SET quantity_available = quantity_available - ?
            WHERE product_id = ?
        ");
        $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        $stmt->execute();
    }

    // Create payment record
    $paymentStatus = ($paymentMethod === 'Cash on Delivery') ? 'Pending' : 'Paid';
    $stmt = $conn->prepare("
        INSERT INTO payment (order_id, payment_method, bkash_number, payment_status, payment_time)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("isss", $orderId, $paymentMethod, $bkashNumber, $paymentStatus);
    $stmt->execute();

    // Clear cart
    $stmt = $conn->prepare("DELETE FROM customer_cart WHERE customer_id = ?");
    $stmt->bind_param("i", $customerId);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    // Store order ID in session for confirmation page
    $_SESSION['last_order_id'] = $orderId;

    setFlashMessage('success', 'Order placed successfully! Your order number is #' . $orderId);
    redirect(BASE_URL . 'customer/order_detail.php?id=' . $orderId);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    setFlashMessage('error', 'Failed to place order. Please try again.');
    redirect(BASE_URL . 'customer/checkout.php');
}
