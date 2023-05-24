<?php

declare(strict_types=1);

namespace Shimmie2;

class HideTagsInfo extends ExtensionInfo
{
    public const KEY = "hide_tags";

    public string $key = self::KEY;
    public string $name = "Hide Tags";
    public string $url = "https://github.com/tegaki-tegaki/shimmie2-tegaki/";
    public array $authors = ["tegaki"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Allow hiding images through special tags";
    public ?string $documentation =
"This shimmie extension lets you hide images from discovery by ".
"setting a tag 'hidden', you can reveal ".
"hidden images in queries by adding the tag 'show_hidden'";
}
