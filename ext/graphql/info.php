<?php

declare(strict_types=1);

namespace Shimmie2;

final class GraphQLInfo extends ExtensionInfo
{
    public const KEY = "graphql";

    public string $name = "GraphQL";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Add a graphql API";
}
