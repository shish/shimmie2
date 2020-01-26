<?php declare(strict_types=1);

class Rule34Info extends ExtensionInfo
{
    public const KEY = "rule34";

    public $key = self::KEY;
    public $name = "Rule34 Customisations";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Extra site-specific bits";
    public $documentation =
"Probably not much use to other sites, but it gives a few examples of how a shimmie-based site can be integrated with other systems";
    public $db_support = [DatabaseDriver::PGSQL];  # Only PG has the NOTIFY pubsub system
}
