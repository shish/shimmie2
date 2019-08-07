<?php

/*
 * Name: Emoticon Filter
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Lets users use graphical smilies
 * Documentation:
 *
 */

class EmoticonsInfo extends ExtensionInfo
{
    public const KEY = "emoticons";

    public $key = self::KEY;
    public $name = "Emoticon Filter";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Lets users use graphical smilies";
    public $documentation =
"This extension will turn colon-something-colon into a link
to an image with that something as the name, eg :smile:
becomes a link to smile.gif
<p>Images are stored in /ext/emoticons/default/, and you can
add more emoticons by uploading images into that folder.";
}

class EmoticonListInfo extends ExtensionInfo
{
    public const KEY = "emoticons_list";

    public $key = self::KEY;
    public $name = "Emoticon List";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Lists available graphical smilies";
}
