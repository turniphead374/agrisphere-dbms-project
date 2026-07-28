<?php
/**
 * AgriSphere Logout Handler
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to landing page
header("Location: " . BASE_URL);
exit();
