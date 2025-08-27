<?php

declare(strict_types=1);

namespace Shimmie2;

final class BlotterInfo extends ExtensionInfo
{
    public const KEY = "blotter";

    public string $name = "Blotter";
    public array $authors = ["Zach Hall" => "mailto:zach@sosguy.net"];
    public string $description = "Displays brief updates about whatever you want on every page";
}
