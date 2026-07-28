<?php
/**
 * Get Government Price API
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$productName = $_GET['product'] ?? '';
$categoryId = (int)($_GET['category'] ?? 0);

if (empty($productName) && $categoryId <= 0) {
    echo json_encode(['error' => 'Product name or category required']);
    exit;
}

// Search by product name
if (!empty($productName)) {
    $searchTerm = '%' . $productName . '%';
    $stmt = $conn->prepare("
        SELECT gp.*, c.category_name
        FROM government_prices gp
        JOIN category c ON gp.category_id = c.category_id
        WHERE gp.product_name LIKE ?
        ORDER BY gp.product_name
        LIMIT 5
    ");
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (!empty($results)) {
        echo json_encode([
            'success' => true,
            'prices' => $results,
            'match' => $results[0] // Return first match as primary
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No government price found for this product'
        ]);
    }
} else {
    // Get all prices for category
    $stmt = $conn->prepare("
        SELECT * FROM government_prices WHERE category_id = ? ORDER BY product_name
    ");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'prices' => $results
    ]);
}
