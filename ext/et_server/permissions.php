<?php

declare(strict_types=1);

namespace Shimmie2;

final class ETServerPermission extends PermissionGroup
{
    public const KEY = "et_server";

    #[PermissionMeta("View registrations", advanced: true)]
    public const VIEW_REGISTRATIONS = "view_registrations";
}
