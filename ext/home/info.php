<?php

declare(strict_types=1);

namespace Shimmie2;

class HomeInfo extends ExtensionInfo
{
    public const KEY = "home";

    public string $key = self::KEY;
    public string $name = "Home Page";
    public array $authors = ["Bzchan" => "bzchan@animemahou.com"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public string $description = "Displays a front page with logo, search box and post count";
    public ?string $documentation =
"Once enabled, the page will show up at the URL \"home\", so if you want
this to be the front page of your site, you should go to \"Board Config\"
and set \"Front Page\" to \"home\".
<p>The images used for the numbers can be changed from the board config
page. If you want to use your own numbers, upload them into a new folder
in <code>/ext/home/counters</code>, and they'll become available
alongside the default choices.";
}
