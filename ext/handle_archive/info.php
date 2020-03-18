<?php declare(strict_types=1);

class ArchiveFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_archive";

    public $key = self::KEY;
    public $name = "Handle Archives";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Allow users to upload archives (zip, etc)";
    public $documentation =
"Note: requires exec() access and an external unzip command
<p>Any command line unzipper should work, some examples:
<p>unzip: <code>unzip -d \"%d\" \"%f\"</code>
<br>7-zip: <code>7zr x -o\"%d\" \"%f\"</code>";
}
