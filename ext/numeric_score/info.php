<?php

declare(strict_types=1);

namespace Shimmie2;

class NumericScoreInfo extends ExtensionInfo
{
    public const KEY = "numeric_score";

    public string $key = self::KEY;
    public string $name = "Post Scores (Numeric)";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Allow users to score images";
    public ?string $documentation = "Each registered user may vote a post +1 or -1, the image's score is the sum of all votes.";
}
