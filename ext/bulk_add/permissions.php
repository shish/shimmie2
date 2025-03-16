<?php

declare(strict_types=1);

namespace Shimmie2;

final class BulkAddPermission extends PermissionGroup
{
    public const KEY = "bulk_add";

    #[PermissionMeta("Bulk add")]
    public const BULK_ADD = "bulk_add";
}
