<?php

declare(strict_types=1);

namespace Shimmie2;

final class BulkParentChildConfig
{
}

final class BulkParentChild extends Extension
{
    public const KEY = "bulk_parent_child";

    #[EventListener]
    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        $event->add_action("parent-child", "Set Parent Child", permission: BulkParentChildPermission::BULK_PARENT_CHILD);
    }

    #[EventListener]
    public function onBulkAction(BulkActionEvent $event): void
    {
        if (
            Ctx::$user->can(BulkParentChildPermission::BULK_PARENT_CHILD)
            && ($event->action === "parent-child")
        ) {
            $prev_id = null;
            foreach ($event->items as $image) {
                if ($prev_id !== null) {
                    send_event(new ImageRelationshipSetEvent($image->id, $prev_id));
                }
                $prev_id = $image->id;
            }
        }
    }
}
