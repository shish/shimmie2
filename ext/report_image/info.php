<?php

/*
 * Name: Report Images
 * Author: ATravelingGeek <atg@atravelinggeek.com>
 * Link: http://atravelinggeek.com/
 * License: GPLv2
 * Description: Report images as dupes/illegal/etc
 * Version 0.3a - See changelog in main.php
 * November 06, 2007
 */

class ReportImageInfo extends ExtensionInfo
{
    public const KEY = "report_image";

    public $key = self::KEY;
    public $name = "Report Images";
    public $url = "http://atravelinggeek.com/";
    public $authors = ["ATravelingGeek"=>"atg@atravelinggeek.com"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Report images as dupes/illegal/etc";
    public $version = "0.3a";
}
