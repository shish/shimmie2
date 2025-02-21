<?php

declare(strict_types=1);

namespace Shimmie2;

class SpeedHaxPermission extends PermissionGroup
{
    public const KEY = "speed_hax";

    #[PermissionMeta("Big search", help: "search for more than 3 tags at once")]
    public const BIG_SEARCH = "big_search";
}
