<?php

declare(strict_types=1);

namespace Shimmie2;

final class WikiHtmlInfo extends ExtensionInfo
{
    public const KEY = "wiki_html";

    public string $name = "Wiki HTML";
    public array $authors = ["Miyuu" => null];
    public ExtensionCategory $category = ExtensionCategory::FEATURE;
    public string $description = "Adds [html] tag support to the wiki.";

    /** @var list<string> */
    public array $requires = [WikiInfo::KEY];
}
