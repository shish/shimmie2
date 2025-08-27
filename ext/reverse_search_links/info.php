<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReverseSearchLinksInfo extends ExtensionInfo
{
    public const KEY = "reverse_search_links";

    public string $name = "Reverse Search Links";
    public array $authors = ['joe' => 'mailto:joe@thisisjoes.site'];
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Provides reverse search links for images";
}
