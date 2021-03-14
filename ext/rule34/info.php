<?php declare(strict_types=1);

class Rule34Info extends ExtensionInfo
{
    public const KEY = "rule34";

    public string $key = self::KEY;
    public string $name = "Rule34 Customisations";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Extra site-specific bits";
    public ?string $documentation =
"Probably not much use to other sites, but it gives a few examples of how a shimmie-based site can be integrated with other systems";
    public array $db_support = [DatabaseDriver::PGSQL];  # Only PG has the NOTIFY pubsub system
}
