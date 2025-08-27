<?php

declare(strict_types=1);

namespace Shimmie2;

final class RatingsBlurInfo extends ExtensionInfo
{
    public const KEY = "ratings_blur";

    public string $name = "Ratings Blur";
    public array $authors = ["Discomrade" => null];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Blurs thumbnails for explicit images";
    public array $dependencies = [RatingsInfo::KEY, UserConfigEditorInfo::KEY];
}
