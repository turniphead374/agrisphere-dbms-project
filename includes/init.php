<?php
require_once __DIR__ . "/db.php";

/*
SQL has no "blocked" field in `user` table :contentReference[oaicite:1]{index=1}
So we add a tiny helper table for admin blocking.
*/

$conn->query("
CREATE TABLE IF NOT EXISTS user_block (
  user_id INT NOT NULL PRIMARY KEY,
  is_blocked TINYINT(1) NOT NULL DEFAULT 1,
  blocked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");
