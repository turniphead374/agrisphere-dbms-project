<?php
function render_topbar($title) {
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo htmlspecialchars($title); ?></title>
  <meta charset="utf-8">

  <!-- Global UI stylesheet -->
  <link rel="stylesheet" href="../assets/css/ui.css">
</head>
<body>

<div class="topbar">
  <div><b><?php echo htmlspecialchars($title); ?></b></div>

  <form class="ai" method="POST" action="http://localhost/Agrisphere/ai/ask.php">
    <input type="text" name="q" placeholder="Ask AgriSphere AI (Gemini)…" required>
    <button type="submit">Ask</button>
  </form>
</div>

<?php
}

function render_footer() {
?>
</body>
</html>
<?php
}
