<?php
/**
 * Admin Product Actions
 * Handle approving/rejecting products
 */

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Unauthorized access');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'admin/products.php');
}

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';
$rejectionReason = isset($_POST['rejection_reason']) ? sanitize($_POST['rejection_reason']) : '';
$redirectUrl = isset($_POST['redirect']) ? $_POST['redirect'] : 'products.php';

if (!$productId || !in_array($action, ['approve', 'reject', 'pending'])) {
    setFlashMessage('error', 'Invalid request');
    redirect(BASE_URL . 'admin/products.php');
}

// Verify product exists
$stmt = $conn->prepare("SELECT product_id, product_name, farmer_id FROM farm_product WHERE product_id = ?");
if ($stmt === false) {
    setFlashMessage('error', 'Database error');
    redirect(BASE_URL . 'admin/products.php');
}
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    setFlashMessage('error', 'Product not found');
    redirect(BASE_URL . 'admin/products.php');
}

// Perform action
if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE farm_product SET status = 'approved', rejection_reason = NULL WHERE product_id = ?");
    $stmt->bind_param("i", $productId);

    if ($stmt->execute()) {
        setFlashMessage('success', 'Product "' . $product['product_name'] . '" has been approved');
    } else {
        setFlashMessage('error', 'Failed to approve product');
    }
} elseif ($action === 'reject') {
    if (empty($rejectionReason)) {
        setFlashMessage('error', 'Please provide a reason for rejection');
        redirect(BASE_URL . 'admin/products.php?review=' . $productId);
    }

    $stmt = $conn->prepare("UPDATE farm_product SET status = 'rejected', rejection_reason = ? WHERE product_id = ?");
    $stmt->bind_param("si", $rejectionReason, $productId);

    if ($stmt->execute()) {
        setFlashMessage('success', 'Product "' . $product['product_name'] . '" has been rejected');
    } else {
        setFlashMessage('error', 'Failed to reject product');
    }
} elseif ($action === 'pending') {
    $stmt = $conn->prepare("UPDATE farm_product SET status = 'pending', rejection_reason = NULL WHERE product_id = ?");
    $stmt->bind_param("i", $productId);

    if ($stmt->execute()) {
        setFlashMessage('success', 'Product "' . $product['product_name'] . '" has been set to pending');
    } else {
        setFlashMessage('error', 'Failed to update product status');
    }
}

// Redirect
redirect(BASE_URL . 'admin/' . $redirectUrl);
