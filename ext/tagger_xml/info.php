<?php declare(strict_types=1);

class TaggerXMLInfo extends ExtensionInfo
{
    public const KEY = "tagger_xml";

    public $key = self::KEY;
    public $name = "Tagger AJAX backend";
    public $authors = ["Artanis (Erik Youngren)"=>"artanis.00@gmail.com"];
    public $visibility = self::VISIBLE_HIDDEN;
    public $description = "Advanced Tagging v2 AJAX backend";
}
