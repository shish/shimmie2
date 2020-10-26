<?php declare(strict_types=1);

class QRImageInfo extends ExtensionInfo
{
    public const KEY = "qr_code";

    public $key = self::KEY;
    public $name = "QR Codes";
    public $url = "http://seemslegit.com";
    public $authors = ["Zach Hall"=>"zach@sosguy.net"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Turns BBCode into HTML";
    public $documentation =
"Shows a QR Code for downloading a post to cell phones.
Based on Artanis's Link to Post Extension <artanis.00@gmail.com>
Further modified by Shish to remove the 7MB local QR generator and replace it with a link to google chart APIs";
}
