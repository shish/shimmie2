<?php

declare(strict_types=1);

namespace Shimmie2;

final class PrivMsgPermission extends PermissionGroup
{
    public const KEY = "pm";
    public ?string $title = "Private Messages";

    #[PermissionMeta("Send PMs")]
    public const SEND_PM = "send_pm";

    #[PermissionMeta("Read PMs")]
    public const READ_PM = "read_pm";

    #[PermissionMeta("Read other people's PMs")]
    public const VIEW_OTHER_PMS = "view_other_pms";
}
