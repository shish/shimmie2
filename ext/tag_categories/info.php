<?php declare(strict_types=1);

class TagCategoriesInfo extends ExtensionInfo
{
    public const KEY = "tag_categories";

    public $key = self::KEY;
    public $name = "Tag Categories";
    public $url = "https://code.shishnet.org/shimmie2/";
    public $authors = ["Daniel Oaks"=>"danneh@danneh.net"];
    public $description = "Let tags be split into 'categories', like Danbooru's tagging";
}
