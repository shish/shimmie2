<?php declare(strict_types=1);

class IPBanInfo extends ExtensionInfo
{
    public const KEY = "ipban";

    public $key = self::KEY;
    public $name = "IP Ban";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Ban IP addresses";
    public $documentation =
"<b>Adding a Ban</b>
<br>IP: Can be a single IP (eg. 123.234.210.21), or a CIDR block (eg. 152.23.43.0/24)
<br>Reason: Any text, for the admin to remember why the ban was put in place
<br>Until: Either a date in YYYY-MM-DD format, or an offset like \"3 days\"";
}
