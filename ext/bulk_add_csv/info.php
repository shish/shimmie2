<?php declare(strict_types=1);

class BulkAddCSVInfo extends ExtensionInfo
{
    public const KEY = "bulk_add_csv";

    public $key = self::KEY;
    public $name = "Bulk Add CSV";
    public $url = self::SHIMMIE_URL;
    public $authors = ["velocity37"=>"velocity37@gmail.com"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Bulk add server-side posts with metadata from CSV file";
    public $documentation =
"Modification of \"Bulk Add\" by Shish.<br><br>
Adds posts from a CSV with the five following values: <br>
\"/path/to/image.jpg\",\"spaced tags\",\"source\",\"rating s/q/e\",\"/path/thumbnail.jpg\" <br>
<b>e.g.</b> \"/tmp/cat.png\",\"shish oekaki\",\"shimmie.shishnet.org\",\"s\",\"tmp/custom.jpg\" <br><br>
Any value but the first may be omitted, but there must be five values per line.<br>
<b>e.g.</b> \"/why/not/try/bulk_add.jpg\",\"\",\"\",\"\",\"\"<br><br>
Post thumbnails will be displayed at the AR of the full post. Thumbnails that are
normally static (e.g. SWF) will be displayed at the board's max thumbnail size<br><br>
Useful for importing tagged posts without having to do database manipulation.<br>
<p><b>Note:</b> requires \"Admin Controls\" and optionally \"Post Ratings\" to be enabled<br><br>";
}
