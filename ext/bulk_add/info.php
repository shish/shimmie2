<?php

declare(strict_types=1);

namespace Shimmie2;

class BulkAddInfo extends ExtensionInfo
{
    public const KEY = "bulk_add";

    public string $key = self::KEY;
    public string $name = "Bulk Add";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Bulk add server-side images";
    public ?string $documentation =
"Upload the images into a new directory via ftp or similar, go to
 shimmie's admin page and put that directory in the bulk add box.
 If there are subdirectories, they get used as tags (eg if you
 upload into <code>/home/bob/uploads/holiday/2008/</code> and point
 shimmie at <code>/home/bob/uploads</code>, then images will be
 tagged \"holiday 2008\")
 <p><b>Note:</b> requires the \"admin\" extension to be enabled
";
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
}
