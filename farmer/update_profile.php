<?php
/**
 * Farmer - Update Profile Action
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
    redirect(BASE_URL . 'farmer/profile.php');
}

$farmerId = getCurrentUserId();
$action = $_POST['action'] ?? 'update_profile';

if ($action === 'change_password') {
    // Handle password change
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        setFlashMessage('error', 'All password fields are required');
        redirect(BASE_URL . 'farmer/profile.php');
    }

    if ($newPassword !== $confirmPassword) {
        setFlashMessage('error', 'New passwords do not match');
        redirect(BASE_URL . 'farmer/profile.php');
    }

    if (strlen($newPassword) < 6) {
        setFlashMessage('error', 'New password must be at least 6 characters');
        redirect(BASE_URL . 'farmer/profile.php');
    }

    // Verify current password
    $stmt = $conn->prepare("SELECT Password FROM user WHERE User_id = ?");
    $stmt->bind_param("i", $farmerId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    $passwordValid = ($currentPassword === $user['Password']) || password_verify($currentPassword, $user['Password']);

    if (!$passwordValid) {
        setFlashMessage('error', 'Current password is incorrect');
        redirect(BASE_URL . 'farmer/profile.php');
    }

    // Update password
    $stmt = $conn->prepare("UPDATE user SET Password = ? WHERE User_id = ?");
    $stmt->bind_param("si", $newPassword, $farmerId);

    if ($stmt->execute()) {
        setFlashMessage('success', 'Password changed successfully');
    } else {
        setFlashMessage('error', 'Failed to change password');
    }

    redirect(BASE_URL . 'farmer/profile.php');

} else {
    // Handle profile update
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $bankName = sanitize($_POST['bank_name'] ?? '');
    $bankAccount = sanitize($_POST['bank_account'] ?? '');

    // Validate
    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone)) {
        setFlashMessage('error', 'Please fill in all required fields');
        redirect(BASE_URL . 'farmer/profile.php');
    }

    // Update user table
    $stmt = $conn->prepare("UPDATE user SET First_name = ?, Last_name = ?, Email = ?, Phone = ? WHERE User_id = ?");
    $stmt->bind_param("ssssi", $firstName, $lastName, $email, $phone, $farmerId);
    $stmt->execute();

    // Update farmer table
    $stmt = $conn->prepare("UPDATE farmer SET address = ?, bank_name = ?, bank_account_number = ? WHERE farmer_id = ?");
    $stmt->bind_param("sssi", $address, $bankName, $bankAccount, $farmerId);
    $stmt->execute();

    // Update session name
    $_SESSION['user_name'] = $firstName . ' ' . $lastName;

    setFlashMessage('success', 'Profile updated successfully');
    redirect(BASE_URL . 'farmer/profile.php');
}
