<?php

declare(strict_types=1);

namespace Shimmie2;

class EmoticonListInfo extends ExtensionInfo
{
    public const KEY = "emoticons_list";

    public string $key = self::KEY;
    public string $name = "Emoticon List";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Lists available graphical smilies";

    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
