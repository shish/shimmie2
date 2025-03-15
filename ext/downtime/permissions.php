<?php

declare(strict_types=1);

namespace Shimmie2;

final class DowntimePermission extends PermissionGroup
{
    public const KEY = "downtime";

    #[PermissionMeta("Ignore downtime", help: "These users can still access the site during downtime")]
    public const IGNORE_DOWNTIME = "ignore_downtime";
}
