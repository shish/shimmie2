<?php declare(strict_types=1);

class PostTitlesInfo extends ExtensionInfo
{
    public const KEY = "post_titles";

    public string $key = self::KEY;
    public string $name = "Post Titles";
    public array $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Add titles to media posts";
}
