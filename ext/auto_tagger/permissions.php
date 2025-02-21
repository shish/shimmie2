<?php

declare(strict_types=1);

namespace Shimmie2;

class AutoTaggerPermission extends PermissionGroup
{
    public const KEY = "auto_tagger";

    #[PermissionMeta("Admin")]
    public const MANAGE_AUTO_TAG = "manage_auto_tag";
}
