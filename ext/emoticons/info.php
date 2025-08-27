<?php

declare(strict_types=1);

namespace Shimmie2;

final class EmoticonsInfo extends ExtensionInfo
{
    public const KEY = "emoticons";

    public string $name = "Emoticon Filter";
    public array $authors = self::SHISH_AUTHOR;
    public array $dependencies = [EmoticonListInfo::KEY];
    public string $description = "Lets users use graphical smilies";
    public ?string $documentation =
        "This extension will turn colon-something-colon into a link
to an image with that something as the name, eg :smile:
becomes a link to smile.gif
<p>Images are stored in /ext/emoticons/default/, and you can
add more emoticons by uploading images into that folder.";
}
