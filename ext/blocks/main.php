<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * @phpstan-type BlockArray array{id:int,title:string,area:string,priority:int,userclass:string,pages:string,content:string}
 * @extends Extension<BlocksTheme>
 */
final class Blocks extends Extension
{
    public const KEY = "blocks";

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;
        if ($this->get_version() < 1) {
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
            $this->set_version(2);
        }
        if ($this->get_version() < 2) {
            $database->execute("ALTER TABLE blocks ADD COLUMN userclass TEXT");
            $this->set_version(2);
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            if (Ctx::$user->can(BlocksPermission::MANAGE_BLOCKS)) {
                $event->add_nav_link(make_link('blocks/list'), "Blocks Editor", "blocks_editor");
            }
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database;
        $page = Ctx::$page;

        $blocks = cache_get_or_set("blocks", fn () => $database->get_all("SELECT * FROM blocks"), 600);
        foreach ($blocks as $block) {
            $path = implode("/", $event->args);
            if (strlen($path) < 4000 && fnmatch($block['pages'], $path)) {
                # Split by comma, trimming whitespaces, and not allowing empty elements.
                $userclasses = preg_split('/\s*,+\s*/', strtolower($block['userclass'] ?? ""), 0, PREG_SPLIT_NO_EMPTY);
                if (empty($userclasses) || in_array(strtolower(Ctx::$user->class->name), $userclasses)) {
                    $page->add_block(new Block($block['title'], \MicroHTML\rawHTML($block['content']), $block['area'], (int)$block['priority'], is_content: false));
                }
            }
        }

        if ($event->page_matches("blocks/add", method: "POST", permission: BlocksPermission::MANAGE_BLOCKS)) {
            $database->execute("
                    INSERT INTO blocks (pages, title, area, priority, content, userclass)
                    VALUES (:pages, :title, :area, :priority, :content, :userclass)
                ", ['pages' => $event->POST->req('pages'), 'title' => $event->POST->req('title'), 'area' => $event->POST->req('area'), 'priority' => (int)$event->POST->req('priority'), 'content' => $event->POST->req('content'), 'userclass' => $event->POST->req('userclass')]);
            Log::info("blocks", "Added Block #".($database->get_last_insert_id('blocks_id_seq'))." (".$event->POST->req('title').")");
            Ctx::$cache->delete("blocks");
            $page->set_redirect(make_link("blocks/list"));
        }
        if ($event->page_matches("blocks/update", method: "POST", permission: BlocksPermission::MANAGE_BLOCKS)) {
            if (!is_null($event->POST->get('delete'))) {
                $database->execute("
                        DELETE FROM blocks
                        WHERE id=:id
                    ", ['id' => $event->POST->req('id')]);
                Log::info("blocks", "Deleted Block #".$event->POST->req('id'));
            } else {
                $database->execute("
                        UPDATE blocks SET pages=:pages, title=:title, area=:area, priority=:priority, content=:content, userclass=:userclass
                        WHERE id=:id
                    ", ['pages' => $event->POST->req('pages'), 'title' => $event->POST->req('title'), 'area' => $event->POST->req('area'), 'priority' => (int)$event->POST->req('priority'), 'content' => $event->POST->req('content'), 'userclass' => $event->POST->req('userclass'), 'id' => $event->POST->req('id')]);
                Log::info("blocks", "Updated Block #".$event->POST->req('id')." (".$event->POST->req('title').")");
            }
            Ctx::$cache->delete("blocks");
            $page->set_redirect(make_link("blocks/list"));
        }
        if ($event->page_matches("blocks/list", permission: BlocksPermission::MANAGE_BLOCKS)) {
            /** @var array<BlockArray> $bs */
            $bs = $database->get_all("SELECT * FROM blocks ORDER BY area, priority");
            $this->theme->display_blocks($bs);
        }
    }
}
