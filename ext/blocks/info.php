<?php

declare(strict_types=1);

namespace Shimmie2;

class BlocksInfo extends ExtensionInfo
{
    public const KEY = "blocks";

    public string $key = self::KEY;
    public string $name = "Generic Blocks";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Add HTML to some space (News, Ads, etc)";
    public ?string $documentation =
        "Blocks with lower priority number appear higher up the page.<br><br>
     The userclass parameter can be left empty for blocks to show to everyone, or specified as a comma-separated, case-insensitive list of user classes that will see that block. Spaces around the comma get stripped.<br><br>
     For example, to show ads only to regular users and anonymous user classes:
     <ul>
     <li>\"&lt;ad here&gt;\": <code>user, anonymous</code></li>
     <li>\"Thanks for supporting us!\": <code>supporter</code></li>
     </ul>
     ";
}
