<?php

/*
 * Name: User-specific settings
 * Author: Matthew Barbour <matthew@darkholme.net>
 * Description: Provides system-wide support for user-specific settings
 * Visibility: admin
 */

class UserConfigInfo extends ExtensionInfo
{
    public const KEY = "user_config";

    public $key = self::KEY;
    public $name = "User-specific settings";
    public $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_WTFPL;
    public $description = "Provides system-wide support for user-specific settings";
    public $visibility = self::VISIBLE_ADMIN;
    public $core = true;
}
