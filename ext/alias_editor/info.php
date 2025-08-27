<?php

declare(strict_types=1);

namespace Shimmie2;

final class AliasEditorInfo extends ExtensionInfo
{
    public const KEY = "alias_editor";

    public string $name = "Alias Editor";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Edit the alias list";
    public bool $core = true;
}
