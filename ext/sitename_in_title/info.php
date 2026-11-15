<?php

declare(strict_types=1);

namespace Shimmie2;

final class SitenameInTitleInfo extends ExtensionInfo
{
    public const KEY = "sitename_in_title";

    public string $name = "Site Name in Title";
    public array $authors = ["Drudex Software" => "mailto:support@drudexsoftware.com"];
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
    public string $description = "Add the site name as a prefix or suffix to all page titles";
}
