<?php declare(strict_types=1);

class NumericScoreInfo extends ExtensionInfo
{
    public const KEY = "numeric_score";

    public $key = self::KEY;
    public $name = "Post Scores (Numeric)";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Allow users to score images";
    public $documentation ="Each registered user may vote a post +1 or -1, the image's score is the sum of all votes.";
}
