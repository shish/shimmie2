<?php

declare(strict_types=1);

namespace Shimmie2;

class RandomImageInfo extends ExtensionInfo
{
    public const KEY = "random_image";

    public string $key = self::KEY;
    public string $name = "Random Post";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Do things with a random post";
    public ?string $documentation =
"<b>Viewing a random post</b>
<br>Visit <code>/random_image/view</code>
<p><b>Downloading a random post</b>
<br>Link to <code>/random_image/download</code>. This will give
the raw data for a post (no HTML). This is useful so that you
can set your desktop wallpaper to be the download URL, refreshed
every couple of hours.
<p><b>Getting a random post from a subset</b>
<br>Adding a slash and some search terms will get a random post
from those results. This can be useful if you want a specific size
of random post, or from a category. You could link to
<code>/random_image/download/size=1024x768+cute</code>";
}
