<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

final class Upgrade extends Extension
{
    public const KEY = "upgrade";
    public const VERSION_KEY = "db_version";

    #[EventListener]
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

    #[EventListener(priority: 5)]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if (!file_exists("data/index.php")) {
            file_put_contents("data/index.php", "<?php\n// Silence is golden...\n");
        }

        if ($this->get_version() < 1) {
            $this->set_version(2);
        }

        // v7 is convert to innodb with adodb
        // now done again as v9 with PDO

        if ($this->get_version() < 8) {
            $database->execute(
                "ALTER TABLE images ADD COLUMN locked BOOLEAN NOT NULL DEFAULT FALSE"
            );

            $this->set_version(8);
        }

        if ($this->get_version() < 9) {
            if ($database->get_driver_id() === DatabaseDriverID::MYSQL) {
                $tables = $database->get_col("SHOW TABLES");
                foreach ($tables as $table) {
                    Log::info("upgrade", "converting $table to innodb");
                    // @phpstan-ignore-next-line
                    Ctx::$database->execute("ALTER TABLE $table ENGINE=INNODB");
                }
            }

            $this->set_version(9);
        }

        if ($this->get_version() < 10) {
            Log::info("upgrade", "Adding foreign keys to images");
            $database->execute("ALTER TABLE images ADD FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT");

            $this->set_version(10);
        }

        if ($this->get_version() < 11) {
            Log::info("upgrade", "Converting user flags to classes");
            $database->execute("ALTER TABLE users ADD COLUMN class VARCHAR(32) NOT NULL default :user", ["user" => "user"]);
            $database->execute("UPDATE users SET class = :name WHERE id=:id", ["name" => "anonymous", "id" => Ctx::$config->get(UserAccountsConfig::ANON_ID)]);
            $database->execute("UPDATE users SET class = :name WHERE admin=:admin", ["name" => "admin", "admin" => 'Y']);

            $this->set_version(11);
        }

        if ($this->get_version() < 12) {
            if ($database->get_driver_id() === DatabaseDriverID::PGSQL) {
                Log::info("upgrade", "Changing ext column to VARCHAR");
                $database->execute("ALTER TABLE images ALTER COLUMN ext SET DATA TYPE VARCHAR(4)");
            }

            Log::info("upgrade", "Lowering case of all exts");
            $database->execute("UPDATE images SET ext = LOWER(ext)");

            $this->set_version(12);
        }

        if ($this->get_version() < 13) {
            Log::info("upgrade", "Changing password column to VARCHAR(250)");
            if ($database->get_driver_id() === DatabaseDriverID::PGSQL) {
                $database->execute("ALTER TABLE users ALTER COLUMN pass SET DATA TYPE VARCHAR(250)");
            } elseif ($database->get_driver_id() === DatabaseDriverID::MYSQL) {
                $database->execute("ALTER TABLE users CHANGE pass pass VARCHAR(250)");
            }

            $this->set_version(13);
        }

        if ($this->get_version() < 14) {
            Log::info("upgrade", "Changing tag column to VARCHAR(255)");
            if ($database->get_driver_id() === DatabaseDriverID::PGSQL) {
                $database->execute('ALTER TABLE tags ALTER COLUMN tag SET DATA TYPE VARCHAR(255)');
                $database->execute('ALTER TABLE aliases ALTER COLUMN oldtag SET DATA TYPE VARCHAR(255)');
                $database->execute('ALTER TABLE aliases ALTER COLUMN newtag SET DATA TYPE VARCHAR(255)');
            } elseif ($database->get_driver_id() === DatabaseDriverID::MYSQL) {
                $database->execute('ALTER TABLE tags MODIFY COLUMN tag VARCHAR(255) NOT NULL');
                $database->execute('ALTER TABLE aliases MODIFY COLUMN oldtag VARCHAR(255) NOT NULL');
                $database->execute('ALTER TABLE aliases MODIFY COLUMN newtag VARCHAR(255) NOT NULL');
            }

            $this->set_version(14);
        }

        if ($this->get_version() < 15) {
            Log::info("upgrade", "Adding lower indexes for postgresql use");
            if ($database->get_driver_id() === DatabaseDriverID::PGSQL) {
                $database->execute('CREATE INDEX tags_lower_tag_idx ON tags ((lower(tag)))');
                $database->execute('CREATE INDEX users_lower_name_idx ON users ((lower(name)))');
            }

            $this->set_version(15);
        }

        if ($this->get_version() < 16) {
            Log::info("upgrade", "Adding tag_id, image_id index to image_tags");
            $database->execute('CREATE UNIQUE INDEX image_tags_tag_id_image_id_idx ON image_tags(tag_id,image_id) ');

            Log::info("upgrade", "Changing filename column to VARCHAR(255)");
            if ($database->get_driver_id() === DatabaseDriverID::PGSQL) {
                $database->execute('ALTER TABLE images ALTER COLUMN filename SET DATA TYPE VARCHAR(255)');
                // Postgresql creates a unique index for unique columns, not just a constraint,
                // so we don't need two indexes on the same column
                $database->execute('DROP INDEX IF EXISTS images_hash_idx');
                $database->execute('DROP INDEX IF EXISTS users_name_idx');
            } elseif ($database->get_driver_id() === DatabaseDriverID::MYSQL) {
                $database->execute('ALTER TABLE images MODIFY COLUMN filename VARCHAR(255) NOT NULL');
            }
            // SQLite doesn't support altering existing columns? This seems like a problem?

            $this->set_version(16);
        }

        if ($this->get_version() < 17) {
            Log::info("upgrade", "Adding media information columns to images table");
            $database->execute("ALTER TABLE images ADD COLUMN lossless BOOLEAN NULL");
            $database->execute("ALTER TABLE images ADD COLUMN video BOOLEAN NULL");
            $database->execute("ALTER TABLE images ADD COLUMN audio BOOLEAN NULL");
            $database->execute("ALTER TABLE images ADD COLUMN length INTEGER NULL ");

            Log::info("upgrade", "Setting indexes for media columns");
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

            Log::info("upgrade", "Setting index for ext column");
            $database->execute('CREATE INDEX images_ext_idx ON images(ext)');

            $this->set_version(17);
        }

        // 18 was populating data using an out of date format

        if ($this->get_version() < 19) {
            Log::info("upgrade", "Adding MIME type column");

            $database->execute("ALTER TABLE images ADD COLUMN mime varchar(512) NULL");
            // Column is primed in mime extension
            Log::info("upgrade", "Setting index for mime column");
            $database->execute('CREATE INDEX images_mime_idx ON images(mime)');

            $this->set_version(19);
        }

        if ($this->get_version() < 20) {
            $database->standardise_boolean("images", "lossless");
            $database->standardise_boolean("images", "video");
            $database->standardise_boolean("images", "audio");
            $this->set_version(20);
        }

        if ($this->get_version() < 21) {
            Log::info("upgrade", "Setting predictable media values for known file types");
            if ($database->is_transaction_open()) {
                // Each of these commands could hit a lot of data, combining
                // them into one big transaction would not be a good idea.
                $database->commit();
            }
            $database->execute("UPDATE images SET lossless = TRUE, video = TRUE WHERE ext IN ('swf')");
            $database->execute("UPDATE images SET lossless = FALSE, video = FALSE, audio = TRUE WHERE ext IN ('mp3')");
            $database->execute("UPDATE images SET lossless = FALSE, video = FALSE, audio = FALSE WHERE ext IN ('jpg','jpeg')");
            $database->execute("UPDATE images SET lossless = TRUE, video = FALSE, audio = FALSE WHERE ext IN ('ico','ani','cur','png','svg')");
            $database->execute("UPDATE images SET lossless = TRUE, audio = FALSE WHERE ext IN ('gif')");
            $database->execute("UPDATE images SET audio = FALSE WHERE ext IN ('webp')");
            $database->execute("UPDATE images SET lossless = FALSE, video = TRUE WHERE ext IN ('flv','mp4','m4v','ogv','webm')");
            $this->set_version(21);
            $database->begin_transaction();
        }
    }
}
