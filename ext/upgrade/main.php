<?php

class Upgrade extends Extension
{
    public function onInitExt(InitExtEvent $event)
    {
        global $config, $database;

        if ($config->get_bool("in_upgrade")) {
            return;
        }

        if (!is_numeric($config->get_string("db_version"))) {
            $config->set_int("db_version", 2);
        }

        if ($config->get_int("db_version") < 6) {
            // cry :S
        }

        // v7 is convert to innodb with adodb
        // now done again as v9 with PDO

        if ($config->get_int("db_version") < 8) {
            $config->set_bool("in_upgrade", true);
            $config->set_int("db_version", 8);

            $database->execute($database->scoreql_to_sql(
                "ALTER TABLE images ADD COLUMN locked SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N"
            ));

            log_info("upgrade", "Database at version 8");
            $config->set_bool("in_upgrade", false);
        }

        if ($config->get_int("db_version") < 9) {
            $config->set_bool("in_upgrade", true);
            $config->set_int("db_version", 9);

            if ($database->get_driver_name() == DatabaseDriver::MYSQL) {
                $tables = $database->get_col("SHOW TABLES");
                foreach ($tables as $table) {
                    log_info("upgrade", "converting $table to innodb");
                    $database->execute("ALTER TABLE $table ENGINE=INNODB");
                }
            }

            log_info("upgrade", "Database at version 9");
            $config->set_bool("in_upgrade", false);
        }

        if ($config->get_int("db_version") < 10) {
            $config->set_bool("in_upgrade", true);
            $config->set_int("db_version", 10);

            log_info("upgrade", "Adding foreign keys to images");
            $database->Execute("ALTER TABLE images ADD FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT");

            log_info("upgrade", "Database at version 10");
            $config->set_bool("in_upgrade", false);
        }

        if ($config->get_int("db_version") < 11) {
            $config->set_bool("in_upgrade", true);
            $config->set_int("db_version", 11);

            log_info("upgrade", "Converting user flags to classes");
            $database->execute("ALTER TABLE users ADD COLUMN class VARCHAR(32) NOT NULL default :user", ["user" => "user"]);
            $database->execute("UPDATE users SET class = :name WHERE id=:id", ["name"=>"anonymous", "id"=>$config->get_int('anon_id')]);
            $database->execute("UPDATE users SET class = :name WHERE admin=:admin", ["name"=>"admin", "admin"=>'Y']);

            log_info("upgrade", "Database at version 11");
            $config->set_bool("in_upgrade", false);
        }

        if ($config->get_int("db_version") < 12) {
            $config->set_bool("in_upgrade", true);
            $config->set_int("db_version", 12);

            if ($database->get_driver_name() == DatabaseDriver::PGSQL) {
                log_info("upgrade", "Changing ext column to VARCHAR");
                $database->execute("ALTER TABLE images ALTER COLUMN ext SET DATA TYPE VARCHAR(4)");
            }

            log_info("upgrade", "Lowering case of all exts");
            $database->execute("UPDATE images SET ext = LOWER(ext)");

            log_info("upgrade", "Database at version 12");
            $config->set_bool("in_upgrade", false);
        }

        if ($config->get_int("db_version") < 13) {
            $config->set_bool("in_upgrade", true);
            $config->set_int("db_version", 13);

            log_info("upgrade", "Changing password column to VARCHAR(250)");
            if ($database->get_driver_name() == DatabaseDriver::PGSQL) {
                $database->execute("ALTER TABLE users ALTER COLUMN pass SET DATA TYPE VARCHAR(250)");
            } elseif ($database->get_driver_name() == DatabaseDriver::MYSQL) {
                $database->execute("ALTER TABLE users CHANGE pass pass VARCHAR(250)");
            }

            log_info("upgrade", "Database at version 13");
            $config->set_bool("in_upgrade", false);
        }

        if ($config->get_int("db_version") < 14) {
            $config->set_bool("in_upgrade", true);
            $config->set_int("db_version", 14);

            log_info("upgrade", "Changing tag column to VARCHAR(255)");
            if ($database->get_driver_name() == DatabaseDriver::PGSQL) {
                $database->execute('ALTER TABLE tags ALTER COLUMN tag SET DATA TYPE VARCHAR(255)');
                $database->execute('ALTER TABLE aliases ALTER COLUMN oldtag SET DATA TYPE VARCHAR(255)');
                $database->execute('ALTER TABLE aliases ALTER COLUMN newtag SET DATA TYPE VARCHAR(255)');
            } elseif ($database->get_driver_name() == DatabaseDriver::MYSQL) {
                $database->execute('ALTER TABLE tags MODIFY COLUMN tag VARCHAR(255) NOT NULL');
                $database->execute('ALTER TABLE aliases MODIFY COLUMN oldtag VARCHAR(255) NOT NULL');
                $database->execute('ALTER TABLE aliases MODIFY COLUMN newtag VARCHAR(255) NOT NULL');
            }

            log_info("upgrade", "Database at version 14");
            $config->set_bool("in_upgrade", false);
        }

        if ($config->get_int("db_version") < 15) {
            $config->set_bool("in_upgrade", true);
            $config->set_int("db_version", 15);

            log_info("upgrade", "Adding lower indexes for postgresql use");
            if ($database->get_driver_name() == DatabaseDriver::PGSQL) {
                $database->execute('CREATE INDEX tags_lower_tag_idx ON tags ((lower(tag)))');
                $database->execute('CREATE INDEX users_lower_name_idx ON users ((lower(name)))');
            }

            log_info("upgrade", "Database at version 15");
            $config->set_bool("in_upgrade", false);
        }

        if ($config->get_int("db_version") < 16) {
            $config->set_bool("in_upgrade", true);
            $config->set_int("db_version", 16);

            log_info("upgrade", "Adding tag_id, image_id index to image_tags");
            $database->execute('CREATE UNIQUE INDEX image_tags_tag_id_image_id_idx ON image_tags(tag_id,image_id) ');

            log_info("upgrade", "Changing filename column to VARCHAR(255)");
            if ($database->get_driver_name() == DatabaseDriver::PGSQL) {
                $database->execute('ALTER TABLE images ALTER COLUMN filename SET DATA TYPE VARCHAR(255)');
                // Postgresql creates a unique index for unique columns, not just a constraint,
                // so we don't need two indexes on the same column
                $database->execute('DROP INDEX IF EXISTS images_hash_idx');
                $database->execute('DROP INDEX IF EXISTS users_name_idx');
            } elseif ($database->get_driver_name() == DatabaseDriver::MYSQL) {
                $database->execute('ALTER TABLE images MODIFY COLUMN filename VARCHAR(255) NOT NULL');
            }
            // SQLite doesn't support altering existing columns? This seems like a problem?

            log_info("upgrade", "Database at version 16");
            $config->set_bool("in_upgrade", false);
        }

        if ($config->get_int("db_version") < 17) {
            $config->set_bool("in_upgrade", true);
            $config->set_int("db_version", 17);

            log_info("upgrade", "Adding media information columns to images table");
            $database->execute($database->scoreql_to_sql(
                "ALTER TABLE images ADD COLUMN lossless SCORE_BOOL NULL"
            ));
            $database->execute($database->scoreql_to_sql(
                "ALTER TABLE images ADD COLUMN video SCORE_BOOL NULL"
            ));
            $database->execute($database->scoreql_to_sql(
                "ALTER TABLE images ADD COLUMN audio SCORE_BOOL NULL"
            ));
            $database->execute("ALTER TABLE images ADD COLUMN length INTEGER NULL ");

            log_info("upgrade", "Setting indexes for media columns");
            switch ($database->get_driver_name()) {
                case DatabaseDriver::PGSQL:
                case DatabaseDriver::SQLITE:
                    $database->execute('CREATE INDEX images_video_idx ON images(video) WHERE video IS NOT NULL');
                    $database->execute('CREATE INDEX images_audio_idx ON images(audio) WHERE audio IS NOT NULL');
                    $database->execute('CREATE INDEX images_length_idx ON images(length) WHERE length IS NOT NULL');
                    break;
                default:
                    $database->execute('CREATE INDEX images_video_idx ON images(video)');
                    $database->execute('CREATE INDEX images_audio_idx ON images(audio)');
                    $database->execute('CREATE INDEX images_length_idx ON images(length)');
                    break;
            }

            $database->set_timeout(300000); // These updates can take a little bit

            log_info("upgrade", "Setting index for ext column");
            $database->execute('CREATE INDEX images_ext_idx ON images(ext)');


            $database->commit(); // Each of these commands could hit a lot of data, combining them into one big transaction would not be a good idea.
            log_info("upgrade", "Setting predictable media values for known file types");
            $database->execute($database->scoreql_to_sql("UPDATE images SET lossless = SCORE_BOOL_Y, video = SCORE_BOOL_Y WHERE ext IN ('swf')"));
            $database->execute($database->scoreql_to_sql("UPDATE images SET lossless = SCORE_BOOL_N, video = SCORE_BOOL_N, audio = SCORE_BOOL_Y WHERE ext IN ('mp3')"));
            $database->execute($database->scoreql_to_sql("UPDATE images SET lossless = SCORE_BOOL_N, video = SCORE_BOOL_N, audio = SCORE_BOOL_N WHERE ext IN ('jpg','jpeg')"));
            $database->execute($database->scoreql_to_sql("UPDATE images SET lossless = SCORE_BOOL_Y, video = SCORE_BOOL_N, audio = SCORE_BOOL_N WHERE ext IN ('ico','ani','cur','png','svg')"));
            $database->execute($database->scoreql_to_sql("UPDATE images SET lossless = SCORE_BOOL_Y, audio = SCORE_BOOL_N WHERE ext IN ('gif')"));
            $database->execute($database->scoreql_to_sql("UPDATE images SET audio = SCORE_BOOL_N WHERE ext IN ('webp')"));
            $database->execute($database->scoreql_to_sql("UPDATE images SET lossless = SCORE_BOOL_N, video = SCORE_BOOL_Y WHERE ext IN ('flv','mp4','m4v','ogv','webm')"));


            log_info("upgrade", "Database at version 17");
            $config->set_bool("in_upgrade", false);
        }
    }

    public function get_priority(): int
    {
        return 5;
    }
}
