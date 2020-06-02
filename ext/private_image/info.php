<?php declare(strict_types=1);

class PrivateImageInfo extends ExtensionInfo
{
    public const KEY = "private_image";

    public $key = self::KEY;
    public $name = "Private Image";
    public $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_WTFPL;
    public $description = "Allows users to mark images as private, which prevents other users from seeing them.";
}
