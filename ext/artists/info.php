<?php

declare(strict_types=1);

namespace Shimmie2;

final class ArtistsInfo extends ExtensionInfo
{
    public const KEY = "artists";

    public string $name = "Artists System";
    public array $authors = ["Sein Kraft" => "mailto:mail@seinkraft.info","Alpha" => "mailto:alpha@furries.com.ar"];
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Simple artists extension";
    public bool $beta = true;
}
