<?php declare(strict_types=1);

class PostPeekInfo extends ExtensionInfo
{
    public const KEY = "post_peek";

    public $key = self::KEY;
    public $name = "Postt Peek";
    public $url = self::SHIMMIE_URL;
    public $authors = ["Matthew Barbour"];
    public $license = self::LICENSE_WTFPL;
}
