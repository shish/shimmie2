<?php

declare(strict_types=1);

namespace Shimmie2;

final class AudioFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_audio";

    public string $key = self::KEY;
    public string $name = "Handle Audio Files";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Handle MP3, OGG, and FLAC audio files";
}
