<?php

/**
 * Name: [Beta] Update
 * Author: DakuTree <dakutree@codeanimu.net>
 * Link: http://www.codeanimu.net
 * License: GPLv2
 * Description: Shimmie updater! (Requires admin panel extension & transload engine (cURL/fopen/Wget))
 */

class UpdateInfo extends ExtensionInfo
{
    public const KEY = "update";

    public $key = self::KEY;
    public $name = "Update";
    public $url = "http://www.codeanimu.net";
    public $authors = ["DakuTree"=>"dakutree@codeanimu.net"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Shimmie updater! (Requires admin panel extension & transload engine (cURL/fopen/Wget))";
}
