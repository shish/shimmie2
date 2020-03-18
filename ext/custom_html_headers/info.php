<?php declare(strict_types=1);

class CustomHtmlHeadersInfo extends ExtensionInfo
{
    public const KEY = "custom_html_headers";

    public $key = self::KEY;
    public $name = "Custom HTML Headers";
    public $url = "http://www.drudexsoftware.com";
    public $authors = ["Drudex Software"=>"support@drudexsoftware.com"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Allows admins to modify & set custom &lt;head&gt; content";
    public $documentation =
"When you go to board config you can find a block named Custom HTML Headers.
In that block you can simply place any thing you can place within &lt;head&gt;&lt;/head&gt;

This can be useful if you want to add website tracking code or other javascript.
NOTE: Only use if you know what you're doing.

You can also add your website name as prefix or suffix to the title of each page on your website.";
}
