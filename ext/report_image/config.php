<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReportImageConfig extends ConfigGroup
{
    public const KEY = "report_image";
    public ?string $title = "Post Reports";

    #[ConfigMeta("Show to users", ConfigType::STRING, options: [
        "Reporter Only" => "user",
        "Reason Only" => "reason",
        "Both" => "both",
        "None" => "none",
    ])]
    public const SHOW_INFO = "report_image_publicity";
}
