<?php
require_once "../includes/auth.php";
require_login();
block_check_or_exit($conn);

require_once "../includes/layout.php";
render_topbar("AgriSphere AI");

$q = isset($_POST['q']) ? trim($_POST['q']) : "";
if ($q === "") {
    echo "<div class='card'>Ask something in the top search bar.</div>";
    render_footer();
    exit();
}

$_SESSION['last_ai_q'] = $q;

// Call gemini endpoint
header("Location: gemini.php");
exit();
