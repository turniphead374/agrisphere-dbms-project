<?php
/**
 * AgriSphere Registration Page
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
if ($role === 'admin') {
    // Admins cannot register, redirect to login
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

$roleLabels = [
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
    <title>Register | <?php echo SITE_NAME; ?></title>

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

    <div class="auth-card" style="max-width: 520px;">
        <div class="auth-header">
            <a href="<?php echo BASE_URL; ?>" class="auth-logo">
                <div class="landing-logo-icon" style="width: 48px; height: 48px;">
                    <i class="fas fa-leaf"></i>
                </div>
                <span class="landing-logo-text"><?php echo SITE_NAME; ?></span>
            </a>

            <h1 class="auth-title">Create Account</h1>
            <p class="auth-subtitle">
                <?php if ($roleLabel): ?>
                    Join as a <?php echo $roleLabel; ?> and start your journey
                <?php else: ?>
                    Join <?php echo SITE_NAME; ?> today
                <?php endif; ?>
            </p>
        </div>

        <?php displayFlashMessage(); ?>

        <form action="register_action.php" method="POST" data-validate>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-input"
                           placeholder="Enter first name" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-input"
                           placeholder="Enter last name" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-input"
                       placeholder="Choose a unique username" required>
                <div class="form-hint">This will be used for login</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input"
                           placeholder="your@email.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-input"
                           placeholder="01XXXXXXXXX" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Address</label>
                <textarea id="address" name="address" class="form-textarea" rows="2"
                          placeholder="Enter your full address" required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input"
                           placeholder="Min. 6 characters" required minlength="6">
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input"
                           placeholder="Re-enter password" required>
                </div>
            </div>

            <?php if (!$role): ?>
            <div class="form-group">
                <label class="form-label" for="role">Register As</label>
                <select id="role" name="role" class="form-select" required>
                    <option value="">Select your role</option>
                    <option value="Farmer">Farmer - I want to sell products</option>
                    <option value="Customer">Customer - I want to buy products</option>
                </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="role" value="<?php echo ucfirst($role); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="terms" required>
                    <span>I agree to the Terms of Service and Privacy Policy</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-user-plus"></i>
                Create Account
            </button>
        </form>

        <div class="auth-footer">
            Already have an account?
            <a href="login.php<?php echo $role ? '?role=' . $role : ''; ?>">Sign In</a>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>
