<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{PRE, emptyHTML};

final class ETServer extends Extension
{
    public const KEY = "et_server";
    public const VERSION_KEY = "et_server_version";

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database;
        $page = Ctx::$page;
        if ($event->page_matches("register.php")) {
            $data = $event->POST->get("data");
            if ($data) {
                $database->execute(
                    "INSERT INTO registration(data) VALUES(:data)",
                    ["data" => $data]
                );
                $page->set_title("Thanks!");
                $page->add_block(new Block("Thanks!", emptyHTML("Your data has been recorded~")));
            } elseif (Ctx::$user->can(ETServerPermission::VIEW_REGISTRATIONS)) {
                $page->set_title("Registrations");
                $n = 0;
                foreach ($database->get_all("SELECT responded, data FROM registration ORDER BY responded DESC") as $row) {
                    $page->add_block(new Block(
                        $row["responded"],
                        PRE(["style" => "text-align: left; overflow: scroll;"], $row["data"]),
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
        if ($this->get_version() < 1) {
            $database->create_table("registration", "
				id SCORE_AIPK,
				responded TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				data TEXT NOT NULL,
			");
            $this->set_version(1);
        }
    }
}
