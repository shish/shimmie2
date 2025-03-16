<?php

declare(strict_types=1);

namespace Shimmie2;

final class BulkParentChildPermission extends PermissionGroup
{
    public const KEY = "bulk_parent_child";

    #[PermissionMeta("Bulk parent-child")]
    public const BULK_PARENT_CHILD = "bulk_parent_child";
}
