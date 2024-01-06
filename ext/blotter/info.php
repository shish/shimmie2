<?php

declare(strict_types=1);

namespace Shimmie2;

class BlotterInfo extends ExtensionInfo
{
    public const KEY = "blotter";

    public string $key = self::KEY;
    public string $name = "Blotter";
    public string $url = "http://seemslegit.com/";
    public array $authors = ["Zach Hall" => "zach@sosguy.net"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Displays brief updates about whatever you want on every page.";
    public ?string $documentation = "Colors and positioning can be configured to match your site's design.<p>Development TODO at https://github.com/zshall/shimmie2/issues";
}
