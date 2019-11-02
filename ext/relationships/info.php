<?php

class RelationshipsInfo extends ExtensionInfo
{
    public const KEY = "relationships";

    public $key = self::KEY;
    public $name = "Post Relationships";
    public $authors = ["Angus Johnston"=>"admin@codeanimu.net"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Allow posts to have relationships (parent/child).";
    public $db_support = [DatabaseDriver::MYSQL, DatabaseDriver::PGSQL];
}
