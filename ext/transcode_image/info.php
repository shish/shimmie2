<?php

declare(strict_types=1);

namespace Shimmie2;

final class TranscodeImageInfo extends ExtensionInfo
{
    public const KEY = "transcode";

    public string $name = "Transcode Image";
    public array $authors = ["Matthew Barbour" => "mailto:matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Allows admins to manually transcode images";
    public ?string $documentation =
        "Supports GD and ImageMagick. Both support bmp, gif, jpg, png, and webp as inputs, and jpg, png, and lossy webp as outputs.
ImageMagick additionally supports tiff and psd inputs, and webp lossless output.";
    public array $dependencies = [ImageFileHandlerInfo::KEY, ReplaceFileInfo::KEY];
}
