<?php declare(strict_types=1);

class Upgrade extends Extension
{
    public function onCommand(CommandEvent $event)
    {
        if ($event->cmd == "help") {
            print "\tdb-upgrade\n";
            print "\t\tRun DB schema updates, if automatic updates are disabled\n\n";
        }
        if ($event->cmd == "db-upgrade") {
            print("Running DB Upgrade\n");
            global $database;
            $database->set_timeout(300000); // These updates can take a little bit
            send_event(new DatabaseUpgradeEvent());
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $config, $database;

        if (!file_exists("data/index.php")) {
            file_put_contents("data/index.php", "<?php\n// Silence is golden...\n");
        }

        if ($config->get_int("db_version") < 1) {
            $this->set_version("db_version", 2);
        }

        // v7 is convert to innodb with adodb
        // now done again as v9 with PDO

        if ($this->get_version("db_version") < 8) {
            $database->execute($database->scoreql_to_sql(
                "ALTER TABLE images ADD COLUMN locked SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N"
            ));

            $this->set_version("db_version", 8);
        }

        if ($this->get_version("db_version") < 9) {
            if ($database->get_driver_name() == DatabaseDriver::MYSQL) {
                $tables = $database->get_col("SHOW TABLES");
                foreach ($tables as $table) {
                    log_info("upgrade", "converting $table to innodb");
                    $database->execute("ALTER TABLE $table ENGINE=INNODB");
                }
            }

            $this->set_version("db_version", 9);
        }

        if ($this->get_version("db_version") < 10) {
            log_info("upgrade", "Adding foreign keys to images");
            $database->Execute("ALTER TABLE images ADD FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT");

            $this->set_version("db_version", 10);
        }

        if ($this->get_version("db_version") < 11) {
            log_info("upgrade", "Converting user flags to classes");
            $database->execute("ALTER TABLE users ADD COLUMN class VARCHAR(32) NOT NULL default :user", ["user" => "user"]);
            $database->execute("UPDATE users SET class = :name WHERE id=:id", ["name"=>"anonymous", "id"=>$config->get_int('anon_id')]);
            $database->execute("UPDATE users SET class = :name WHERE admin=:admin", ["name"=>"admin", "admin"=>'Y']);

            $this->set_version("db_version", 11);
        }

        if ($this->get_version("db_version") < 12) {
            if ($database->get_driver_name() == DatabaseDriver::PGSQL) {
                log_info("upgrade", "Changing ext column to VARCHAR");
                $database->execute("ALTER TABLE images ALTER COLUMN ext SET DATA TYPE VARCHAR(4)");
            }

            log_info("upgrade", "Lowering case of all exts");
            $database->execute("UPDATE images SET ext = LOWER(ext)");

            $this->set_version("db_version", 12);
        }

        if ($this->get_version("db_version") < 13) {
            log_info("upgrade", "Changing password column to VARCHAR(250)");
            if ($database->get_driver_name() == DatabaseDriver::PGSQL) {
                $database->execute("ALTER TABLE users ALTER COLUMN pass SET DATA TYPE VARCHAR(250)");
            } elseif ($database->get_driver_name() == DatabaseDriver::MYSQL) {
                $database->execute("ALTER TABLE users CHANGE pass pass VARCHAR(250)");
            }

            $this->set_version("db_version", 13);
        }

        if ($this->get_version("db_version") < 14) {
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

            $this->set_version("db_version", 14);
        }

        if ($this->get_version("db_version") < 15) {
            log_info("upgrade", "Adding lower indexes for postgresql use");
            if ($database->get_driver_name() == DatabaseDriver::PGSQL) {
                $database->execute('CREATE INDEX tags_lower_tag_idx ON tags ((lower(tag)))');
                $database->execute('CREATE INDEX users_lower_name_idx ON users ((lower(name)))');
            }

            $this->set_version("db_version", 15);
        }

        if ($this->get_version("db_version") < 16) {
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

            $this->set_version("db_version", 16);
        }

        if ($this->get_version("db_version") < 17) {
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

            $this->set_version("db_version", 17);
        }

        if ($this->get_version("db_version") < 18) {
            log_info("upgrade", "Setting predictable media values for known file types");
            if ($database->transaction) {
                // Each of these commands could hit a lot of data, combining
                // them into one big transaction would not be a good idea.
                $database->commit();
            }
            $database->execute($database->scoreql_to_sql("UPDATE images SET lossless = SCORE_BOOL_Y, video = SCORE_BOOL_Y WHERE ext IN ('swf')"));
            $database->execute($database->scoreql_to_sql("UPDATE images SET lossless = SCORE_BOOL_N, video = SCORE_BOOL_N, audio = SCORE_BOOL_Y WHERE ext IN ('mp3')"));
            $database->execute($database->scoreql_to_sql("UPDATE images SET lossless = SCORE_BOOL_N, video = SCORE_BOOL_N, audio = SCORE_BOOL_N WHERE ext IN ('jpg','jpeg')"));
            $database->execute($database->scoreql_to_sql("UPDATE images SET lossless = SCORE_BOOL_Y, video = SCORE_BOOL_N, audio = SCORE_BOOL_N WHERE ext IN ('ico','ani','cur','png','svg')"));
            $database->execute($database->scoreql_to_sql("UPDATE images SET lossless = SCORE_BOOL_Y, audio = SCORE_BOOL_N WHERE ext IN ('gif')"));
            $database->execute($database->scoreql_to_sql("UPDATE images SET audio = SCORE_BOOL_N WHERE ext IN ('webp')"));
            $database->execute($database->scoreql_to_sql("UPDATE images SET lossless = SCORE_BOOL_N, video = SCORE_BOOL_Y WHERE ext IN ('flv','mp4','m4v','ogv','webm')"));
            $this->set_version("db_version", 18);
        }

        if ($this->get_version("db_version") < 19) {
            log_info("upgrade", "Adding MIME type column");

            $database->execute($database->scoreql_to_sql(
                "ALTER TABLE images ADD COLUMN mime varchar(512) NULL"
            ));
            // Column is primed in mime extension
            log_info("upgrade", "Setting index for mime column");
            $database->execute('CREATE INDEX images_mime_idx ON images(mime)');

            $this->set_version("db_version", 19);
        }
    }

    public function get_priority(): int
    {
        return 5;
    }
}
