<?php

declare(strict_types=1);

namespace Shimmie2;

final class PrivMsgInfo extends ExtensionInfo
{
    public const KEY = "pm";

    public string $name = "Private Messaging";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::FEATURE;
    public string $description = "Allow users to send messages to eachother";
    public ?string $documentation =
        "PMs show up on a user's profile page, readable by that user
as well as board admins. To send a PM, visit another user's
profile page and a box will be shown.";
}
