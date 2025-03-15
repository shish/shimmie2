<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostTagsPermission extends PermissionGroup
{
    public const KEY = "post_tags";

    #[PermissionMeta("Edit post tag")]
    public const EDIT_IMAGE_TAG = "edit_image_tag";

    #[PermissionMeta("Mass tag edit")]
    public const MASS_TAG_EDIT = "mass_tag_edit";
}
