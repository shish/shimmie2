<?php

declare(strict_types=1);

namespace Shimmie2;

final class AudioFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_audio";

    public string $name = "Audio Files";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::FORMAT_SUPPORT;
    public string $description = "Handle MP3, OGG, and FLAC audio files";
}
