<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReplaceFilePermission extends PermissionGroup
{
    public const KEY = "replace_file";

    #[PermissionMeta("Replace post")]
    public const REPLACE_IMAGE = "replace_image";
}
