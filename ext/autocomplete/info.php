<?php

declare(strict_types=1);

namespace Shimmie2;

class AutoCompleteInfo extends ExtensionInfo
{
    public const KEY = "autocomplete";

    public string $key = self::KEY;
    public string $name = "Autocomplete";
    public array $authors = ["Daku" => "admin@codeanimu.net"];
    public string $description = "Adds autocomplete to search & tagging.";
}
