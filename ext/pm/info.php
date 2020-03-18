<?php declare(strict_types=1);

class PrivMsgInfo extends ExtensionInfo
{
    public const KEY = "pm";

    public $key = self::KEY;
    public $name = "Private Messaging";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Allow users to send messages to eachother";
    public $documentation =
"PMs show up on a user's profile page, readable by that user
as well as board admins. To send a PM, visit another user's
profile page and a box will be shown.";
}
