<?php

declare(strict_types=1);

namespace Shimmie2;

final class AdminPermission extends PermissionGroup
{
    public const KEY = "admin";

    #[PermissionMeta("Use admin power-tools")]
    public const MANAGE_ADMINTOOLS = "manage_admintools";
}
