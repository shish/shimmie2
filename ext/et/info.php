<?php

/*
 * Name: System Info
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Show various bits of system information
 * Documentation:
 */

class ETInfo extends ExtensionInfo
{
    public const KEY = "et";

    public $key = self::KEY;
    public $name = "System Info";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Show various bits of system information";
    public $documentation =
"Knowing the information that this extension shows can be very useful for debugging. There's also an option to send
your stats to my database, so I can get some idea of how shimmie is used, which servers I need to support, which
versions of PHP I should test with, etc.";
}
