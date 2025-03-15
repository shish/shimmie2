<?php

declare(strict_types=1);

namespace Shimmie2;

final class GraphQLConfig extends ConfigGroup
{
    public const KEY = "graphql";
    public ?string $title = "GraphQL";

    #[ConfigMeta("CORS pattern", ConfigType::STRING, default: "", advanced: true)]
    public const CORS_PATTERN = "graphql_cors_pattern";

    #[ConfigMeta("Debug", ConfigType::BOOL, default: false, advanced: true)]
    public const DEBUG = "graphql_debug";
}
