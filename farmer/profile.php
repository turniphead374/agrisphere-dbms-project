<?php
/**
 * Farmer - Profile Page
 */

$pageTitle = 'My Profile';
$currentPage = 'profile';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is a farmer
if (!isLoggedIn() || !isFarmer()) {
    setFlashMessage('error', 'Please login as a farmer to access this page');
    redirect(BASE_URL . 'auth/login.php?role=farmer');
}

$farmerId = getCurrentUserId();

// Get user and farmer data
$stmt = $conn->prepare("
    SELECT u.*, f.address, f.bank_name, f.bank_account_number
    FROM user u
    JOIN farmer f ON u.User_id = f.farmer_id
    WHERE u.User_id = ?
");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();

// Get stats
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM farm_product WHERE farmer_id = ?");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$totalProducts = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("
    SELECT COALESCE(SUM(oi.quantity * oi.price_per_unit), 0) as revenue
    FROM order_items oi
    JOIN farm_product fp ON oi.product_id = fp.product_id
    JOIN customer_order co ON oi.order_id = co.order_id
    WHERE fp.farmer_id = ? AND co.status = 'Delivered'
");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$totalRevenue = $stmt->get_result()->fetch_assoc()['revenue'];

// Include sidebar
include __DIR__ . '/../includes/sidebar_farmer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">My Profile</h1>
    <p class="page-subtitle">View and manage your account information.</p>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
    <!-- Profile Card -->
    <div class="card">
        <div class="card-body" style="text-align: center;">
            <div class="sidebar-avatar" style="width: 100px; height: 100px; font-size: 2.5rem; margin: 0 auto 16px;">
                <?php echo strtoupper(substr($farmer['First_name'], 0, 1)); ?>
            </div>
            <h3 style="margin-bottom: 4px;"><?php echo sanitize($farmer['First_name'] . ' ' . $farmer['Last_name']); ?></h3>
            <p class="text-muted">@<?php echo sanitize($farmer['Username']); ?></p>

            <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--gray-100);">
                <div style="display: flex; justify-content: space-around;">
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);"><?php echo $totalProducts; ?></div>
                        <div class="text-muted" style="font-size: 0.8125rem;">Products</div>
                    </div>
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);"><?php echo formatCurrency($totalRevenue); ?></div>
                        <div class="text-muted" style="font-size: 0.8125rem;">Revenue</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Profile</h3>
        </div>
        <div class="card-body">
            <form action="update_profile.php" method="POST" data-validate>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-input"
                               value="<?php echo sanitize($farmer['First_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-input"
                               value="<?php echo sanitize($farmer['Last_name']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" class="form-input"
                           value="<?php echo sanitize($farmer['Username']); ?>" disabled>
                    <div class="form-hint">Username cannot be changed</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-input"
                               value="<?php echo sanitize($farmer['Email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-input"
                               value="<?php echo sanitize($farmer['Phone']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Address</label>
                    <textarea id="address" name="address" class="form-textarea" rows="2" required><?php echo sanitize($farmer['address']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="bank_name">Bank Name</label>
                        <input type="text" id="bank_name" name="bank_name" class="form-input"
                               value="<?php echo sanitize($farmer['bank_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="bank_account">Bank Account Number</label>
                        <input type="text" id="bank_account" name="bank_account" class="form-input"
                               value="<?php echo sanitize($farmer['bank_account_number']); ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Section -->
<div class="card mt-4" style="max-width: 600px;">
    <div class="card-header">
        <h3 class="card-title">Change Password</h3>
    </div>
    <div class="card-body">
        <form action="update_profile.php" method="POST" data-validate>
            <input type="hidden" name="action" value="change_password">

            <div class="form-group">
                <label class="form-label" for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="form-input" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-input" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                </div>
            </div>

            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-key"></i> Change Password
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
