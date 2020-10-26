<?php declare(strict_types=1);

class FeaturedInfo extends ExtensionInfo
{
    public const KEY = "featured";

    public $key = self::KEY;
    public $name = "Featured Post";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Bring a specific image to the users' attentions";
    public $documentation =
"Once enabled, a new \"feature this\" button will appear next
to the other image control buttons (delete, rotate, etc).
Clicking it will set the image as the site's current feature,
which will be shown in the side bar of the post list.
<p><b>Viewing a featured post</b>
<br>Visit <code>/featured_image/view</code>
<p><b>Downloading a featured post</b>
<br>Link to <code>/featured_image/download</code>. This will give
the raw data for an image (no HTML). This is useful so that you
can set your desktop wallpaper to be the download URL, refreshed
every couple of hours.";
}
