<?php

declare(strict_types=1);

namespace Shimmie2;

final class BlocksPermission extends PermissionGroup
{
    public const KEY = "blocks";

    #[PermissionMeta("Admin")]
    public const MANAGE_BLOCKS = "manage_blocks";
}
