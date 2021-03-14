<?php declare(strict_types=1);

class TrashInfo extends ExtensionInfo
{
    public const KEY = "trash";

    public string $key = self::KEY;
    public string $name = "Trash";
    public array $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Provides \"Trash\" or \"Recycle Bin\"-type functionality, storing deleted images for later recovery";
}
