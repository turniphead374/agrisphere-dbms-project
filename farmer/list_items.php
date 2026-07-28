<?php
require_once "../includes/auth.php";
require_role("Farmer");
block_check_or_exit($conn);

require_once "../includes/layout.php";
render_topbar("List Items For Sale");

$farmer_id = (int)$_SESSION['user_id'];

$cats = $conn->query("SELECT category_id, category_name FROM category ORDER BY category_name");
?>

<div class="card">
  <form method="POST" action="manage_inventory.php">
    <input type="hidden" name="action" value="add_product">

    <input type="text" name="product_name" placeholder="Product name (e.g., Fresh Potato)" required><br><br>
    <select name="category_id" required>
      <?php while($c = $cats->fetch_assoc()): ?>
        <option value="<?php echo (int)$c['category_id']; ?>"><?php echo htmlspecialchars($c['category_name']); ?></option>
      <?php endwhile; ?>
    </select><br><br>

    <input type="text" name="description" placeholder="Short description"><br><br>
    <input type="text" name="unit" placeholder="Unit (kg/dozen/bundle)" required><br><br>
    <input type="number" step="0.01" name="price_per_unit" placeholder="Price per unit" required><br><br>
    <input type="number" name="quantity_available" placeholder="Starting quantity" required><br><br>

    <button type="submit">Add Product</button>
  </form>
</div>

<?php render_footer(); ?>
