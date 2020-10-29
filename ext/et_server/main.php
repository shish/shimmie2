<?php declare(strict_types=1);

use function MicroHTML\{PRE};

class ETServer extends Extension
{
    public function onPageRequest(PageRequestEvent $event)
    {
        global $database, $page, $user;
        if ($event->page_matches("register.php")) {
            error_log("register.php");
            if (isset($_POST["data"])) {
                $database->execute(
                    "INSERT INTO registration(data) VALUES(:data)",
                    ["data"=>$_POST["data"]]
                );
                $page->set_title("Thanks!");
                $page->set_heading("Thanks!");
                $page->add_block(new Block("Thanks!", "Your data has been recorded~"));
            } elseif ($user->can(Permissions::VIEW_REGISTRATIONS)) {
                $page->set_title("Registrations");
                $page->set_heading("Registrations");
                $n = 0;
                foreach ($database->get_all("SELECT responded, data FROM registration ORDER BY responded DESC") as $row) {
                    $page->add_block(new Block(
                        $row["responded"],
                        (string)PRE(["style"=>"text-align: left; overflow: scroll;"], $row["data"]),
                        "main",
                        $n++
                    ));
                }
            }
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
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
