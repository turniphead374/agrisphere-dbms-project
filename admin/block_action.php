<?php
require_once "../includes/auth.php";
require_role("Admin");

$uid = (int)$_POST['user_id'];

if (isset($_POST['block'])) {
    $stmt = $conn->prepare("INSERT INTO user_block (user_id, is_blocked) VALUES (?, 1) ON DUPLICATE KEY UPDATE is_blocked=1, blocked_at=NOW()");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
}

if (isset($_POST['unblock'])) {
    $stmt = $conn->prepare("DELETE FROM user_block WHERE user_id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
}

header("Location: dashboard.php");
exit();
