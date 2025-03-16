<?php

declare(strict_types=1);

namespace Shimmie2;

final class CreateTipEvent extends Event
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

final class DeleteTipEvent extends Event
{
    public int $tip_id;
    public function __construct(int $tip_id)
    {
        parent::__construct();
        $this->tip_id = $tip_id;
    }
}

final class Tips extends Extension
{
    public const KEY = "tips";
    /** @var TipsTheme */
    protected Themelet $theme;

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version() < 1) {
            $database->create_table("tips", "
                id SCORE_AIPK,
                enable BOOLEAN NOT NULL DEFAULT FALSE,
                image TEXT NOT NULL,
                text TEXT NOT NULL,
            ");

            $this->set_version(2);
        }
        if ($this->get_version() < 2) {
            $database->standardise_boolean("tips", "enable");
            $this->set_version(2);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;

        $this->getTip();

        if ($event->page_matches("tips/list", permission: TipsPermission::ADMIN)) {
            $this->manageTips();
            $this->getAll();
        }
        if ($event->page_matches("tips/save", method: "POST", permission: TipsPermission::ADMIN)) {
            send_event(new CreateTipEvent(
                $event->get_POST("enable") == "on",
                $event->req_POST("image"),
                $event->req_POST("text")
            ));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("tips/list"));
        }
        if ($event->page_matches("tips/status/{tipID}", permission: TipsPermission::ADMIN)) {
            // FIXME: HTTP GET CSRF
            $tipID = $event->get_iarg('tipID');
            $this->setStatus($tipID);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("tips/list"));
        }
        if ($event->page_matches("tips/delete/{tipID}", permission: TipsPermission::ADMIN)) {
            // FIXME: HTTP GET CSRF
            $tipID = $event->get_iarg('tipID');
            send_event(new DeleteTipEvent($tipID));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("tips/list"));
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "system") {
            if ($user->can(TipsPermission::ADMIN)) {
                $event->add_nav_link(make_link('tips/list'), "Tips Editor");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(TipsPermission::ADMIN)) {
            $event->add_link("Tips Editor", make_link("tips/list"));
        }
    }

    private function manageTips(): void
    {
        $data_href = Url::base();
        $url = $data_href."/ext/tips/images/";

        $dirPath = \Safe\dir('./ext/tips/images');
        $images = [];
        while (($file = $dirPath->read()) !== false) {
            if ($file[0] !== ".") {
                $images[] = trim($file);
            }
        }
        $dirPath->close();
        sort($images);

        $this->theme->manageTips($url, $images);
    }

    public function onCreateTip(CreateTipEvent $event): void
    {
        global $database;
        $database->execute(
            "
				INSERT INTO tips (enable, image, text)
				VALUES (:enable, :image, :text)",
            ["enable" => $event->enable, "image" => $event->image, "text" => $event->text]
        );
    }

    private function getTip(): void
    {
        global $database;

        $data_href = Url::base();
        $url = $data_href."/ext/tips/images/";

        $tip = $database->get_row("
            SELECT *
            FROM tips
            WHERE enable = :true
            ORDER BY RAND()
            LIMIT 1
        ", ["true" => true]);

        if ($tip) {
            $this->theme->showTip($url, $tip);
        }
    }

    private function getAll(): void
    {
        global $database;

        $data_href = Url::base();
        $url = $data_href."/ext/tips/images/";

        $tips = $database->get_all("SELECT * FROM tips ORDER BY id ASC");

        $this->theme->showAll($url, $tips);
    }

    private function setStatus(int $tipID): void
    {
        global $database;

        $tip = $database->get_row("SELECT * FROM tips WHERE id = :id ", ["id" => $tipID]);

        $enable = bool_escape($tip['enable']);

        $database->execute("UPDATE tips SET enable = :enable WHERE id = :id", ["enable" => $enable, "id" => $tipID]);
    }

    public function onDeleteTip(DeleteTipEvent $event): void
    {
        global $database;
        $database->execute("DELETE FROM tips WHERE id = :id", ["id" => $event->tip_id]);
    }
}
