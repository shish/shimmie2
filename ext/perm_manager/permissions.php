<?php

declare(strict_types=1);

namespace Shimmie2;

final class PermManagerPermission extends PermissionGroup
{
    public const KEY = "perm_manager";
    public ?string $title = "Permission Manager";

    #[PermissionMeta("Manage user permissions")]
    public const MANAGE_USER_PERMISSIONS = "manage_user_permissions";
}
