<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{CODE,rawHTML};

class ETServer extends Extension
{
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database, $page, $user;
        if ($event->page_matches("register.php")) {
            $data = $event->get_POST("data");
            if ($data) {
                $database->execute(
                    "INSERT INTO registration(data) VALUES(:data)",
                    ["data" => $data]
                );
                $page->set_title("Thanks!");
                $page->add_block(new Block("Thanks!", rawHTML("Your data has been recorded~")));
            } elseif ($user->can(Permissions::VIEW_REGISTRATIONS)) {
                $page->set_title("Registrations");
                $n = 0;
                foreach ($database->get_all("SELECT responded, data FROM registration ORDER BY responded DESC") as $row) {
                    $page->add_block(new Block(
                        $row["responded"],
                        CODE(["style" => "text-align: left; overflow: scroll;"], $row["data"]),
                        "main",
                        $n++
                    ));
                }
            }
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        // shortcut to latest
        if ($this->get_version("et_server_version") < 1) {
            $database->create_table("registration", "
				id SCORE_AIPK,
				responded TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				data TEXT NOT NULL,
			");
            $this->set_version("et_server_version", 1);
        }
    }
}
