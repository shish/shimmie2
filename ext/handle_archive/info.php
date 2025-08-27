<?php

declare(strict_types=1);

namespace Shimmie2;

final class ArchiveFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_archive";

    public string $name = "ZIP Archives";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Upload multiple files in one go";
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public ?string $documentation =
        "Note: requires exec() access and an external unzip command
<p>Any command line unzipper should work, some examples:
<p>unzip: <code>unzip -d \"%d\" \"%f\"</code>
<br>7-zip: <code>7zr x -o\"%d\" \"%f\"</code>";
}
