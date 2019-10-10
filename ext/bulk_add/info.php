<?php

/*
 * Name: Bulk Add
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Bulk add server-side images
 * Documentation:
 */

class BulkAddInfo extends ExtensionInfo
{
    public const KEY = "bulk_add";

    public $key = self::KEY;
    public $name = "Bulk Add";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Bulk add server-side images";
    public $documentation =
"  Upload the images into a new directory via ftp or similar, go to
 shimmie's admin page and put that directory in the bulk add box.
 If there are subdirectories, they get used as tags (eg if you
 upload into <code>/home/bob/uploads/holiday/2008/</code> and point
 shimmie at <code>/home/bob/uploads</code>, then images will be
 tagged \"holiday 2008\")
 <p><b>Note:</b> requires the \"admin\" extension to be enabled
";
}
