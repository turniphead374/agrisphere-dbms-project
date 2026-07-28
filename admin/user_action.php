<?php
/**
 * Admin User Actions
 * Handle blocking/unblocking users
 */

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Unauthorized access');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'admin/users.php');
}

$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';

if (!$userId || !in_array($action, ['block', 'unblock'])) {
    setFlashMessage('error', 'Invalid request');
    redirect(BASE_URL . 'admin/users.php');
}

// Verify user exists and is not an admin
$stmt = $conn->prepare("SELECT User_id, User_type FROM user WHERE User_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    setFlashMessage('error', 'User not found');
    redirect(BASE_URL . 'admin/users.php');
}

if ($user['User_type'] === 'Admin') {
    setFlashMessage('error', 'Cannot block admin users');
    redirect(BASE_URL . 'admin/users.php');
}

if ($action === 'block') {
    $stmt = $conn->prepare("INSERT INTO user_block (user_id, is_blocked, blocked_at) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE is_blocked = 1, blocked_at = NOW()");
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        setFlashMessage('success', 'User has been blocked successfully');
    } else {
        setFlashMessage('error', 'Failed to block user');
    }
} elseif ($action === 'unblock') {
    $stmt = $conn->prepare("UPDATE user_block SET is_blocked = 0 WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        setFlashMessage('success', 'User has been unblocked successfully');
    } else {
        setFlashMessage('error', 'Failed to unblock user');
    }
}

// Redirect back to referring page or users list
$referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . 'admin/users.php';
redirect($referer);
