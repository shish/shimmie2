<?php

/*
 * Name: Image Viewer
 * Author: Shish
 * Description: Allows users to see uploaded images
 */


class ViewImageInfo extends ExtensionInfo
{
    public const KEY = "view";

    public $key = self::KEY;
    public $name = "Image Viewer";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Allows users to see uploaded images";
    public $core = true;
}
