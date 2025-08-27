<?php

declare(strict_types=1);

namespace Shimmie2;

final class AutoCompleteInfo extends ExtensionInfo
{
    public const KEY = "autocomplete";

    public string $name = "Autocomplete";
    public array $authors = ["Daku" => "mailto:admin@codeanimu.net"];
    public string $description = "Adds autocomplete to search & tagging";
}
