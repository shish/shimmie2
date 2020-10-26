<?php declare(strict_types=1);

class RegenThumbInfo extends ExtensionInfo
{
    public const KEY = "regen_thumb";

    public $key = self::KEY;
    public $name = "Regen Thumb";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Regenerate a thumbnail image";
    public $documentation =
"This adds a button in the post control section on a post's view page, which allows an admin to regenerate
a post's thumbnail; useful for instance if the first attempt failed due to lack of memory, and memory has
since been increased.";
}
