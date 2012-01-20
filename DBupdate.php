<?php
require_once "core/compat.inc.php";
require_once "core/database.class.php";
include_once "config.php";
$db = new Database();
echo "Fixing user_favorites table....";
($db->Execute("ALTER TABLE user_favorites ENGINE=InnoDB;")) ? print_r("ok<br>") : print_r("failed<br>");
echo "adding Foreign key to users...";
($db->Execute("ALTER TABLE user_favorites ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;"))? print_r("ok<br>"):print_r("failed<br>");
?>