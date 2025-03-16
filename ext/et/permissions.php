<?php

declare(strict_types=1);

namespace Shimmie2;

final class ETPermission extends PermissionGroup
{
    public const KEY = "et";

    #[PermissionMeta("View system info")]
    public const VIEW_SYSINFO = "view_sysinfo";
}
