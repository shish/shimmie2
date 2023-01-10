<?php

declare(strict_types=1);

namespace Shimmie2;

class FeaturedInfo extends ExtensionInfo
{
    public const KEY = "featured";

    public string $key = self::KEY;
    public string $name = "Featured Post";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Bring a specific image to the users' attentions";
    public ?string $documentation =
"Once enabled, a new \"feature this\" button will appear next
to the other post control buttons (delete, rotate, etc).
Clicking it will set the image as the site's current feature,
which will be shown in the side bar of the post list.
<p><b>Viewing a featured post</b>
<br>Visit <code>/featured_image/view</code>
<p><b>Downloading a featured post</b>
<br>Link to <code>/featured_image/download</code>. This will give
the raw data for a post (no HTML). This is useful so that you
can set your desktop wallpaper to be the download URL, refreshed
every couple of hours.";
}
