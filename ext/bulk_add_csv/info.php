<?php

declare(strict_types=1);

namespace Shimmie2;

class BulkAddCSVInfo extends ExtensionInfo
{
    public const KEY = "bulk_add_csv";

    public string $key = self::KEY;
    public string $name = "Bulk Add CSV";
    public string $url = self::SHIMMIE_URL;
    public array $authors = ["velocity37" => "velocity37@gmail.com"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Bulk add server-side posts with metadata from CSV file";
    public ?string $documentation =
"Adds posts from a CSV with the five following values:
<pre>\"/path/to/image.jpg\",\"spaced tags\",\"source\",\"rating s/q/e\",\"/path/thumbnail.jpg\"</pre>

<b>e.g.</b>
<pre>\"/tmp/cat.png\",\"shish oekaki\",\"http://shimmie.shishnet.org\",\"s\",\"tmp/custom.jpg\"</pre>

Any value but the first may be omitted, but there must be five values per line.
<b>e.g.</b> <pre>\"/why/not/try/bulk_add.jpg\",\"\",\"\",\"\",\"\"</pre>

Useful for importing tagged posts without having to do database manipulation.

<p><b>Note:</b> requires \"Admin Controls\" and optionally \"Post Ratings\" to be enabled<br><br>";
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
}
