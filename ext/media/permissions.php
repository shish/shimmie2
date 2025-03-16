<?php

declare(strict_types=1);

namespace Shimmie2;

final class MediaPermission extends PermissionGroup
{
    public const KEY = "media";

    #[PermissionMeta("Rescan media")]
    public const RESCAN_MEDIA = "rescan_media";
}
