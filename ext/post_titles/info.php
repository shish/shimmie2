<?php

/**
 * Name: Post Titles
 * Author: Matthew Barbour <matthew@darkholme.net>
 * Description: Add titles to media posts
 */

class PostTitlesInfo extends ExtensionInfo
{
    public const KEY = "post_titles";

    public $key = self::KEY;
    public $name = "Post Titles";
    public $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_WTFPL;
    public $description = "Add titles to media posts";
}
