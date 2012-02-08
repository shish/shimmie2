<?php
/*
 * Name: Database Upgrader
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Keeps things happy behind the scenes
 * Visibility: admin
 */

class Upgrade extends SimpleExtension {
	public function onInitExt(InitExtEvent $event) {
		global $config, $database;

		if($config->get_bool("in_upgrade")) return;

		if(!is_numeric($config->get_string("db_version"))) {
			$config->set_int("db_version", 2);
		}

		if($config->get_int("db_version") < 6) {
			// cry :S
		}

		// v7 is convert to innodb with adodb
		// now done again as v9 with PDO

		if($config->get_int("db_version") < 8) {
			// if this fails, don't try again
			$config->set_bool("in_upgrade", true);
			$config->set_int("db_version", 8);
			$database->execute($database->engine->scoreql_to_sql(
				"ALTER TABLE images ADD COLUMN locked SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N"
			));
			log_info("upgrade", "Database at version 8");
			$config->set_bool("in_upgrade", false);
		}

		if($config->get_int("db_version") < 9) {
			$config->set_bool("in_upgrade", true);
			if($database->db->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
				$tables = $database->get_col("SHOW TABLES");
				foreach($tables as $table) {
					log_info("upgrade", "converting $table to innodb");
					$database->execute("ALTER TABLE $table TYPE=INNODB");
				}
			}
			$config->set_int("db_version", 9);
			log_info("upgrade", "Database at version 9");
			$config->set_bool("in_upgrade", false);
		}

		if($config->get_int("db_version") < 10) {
			$config->set_bool("in_upgrade", true);

			log_info("upgrade", "Adding foreign keys to images");
			$database->Execute("ALTER TABLE images ADD CONSTRAINT foreign_images_owner_id FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT");
		
			$config->set_int("db_version", 10);
			log_info("upgrade", "Database at version 10");
			$config->set_bool("in_upgrade", false);
		}
	}

	public function get_priority() {return 5;}
}
?>
