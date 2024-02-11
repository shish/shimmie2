<?php

declare(strict_types=1);

namespace Shimmie2;

class BulkParentChildConfig
{
}

class BulkParentChild extends Extension
{
    private const PARENT_CHILD_ACTION_NAME = "bulk_parent_child";

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        global $user;

        if ($user->can(Permissions::BULK_PARENT_CHILD)) {
            $event->add_action(BulkParentChild::PARENT_CHILD_ACTION_NAME, "Set Parent Child");
        }
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        global $user, $page, $config;
        if ($user->can(Permissions::BULK_PARENT_CHILD) &&
            ($event->action == BulkParentChild::PARENT_CHILD_ACTION_NAME)) {
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
