<?php

declare(strict_types=1);

namespace Shimmie2;

class GraphQLInfo extends ExtensionInfo
{
    public const KEY = "graphql";

    public string $key = self::KEY;
    public string $name = "GraphQL";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Add a graphql API";
}
