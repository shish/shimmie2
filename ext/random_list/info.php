<?php

declare(strict_types=1);

namespace Shimmie2;

class RandomListInfo extends ExtensionInfo
{
    public const KEY = "random_list";

    public string $key = self::KEY;
    public string $name = "Random List";
    public string $url = "http://www.drudexsoftware.com";
    public array $authors = ["Drudex Software" => "support@drudexsoftware.com"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Allows displaying a page with random posts";
    public ?string $documentation =
"Random post list can be accessed through www.yoursite.com/random
It is recommended that you create a link to this page so users know it exists.";
}
