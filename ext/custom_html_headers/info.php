<?php

declare(strict_types=1);

namespace Shimmie2;

final class CustomHtmlHeadersInfo extends ExtensionInfo
{
    public const KEY = "custom_html_headers";

    public string $name = "Custom HTML Headers";
    public array $authors = ["Drudex Software" => "mailto:support@drudexsoftware.com"];
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
    public string $description = "Allows admins to modify & set custom <head> content";
    public ?string $documentation =
        "When you go to board config you can find a block named Custom HTML Headers.
<br>In that block you can simply place any thing you can place within &lt;head&gt;&lt;/head&gt;
<br>
<br>This can be useful if you want to add website tracking code or other javascript.
<br>NOTE: Only use if you know what you're doing.
<br>
<br>You can also add your website name as prefix or suffix to the title of each page on your website.";
}
