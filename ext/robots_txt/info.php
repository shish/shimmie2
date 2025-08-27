<?php

declare(strict_types=1);

namespace Shimmie2;

final class RobotsTxtInfo extends ExtensionInfo
{
    public const KEY = "robots_txt";

    public string $name = "robots.txt generator";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
    public string $description = 'Generate robots.txt file';
    public bool $core = true;
}
