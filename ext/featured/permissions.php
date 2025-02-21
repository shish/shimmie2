<?php

declare(strict_types=1);

namespace Shimmie2;

class FeaturedPermission extends PermissionGroup
{
    public const KEY = "featured";

    #[PermissionMeta("Edit feature")]
    public const EDIT_FEATURE = "edit_feature";
}
