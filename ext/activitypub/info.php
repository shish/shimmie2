<?php

declare(strict_types=1);

namespace Shimmie2;

class ActivityPubInfo extends ExtensionInfo
{
    public const KEY = "activitypub";

    public string $key = self::KEY;
    public string $name = "ActivityPub Server";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Support for the ActivityPub protocol";
    public ?string $documentation = "Enable this so that ActivityPub clients can subscribe to our feed";
}
