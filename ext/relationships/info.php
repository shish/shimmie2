<?php declare(strict_types=1);

class RelationshipsInfo extends ExtensionInfo
{
    public const KEY = "relationships";

    public $key = self::KEY;
    public $name = "Post Relationships";
    public $authors = ["Angus Johnston"=>"admin@codeanimu.net"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Allow posts to have relationships (parent/child).";
}
