<?php

declare(strict_types=1);

namespace Shimmie2;

final class RelationshipsPermission extends PermissionGroup
{
    public const KEY = "relationships";

    #[PermissionMeta("Edit post relationships")]
    public const EDIT_IMAGE_RELATIONSHIPS = "edit_image_relationships";
}
