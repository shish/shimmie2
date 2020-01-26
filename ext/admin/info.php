<?php declare(strict_types=1);

class AdminPageInfo extends ExtensionInfo
{
    public const KEY = "admin";

    public $key = self::KEY;
    public $name = "Admin Controls";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Various things to make admins' lives easier";
    public $documentation =
"Various moderate-level tools for admins; for advanced, obscure, and possibly dangerous tools see the shimmie2-utils script set
  <p>Lowercase all tags:
  <br>Set all tags to lowercase for consistency
  <p>Recount tag use:
  <br>If the counts of images per tag get messed up somehow, this will reset them, and remove any unused tags
  <p>Database dump:
  <br>Download the contents of the database in plain text format, useful for backups.
  <p>Image dump:
  <br>Download all the images as a .zip file (Requires ZipArchive)";
}
