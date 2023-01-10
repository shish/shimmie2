<?php

declare(strict_types=1);

namespace Shimmie2;

class UploadInfo extends ExtensionInfo
{
    public const KEY = "upload";

    public string $key = self::KEY;
    public string $name = "Uploader";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Allows people to upload files to the website";
    public bool $core = true;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
