<?php

declare(strict_types=1);

namespace Shimmie2;

final class SitenameInTitleConfig extends ConfigGroup
{
    public const KEY = "sitename_in_title";
    public ?string $title = "Site Name in Title";

    #[ConfigMeta("Website name in <title>", ConfigType::STRING, default: "prefix", options: [
        "as prefix" => "prefix",
        "as suffix" => "suffix"
    ])]
    public const SITENAME_IN_TITLE = "sitename_in_title";
}
