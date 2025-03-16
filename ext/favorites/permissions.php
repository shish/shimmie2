<?php

declare(strict_types=1);

namespace Shimmie2;

final class FavouritesPermission extends PermissionGroup
{
    public const KEY = "favorites";

    #[PermissionMeta("Edit")]
    public const EDIT_FAVOURITES = "edit_favourites";
}
