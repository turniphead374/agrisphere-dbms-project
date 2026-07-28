<?php
/**
 * Admin Users Management
 * View, filter, and manage all users
 */

$pageTitle = 'Users';
$currentPage = 'users';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Please login as admin to access this page');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

// Get filter parameters
$filterType = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$filterStatus = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$whereConditions = ["u.User_type != 'Admin'"];
$params = [];
$types = '';

if ($filterType && in_array($filterType, ['Farmer', 'Customer'])) {
    $whereConditions[] = "u.User_type = ?";
    $params[] = $filterType;
    $types .= 's';
}

if ($filterStatus === 'blocked') {
    $whereConditions[] = "ub.is_blocked = 1";
} elseif ($filterStatus === 'active') {
    $whereConditions[] = "(ub.is_blocked IS NULL OR ub.is_blocked = 0)";
}

if ($search) {
    $whereConditions[] = "(u.First_name LIKE ? OR u.Last_name LIKE ? OR u.Username LIKE ? OR u.Email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ssss';
}

$whereClause = implode(' AND ', $whereConditions);

$query = "
    SELECT u.*,
           COALESCE(ub.is_blocked, 0) as is_blocked,
           ub.blocked_at
    FROM user u
    LEFT JOIN user_block ub ON u.User_id = ub.user_id
    WHERE $whereClause
    ORDER BY u.User_id DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $users = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
}

// Get counts for filter badges
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM user WHERE User_type != 'Admin'")->fetch_assoc()['count'];
$totalFarmers = $conn->query("SELECT COUNT(*) as count FROM user WHERE User_type = 'Farmer'")->fetch_assoc()['count'];
$totalCustomers = $conn->query("SELECT COUNT(*) as count FROM user WHERE User_type = 'Customer'")->fetch_assoc()['count'];
$blockedUsers = $conn->query("SELECT COUNT(*) as count FROM user_block WHERE is_blocked = 1")->fetch_assoc()['count'];

// Include sidebar
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 class="page-title">User Management</h1>
            <p class="page-subtitle">Manage and monitor all registered users</p>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="card mb-4">
    <div class="card-body" style="padding: 12px 16px;">
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <a href="users.php" class="filter-tab <?php echo (!$filterType && !$filterStatus) ? 'active' : ''; ?>">
                All Users <span class="filter-count"><?php echo $totalUsers; ?></span>
            </a>
            <a href="users.php?type=Farmer" class="filter-tab <?php echo $filterType === 'Farmer' ? 'active' : ''; ?>">
                Farmers <span class="filter-count"><?php echo $totalFarmers; ?></span>
            </a>
            <a href="users.php?type=Customer" class="filter-tab <?php echo $filterType === 'Customer' ? 'active' : ''; ?>">
                Customers <span class="filter-count"><?php echo $totalCustomers; ?></span>
            </a>
            <a href="users.php?status=blocked" class="filter-tab <?php echo $filterStatus === 'blocked' ? 'active' : ''; ?>">
                Blocked <span class="filter-count"><?php echo $blockedUsers; ?></span>
            </a>

            <div style="margin-left: auto;">
                <form method="GET" action="users.php" style="display: flex; gap: 8px;">
                    <?php if ($filterType): ?>
                        <input type="hidden" name="type" value="<?php echo $filterType; ?>">
                    <?php endif; ?>
                    <?php if ($filterStatus): ?>
                        <input type="hidden" name="status" value="<?php echo $filterStatus; ?>">
                    <?php endif; ?>
                    <input type="text" name="search" placeholder="Search users..." value="<?php echo $search; ?>" class="form-control" style="width: 250px;">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if ($search): ?>
                        <a href="users.php<?php echo $filterType ? '?type='.$filterType : ''; ?><?php echo $filterStatus ? ($filterType ? '&' : '?').'status='.$filterStatus : ''; ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <?php
            if ($filterType) echo $filterType . 's';
            elseif ($filterStatus === 'blocked') echo 'Blocked Users';
            else echo 'All Users';
            ?>
            <?php if ($search): ?>
                <span class="text-muted"> - Search: "<?php echo $search; ?>"</span>
            <?php endif; ?>
        </h3>
        <span class="text-muted"><?php echo count($users); ?> users found</span>
    </div>

    <?php if (empty($users)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h4 class="empty-state-title">No users found</h4>
                <p class="empty-state-text">
                    <?php if ($search): ?>
                        No users match your search criteria.
                    <?php else: ?>
                        There are no users matching this filter.
                    <?php endif; ?>
                </p>
                <a href="users.php" class="btn btn-primary">View All Users</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Contact</th>
                        <th>Type</th>
                        <th>Subscription</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="user-avatar" style="background: <?php echo $user['User_type'] === 'Farmer' ? '#D1FAE5' : '#DBEAFE'; ?>; color: <?php echo $user['User_type'] === 'Farmer' ? '#059669' : '#2563EB'; ?>;">
                                        <?php echo strtoupper(substr($user['First_name'], 0, 1) . substr($user['Last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo sanitize($user['First_name'] . ' ' . $user['Last_name']); ?></strong>
                                        <div class="text-muted text-sm">@<?php echo sanitize($user['Username']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><?php echo sanitize($user['Email']); ?></div>
                                <?php if ($user['Phone']): ?>
                                    <div class="text-muted text-sm"><?php echo sanitize($user['Phone']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $user['User_type'] === 'Farmer' ? 'badge-approved' : 'badge-shipped'; ?>">
                                    <?php echo $user['User_type']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['User_type'] === 'Farmer'): ?>
                                    <span class="badge badge-approved">৳1,000</span>
                                    <div class="text-muted text-sm">Subscribed</div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['is_blocked']): ?>
                                    <span class="badge badge-rejected">Blocked</span>
                                    <?php if ($user['blocked_at']): ?>
                                        <div class="text-muted text-sm"><?php echo formatDate($user['blocked_at']); ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-approved">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <button type="button" class="btn btn-sm btn-outline" onclick="viewUser(<?php echo $user['User_id']; ?>)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($user['is_blocked']): ?>
                                        <form method="POST" action="user_action.php" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['User_id']; ?>">
                                            <input type="hidden" name="action" value="unblock">
                                            <button type="submit" class="btn btn-sm btn-success" title="Unblock User">
                                                <i class="fas fa-unlock"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="user_action.php" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['User_id']; ?>">
                                            <input type="hidden" name="action" value="block">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Block User" onclick="return confirm('Are you sure you want to block this user?')">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- User Details Modal -->
<div id="userModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">User Details</h3>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="userModalBody">
            <div class="loading">Loading...</div>
        </div>
    </div>
</div>

<style>
.filter-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: var(--gray-100);
    border-radius: var(--radius-full);
    color: var(--gray-600);
    font-size: 0.875rem;
    font-weight: 500;
    transition: all var(--transition-fast);
}

.filter-tab:hover {
    background: var(--gray-200);
    color: var(--gray-700);
}

.filter-tab.active {
    background: var(--primary);
    color: white;
}

.filter-tab.active .filter-count {
    background: rgba(255,255,255,0.2);
    color: white;
}

.filter-count {
    background: var(--gray-200);
    padding: 2px 8px;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: var(--radius-lg);
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: var(--shadow-xl);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    border-bottom: 1px solid var(--gray-200);
}

.modal-title {
    margin: 0;
    font-size: 1.25rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--gray-500);
}

.modal-body {
    padding: 24px;
    overflow-y: auto;
}

.loading {
    text-align: center;
    padding: 40px;
    color: var(--gray-500);
}

.detail-row {
    display: flex;
    padding: 12px 0;
    border-bottom: 1px solid var(--gray-100);
}

.detail-label {
    width: 120px;
    color: var(--gray-500);
    font-size: 0.875rem;
}

.detail-value {
    flex: 1;
    font-weight: 500;
}

.btn-success {
    background: var(--success);
    color: white;
    border: none;
}

.btn-success:hover {
    background: #059669;
}

.btn-danger {
    background: var(--error);
    color: white;
    border: none;
}

.btn-danger:hover {
    background: #DC2626;
}
</style>

<script>
function viewUser(userId) {
    document.getElementById('userModal').style.display = 'flex';
    document.getElementById('userModalBody').innerHTML = '<div class="loading">Loading...</div>';

    fetch('get_user.php?id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                document.getElementById('userModalBody').innerHTML = `
                    <div style="text-align: center; margin-bottom: 24px;">
                        <div class="user-avatar" style="width: 80px; height: 80px; font-size: 1.5rem; margin: 0 auto; background: ${user.User_type === 'Farmer' ? '#D1FAE5' : '#DBEAFE'}; color: ${user.User_type === 'Farmer' ? '#059669' : '#2563EB'};">
                            ${user.First_name[0]}${user.Last_name[0]}
                        </div>
                        <h3 style="margin: 16px 0 4px;">${user.First_name} ${user.Last_name}</h3>
                        <span class="badge ${user.User_type === 'Farmer' ? 'badge-approved' : 'badge-shipped'}">${user.User_type}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Username</span>
                        <span class="detail-value">@${user.Username}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value">${user.Email}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value">${user.Phone || 'Not provided'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">NID</span>
                        <span class="detail-value">${user.NID || 'Not provided'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Subscription</span>
                        <span class="detail-value">${user.User_type === 'Farmer' ? '৳1,000 (Active)' : 'N/A'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <span class="badge ${user.is_blocked ? 'badge-rejected' : 'badge-approved'}">
                                ${user.is_blocked ? 'Blocked' : 'Active'}
                            </span>
                        </span>
                    </div>
                    ${user.stats ? `
                    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--gray-200);">
                        <h4 style="margin-bottom: 12px;">Statistics</h4>
                        ${user.User_type === 'Farmer' ? `
                            <div class="detail-row">
                                <span class="detail-label">Products</span>
                                <span class="detail-value">${user.stats.total_products} total (${user.stats.approved_products} approved)</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Revenue</span>
                                <span class="detail-value">৳${parseFloat(user.stats.total_revenue).toLocaleString()}</span>
                            </div>
                        ` : `
                            <div class="detail-row">
                                <span class="detail-label">Orders</span>
                                <span class="detail-value">${user.stats.total_orders}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Total Spent</span>
                                <span class="detail-value">৳${parseFloat(user.stats.total_spent).toLocaleString()}</span>
                            </div>
                        `}
                    </div>
                    ` : ''}
                `;
            } else {
                document.getElementById('userModalBody').innerHTML = '<p class="text-error">Error loading user details</p>';
            }
        })
        .catch(error => {
            document.getElementById('userModalBody').innerHTML = '<p class="text-error">Error loading user details</p>';
        });
}

function closeModal() {
    document.getElementById('userModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
