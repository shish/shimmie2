<?php

/** @noinspection PhpUnusedPrivateMethodInspection */
declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<TagToolsTheme> */
final class TagTools extends Extension
{
    public const KEY = "tag_tools";

    #[EventListener]
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_form();
    }

    #[EventListener]
    public function onAdminAction(AdminActionEvent $event): void
    {
        global $database;
        switch ($event->action) {
            case "set_tag_case":
                $database->execute(
                    "UPDATE tags SET tag=:tag1 WHERE LOWER(tag) = LOWER(:tag2)",
                    ["tag1" => $event->params['tag'], "tag2" => $event->params['tag']]
                );
                Log::info("admin", "Fixed the case of {$event->params['tag']}", "Fixed case");
                $event->redirect = true;
                break;
            case "lowercase_all_tags":
                $database->execute("UPDATE tags SET tag=lower(tag)");
                Log::warning("admin", "Set all tags to lowercase", "Set all tags to lowercase");
                $event->redirect = true;
                break;
            case "recount_tag_use":
                $database->execute("
                    UPDATE tags
                    SET count = COALESCE(
                        (SELECT COUNT(image_id) FROM image_tags WHERE tag_id=tags.id GROUP BY tag_id),
                        0
                    )
                ");
                $database->execute("DELETE FROM tags WHERE count=0");
                Log::warning("admin", "Re-counted tags", "Re-counted tags");
                $event->redirect = true;
                break;
        }
    }
}
