<?php

declare(strict_types=1);

namespace Shimmie2;

final class ExtManagerPermission extends PermissionGroup
{
    public const KEY = "ext_manager";

    #[PermissionMeta("Enable or disable extensions")]
    public const MANAGE_EXTENSION_LIST = "manage_extension_list";
}
