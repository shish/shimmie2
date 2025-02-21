<?php

declare(strict_types=1);

namespace Shimmie2;

class BlotterPermission extends PermissionGroup
{
    public const KEY = "blotter";

    #[PermissionMeta("Admin")]
    public const ADMIN = "blotter_admin";
}
