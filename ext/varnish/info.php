<?php

declare(strict_types=1);

namespace Shimmie2;

final class VarnishPurgerInfo extends ExtensionInfo
{
    public const KEY = "varnish";

    public string $name = "Varnish Purger";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Sends PURGE requests when a /post/view is updated";
}
