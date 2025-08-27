<?php

declare(strict_types=1);

namespace Shimmie2;

final class EmoticonListInfo extends ExtensionInfo
{
    public const KEY = "emoticons_list";

    public string $name = "Emoticon List";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Lists available graphical smilies";

    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
