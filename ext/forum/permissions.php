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

    #[PermissionMeta("Bypass Checks", help: "Allow a user to make forum threads and posts even if the spam-detector disapproves")]
    public const BYPASS_FORUM_CHECKS = "bypass_forum_checks";
}
