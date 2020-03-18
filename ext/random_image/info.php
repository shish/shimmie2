<?php declare(strict_types=1);

class RandomImageInfo extends ExtensionInfo
{
    public const KEY = "random_image";

    public $key = self::KEY;
    public $name = "Random Image";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Do things with a random image";
    public $documentation =
"<b>Viewing a random image</b>
<br>Visit <code>/random_image/view</code>
<p><b>Downloading a random image</b>
<br>Link to <code>/random_image/download</code>. This will give
the raw data for an image (no HTML). This is useful so that you
can set your desktop wallpaper to be the download URL, refreshed
every couple of hours.
<p><b>Getting a random image from a subset</b>
<br>Adding a slash and some search terms will get a random image
from those results. This can be useful if you want a specific size
of random image, or from a category. You could link to
<code>/random_image/download/size=1024x768+cute</code>";
}
