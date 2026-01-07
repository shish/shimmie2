<?php

declare(strict_types=1);

namespace Shimmie2;

final class ForumPermission extends PermissionGroup
{
    public const KEY = "forum";

    #[PermissionMeta("Admin")]
    public const FORUM_ADMIN = "forum_admin";

    #[PermissionMeta("Create")]
    public const FORUM_CREATE = "forum_create";
}
