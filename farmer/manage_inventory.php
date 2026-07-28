<?php
require_once "../includes/auth.php";
require_role("Farmer");
block_check_or_exit($conn);

require_once "../includes/layout.php";
render_topbar("Manage Inventory");

$farmer_id = (int)$_SESSION['user_id'];

// Emoji mapping (presentation-only)
$emojiMap = [
  "Fresh Potato" => "🥔",
  "Green Spinach" => "🥬",
  "Banana" => "🍌",
  "Miniket Rice" => "🌾"
];

// Handle adding a product (from list_items.php)
if (isset($_POST['action']) && $_POST['action'] === "add_product") {
    $name = trim($_POST['product_name']);
    $cat  = (int)$_POST['category_id'];
    $desc = trim($_POST['description']);
    $unit = trim($_POST['unit']);
    $price= (float)$_POST['price_per_unit'];
    $qty  = (int)$_POST['quantity_available'];

    $stmt = $conn->prepare("
      INSERT INTO farm_product (farmer_id, category_id, product_name, description, unit, price_per_unit)
      VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisssd", $farmer_id, $cat, $name, $desc, $unit, $price);
    $stmt->execute();

    $newPid = $conn->insert_id;

    // Insert inventory row
    $inv = $conn->prepare("INSERT INTO farm_inventory (product_id, quantity_available) VALUES (?, ?)");
    $inv->bind_param("ii", $newPid, $qty);
    $inv->execute();

    header("Location: manage_inventory.php");
    exit();
}

// Handle updates
if (isset($_POST['save']) && isset($_POST['product_id'])) {
    $pid = (int)$_POST['product_id'];
    $price = (float)$_POST['price_per_unit'];
    $qty = (int)$_POST['quantity_available'];

    // update product price (only if belongs to this farmer)
    $p = $conn->prepare("UPDATE farm_product SET price_per_unit=? WHERE product_id=? AND farmer_id=?");
    $p->bind_param("dii", $price, $pid, $farmer_id);
    $p->execute();

    // update inventory qty
    $q = $conn->prepare("
      UPDATE farm_inventory fi
      JOIN farm_product fp ON fp.product_id = fi.product_id
      SET fi.quantity_available = ?
      WHERE fi.product_id = ? AND fp.farmer_id = ?
    ");
    $q->bind_param("iii", $qty, $pid, $farmer_id);
    $q->execute();

    header("Location: manage_inventory.php");
    exit();
}

$stmt = $conn->prepare("
SELECT fp.product_id, fp.product_name, fp.unit, fp.price_per_unit, c.category_name,
       COALESCE(fi.quantity_available, 0) AS quantity_available
FROM farm_product fp
JOIN category c ON c.category_id = fp.category_id
LEFT JOIN farm_inventory fi ON fi.product_id = fp.product_id
WHERE fp.farmer_id = ?
ORDER BY fp.product_id DESC
");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$res = $stmt->get_result();
?>

<div class="card">
  <a href="list_items.php">+ Add new product</a> | <a href="dashboard.php">Back</a>
</div>

<?php while($r = $res->fetch_assoc()): 
  $name = $r['product_name'];
  $emoji = isset($emojiMap[$name]) ? $emojiMap[$name] : "🧺";
?>
  <div class="card">
    <b><?php echo $emoji . " " . htmlspecialchars($name); ?></b>
    <div class="muted"><?php echo htmlspecialchars($r['category_name']); ?></div>

    <form method="POST">
      <input type="hidden" name="product_id" value="<?php echo (int)$r['product_id']; ?>">
      Price: <input type="number" step="0.01" name="price_per_unit" value="<?php echo (float)$r['price_per_unit']; ?>">
      Qty: <input type="number" name="quantity_available" value="<?php echo (int)$r['quantity_available']; ?>">
      <button name="save" type="submit">Save</button>
    </form>

    <div class="muted">Unit: <?php echo htmlspecialchars($r['unit']); ?></div>
  </div>
<?php endwhile; ?>

<?php render_footer(); ?>
