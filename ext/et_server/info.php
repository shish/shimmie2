<?php

declare(strict_types=1);

namespace Shimmie2;

final class ETServerInfo extends ExtensionInfo
{
    public const KEY = "et_server";

    public string $name = "System Info Registry";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Keep track of shimmie registrations";
    public ?string $documentation = "For internal use";
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
