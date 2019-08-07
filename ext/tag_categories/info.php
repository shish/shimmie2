<?php

/**
 * Name: Tag Categories
 * Author: Daniel Oaks <danneh@danneh.net>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Let tags be split into 'categories', like Danbooru's tagging
 */
class TagCategoriesInfo extends ExtensionInfo
{
    public const KEY = "tag_categories";

    public $key = self::KEY;
    public $name = "Tag Categories";
    public $url = "http://code.shishnet.org/shimmie2/";
    public $authors = ["Daniel Oaks"=>"danneh@danneh.net"];
    public $description = "Let tags be split into 'categories', like Danbooru's tagging";
}
