<?php

declare(strict_types=1);

namespace Shimmie2;

final class TrashPermission extends PermissionGroup
{
    public const KEY = "trash";

    #[PermissionMeta("View posts in the trashcan")]
    public const VIEW_TRASH = "view_trash";
}
