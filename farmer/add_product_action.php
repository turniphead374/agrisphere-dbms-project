<?php
/**
 * Farmer - Add Product Action Handler
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

// Check if user is logged in and is a farmer
if (!isLoggedIn() || !isFarmer()) {
    redirect(BASE_URL . 'auth/login.php?role=farmer');
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'farmer/sell_products.php');
}

$farmerId = getCurrentUserId();

// Get form data
$productName = sanitize($_POST['product_name'] ?? '');
$categoryId = (int)($_POST['category_id'] ?? 0);
$description = sanitize($_POST['description'] ?? '');
$quantity = (int)($_POST['quantity'] ?? 0);
$unit = sanitize($_POST['unit'] ?? '');
$pricePerUnit = (float)($_POST['price_per_unit'] ?? 0);

// Validation
$errors = [];

if (empty($productName)) $errors[] = 'Product name is required';
if ($categoryId <= 0) $errors[] = 'Please select a category';
if ($quantity <= 0) $errors[] = 'Quantity must be greater than 0';
if (empty($unit)) $errors[] = 'Please select a unit';
if ($pricePerUnit <= 0) $errors[] = 'Price must be greater than 0';

if (!empty($errors)) {
    setFlashMessage('error', implode('<br>', $errors));
    redirect(BASE_URL . 'farmer/sell_products.php');
}

// Handle image upload
$productImage = null;
if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    $uploadResult = uploadProductImage($_FILES['product_image']);
    if ($uploadResult['success']) {
        $productImage = $uploadResult['filename'];
    } else {
        setFlashMessage('error', 'Image upload failed: ' . $uploadResult['error']);
        redirect(BASE_URL . 'farmer/sell_products.php');
    }
}

// Insert product with quantity
$stmt = $conn->prepare("
    INSERT INTO farm_product (farmer_id, category_id, product_name, description, product_image, unit, price_per_unit, quantity, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
");
if ($stmt === false) {
    setFlashMessage('error', 'Database error. Please try again.');
    redirect(BASE_URL . 'farmer/sell_products.php');
}
$stmt->bind_param("iissssdi", $farmerId, $categoryId, $productName, $description, $productImage, $unit, $pricePerUnit, $quantity);

if ($stmt->execute()) {
    $productId = $conn->insert_id;

    // Create inventory record
    $stmt = $conn->prepare("INSERT INTO farm_inventory (product_id, quantity_available) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity_available = ?");
    if ($stmt) {
        $stmt->bind_param("iii", $productId, $quantity, $quantity);
        $stmt->execute();
    }

    setFlashMessage('success', 'Product submitted for approval! It will be visible to customers once approved by admin.');
    redirect(BASE_URL . 'farmer/sales_history.php');
} else {
    setFlashMessage('error', 'Failed to add product. Please try again.');
    redirect(BASE_URL . 'farmer/sell_products.php');
}
