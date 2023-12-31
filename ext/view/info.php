<?php

declare(strict_types=1);

namespace Shimmie2;

class ViewPostInfo extends ExtensionInfo
{
    public const KEY = "view";

    public string $key = self::KEY;
    public string $name = "Post Viewer";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Allows users to see uploaded posts";
    public bool $core = true;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
