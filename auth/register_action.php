<?php
/**
 * AgriSphere Registration Action Handler
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'auth/register.php');
}

// Get form data
$firstName = sanitize($_POST['first_name'] ?? '');
$lastName = sanitize($_POST['last_name'] ?? '');
$username = sanitize($_POST['username'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$role = sanitize($_POST['role'] ?? '');

// Validation
$errors = [];

if (empty($firstName)) $errors[] = 'First name is required';
if (empty($lastName)) $errors[] = 'Last name is required';
if (empty($username)) $errors[] = 'Username is required';
if (empty($email)) $errors[] = 'Email is required';
if (empty($phone)) $errors[] = 'Phone number is required';
if (empty($address)) $errors[] = 'Address is required';
if (empty($password)) $errors[] = 'Password is required';
if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
if ($password !== $confirmPassword) $errors[] = 'Passwords do not match';
if (empty($role) || !in_array($role, ['Farmer', 'Customer'])) $errors[] = 'Invalid role selected';

// Check if username already exists
$stmt = $conn->prepare("SELECT User_id FROM user WHERE Username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $errors[] = 'Username already taken. Please choose another.';
}

// Check if email already exists
$stmt = $conn->prepare("SELECT User_id FROM user WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $errors[] = 'Email already registered. Please use another email or login.';
}

// If there are errors, redirect back with message
if (!empty($errors)) {
    setFlashMessage('error', implode('<br>', $errors));
    redirect(BASE_URL . 'auth/register.php?role=' . strtolower($role));
}

// Generate a random NID (for demo purposes)
$nid = str_pad(rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);

// Insert user into database
// Note: In production, use password_hash($password, PASSWORD_DEFAULT)
$stmt = $conn->prepare("INSERT INTO user (First_name, Last_name, Username, Password, User_type, Phone, Email, NID)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssss", $firstName, $lastName, $username, $password, $role, $phone, $email, $nid);

if ($stmt->execute()) {
    $userId = $conn->insert_id;

    // The trigger should automatically create farmer/customer record
    // But let's ensure the address is updated

    if ($role === 'Farmer') {
        // Update farmer address
        $stmt = $conn->prepare("UPDATE farmer SET address = ? WHERE farmer_id = ?");
        $stmt->bind_param("si", $address, $userId);
        $stmt->execute();
    } else {
        // Update customer address
        $stmt = $conn->prepare("UPDATE customer SET address = ?, phone = ? WHERE customer_id = ?");
        $stmt->bind_param("ssi", $address, $phone, $userId);
        $stmt->execute();
    }

    // Auto-login the user
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $firstName . ' ' . $lastName;
    $_SESSION['user_type'] = $role;
    $_SESSION['username'] = $username;

    // Regenerate session ID
    session_regenerate_id(true);

    // Set success message and redirect
    setFlashMessage('success', 'Registration successful! Welcome to ' . SITE_NAME);

    if ($role === 'Farmer') {
        redirect(BASE_URL . 'farmer/dashboard.php');
    } else {
        redirect(BASE_URL . 'customer/dashboard.php');
    }
} else {
    setFlashMessage('error', 'Registration failed. Please try again.');
    redirect(BASE_URL . 'auth/register.php?role=' . strtolower($role));
}
