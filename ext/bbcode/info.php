<?php declare(strict_types=1);

class BBCodeInfo extends ExtensionInfo
{
    public const KEY = "bbcode";

    public $key = self::KEY;
    public $name = "BBCode";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $core = true;
    public $description = "Turns BBCode into HTML";
    public $documentation =
"  Supported tags:
   <ul>
     <li>[img]url[/img]
     <li>[url]<a href=\"{self::SHIMMIE_URL}\">https://code.shishnet.org/</a>[/url]
     <li>[email]<a href=\"mailto:{self::SHISH_EMAIL}\">webmaster@shishnet.org</a>[/email]
     <li>[b]<b>bold</b>[/b]
     <li>[i]<i>italic</i>[/i]
     <li>[u]<u>underline</u>[/u]
     <li>[s]<s>strikethrough</s>[/s]
     <li>[sup]<sup>superscript</sup>[/sup]
     <li>[sub]<sub>subscript</sub>[/sub]
     <li>[[wiki article]]
     <li>[[wiki article|with some text]]
     <li>[quote]text[/quote]
     <li>[quote=Username]text[/quote]
     <li>&gt;&gt;123 (link to post #123)
   </ul>";
}
