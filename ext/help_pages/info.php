<?php

/**
 * Name: Help Pages
 * Author: Matthew Barbour <matthew@darkholme.net>
 * Description: Provides documentation screens
 */

class HelpPagesInfo extends ExtensionInfo
{
    public const KEY = "help_pages";

    public $key = self::KEY;
    public $name = "Help Pages";
    public $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_WTFPL;
    public $description = "Provides documentation screens";
    public $core = true;
}
