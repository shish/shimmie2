<?php declare(strict_types=1);

class TrashInfo extends ExtensionInfo
{
    public const KEY = "trash";

    public $key = self::KEY;
    public $name = "Trash";
    public $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_WTFPL;
    public $description = "Provides \"Trash\" or \"Recycle Bin\"-type functionality, storing deleted images for later recovery";
}
