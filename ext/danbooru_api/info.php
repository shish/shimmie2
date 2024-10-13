<?php

declare(strict_types=1);

namespace Shimmie2;

class DanbooruApiInfo extends ExtensionInfo
{
    public const KEY = "danbooru_api";

    public string $key = self::KEY;
    public string $name = "Danbooru Client API";
    public array $authors = ["JJS" => "jsutinen@gmail.com"];
    public string $description = "Allow Danbooru apps like Danbooru Uploader for Firefox to communicate with Shimmie";
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public ?string $documentation =
        "<b>Notes</b>:
 <br>danbooru API based on documentation from danbooru 1.0
 <br>I've only been able to test add_post and find_tags because I use the
 old danbooru firefox extension for firefox 1.5
 <p>Functions currently implemented:
 <ul>
 <li>add_post - title and rating are currently ignored because shimmie does not support them
 <li>find_posts - sort of works, filename is returned as the original filename and probably won't help when it comes to actually downloading it
 <li>find_tags - id, name, and after_id all work but the tags parameter is ignored just like danbooru 1.0 ignores it
 </ul>
";
}
