<?php

declare(strict_types=1);

namespace Shimmie2;

final class BulkActionsPermission extends PermissionGroup
{
    public const KEY = "bulk_actions";

    #[PermissionMeta("Perform bulk actions")]
    public const PERFORM_BULK_ACTIONS = "perform_bulk_actions";
}
