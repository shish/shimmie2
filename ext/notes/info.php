<?php

declare(strict_types=1);

namespace Shimmie2;

class NotesInfo extends ExtensionInfo
{
    public const KEY = "notes";

    public string $key = self::KEY;
    public string $name = "Notes";
    public array $authors = ["Sein Kraft" => "mail@seinkraft.info"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Annotate images";
}
