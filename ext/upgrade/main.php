<?php

class Upgrade extends Extension {
	public function receive_event($event) {
		if(is_a($event, 'InitExtEvent')) {
			$this->do_things();
		}
	}

	private function do_things() {
		global $config;
		global $database;

		if($config->get_bool("in_upgrade")) {
			return;
		}
		

		if(!is_numeric($config->get_string("db_version"))) {
			$config->set_int("db_version", 2);
		}

		if($config->get_int("db_version") <= 2) {
			$database->Execute("CREATE TABLE layout (
				title varchar(64) primary key not null,
				section varchar(32) not null default \"left\",
				position int not null default 50,
				visible enum('Y', 'N') default 'Y' not null
			)");
			$config->set_int("db_version", 3);
		}

		if($config->get_int("db_version") == 3) {
			$config->set_bool("in_upgrade", true);
			$database->Execute("RENAME TABLE tags TO old_tags");
			$database->Execute("CREATE TABLE tags (
				id int not null auto_increment primary key,
				tag varchar(64) not null unique,
				count int not null default 0,
				KEY tags_count(count)
			)");
			$database->Execute("CREATE TABLE image_tags (
				image_id int NOT NULL default 0,
				tag_id int NOT NULL default 0,
				UNIQUE KEY image_id_tag_id (image_id,tag_id),
				KEY tags_tag_id (tag_id),
				KEY tags_image_id (image_id)
			)");
			$config->set_int("db_version", 4);
			$config->set_bool("in_upgrade", false);
		}
		
		if($config->get_int("db_version") == 4) {
			$config->set_bool("in_upgrade", true);
			$database->Execute("DELETE FROM tags");
			$database->Execute("INSERT INTO tags(tag) SELECT DISTINCT tag FROM old_tags");
			$database->Execute("DELETE FROM image_tags");
			$database->Execute("INSERT INTO image_tags(image_id, tag_id) SELECT old_tags.image_id, tags.id FROM old_tags JOIN tags ON old_tags.tag = tags.tag");
			$database->Execute("UPDATE tags SET count=(SELECT COUNT(image_id) FROM image_tags WHERE tag_id=tags.id GROUP BY tag_id)");
			$config->set_int("db_version", 5);
			$config->set_bool("in_upgrade", false);
		}

		if($config->get_int("db_version") == 5) {
			$config->set_bool("in_upgrade", true);
			$tables = $database->db->GetCol("SHOW TABLES");
			foreach($tables as $table) {
				$database->Execute("ALTER TABLE $table CONVERT TO CHARACTER SET utf8");
			}
			$config->set_int("db_version", 6);
			$config->set_bool("in_upgrade", false);
		}

		if($config->get_int("db_version") == -1) {
			$database->Execute("ALTER TABLE users ADD COLUMN parent INTEGER");
			$database->Execute("ALTER TABLE users ADD COLUMN is_template ENUM('Y','N') DEFAULT 'N'");
			$database->Execute("INSERT INTO users(name, is_template) VALUES(?, 'Y')", array("[Anonymous]"));
			$database->Execute("INSERT INTO users(name, is_template) VALUES(?, 'Y')", array("[User]"));
			$database->Execute("INSERT INTO users(name, is_template) VALUES(?, 'Y')", array("[Moderator]"));
			$database->Execute("INSERT INTO users(name, is_template) VALUES(?, 'Y')", array("[Admin]"));
			$anon_id  = $database->db->GetOne("SELECT id FROM users WHERE name=?", array("[Anonymous]"));
			$user_id  = $database->db->GetOne("SELECT id FROM users WHERE name=?", array("[User]"));
			$admin_id = $database->db->GetOne("SELECT id FROM users WHERE name=?", array("[Admin]"));
			$database->Execute("UPDATE users SET parent=?", array($user_id));
			$database->Execute("UPDATE users SET parent=? WHERE password IS NULL", array($anon_id));
			$database->Execute("UPDATE users SET parent=? WHERE is_admin='Y'", array($admin_id));
			$config->set_int("db_version", 7);
		}
	}
}
add_event_listener(new Upgrade(), 5);
?>
