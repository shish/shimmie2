<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

class Upgrade extends Extension
{
    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('db-upgrade')
            ->setDescription('Run DB schema updates, if automatic updates are disabled')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $output->writeln("Running DB Upgrade");
                global $database;
                $database->set_timeout(null); // These updates can take a little bit
                send_event(new DatabaseUpgradeEvent());
                return Command::SUCCESS;
            });
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $config, $database;

        if (!file_exists("data/index.php")) {
            file_put_contents("data/index.php", "<?php\n// Silence is golden...\n");
        }

        if ($this->get_version("db_version") < 1) {
            $this->set_version("db_version", 2);
        }

        // v7 is convert to innodb with adodb
        // now done again as v9 with PDO

        if ($this->get_version("db_version") < 8) {
            $database->execute(
                "ALTER TABLE images ADD COLUMN locked BOOLEAN NOT NULL DEFAULT FALSE"
            );

            $this->set_version("db_version", 8);
        }

        if ($this->get_version("db_version") < 9) {
            if ($database->get_driver_id() == DatabaseDriverID::MYSQL) {
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
            $database->execute("ALTER TABLE images ADD FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT");

            $this->set_version("db_version", 10);
        }

        if ($this->get_version("db_version") < 11) {
            log_info("upgrade", "Converting user flags to classes");
            $database->execute("ALTER TABLE users ADD COLUMN class VARCHAR(32) NOT NULL default :user", ["user" => "user"]);
            $database->execute("UPDATE users SET class = :name WHERE id=:id", ["name" => "anonymous", "id" => $config->get_int('anon_id')]);
            $database->execute("UPDATE users SET class = :name WHERE admin=:admin", ["name" => "admin", "admin" => 'Y']);

            $this->set_version("db_version", 11);
        }

        if ($this->get_version("db_version") < 12) {
            if ($database->get_driver_id() == DatabaseDriverID::PGSQL) {
                log_info("upgrade", "Changing ext column to VARCHAR");
                $database->execute("ALTER TABLE images ALTER COLUMN ext SET DATA TYPE VARCHAR(4)");
            }

            log_info("upgrade", "Lowering case of all exts");
            $database->execute("UPDATE images SET ext = LOWER(ext)");

            $this->set_version("db_version", 12);
        }

        if ($this->get_version("db_version") < 13) {
            log_info("upgrade", "Changing password column to VARCHAR(250)");
            if ($database->get_driver_id() == DatabaseDriverID::PGSQL) {
                $database->execute("ALTER TABLE users ALTER COLUMN pass SET DATA TYPE VARCHAR(250)");
            } elseif ($database->get_driver_id() == DatabaseDriverID::MYSQL) {
                $database->execute("ALTER TABLE users CHANGE pass pass VARCHAR(250)");
            }

            $this->set_version("db_version", 13);
        }

        if ($this->get_version("db_version") < 14) {
            log_info("upgrade", "Changing tag column to VARCHAR(255)");
            if ($database->get_driver_id() == DatabaseDriverID::PGSQL) {
                $database->execute('ALTER TABLE tags ALTER COLUMN tag SET DATA TYPE VARCHAR(255)');
                $database->execute('ALTER TABLE aliases ALTER COLUMN oldtag SET DATA TYPE VARCHAR(255)');
                $database->execute('ALTER TABLE aliases ALTER COLUMN newtag SET DATA TYPE VARCHAR(255)');
            } elseif ($database->get_driver_id() == DatabaseDriverID::MYSQL) {
                $database->execute('ALTER TABLE tags MODIFY COLUMN tag VARCHAR(255) NOT NULL');
                $database->execute('ALTER TABLE aliases MODIFY COLUMN oldtag VARCHAR(255) NOT NULL');
                $database->execute('ALTER TABLE aliases MODIFY COLUMN newtag VARCHAR(255) NOT NULL');
            }

            $this->set_version("db_version", 14);
        }

        if ($this->get_version("db_version") < 15) {
            log_info("upgrade", "Adding lower indexes for postgresql use");
            if ($database->get_driver_id() == DatabaseDriverID::PGSQL) {
                $database->execute('CREATE INDEX tags_lower_tag_idx ON tags ((lower(tag)))');
                $database->execute('CREATE INDEX users_lower_name_idx ON users ((lower(name)))');
            }

            $this->set_version("db_version", 15);
        }

        if ($this->get_version("db_version") < 16) {
            log_info("upgrade", "Adding tag_id, image_id index to image_tags");
            $database->execute('CREATE UNIQUE INDEX image_tags_tag_id_image_id_idx ON image_tags(tag_id,image_id) ');

            log_info("upgrade", "Changing filename column to VARCHAR(255)");
            if ($database->get_driver_id() == DatabaseDriverID::PGSQL) {
                $database->execute('ALTER TABLE images ALTER COLUMN filename SET DATA TYPE VARCHAR(255)');
                // Postgresql creates a unique index for unique columns, not just a constraint,
                // so we don't need two indexes on the same column
                $database->execute('DROP INDEX IF EXISTS images_hash_idx');
                $database->execute('DROP INDEX IF EXISTS users_name_idx');
            } elseif ($database->get_driver_id() == DatabaseDriverID::MYSQL) {
                $database->execute('ALTER TABLE images MODIFY COLUMN filename VARCHAR(255) NOT NULL');
            }
            // SQLite doesn't support altering existing columns? This seems like a problem?

            $this->set_version("db_version", 16);
        }

        if ($this->get_version("db_version") < 17) {
            log_info("upgrade", "Adding media information columns to images table");
            $database->execute("ALTER TABLE images ADD COLUMN lossless BOOLEAN NULL");
            $database->execute("ALTER TABLE images ADD COLUMN video BOOLEAN NULL");
            $database->execute("ALTER TABLE images ADD COLUMN audio BOOLEAN NULL");
            $database->execute("ALTER TABLE images ADD COLUMN length INTEGER NULL ");

            log_info("upgrade", "Setting indexes for media columns");
            switch ($database->get_driver_id()) {
                case DatabaseDriverID::PGSQL:
                case DatabaseDriverID::SQLITE:
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

            $database->set_timeout(null); // These updates can take a little bit

            log_info("upgrade", "Setting index for ext column");
            $database->execute('CREATE INDEX images_ext_idx ON images(ext)');

            $this->set_version("db_version", 17);
        }

        // 18 was populating data using an out of date format

        if ($this->get_version("db_version") < 19) {
            log_info("upgrade", "Adding MIME type column");

            $database->execute("ALTER TABLE images ADD COLUMN mime varchar(512) NULL");
            // Column is primed in mime extension
            log_info("upgrade", "Setting index for mime column");
            $database->execute('CREATE INDEX images_mime_idx ON images(mime)');

            $this->set_version("db_version", 19);
        }

        if ($this->get_version("db_version") < 20) {
            $database->standardise_boolean("images", "lossless");
            $database->standardise_boolean("images", "video");
            $database->standardise_boolean("images", "audio");
            $this->set_version("db_version", 20);
        }

        if ($this->get_version("db_version") < 21) {
            log_info("upgrade", "Setting predictable media values for known file types");
            if ($database->is_transaction_open()) {
                // Each of these commands could hit a lot of data, combining
                // them into one big transaction would not be a good idea.
                $database->commit();
            }
            $database->execute("UPDATE images SET lossless = :t, video = :t WHERE ext IN ('swf')", ["t" => true]);
            $database->execute("UPDATE images SET lossless = :f, video = :f, audio = :t WHERE ext IN ('mp3')", ["t" => true, "f" => false]);
            $database->execute("UPDATE images SET lossless = :f, video = :f, audio = :f WHERE ext IN ('jpg','jpeg')", ["f" => false]);
            $database->execute("UPDATE images SET lossless = :t, video = :f, audio = :f WHERE ext IN ('ico','ani','cur','png','svg')", ["t" => true, "f" => false]);
            $database->execute("UPDATE images SET lossless = :t, audio = :f WHERE ext IN ('gif')", ["t" => true, "f" => false]);
            $database->execute("UPDATE images SET audio = :f WHERE ext IN ('webp')", ["f" => false]);
            $database->execute("UPDATE images SET lossless = :f, video = :t WHERE ext IN ('flv','mp4','m4v','ogv','webm')", ["t" => true, "f" => false]);
            $this->set_version("db_version", 21);
            $database->begin_transaction();
        }

        if ($this->get_version("db_version") < 22) {
            log_info("upgrade", "Adding permissions table");
            $permissions = ["change_setting", "override_config", "change_user_setting", "change_other_user_setting", "big_search", "manage_extension_list", "manage_permission_list", "manage_alias_list", "manage_auto_tag", "mass_tag_edit", "view_ip", "ban_ip", "create_user", "create_other_user", "edit_user_name", "edit_user_password", "edit_user_info", "edit_user_class", "delete_user", "create_comment", "delete_comment", "bypass_comment_checks", "replace_image", "create_image", "edit_image_tag", "edit_image_source", "edit_image_owner", "edit_image_lock", "edit_image_title", "edit_image_relationships", "edit_image_artist", "bulk_edit_image_tag", "bulk_edit_image_source", "delete_image", "ban_image", "view_eventlog", "ignore_downtime", "view_registrations", "create_image_report", "view_image_report", "wiki_admin", "edit_wiki_page", "delete_wiki_page", "manage_blocks", "manage_admintools", "send_pm", "read_pm", "view_other_pms", "edit_feature", "create_vote", "bulk_edit_vote", "edit_other_vote", "view_sysinfo", "hellbanned", "view_hellbanned", "protected", "edit_image_rating", "bulk_edit_image_rating", "view_trash", "perform_bulk_actions", "bulk_add", "edit_files", "edit_tag_categories", "rescan_media", "see_image_view_counts", "edit_favourites", "artists_admin", "blotter_admin", "tips_admin", "cron_admin", "approve_image", "approve_comment", "bypass_image_approval", "forum_admin", "forum_create", "notes_admin", "notes_create", "notes_edit", "notes_request", "pools_admin", "pools_create", "pools_update", "set_private_image", "set_others_private_images", "cron_run", "bulk_import", "bulk_export", "bulk_download", "bulk_parent_child"];
            $perms_query = implode(" BOOLEAN,\n", $permissions);
            $perms_query .= " BOOLEAN,\n";


            // id is needed to keep dependencies in order when loading
            $database->create_table("permissions", "
                id SCORE_AIPK,
                class VARCHAR(32) NOT NULL UNIQUE,
                parent VARCHAR(32) NULL,
                core BOOLEAN,
                {$perms_query}
                ");
            $database->execute("CREATE INDEX permissions_class_idx ON permissions(class)", []);

            $database->standardise_boolean("permissions", "core");
            foreach($permissions as $p) {
                $database->standardise_boolean("permissions", $p);
            }

            // add default classes
            $database->execute("INSERT INTO permissions (class, core) VALUES ('base', TRUE)");
            // admin is a placeholder class which is overridden in UserClass->can()
            $database->execute("INSERT INTO permissions (class, core) VALUES ('admin', TRUE)");
            $database->execute("INSERT INTO permissions (class, parent, core, read_pm) VALUES ('ghost', 'base', TRUE, TRUE)");
            $database->execute("INSERT INTO permissions (class, parent, core, create_user) VALUES ('anonymous', 'base', TRUE, TRUE)");
            $database->execute("INSERT INTO permissions (class, parent, core, big_search, create_image, create_comment, edit_image_tag, edit_image_source, edit_image_title, edit_image_relationships, edit_image_artist, create_image_report, edit_image_rating, edit_favourites, create_vote, send_pm, read_pm, set_private_image, perform_bulk_actions, bulk_download, change_user_setting, forum_create, notes_create, notes_edit, notes_request, pools_create, pools_update) VALUES ('user', 'base', TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE)");
            $database->execute("INSERT INTO permissions (class, parent, core, hellbanned) VALUES ('hellbanned', 'user', TRUE, TRUE)");

            // All user's classes must exist in the permissions table. prevent deletion of a class if any users have that class.
            // This code is optional and SQLite doesn't support altering tables to add foreign keys.
            if ($database->get_driver_id() != DatabaseDriverID::SQLITE) {
                $database->execute("ALTER TABLE users ADD FOREIGN KEY (class) REFERENCES permissions(class) ON DELETE RESTRICT");
            }
            $this->set_version("db_version", 22);
        }
    }

    public function get_priority(): int
    {
        return 5;
    }
}
