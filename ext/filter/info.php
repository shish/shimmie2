<?php

declare(strict_types=1);

namespace Shimmie2;

class FilterInfo extends ExtensionInfo
{
    public const KEY = "filter";

    public string $key = self::KEY;
    public string $name = "Filter Tags";
    public array $authors = ["Danbooru Project" => "", "Discomrade" => ""];
    public string $license = "WTFPL";
    public string $description = "Allow users to filter out tags.";
    public ?string $documentation = "Admins can set default filters and users can override them in user settings. This is derived from Danbooru's blacklist code, it works in the user's browser with JavaScript, and will hide posts until the filter runs.";
}
