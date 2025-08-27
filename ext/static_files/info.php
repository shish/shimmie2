<?php

declare(strict_types=1);

namespace Shimmie2;

final class StaticFilesInfo extends ExtensionInfo
{
    public const KEY = "static_files";

    public string $name = "Static File Handler";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
    public string $description = 'If Shimmie can\'t handle a request, check static files ($theme/static/$filename, then ext/static_files/static/$filename)';
    public bool $core = true;
}
