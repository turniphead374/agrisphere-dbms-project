<?php
/**
 * Customer - Cart Actions Handler
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

// Check if user is logged in and is a customer
if (!isLoggedIn() || !isCustomer()) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Please login first']);
        exit;
    }
    redirect(BASE_URL . 'auth/login.php?role=customer');
}

$customerId = getCurrentUserId();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle AJAX requests
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

switch ($action) {
    case 'add':
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);

        if ($productId <= 0 || $quantity <= 0) {
            if ($isAjax) {
                echo json_encode(['error' => 'Invalid product or quantity']);
                exit;
            }
            setFlashMessage('error', 'Invalid product or quantity');
            redirect(BASE_URL . 'customer/products.php');
        }

        // Check if product exists and is approved
        $stmt = $conn->prepare("SELECT product_id FROM farm_product WHERE product_id = ? AND status = 'approved'");
        if ($stmt === false) {
            if ($isAjax) {
                echo json_encode(['error' => 'Database error']);
                exit;
            }
            setFlashMessage('error', 'Database error');
            redirect(BASE_URL . 'customer/products.php');
        }
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            if ($isAjax) {
                echo json_encode(['error' => 'Product not available']);
                exit;
            }
            setFlashMessage('error', 'Product not available');
            redirect(BASE_URL . 'customer/products.php');
        }

        // Check if product already in cart
        $stmt = $conn->prepare("SELECT cart_id, quantity FROM customer_cart WHERE customer_id = ? AND product_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $customerId, $productId);
            $stmt->execute();
            $existingItem = $stmt->get_result()->fetch_assoc();
        } else {
            $existingItem = null;
        }

        if ($existingItem) {
            // Update quantity
            $newQuantity = $existingItem['quantity'] + $quantity;
            $stmt = $conn->prepare("UPDATE customer_cart SET quantity = ? WHERE cart_id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $newQuantity, $existingItem['cart_id']);
                $stmt->execute();
            }
        } else {
            // Add new item
            $stmt = $conn->prepare("INSERT INTO customer_cart (customer_id, product_id, quantity) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iii", $customerId, $productId, $quantity);
                $stmt->execute();
            }
        }

        $cartCount = getCartCount($conn, $customerId);

        if ($isAjax) {
            echo json_encode(['success' => true, 'message' => 'Product added to cart', 'cartCount' => $cartCount]);
            exit;
        }

        setFlashMessage('success', 'Product added to cart');
        redirect(BASE_URL . 'customer/cart.php');
        break;

    case 'update':
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);

        if ($cartId <= 0 || $quantity <= 0) {
            setFlashMessage('error', 'Invalid request');
            redirect(BASE_URL . 'customer/cart.php');
        }

        $stmt = $conn->prepare("UPDATE customer_cart SET quantity = ? WHERE cart_id = ? AND customer_id = ?");
        $stmt->bind_param("iii", $quantity, $cartId, $customerId);
        $stmt->execute();

        setFlashMessage('success', 'Cart updated');
        redirect(BASE_URL . 'customer/cart.php');
        break;

    case 'remove':
        $cartId = (int)($_POST['cart_id'] ?? 0);

        if ($cartId <= 0) {
            setFlashMessage('error', 'Invalid request');
            redirect(BASE_URL . 'customer/cart.php');
        }

        $stmt = $conn->prepare("DELETE FROM customer_cart WHERE cart_id = ? AND customer_id = ?");
        $stmt->bind_param("ii", $cartId, $customerId);
        $stmt->execute();

        setFlashMessage('success', 'Item removed from cart');
        redirect(BASE_URL . 'customer/cart.php');
        break;

    case 'clear':
        $stmt = $conn->prepare("DELETE FROM customer_cart WHERE customer_id = ?");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();

        setFlashMessage('success', 'Cart cleared');
        redirect(BASE_URL . 'customer/cart.php');
        break;

    default:
        redirect(BASE_URL . 'customer/cart.php');
}
