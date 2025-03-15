<?php

declare(strict_types=1);

namespace Shimmie2;

final class IPBanPermission extends PermissionGroup
{
    public const KEY = "ipban";

    #[PermissionMeta("View IPs", help: "View which IP address posted a comment / image / etc")]
    public const VIEW_IP = "view_ip";

    #[PermissionMeta("Ban IP")]
    public const BAN_IP = "ban_ip";
}
