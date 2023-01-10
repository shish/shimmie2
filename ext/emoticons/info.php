<?php

declare(strict_types=1);

namespace Shimmie2;

class EmoticonsInfo extends ExtensionInfo
{
    public const KEY = "emoticons";

    public string $key = self::KEY;
    public string $name = "Emoticon Filter";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public array $dependencies = [EmoticonListInfo::KEY];
    public string $description = "Lets users use graphical smilies";
    public ?string $documentation =
"This extension will turn colon-something-colon into a link
to an image with that something as the name, eg :smile:
becomes a link to smile.gif
<p>Images are stored in /ext/emoticons/default/, and you can
add more emoticons by uploading images into that folder.";
}
