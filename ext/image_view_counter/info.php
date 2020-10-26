<?php declare(strict_types=1);

class ImageViewCounterInfo extends ExtensionInfo
{
    public const KEY = "image_view_counter";

    public $key = self::KEY;
    public $name = "Post View Counter";
    public $url = "http://www.drudexsoftware.com/";
    public $authors = ["Drudex Software"=>"support@drudexsoftware.com"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Tracks & displays how many times a post is viewed";
    public $documentation =
"Whenever anyone views a post, a view will be added to that image.
This extension will also track any username & the IP address.
This is done to prevent duplicate views.
A person can only count as a view again 1 hour after viewing the image initially.";
}
