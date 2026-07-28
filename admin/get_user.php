<?php
/**
 * Get User Details API
 * Returns user data as JSON for modal display
 */

require_once __DIR__ . '/../includes/header.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

// Get user details
$stmt = $conn->prepare("
    SELECT u.*,
           COALESCE(ub.is_blocked, 0) as is_blocked
    FROM user u
    LEFT JOIN user_block ub ON u.User_id = ub.user_id
    WHERE u.User_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// Set subscription info for farmers
$user['subscription_fee'] = $user['User_type'] === 'Farmer' ? 1000 : 0;
$user['subscription_status'] = $user['User_type'] === 'Farmer' ? 'Active' : 'N/A';

// Remove sensitive data
unset($user['Password']);

// Get user-specific stats
$stats = null;

if ($user['User_type'] === 'Farmer') {
    // Farmer stats
    $stmt = $conn->prepare("SELECT COUNT(*) as total_products FROM farm_product WHERE farmer_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['total_products'] = $stmt->get_result()->fetch_assoc()['total_products'];

    $stmt = $conn->prepare("SELECT COUNT(*) as approved_products FROM farm_product WHERE farmer_id = ? AND status = 'approved'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['approved_products'] = $stmt->get_result()->fetch_assoc()['approved_products'];

    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(oi.quantity * oi.price_per_unit), 0) as total_revenue
        FROM order_items oi
        JOIN farm_product fp ON oi.product_id = fp.product_id
        JOIN customer_order co ON oi.order_id = co.order_id
        WHERE fp.farmer_id = ? AND co.status = 'Delivered'
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['total_revenue'] = $stmt->get_result()->fetch_assoc()['total_revenue'];
} elseif ($user['User_type'] === 'Customer') {
    // Customer stats
    $stmt = $conn->prepare("SELECT COUNT(*) as total_orders FROM customer_order WHERE customer_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['total_orders'] = $stmt->get_result()->fetch_assoc()['total_orders'];

    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as total_spent FROM customer_order WHERE customer_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['total_spent'] = $stmt->get_result()->fetch_assoc()['total_spent'];
}

$user['stats'] = $stats;

echo json_encode(['success' => true, 'user' => $user]);
