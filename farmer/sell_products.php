<?php
/**
 * Farmer - Sell Products (Add New Product)
 */

$pageTitle = 'Sell Products';
$currentPage = 'sell_products';

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is a farmer
if (!isLoggedIn() || !isFarmer()) {
    setFlashMessage('error', 'Please login as a farmer to access this page');
    redirect(BASE_URL . 'auth/login.php?role=farmer');
}

// Get all categories
$categories = getAllCategories($conn);

// Get government prices for auto-fill
$govtPricesResult = $conn->query("SELECT * FROM government_prices ORDER BY product_name");
$govtPrices = $govtPricesResult ? $govtPricesResult->fetch_all(MYSQLI_ASSOC) : [];

// Include sidebar
include __DIR__ . '/../includes/sidebar_farmer.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Sell Products</h1>
    <p class="page-subtitle">List a new product for sale. Prices are set based on government rates.</p>
</div>

<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <h3 class="card-title">Add New Product</h3>
    </div>
    <div class="card-body">
        <form action="add_product_action.php" method="POST" enctype="multipart/form-data" data-validate>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="product_name">Product Name</label>
                    <input type="text" id="product_name" name="product_name" class="form-input"
                           placeholder="e.g., Fresh Potato" required list="product_suggestions">
                    <datalist id="product_suggestions">
                        <?php foreach ($govtPrices as $price): ?>
                            <option value="<?php echo sanitize($price['product_name']); ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <div class="form-hint">Start typing to see government-priced products</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>">
                                <?php echo sanitize($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-textarea" rows="3"
                          placeholder="Describe your product quality, freshness, etc."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="quantity">Quantity Available</label>
                    <input type="number" id="quantity" name="quantity" class="form-input"
                           placeholder="e.g., 100" min="1" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="unit">Unit</label>
                    <select id="unit" name="unit" class="form-select" required>
                        <option value="">Select Unit</option>
                        <option value="kg">Kilogram (kg)</option>
                        <option value="liter">Liter</option>
                        <option value="piece">Piece</option>
                        <option value="dozen">Dozen</option>
                        <option value="bundle">Bundle</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="price_per_unit">Price per Unit (<?php echo CURRENCY; ?>)</label>
                    <input type="number" id="price_per_unit" name="price_per_unit" class="form-input"
                           placeholder="Auto-filled from govt price" step="0.01" min="0" required>
                    <div class="form-hint" id="govt_price_hint">Enter product name to see government price</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="product_image">Product Image (Optional)</label>
                    <input type="file" id="product_image" name="product_image" class="form-input"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="form-hint">Max 5MB. JPG, PNG, GIF, or WebP</div>
                </div>
            </div>

            <div class="alert alert-info" style="margin-bottom: 20px;">
                <i class="fas fa-info-circle"></i>
                Your product will be reviewed by an admin before it becomes visible to customers.
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane"></i> Submit for Approval
                </button>
                <a href="dashboard.php" class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- Government Prices Reference -->
<div class="card mt-4" style="max-width: 800px;">
    <div class="card-header">
        <h3 class="card-title">Government Price Reference</h3>
    </div>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Unit</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $govtPricesWithCategoryResult = $conn->query("
                    SELECT gp.*, c.category_name
                    FROM government_prices gp
                    LEFT JOIN category c ON gp.category_id = c.category_id
                    ORDER BY c.category_name, gp.product_name
                    LIMIT 15
                ");
                $govtPricesWithCategory = $govtPricesWithCategoryResult ? $govtPricesWithCategoryResult->fetch_all(MYSQLI_ASSOC) : [];
                foreach ($govtPricesWithCategory as $gp):
                ?>
                    <tr>
                        <td><?php echo sanitize($gp['product_name']); ?></td>
                        <td><?php echo sanitize($gp['category_name']); ?></td>
                        <td><?php echo formatCurrency($gp['price_per_unit']); ?></td>
                        <td><?php echo sanitize($gp['unit'] ?? 'kg'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Government price data
const govtPrices = <?php echo json_encode($govtPrices); ?>;

// Auto-fill price when product name is entered
document.getElementById('product_name').addEventListener('input', function() {
    const productName = this.value.toLowerCase().trim();
    const priceInput = document.getElementById('price_per_unit');
    const unitSelect = document.getElementById('unit');
    const categorySelect = document.getElementById('category_id');
    const hintDiv = document.getElementById('govt_price_hint');

    // Find matching government price
    const match = govtPrices.find(p =>
        p.product_name.toLowerCase() === productName ||
        p.product_name.toLowerCase().includes(productName)
    );

    if (match) {
        priceInput.value = match.price_per_unit;
        unitSelect.value = match.unit;
        categorySelect.value = match.category_id;
        hintDiv.innerHTML = '<span style="color: var(--success);">Government Price: <?php echo CURRENCY; ?>' + match.price_per_unit + '/' + match.unit + '</span>';
    } else {
        hintDiv.textContent = 'No government price found. Enter custom price.';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
