<?php

declare(strict_types=1);

namespace Shimmie2;

final class RandomListInfo extends ExtensionInfo
{
    public const KEY = "random_list";

    public string $name = "Random List";
    public array $authors = ["Drudex Software" => "mailto:support@drudexsoftware.com"];
    public string $description = "Allows displaying a page with random posts";
    public ?string $documentation =
        "Random post list can be accessed through www.yoursite.com/random
It is recommended that you create a link to this page so users know it exists.";
}
