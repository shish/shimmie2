<?php
/*
 * Name: Database Upgrader
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Keeps things happy behind the scenes
 * Visibility: admin
 */

class Upgrade extends Extension {
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
			$config->set_bool("in_upgrade", true);
			$config->set_int("db_version", 8);

			$database->execute($database->scoreql_to_sql(
				"ALTER TABLE images ADD COLUMN locked SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N"
			));

			log_info("upgrade", "Database at version 8");
			$config->set_bool("in_upgrade", false);
		}

		if($config->get_int("db_version") < 9) {
			$config->set_bool("in_upgrade", true);
			$config->set_int("db_version", 9);

			if($database->get_driver_name() == 'mysql') {
				$tables = $database->get_col("SHOW TABLES");
				foreach($tables as $table) {
					log_info("upgrade", "converting $table to innodb");
					$database->execute("ALTER TABLE $table ENGINE=INNODB");
				}
			}

			log_info("upgrade", "Database at version 9");
			$config->set_bool("in_upgrade", false);
		}

		if($config->get_int("db_version") < 10) {
			$config->set_bool("in_upgrade", true);
			$config->set_int("db_version", 10);

			log_info("upgrade", "Adding foreign keys to images");
			$database->Execute("ALTER TABLE images ADD FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT");
		
			log_info("upgrade", "Database at version 10");
			$config->set_bool("in_upgrade", false);
		}

		if($config->get_int("db_version") < 11) {
			$config->set_bool("in_upgrade", true);
			$config->set_int("db_version", 11);

			log_info("upgrade", "Converting user flags to classes");
			$database->execute("ALTER TABLE users ADD COLUMN class VARCHAR(32) NOT NULL default :user", array("user" => "user"));
			$database->execute("UPDATE users SET class = :name WHERE id=:id", array("name"=>"anonymous", "id"=>$config->get_int('anon_id')));
			$database->execute("UPDATE users SET class = :name WHERE admin=:admin", array("name"=>"admin", "admin"=>'Y'));

			log_info("upgrade", "Database at version 11");
			$config->set_bool("in_upgrade", false);
		}

		if($config->get_int("db_version") < 12) {
			$config->set_bool("in_upgrade", true);
			$config->set_int("db_version", 12);

			if($database->get_driver_name() == 'pgsql') {
				log_info("upgrade", "Changing ext column to VARCHAR");
				$database->execute("ALTER TABLE images ALTER COLUMN ext SET DATA TYPE VARCHAR(4)");
			}

			log_info("upgrade", "Lowering case of all exts");
			$database->execute("UPDATE images SET ext = LOWER(ext)");

			log_info("upgrade", "Database at version 12");
			$config->set_bool("in_upgrade", false);
		}

		if($config->get_int("db_version") < 13) {
			$config->set_bool("in_upgrade", true);
			$config->set_int("db_version", 13);

			log_info("upgrade", "Changing password column to VARCHAR(250)");
			if($database->get_driver_name() == 'pgsql') {
				$database->execute("ALTER TABLE users ALTER COLUMN pass SET DATA TYPE VARCHAR(250)");
			}
			else if($database->get_driver_name() == 'mysql') {
				$database->execute("ALTER TABLE users CHANGE pass pass VARCHAR(250)");
			}

			log_info("upgrade", "Database at version 13");
			$config->set_bool("in_upgrade", false);
		}

		if($config->get_int("db_version") < 14) {
			$config->set_bool("in_upgrade", true);
			$config->set_int("db_version", 14);

			log_info("upgrade", "Changing tag column to VARCHAR(255)");
			if($database->get_driver_name() == 'pgsql') {
				$database->execute('ALTER TABLE tags ALTER COLUMN tag SET DATA TYPE VARCHAR(255)');
				$database->execute('ALTER TABLE aliases ALTER COLUMN oldtag SET DATA TYPE VARCHAR(255)');
				$database->execute('ALTER TABLE aliases ALTER COLUMN newtag SET DATA TYPE VARCHAR(255)');
			}
			else if($database->get_driver_name() == 'mysql') {
				$database->execute('ALTER TABLE tags MODIFY COLUMN tag VARCHAR(255) NOT NULL');
				$database->execute('ALTER TABLE aliases MODIFY COLUMN oldtag VARCHAR(255) NOT NULL');
				$database->execute('ALTER TABLE aliases MODIFY COLUMN newtag VARCHAR(255) NOT NULL');
			}

			log_info("upgrade", "Database at version 14");
			$config->set_bool("in_upgrade", false);
		}
	}

	public function get_priority() {return 5;}
}

