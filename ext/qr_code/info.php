<?php declare(strict_types=1);

class QRImageInfo extends ExtensionInfo
{
    public const KEY = "qr_code";

    public string $key = self::KEY;
    public string $name = "QR Codes";
    public string $url = "http://seemslegit.com";
    public array $authors = ["Zach Hall"=>"zach@sosguy.net"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Turns BBCode into HTML";
    public ?string $documentation =
"Shows a QR Code for downloading a post to cell phones.
Based on Artanis's Link to Post Extension <artanis.00@gmail.com>
Further modified by Shish to remove the 7MB local QR generator and replace it with a link to google chart APIs";
}
