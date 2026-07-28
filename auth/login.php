<?php
/**
 * AgriSphere Login Page
 */

define('AGRISPHERE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

// If already logged in, redirect
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(BASE_URL . 'admin/dashboard.php');
    } elseif (isFarmer()) {
        redirect(BASE_URL . 'farmer/dashboard.php');
    } else {
        redirect(BASE_URL . 'customer/dashboard.php');
    }
}

// Get role from query string
$role = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$roleLabels = [
    'admin' => 'Administrator',
    'farmer' => 'Farmer',
    'customer' => 'Customer'
];
$roleLabel = $roleLabels[$role] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo SITE_NAME; ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/main.css">
</head>
<body class="auth-page">

    <div class="auth-card">
        <div class="auth-header">
            <a href="<?php echo BASE_URL; ?>" class="auth-logo">
                <div class="landing-logo-icon" style="width: 48px; height: 48px;">
                    <i class="fas fa-leaf"></i>
                </div>
                <span class="landing-logo-text"><?php echo SITE_NAME; ?></span>
            </a>

            <h1 class="auth-title">Welcome Back!</h1>
            <p class="auth-subtitle">
                <?php if ($roleLabel): ?>
                    Sign in to your <?php echo $roleLabel; ?> account
                <?php else: ?>
                    Sign in to continue to <?php echo SITE_NAME; ?>
                <?php endif; ?>
            </p>
        </div>

        <?php displayFlashMessage(); ?>

        <form action="login_action.php" method="POST" data-validate>
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-input"
                       placeholder="Enter your username" required
                       value="<?php echo isset($_GET['username']) ? sanitize($_GET['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input"
                       placeholder="Enter your password" required>
            </div>

            <?php if (!$role): ?>
            <div class="form-group">
                <label class="form-label" for="role">Login As</label>
                <select id="role" name="role" class="form-select" required>
                    <option value="">Select your role</option>
                    <option value="Admin">Administrator</option>
                    <option value="Farmer">Farmer</option>
                    <option value="Customer">Customer</option>
                </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="role" value="<?php echo ucfirst($role); ?>">
            <?php endif; ?>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>
        </form>

        <div class="auth-footer">
            <?php if ($role === 'admin'): ?>
                <a href="<?php echo BASE_URL; ?>"><i class="fas fa-arrow-left"></i> Back to Home</a>
            <?php else: ?>
                Don't have an account?
                <a href="register.php<?php echo $role ? '?role=' . $role : ''; ?>">Create Account</a>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
