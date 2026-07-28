<?php
/**
 * AgriSphere Login Action Handler
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'auth/login.php');
}

// Get form data
$username = sanitize($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role = sanitize($_POST['role'] ?? '');

// Validate input
if (empty($username) || empty($password) || empty($role)) {
    setFlashMessage('error', 'All fields are required');
    redirect(BASE_URL . 'auth/login.php');
}

// Check for hardcoded admin credentials
if ($role === 'Admin' && $username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
    // Try to find admin in database first
    $stmt = $conn->prepare("SELECT User_id, First_name, Last_name FROM user WHERE Username = ? AND User_type = 'Admin'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['User_id'];
        $_SESSION['user_name'] = $user['First_name'] . ' ' . $user['Last_name'];
    } else {
        // Use default admin session
        $_SESSION['user_id'] = 9999999;
        $_SESSION['user_name'] = 'System Admin';
    }

    $_SESSION['user_type'] = USER_TYPE_ADMIN;
    $_SESSION['username'] = $username;

    setFlashMessage('success', 'Welcome back, Admin!');
    redirect(BASE_URL . 'admin/dashboard.php');
}

// Query database for user
$stmt = $conn->prepare("SELECT User_id, First_name, Last_name, Username, Password, User_type
                        FROM user WHERE Username = ? AND User_type = ?");
$stmt->bind_param("ss", $username, $role);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Invalid username or password');
    redirect(BASE_URL . 'auth/login.php?role=' . strtolower($role));
}

$user = $result->fetch_assoc();

// Verify password (currently stored as plain text in demo DB)
// In production, use password_verify()
$passwordValid = false;
if ($password === $user['Password']) {
    $passwordValid = true;
} elseif (password_verify($password, $user['Password'])) {
    $passwordValid = true;
}

if (!$passwordValid) {
    setFlashMessage('error', 'Invalid username or password');
    redirect(BASE_URL . 'auth/login.php?role=' . strtolower($role));
}

// Check if user is blocked
if (isUserBlocked($conn, $user['User_id'])) {
    setFlashMessage('error', 'Your account has been blocked. Please contact support.');
    redirect(BASE_URL . 'auth/login.php?role=' . strtolower($role));
}

// Set session variables
$_SESSION['user_id'] = $user['User_id'];
$_SESSION['user_name'] = $user['First_name'] . ' ' . $user['Last_name'];
$_SESSION['user_type'] = $user['User_type'];
$_SESSION['username'] = $user['Username'];

// Regenerate session ID for security
session_regenerate_id(true);

// Set success message
setFlashMessage('success', 'Welcome back, ' . $user['First_name'] . '!');

// Redirect based on role
switch ($user['User_type']) {
    case 'Admin':
        redirect(BASE_URL . 'admin/dashboard.php');
        break;
    case 'Farmer':
        redirect(BASE_URL . 'farmer/dashboard.php');
        break;
    case 'Customer':
        redirect(BASE_URL . 'customer/dashboard.php');
        break;
    default:
        redirect(BASE_URL . 'auth/login.php');
}
