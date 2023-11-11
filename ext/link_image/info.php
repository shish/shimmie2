<?php

declare(strict_types=1);

namespace Shimmie2;

class LinkImageInfo extends ExtensionInfo
{
    public const KEY = "link_image";

    public string $key = self::KEY;
    public string $name = "Link to Post";
    public array $authors = ["Artanis" => "artanis.00@gmail.com"];
    public string $description = "Show various forms of link to each image, for copy & paste";
    public string $license = self::LICENSE_GPLV2;
    public ?string $documentation = "There is one option in Board Config: Text Link Format.
It takes the following arguments as well as plain text.

<pre>
|| arguments    || replacement                      ||
|| \$id          || The image ID.                    ||
|| \$hash        || The MD5 hash of the image.       ||
|| \$tags        || The image's tag list.            ||
|| \$base        || The base HREF as set in Config.  ||
|| \$ext         || The image's extension.           ||
|| \$size        || The image's display size.        ||
|| \$filesize    || The image's size in KB.          ||
|| \$filename    || The image's original filename.   ||
|| \$title       || The site title as set in Config. ||
</pre>

<p>Link to Post will default this option to '\$title - \$id (\$ext \$size \$filesize)'.

<p>To reset to the default, simply clear the current setting. Link to Post
will then fill in the default value after the save.

<p>To leave the setting blank for any reason, leave a space (' ') in it.";
}
