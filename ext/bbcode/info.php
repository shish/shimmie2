<?php

declare(strict_types=1);

namespace Shimmie2;

final class BBCodeInfo extends ExtensionInfo
{
    public const KEY = "bbcode";

    public string $name = "BBCode";
    public array $authors = self::SHISH_AUTHOR;
    public bool $core = true;
    public string $description = "Turns BBCode into HTML";
}
