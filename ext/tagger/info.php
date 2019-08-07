<?php

/**
 * Name: Tagger
 * Description: Advanced Tagging v2
 * Author: Artanis (Erik Youngren) <artanis.00@gmail.com>
 * Do not remove this notice.
 */

class TaggerInfo extends ExtensionInfo
{
    public const KEY = "tagger";

    public $key = self::KEY;
    public $name = "Tagger";
    public $authors = ["Artanis (Erik Youngren)"=>"artanis.00@gmail.com"];
    public $dependencies = [TaggerXMLInfo::KEY];
    public $description = "Advanced Tagging v2";
}
