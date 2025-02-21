<?php

declare(strict_types=1);

namespace Shimmie2;

class PoolsPermission extends PermissionGroup
{
    public const KEY = "pools";

    #[PermissionMeta("Admin")]
    public const ADMIN = "pools_admin";

    #[PermissionMeta("Create")]
    public const CREATE = "pools_create";

    #[PermissionMeta("Update")]
    public const UPDATE = "pools_update";
}
