<?php

declare(strict_types=1);

namespace Shimmie2;

class QRImageInfo extends ExtensionInfo
{
    public const KEY = "qr_code";

    public string $key = self::KEY;
    public string $name = "QR Codes";
    public string $url = "http://seemslegit.com";
    public array $authors = ["Zach Hall" => "zach@sosguy.net"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Shows a QR Code for downloading a post to cell phones";
    public ?string $documentation =
"Shows a QR Code for downloading a post to cell phones.
<br>Based on <a href='mailto:artanis.00@gmail.com'>Artanis</a>'s Link to Post Extension.
<br>Further modified by Shish to remove the 7MB local QR generator and replace it with a link to Google Chart APIs";
}
