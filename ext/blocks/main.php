<?php

declare(strict_types=1);

namespace Shimmie2;

class Blocks extends Extension
{
    /** @var BlocksTheme */
    protected Themelet $theme;

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;
        if ($this->get_version("ext_blocks_version") < 1) {
            $database->create_table("blocks", "
				id SCORE_AIPK,
				pages VARCHAR(128) NOT NULL,
				title VARCHAR(128) NOT NULL,
				area VARCHAR(16) NOT NULL,
				priority INTEGER NOT NULL,
				content TEXT NOT NULL,
                userclass TEXT
			");
            $database->execute("CREATE INDEX blocks_pages_idx ON blocks(pages)", []);
            $this->set_version("ext_blocks_version", 2);
        }
        if ($this->get_version("ext_blocks_version") < 2) {
            $database->execute("ALTER TABLE blocks ADD COLUMN userclass TEXT");
            $this->set_version("ext_blocks_version", 2);
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "system") {
            if ($user->can(Permissions::MANAGE_BLOCKS)) {
                $event->add_nav_link("blocks", new Link('blocks/list'), "Blocks Editor");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::MANAGE_BLOCKS)) {
            $event->add_link("Blocks Editor", make_link("blocks/list"));
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $cache, $database, $page, $user;

        $blocks = cache_get_or_set("blocks", fn () => $database->get_all("SELECT * FROM blocks"), 600);
        foreach ($blocks as $block) {
            $path = implode("/", $event->args);
            if (strlen($path) < 4000 && fnmatch($block['pages'], $path)) {
                $b = new Block($block['title'], $block['content'], $block['area'], (int)$block['priority']);
                $b->is_content = false;

                # Split by comma, trimming whitespaces, and not allowing empty elements.
                $userclasses = preg_split('/\s*,+\s*/', strtolower($block['userclass'] ?? ""), 0, PREG_SPLIT_NO_EMPTY);
                if (empty($userclasses) || in_array(strtolower($user->class->name), $userclasses)) {
                    $page->add_block($b);
                }
            }
        }

        if ($event->page_matches("blocks/add", method: "POST", permission: Permissions::MANAGE_BLOCKS)) {
            $database->execute("
                    INSERT INTO blocks (pages, title, area, priority, content, userclass)
                    VALUES (:pages, :title, :area, :priority, :content, :userclass)
                ", ['pages' => $event->req_POST('pages'), 'title' => $event->req_POST('title'), 'area' => $event->req_POST('area'), 'priority' => (int)$event->req_POST('priority'), 'content' => $event->req_POST('content'), 'userclass' => $event->req_POST('userclass')]);
            log_info("blocks", "Added Block #".($database->get_last_insert_id('blocks_id_seq'))." (".$event->req_POST('title').")");
            $cache->delete("blocks");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("blocks/list"));
        }
        if ($event->page_matches("blocks/update", method: "POST", permission: Permissions::MANAGE_BLOCKS)) {
            if (!is_null($event->get_POST('delete'))) {
                $database->execute("
                        DELETE FROM blocks
                        WHERE id=:id
                    ", ['id' => $event->req_POST('id')]);
                log_info("blocks", "Deleted Block #".$event->req_POST('id'));
            } else {
                $database->execute("
                        UPDATE blocks SET pages=:pages, title=:title, area=:area, priority=:priority, content=:content, userclass=:userclass
                        WHERE id=:id
                    ", ['pages' => $event->req_POST('pages'), 'title' => $event->req_POST('title'), 'area' => $event->req_POST('area'), 'priority' => (int)$event->req_POST('priority'), 'content' => $event->req_POST('content'), 'userclass' => $event->req_POST('userclass'), 'id' => $event->req_POST('id')]);
                log_info("blocks", "Updated Block #".$event->req_POST('id')." (".$event->req_POST('title').")");
            }
            $cache->delete("blocks");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("blocks/list"));
        }
        if ($event->page_matches("blocks/list", permission: Permissions::MANAGE_BLOCKS)) {
            $this->theme->display_blocks($database->get_all("SELECT * FROM blocks ORDER BY area, priority"));
        }
    }
}
