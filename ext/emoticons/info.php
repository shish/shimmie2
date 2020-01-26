<?php declare(strict_types=1);

class EmoticonsInfo extends ExtensionInfo
{
    public const KEY = "emoticons";

    public $key = self::KEY;
    public $name = "Emoticon Filter";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $dependencies = [EmoticonListInfo::KEY];
    public $description = "Lets users use graphical smilies";
    public $documentation =
"This extension will turn colon-something-colon into a link
to an image with that something as the name, eg :smile:
becomes a link to smile.gif
<p>Images are stored in /ext/emoticons/default/, and you can
add more emoticons by uploading images into that folder.";
}
