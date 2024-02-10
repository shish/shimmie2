<?php

declare(strict_types=1);

namespace Shimmie2;

class ImageViewCounterInfo extends ExtensionInfo
{
    public const KEY = "image_view_counter";

    public string $key = self::KEY;
    public string $name = "Post View Counter";
    public string $url = "http://www.drudexsoftware.com/";
    public array $authors = ["Drudex Software" => "support@drudexsoftware.com"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Tracks & displays how many times a post is viewed";
    public ?string $documentation =
"Whenever anyone views a post, a view will be added to that image.
This extension will also track any username & the IP address.
This is done to prevent duplicate views.
A person can only count as a view again 1 hour after viewing the image initially.";
}
