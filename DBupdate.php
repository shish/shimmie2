<?php
require_once "core/compat.inc.php";
require_once "core/database.class.php";
include_once "config.php";
$db = new Database();
echo "Fixing user_favorites table....";
($db->Execute("ALTER TABLE user_favorites ENGINE=InnoDB;")) ? print_r("ok<br>") : print_r("failed<br>");
echo "adding Foreign key to user ids...";
($db->Execute("ALTER TABLE user_favorites ADD CONSTRAINT foreign_user_favorites_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;"))? print_r("ok<br>"):print_r("failed<br>");
echo "cleaning, the table from deleted image favorites...<br>";
$rows = $db->get_all("SELECT * FROM user_favorites WHERE image_id NOT IN ( SELECT id FROM images );");
foreach( $rows as $key => $value)
	$db->Execute("DELETE FROM user_favorites WHERE image_id = :image_id;", array("image_id" => $value["image_id"]));
echo "adding forign key to image ids...";
($db->Execute("ALTER TABLE user_favorites ADD CONSTRAINT user_favorites_image_id FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE;"))? print_r("ok<br>"):print_r("failed<br>");
echo "adding foreign keys to private messages...";
($db->Execute("ALTER TABLE private_message 
ADD CONSTRAINT foreign_private_message_from_id FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
ADD CONSTRAINT foreign_private_message_to_id FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE;")) ? print_r("ok<br>"):print_r("failed<br>");
echo "DONE!!!!";
?>