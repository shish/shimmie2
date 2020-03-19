<?php declare(strict_types=1);

class ETServer extends Extension
{
    public function onPageRequest(PageRequestEvent $event)
    {
        global $database, $page;
        if ($event->page_matches("register.php")) {
            $database->execute(
                "INSERT INTO registration(data) VALUES(:data)",
                ["data"=>$_POST["data"]]
            );
            $page->add_block(new Block("Thanks!", "Your data has been recorded~"));
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $config, $database;

        // shortcut to latest
        if ($config->get_int("et_server_version") < 1) {
            $database->create_table("registration", "
				id SCORE_AIPK,
				responded TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				data TEXT NOT NULL,
			");
            $config->set_int("et_server_version", 1);
            log_info("et_server", "extension installed");
        }
    }
}
