<?php

declare(strict_types=1);

namespace Shimmie2;

final class TagCategoriesPermission extends PermissionGroup
{
    public const KEY = "tag_categories";

    #[PermissionMeta("Edit tag categories")]
    public const EDIT_TAG_CATEGORIES = "edit_tag_categories";
}
