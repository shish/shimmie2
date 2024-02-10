<?php

declare(strict_types=1);

namespace Shimmie2;

class ArtistsInfo extends ExtensionInfo
{
    public const KEY = "artists";

    public string $key = self::KEY;
    public string $name = "Artists System";
    public string $url = self::SHIMMIE_URL;
    public array $authors = ["Sein Kraft" => "mail@seinkraft.info","Alpha" => "alpha@furries.com.ar"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Simple artists extension";
    public bool $beta = true;
}
