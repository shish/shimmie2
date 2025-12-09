<?php

declare(strict_types=1);

namespace Shimmie2;

final class NotATagPermission extends PermissionGroup
{
    public const KEY = "not_a_tag";

    #[PermissionMeta("Manage untag list")]
    public const MANAGE_UNTAG_LIST = "manage_untag_list";

    #[PermissionMeta(
        "Ignore invalid tags",
        help: "With this permission, users can try to add invalid tags, and they will be ignored, but other valid tags will still be applied."
    )]
    public const IGNORE_INVALID_TAGS = "ignore_invalid_tags";
}
