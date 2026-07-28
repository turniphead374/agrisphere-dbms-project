<?php
session_start();
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/init.php";

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: http://localhost/Agrisphere/auth/login.php");
        exit();
    }
}

function require_role($role) {
    require_login();
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== $role) {
        header("Location: http://localhost/Agrisphere/auth/login.php");
        exit();
    }
}

function block_check_or_exit($conn) {
    if (!isset($_SESSION['user_id'])) return;

    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT is_blocked FROM user_block WHERE user_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if ((int)$row['is_blocked'] === 1) {
            session_unset();
            session_destroy();
            echo "Your account is blocked by admin.";
            exit();
        }
    }
}
