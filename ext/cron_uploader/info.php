<?php declare(strict_types=1);

/*
 * Name: Cron Uploader
 * Authors: YaoiFox <admin@yaoifox.com>, Matthew Barbour <matthew@darkholme.net>
 * Link: http://www.yaoifox.com/
 * License: GPLv2
 * Description: Uploads images automatically using Cron Jobs
 * Documentation: Installation guide: activate this extension and navigate to www.yoursite.com/cron_upload
 */

class CronUploaderInfo extends ExtensionInfo
{
    public const KEY = "cron_uploader";

    public $key = self::KEY;
    public $name = "Cron Uploader";
    public $url = self::SHIMMIE_URL;
    public $authors = ["YaoiFox"=>"admin@yaoifox.com", "Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Uploads images automatically using Cron Jobs";

    public function __construct()
    {
        $this->documentation = "Installation guide: activate this extension and navigate to System Config screen.</a>";
        parent::__construct();
    }
}
