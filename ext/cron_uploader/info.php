<?php

declare(strict_types=1);

namespace Shimmie2;

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

    public string $key = self::KEY;
    public string $name = "Cron Uploader";
    public string $url = self::SHIMMIE_URL;
    public array $authors = ["YaoiFox" => "admin@yaoifox.com", "Matthew Barbour" => "matthew@darkholme.net"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Uploads images automatically using Cron Jobs";

    public function __construct()
    {
        $this->documentation = "Installation guide: activate this extension and navigate to Board Config screen.</a>";
        parent::__construct();
    }
}
