<?php

declare(strict_types=1);

namespace Shimmie2;

final class NavInfo extends ExtensionInfo
{
    public const string KEY = "nav";

    public string $name = "Navigation";
    public array $authors = [
        ... self::SHISH_AUTHOR,
        "Luana Latte" => "mailto:luana.latte.cat@gmail.com"
    ];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Provides navigation links";
    public bool $core = true;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
