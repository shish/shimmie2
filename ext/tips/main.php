<?php

declare(strict_types=1);

namespace Shimmie2;

final class CreateTipEvent extends Event
{
    public function __construct(
        public bool $enable,
        public string $image,
        public string $text
    ) {
        parent::__construct();
    }
}

final class DeleteTipEvent extends Event
{
    public function __construct(
        public int $tip_id
    ) {
        parent::__construct();
    }
}

/**
 * @phpstan-type Tip array{id: int, image: string, text: string, enable: bool}
 * @extends Extension<TipsTheme>
 */
final class Tips extends Extension
{
    public const KEY = "tips";

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        $database = Ctx::$database;

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
        $this->getTip();

        if ($event->page_matches("tips/list", permission: TipsPermission::ADMIN)) {
            $this->manageTips();
            $this->getAll();
        }
        if ($event->page_matches("tips/save", method: "POST", permission: TipsPermission::ADMIN)) {
            send_event(new CreateTipEvent(
                $event->POST->get("enable") === "on",
                $event->POST->req("image"),
                $event->POST->req("text")
            ));
            Ctx::$page->set_redirect(make_link("tips/list"));
        }
        if ($event->page_matches("tips/status/{tipID}", permission: TipsPermission::ADMIN)) {
            // FIXME: HTTP GET CSRF
            $tipID = $event->get_iarg('tipID');
            $this->setStatus($tipID);
            Ctx::$page->set_redirect(make_link("tips/list"));
        }
        if ($event->page_matches("tips/delete/{tipID}", permission: TipsPermission::ADMIN)) {
            // FIXME: HTTP GET CSRF
            $tipID = $event->get_iarg('tipID');
            send_event(new DeleteTipEvent($tipID));
            Ctx::$page->set_redirect(make_link("tips/list"));
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            if (Ctx::$user->can(TipsPermission::ADMIN)) {
                $event->add_nav_link(make_link('tips/list'), "Tips Editor", "tips_editor");
            }
        }
    }

    private function manageTips(): void
    {
        $images = Filesystem::get_dir_contents(new Path("ext/tips/images"));
        $images = array_map(fn ($p) => $p->basename()->str(), $images);
        sort($images);
        $this->theme->manageTips($images);
    }

    public function onCreateTip(CreateTipEvent $event): void
    {
        Ctx::$database->execute(
            "
				INSERT INTO tips (enable, image, text)
				VALUES (:enable, :image, :text)",
            ["enable" => $event->enable, "image" => $event->image, "text" => $event->text]
        );
    }

    private function getTip(): void
    {
        /** @var ?Tip $tip */
        $tip = Ctx::$database->get_row("
            SELECT *
            FROM tips
            WHERE enable = TRUE
            ORDER BY RAND()
            LIMIT 1
        ");

        if ($tip) {
            $this->theme->showTip($tip);
        }
    }

    private function getAll(): void
    {
        /** @var array<Tip> $tips */
        $tips = Ctx::$database->get_all("SELECT * FROM tips ORDER BY id ASC");
        $this->theme->showAll($tips);
    }

    private function setStatus(int $tipID): void
    {
        $enabled = Ctx::$database->get_one("SELECT enable FROM tips WHERE id = :id ", ["id" => $tipID]);
        Ctx::$database->execute(
            "UPDATE tips SET enable = :enable WHERE id = :id",
            ["enable" => !bool_escape($enabled), "id" => $tipID]
        );
    }

    public function onDeleteTip(DeleteTipEvent $event): void
    {
        Ctx::$database->execute("DELETE FROM tips WHERE id = :id", ["id" => $event->tip_id]);
    }
}
