<?php

declare(strict_types=1);

namespace Shimmie2;

final class IndexPermission extends PermissionGroup
{
    public const KEY = "index";

    #[PermissionMeta("Big search", help: "search for more than 3 tags at once")]
    public const BIG_SEARCH = "big_search";
}
