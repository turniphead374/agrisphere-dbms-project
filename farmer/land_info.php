<?php
require_once "../includes/auth.php";
require_role("Farmer");
block_check_or_exit($conn);

require_once "../includes/layout.php";
render_topbar("Land Info");

$farmer_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT land_size, soil_type, district, upazila, latitude, longitude FROM land WHERE farmer_id=?");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$res = $stmt->get_result();
?>

<div class="card"><a href="dashboard.php">Back</a></div>

<?php if ($res->num_rows === 0): ?>
  <div class="card">No land info found for your farmer ID.</div>
<?php endif; ?>

<?php while($r = $res->fetch_assoc()): ?>
  <div class="card">
    <b>🗺️ Land</b><br>
    Size: <?php echo htmlspecialchars($r['land_size']); ?> acres<br>
    Soil: <?php echo htmlspecialchars($r['soil_type']); ?><br>
    District: <?php echo htmlspecialchars($r['district']); ?><br>
    Upazila: <?php echo htmlspecialchars($r['upazila']); ?><br>
    Lat/Lon: <?php echo htmlspecialchars($r['latitude']); ?>, <?php echo htmlspecialchars($r['longitude']); ?><br>
  </div>
<?php endwhile; ?>

<?php render_footer(); ?>
