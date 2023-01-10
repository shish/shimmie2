<?php

declare(strict_types=1);

namespace Shimmie2;

class ETInfo extends ExtensionInfo
{
    public const KEY = "et";

    public string $key = self::KEY;
    public string $name = "System Info";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public bool $core = true;
    public string $description = "Show various bits of system information";
    public ?string $documentation =
"Knowing the information that this extension shows can be very useful for debugging. There's also an option to send
your stats to my database, so I can get some idea of how shimmie is used, which servers I need to support, which
versions of PHP I should test with, etc.";
}
