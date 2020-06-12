<?php declare(strict_types=1);

class SetupInfo extends ExtensionInfo
{
    public const KEY = "setup";

    public $key = self::KEY;
    public $name = "Board Config";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Allows the site admin to configure the board to his or her taste";
    public $core = true;
    public $visibility = self::VISIBLE_HIDDEN;
}
