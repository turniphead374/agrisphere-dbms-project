<?php
/**
 * AgriSphere Helper Functions
 */

/**
 * Sanitize user input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency with Taka symbol
 */
function formatCurrency($amount) {
    return CURRENCY . number_format($amount, 2);
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = 'd M Y, h:i A') {
    return date($format, strtotime($datetime));
}

/**
 * Generate a unique order number
 */
function generateOrderNumber() {
    return 'AG-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === USER_TYPE_ADMIN;
}

/**
 * Check if user is farmer
 */
function isFarmer() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === USER_TYPE_FARMER;
}

/**
 * Check if user is customer
 */
function isCustomer() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === USER_TYPE_CUSTOMER;
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user type
 */
function getCurrentUserType() {
    return $_SESSION['user_type'] ?? null;
}

/**
 * Get current user name
 */
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? 'User';
}

/**
 * Set flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message HTML
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $typeClass = $flash['type'] === 'success' ? 'alert-success' :
                    ($flash['type'] === 'error' ? 'alert-error' : 'alert-warning');
        echo '<div class="alert ' . $typeClass . '">' . sanitize($flash['message']) . '</div>';
    }
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $statusLower = strtolower($status);
    $badgeClass = 'badge-' . $statusLower;
    return '<span class="badge ' . $badgeClass . '">' . ucfirst($status) . '</span>';
}

/**
 * Get order status badge HTML
 */
function getOrderStatusBadge($status) {
    $statusMap = [
        'Pending' => 'badge-pending',
        'Processing' => 'badge-processing',
        'Shipped' => 'badge-shipped',
        'Delivered' => 'badge-delivered',
        'Cancelled' => 'badge-rejected'
    ];
    $badgeClass = $statusMap[$status] ?? 'badge-pending';
    return '<span class="badge ' . $badgeClass . '">' . $status . '</span>';
}

/**
 * Upload product image
 */
function uploadProductImage($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed'];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'File too large (max 5MB)'];
    }

    if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }

    // Create directory if not exists
    if (!file_exists(PRODUCT_IMAGE_PATH)) {
        mkdir(PRODUCT_IMAGE_PATH, 0755, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'product_' . uniqid() . '.' . $extension;
    $filepath = PRODUCT_IMAGE_PATH . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    }

    return ['success' => false, 'error' => 'Failed to save file'];
}

/**
 * Get product image URL or placeholder
 */
function getProductImageUrl($imageName) {
    if ($imageName && file_exists(PRODUCT_IMAGE_PATH . $imageName)) {
        return BASE_URL . 'uploads/products/' . $imageName;
    }
    return BASE_URL . 'assets/images/product-placeholder.png';
}

/**
 * Check if user is blocked
 */
function isUserBlocked($conn, $userId) {
    $stmt = $conn->prepare("SELECT is_blocked FROM user_block WHERE user_id = ?");
    if ($stmt === false) {
        return false;
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['is_blocked'] == 1;
    }
    return false;
}

/**
 * Get category name by ID
 */
function getCategoryName($conn, $categoryId) {
    $stmt = $conn->prepare("SELECT category_name FROM category WHERE category_id = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['category_name'];
    }
    return 'Unknown';
}

/**
 * Get all categories
 */
function getAllCategories($conn) {
    $result = $conn->query("SELECT * FROM category ORDER BY category_name");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get cart count for customer
 */
function getCartCount($conn, $customerId) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM customer_cart WHERE customer_id = ?");
    if ($stmt === false) {
        return 0;
    }
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

/**
 * Truncate text to specified length
 */
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get user's full name
 */
function getUserFullName($conn, $userId) {
    $stmt = $conn->prepare("SELECT First_name, Last_name FROM user WHERE User_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['First_name'] . ' ' . $row['Last_name'];
    }
    return 'Unknown';
}
