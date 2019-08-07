<?php

/*
 * Name: Image Scores (Numeric)
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Allow users to score images
 * Documentation:
 */

class NumericScoreInfo extends ExtensionInfo
{
    public const KEY = "numeric_score";

    public $key = self::KEY;
    public $name = "Image Scores (Numeric)";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Allow users to score images";
    public $documentation ="Each registered user may vote an image +1 or -1, the image's score is the sum of all votes.";
}
