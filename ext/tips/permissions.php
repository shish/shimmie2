<?php

declare(strict_types=1);

namespace Shimmie2;

final class TipsPermission extends PermissionGroup
{
    public const KEY = "tips";

    #[PermissionMeta("Admin")]
    public const ADMIN = "tips_admin";
}
