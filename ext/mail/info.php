<?php

/**
 * Name: Mail System
 * Author: Zach Hall <zach@sosguy.net>
 * Link: http://seemslegit.com
 * License: GPLv2
 * Description: Provides an interface for sending and receiving mail.
 */

class MailInfo extends ExtensionInfo
{
    public const KEY = "mail";

    public $key = self::KEY;
    public $name = "Mail System";
    public $url = "http://seemslegit.com";
    public $authors = ["Zach Hall"=>"zach@sosguy.net"];
    public $license = self::LICENSE_GPLV2;
    public $core = true;
    public $description = "Provides an interface for sending and receiving mail.";
}
