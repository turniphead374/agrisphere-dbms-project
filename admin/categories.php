<?php
/**
 * Admin Category Management
 * Add, edit, and manage product categories
 */

$pageTitle = 'Categories';
$currentPage = 'categories';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Please login as admin to access this page');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            $categoryName = sanitize($_POST['category_name']);
            $description = sanitize($_POST['description'] ?? '');

            if (empty($categoryName)) {
                setFlashMessage('error', 'Category name is required');
            } else {
                // Check if category exists
                $stmt = $conn->prepare("SELECT category_id FROM category WHERE category_name = ?");
                $stmt->bind_param("s", $categoryName);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    setFlashMessage('error', 'Category already exists');
                } else {
                    $stmt = $conn->prepare("INSERT INTO category (category_name, description) VALUES (?, ?)");
                    $stmt->bind_param("ss", $categoryName, $description);
                    if ($stmt->execute()) {
                        setFlashMessage('success', 'Category added successfully');
                    } else {
                        setFlashMessage('error', 'Failed to add category');
                    }
                }
            }
        } elseif ($action === 'edit') {
            $categoryId = (int)$_POST['category_id'];
            $categoryName = sanitize($_POST['category_name']);
            $description = sanitize($_POST['description'] ?? '');

            if (empty($categoryName)) {
                setFlashMessage('error', 'Category name is required');
            } else {
                $stmt = $conn->prepare("UPDATE category SET category_name = ?, description = ? WHERE category_id = ?");
                $stmt->bind_param("ssi", $categoryName, $description, $categoryId);
                if ($stmt->execute()) {
                    setFlashMessage('success', 'Category updated successfully');
                } else {
                    setFlashMessage('error', 'Failed to update category');
                }
            }
        } elseif ($action === 'delete') {
            $categoryId = (int)$_POST['category_id'];

            // Check if category has products
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM farm_product WHERE category_id = ?");
            $stmt->bind_param("i", $categoryId);
            $stmt->execute();
            $productCount = $stmt->get_result()->fetch_assoc()['count'];

            if ($productCount > 0) {
                setFlashMessage('error', 'Cannot delete category with existing products');
            } else {
                $stmt = $conn->prepare("DELETE FROM category WHERE category_id = ?");
                $stmt->bind_param("i", $categoryId);
                if ($stmt->execute()) {
                    setFlashMessage('success', 'Category deleted successfully');
                } else {
                    setFlashMessage('error', 'Failed to delete category');
                }
            }
        }
        redirect(BASE_URL . 'admin/categories.php');
    }
}

// Get all categories with product counts
$categories = $conn->query("
    SELECT c.*,
           COUNT(fp.product_id) as total_products,
           SUM(CASE WHEN fp.status = 'approved' THEN 1 ELSE 0 END) as approved_products
    FROM category c
    LEFT JOIN farm_product fp ON c.category_id = fp.category_id
    GROUP BY c.category_id
    ORDER BY c.category_name
")->fetch_all(MYSQLI_ASSOC);

// Include sidebar
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 class="page-title">Category Management</h1>
            <p class="page-subtitle">Manage product categories for the marketplace</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Add Category
        </button>
    </div>
</div>

<!-- Categories Grid -->
<div class="categories-grid">
    <?php if (empty($categories)): ?>
        <div class="card" style="grid-column: 1 / -1;">
            <div class="card-body">
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h4 class="empty-state-title">No categories found</h4>
                    <p class="empty-state-text">Create your first category to organize products.</p>
                    <button type="button" class="btn btn-primary" onclick="showAddModal()">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($categories as $cat): ?>
            <div class="category-card">
                <div class="category-icon">
                    <i class="fas fa-<?php echo getCategoryIcon($cat['category_name']); ?>"></i>
                </div>
                <div class="category-info">
                    <h3 class="category-name"><?php echo sanitize($cat['category_name']); ?></h3>
                    <?php if ($cat['description']): ?>
                        <p class="category-description"><?php echo sanitize($cat['description']); ?></p>
                    <?php endif; ?>
                    <div class="category-stats">
                        <span class="category-stat">
                            <i class="fas fa-box"></i> <?php echo $cat['total_products']; ?> products
                        </span>
                        <span class="category-stat approved">
                            <i class="fas fa-check"></i> <?php echo $cat['approved_products']; ?> approved
                        </span>
                    </div>
                </div>
                <div class="category-actions">
                    <button type="button" class="btn btn-sm btn-outline" onclick="showEditModal(<?php echo $cat['category_id']; ?>, '<?php echo addslashes($cat['category_name']); ?>', '<?php echo addslashes($cat['description'] ?? ''); ?>')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <?php if ($cat['total_products'] == 0): ?>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="category_id" value="<?php echo $cat['category_id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this category?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Category Modal -->
<div id="addModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title">Add New Category</h3>
            <button type="button" class="modal-close" onclick="closeAddModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label for="category_name" class="form-label">Category Name <span class="text-error">*</span></label>
                    <input type="text" id="category_name" name="category_name" class="form-control" required placeholder="e.g., Vegetables, Fruits, Grains">
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="Brief description of the category"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title">Edit Category</h3>
            <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="category_id" id="edit_category_id">

                <div class="form-group">
                    <label for="edit_category_name" class="form-label">Category Name <span class="text-error">*</span></label>
                    <input type="text" id="edit_category_name" name="category_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="edit_description" class="form-label">Description</label>
                    <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
}

.category-card {
    background: white;
    border-radius: var(--radius-lg);
    padding: 24px;
    box-shadow: var(--shadow);
    display: flex;
    flex-direction: column;
    gap: 16px;
    transition: all var(--transition);
}

.category-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.category-icon {
    width: 60px;
    height: 60px;
    border-radius: var(--radius);
    background: linear-gradient(135deg, var(--primary-50) 0%, var(--primary-100) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--primary);
}

.category-name {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-800);
}

.category-description {
    font-size: 0.875rem;
    color: var(--gray-500);
    margin: 4px 0 0;
}

.category-stats {
    display: flex;
    gap: 16px;
    margin-top: 8px;
}

.category-stat {
    font-size: 0.875rem;
    color: var(--gray-500);
    display: flex;
    align-items: center;
    gap: 6px;
}

.category-stat.approved {
    color: var(--success);
}

.category-actions {
    display: flex;
    gap: 8px;
    margin-top: auto;
    padding-top: 16px;
    border-top: 1px solid var(--gray-100);
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
    max-height: 90vh;
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
}

.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--gray-200);
    display: flex;
    gap: 12px;
    justify-content: flex-end;
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
function showAddModal() {
    document.getElementById('addModal').style.display = 'flex';
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
}

function showEditModal(id, name, description) {
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_category_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modals when clicking outside
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});
</script>

<?php
// Helper function to get category icon
function getCategoryIcon($name) {
    $name = strtolower($name);
    if (strpos($name, 'vegetable') !== false) return 'carrot';
    if (strpos($name, 'fruit') !== false) return 'apple-alt';
    if (strpos($name, 'grain') !== false || strpos($name, 'rice') !== false) return 'wheat-awn';
    if (strpos($name, 'dairy') !== false || strpos($name, 'milk') !== false) return 'cheese';
    if (strpos($name, 'meat') !== false || strpos($name, 'fish') !== false) return 'drumstick-bite';
    if (strpos($name, 'spice') !== false) return 'pepper-hot';
    if (strpos($name, 'oil') !== false) return 'oil-can';
    return 'leaf';
}
?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
