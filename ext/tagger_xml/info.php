<?php declare(strict_types=1);

class TaggerXMLInfo extends ExtensionInfo
{
    public const KEY = "tagger_xml";

    public string $key = self::KEY;
    public string $name = "Tagger AJAX backend";
    public array $authors = ["Artanis (Erik Youngren)"=>"artanis.00@gmail.com"];
    public string $visibility = self::VISIBLE_HIDDEN;
    public string $description = "Advanced Tagging v2 AJAX backend";
}
