<?php

declare(strict_types=1);

namespace Shimmie2;

final class ArtistsPermission extends PermissionGroup
{
    public const KEY = "artists";

    #[PermissionMeta("Admin")]
    public const ADMIN = "artists_admin";

    #[PermissionMeta("Edit artist info")]
    public const EDIT_ARTIST_INFO = "edit_artist_info";

    #[PermissionMeta("Edit post artist")]
    public const EDIT_IMAGE_ARTIST = "edit_image_artist";
}
