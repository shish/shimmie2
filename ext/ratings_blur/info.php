<?php

declare(strict_types=1);

namespace Shimmie2;

class RatingsBlurInfo extends ExtensionInfo
{
    public const KEY = "ratings_blur";

    public string $key = self::KEY;
    public string $name = "Ratings Blur";
    public array $authors = ["Discomrade" => ""];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Blurs thumbs based on rating, users can override. Requires 'Post Ratings'.";
    public array $dependencies = [RatingsInfo::KEY];
}
