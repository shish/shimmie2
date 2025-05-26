<?php

declare(strict_types=1);

namespace Shimmie2;

final class DevToolsPermission extends PermissionGroup
{
    public const KEY = "devtools";

    #[PermissionMeta("Use devtools")]
    public const MANAGE_DEVTOOLS = "manage_devtools";
}
