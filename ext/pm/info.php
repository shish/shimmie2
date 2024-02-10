<?php

declare(strict_types=1);

namespace Shimmie2;

class PrivMsgInfo extends ExtensionInfo
{
    public const KEY = "pm";

    public string $key = self::KEY;
    public string $name = "Private Messaging";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::FEATURE;
    public string $description = "Allow users to send messages to eachother";
    public ?string $documentation =
"PMs show up on a user's profile page, readable by that user
as well as board admins. To send a PM, visit another user's
profile page and a box will be shown.";
}
