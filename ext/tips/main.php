<?php declare(strict_types=1);

class CreateTipEvent extends Event
{
    public bool $enable;
    public string $image;
    public string $text;

    public function __construct(bool $enable, string $image, string $text)
    {
        parent::__construct();
        $this->enable = $enable;
        $this->image = $image;
        $this->text = $text;
    }
}

class DeleteTipEvent extends Event
{
    public int $tip_id;
    public function __construct(int $tip_id)
    {
        parent::__construct();
        $this->tip_id = $tip_id;
    }
}

class Tips extends Extension
{
    /** @var TipsTheme */
    protected ?Themelet $theme;

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $database;

        if ($this->get_version("ext_tips_version") < 1) {
            $database->create_table("tips", "
                id SCORE_AIPK,
                enable BOOLEAN NOT NULL DEFAULT FALSE,
                image TEXT NOT NULL,
                text TEXT NOT NULL,
            ");

            $database->execute(
                "
					INSERT INTO tips (enable, image, text)
					VALUES (:enable, :image, :text)",
                ["enable"=>true, "image"=>"coins.png", "text"=>"Do you like this extension? Please support us for developing new ones. <a href=\"https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8235933\" target=\"_blank\">Donate through paypal</a>."]
            );

            $this->set_version("ext_tips_version", 2);
        }
        if ($this->get_version("ext_tips_version") < 2) {
            $database->standardise_boolean("tips", "enable");
            $this->set_version("ext_tips_version", 2);
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        $this->getTip();

        if ($event->page_matches("tips") && $user->can(Permissions::TIPS_ADMIN)) {
            switch ($event->get_arg(0)) {
                case "list":
                    $this->manageTips();
                    $this->getAll();
                    break;
                case "save":
                    if ($user->check_auth_token()) {
                        send_event(new CreateTipEvent(isset($_POST["enable"]), $_POST["image"], $_POST["text"]));
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("tips/list"));
                    }
                    break;
                case "status":
                    // FIXME: HTTP GET CSRF
                    $tipID = int_escape($event->get_arg(1));
                    $this->setStatus($tipID);
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("tips/list"));
                    break;
                case "delete":
                    // FIXME: HTTP GET CSRF
                    $tipID = int_escape($event->get_arg(1));
                    send_event(new DeleteTipEvent($tipID));
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("tips/list"));
                    break;
            }
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if ($event->parent==="system") {
            if ($user->can(Permissions::TIPS_ADMIN)) {
                $event->add_nav_link("tips", new Link('tips/list'), "Tips Editor");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::TIPS_ADMIN)) {
            $event->add_link("Tips Editor", make_link("tips/list"));
        }
    }

    private function manageTips()
    {
        $data_href = get_base_href();
        $url = $data_href."/ext/tips/images/";

        $dirPath = dir('./ext/tips/images');
        $images = [];
        while (($file = $dirPath->read()) !== false) {
            if ($file[0] != ".") {
                $images[] = trim($file);
            }
        }
        $dirPath->close();
        sort($images);

        $this->theme->manageTips($url, $images);
    }

    public function onCreateTip(CreateTipEvent $event)
    {
        global $database;
        $database->execute(
            "
				INSERT INTO tips (enable, image, text)
				VALUES (:enable, :image, :text)",
            ["enable"=>$event->enable, "image"=>$event->image, "text"=>$event->text]
        );
    }

    private function getTip()
    {
        global $database;

        $data_href = get_base_href();
        $url = $data_href."/ext/tips/images/";

        $tip = $database->get_row("
            SELECT *
            FROM tips
            WHERE enable = :true
            ORDER BY RAND()
            LIMIT 1
        ", ["true"=>true]);

        if ($tip) {
            $this->theme->showTip($url, $tip);
        }
    }

    private function getAll()
    {
        global $database;

        $data_href = get_base_href();
        $url = $data_href."/ext/tips/images/";

        $tips = $database->get_all("SELECT * FROM tips ORDER BY id ASC");

        $this->theme->showAll($url, $tips);
    }

    private function setStatus(int $tipID)
    {
        global $database;

        $tip = $database->get_row("SELECT * FROM tips WHERE id = :id ", ["id"=>$tipID]);

        $enable = bool_escape($tip['enable']);

        $database->execute("UPDATE tips SET enable = :enable WHERE id = :id", ["enable"=>$enable, "id"=>$tipID]);
    }

    public function onDeleteTip(DeleteTipEvent $event)
    {
        global $database;
        $database->execute("DELETE FROM tips WHERE id = :id", ["id"=>$event->tip_id]);
    }
}
