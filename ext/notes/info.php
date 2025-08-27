<?php

declare(strict_types=1);

namespace Shimmie2;

final class NotesInfo extends ExtensionInfo
{
    public const KEY = "notes";

    public string $name = "Notes";
    public array $authors = ["Sein Kraft" => "mailto:mail@seinkraft.info"];
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Annotate images";
}
