<?php

declare(strict_types=1);

namespace Shimmie2;

class ArtistsPermission extends PermissionGroup
{
    public const KEY = "artists";

    #[PermissionMeta("Admin")]
    public const ADMIN = "artists_admin";

    #[PermissionMeta("Edit post artist")]
    public const EDIT_IMAGE_ARTIST = "edit_image_artist";
}
