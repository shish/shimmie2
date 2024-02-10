<?php

declare(strict_types=1);

namespace Shimmie2;

class IPBanInfo extends ExtensionInfo
{
    public const KEY = "ipban";

    public string $key = self::KEY;
    public string $name = "IP Ban";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = "Ban IP addresses";
    public ?string $documentation =
"<b>Adding a Ban</b>
<br>IP: Can be a single IP (eg. 123.234.210.21), or a CIDR block (eg. 152.23.43.0/24)
<br>Reason: Any text, for the admin to remember why the ban was put in place
<br>Until: Either a date in YYYY-MM-DD format, or an offset like \"3 days\"";
}
