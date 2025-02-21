<?php

declare(strict_types=1);

namespace Shimmie2;

class SetupPermission extends PermissionGroup
{
    public const KEY = "setup";

    #[PermissionMeta("Modify web-level settings, eg the config table")]
    public const CHANGE_SETTING = "change_setting";
}
