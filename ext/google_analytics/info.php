<?php

/**
 * Name: Google Analytics
 * Author: Drudex Software <support@drudexsoftware.com>
 * Link: http://drudexsoftware.com
 * License: GPLv2
 * Description: Integrates Google Analytics tracking
 * Documentation:
 *
 */
class google_analyticsInfo extends ExtensionInfo
{
    public const KEY = "google_analytics";

    public $key = self::KEY;
    public $name = "Google Analytics";
    public $url = "http://drudexsoftware.com";
    public $authors = ["Drudex Software"=>"support@drudexsoftware.com"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Integrates Google Analytics tracking";
    public $documentation =
"User has to enter their Google Analytics ID in the Board Config to use this extension.";
}
