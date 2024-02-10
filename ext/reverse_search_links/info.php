<?php

declare(strict_types=1);

namespace Shimmie2;

class ReverseSearchLinksInfo extends ExtensionInfo
{
    public const KEY = "reverse_search_links";

    public string $key = self::KEY;
    public string $name = "Reverse Search Links";
    public array $authors = ['joe' => 'joe@thisisjoes.site'];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Provides reverse search links for images.";
    public ?string $documentation = "Click on an icon in the 'Reverse Image Search' block to search for the image using the corresponding service. This may be useful to find the original source or author of an image.<br/>
                                     Options for which services to show and the position and priority of the block are available for admins on the config page.";
}
