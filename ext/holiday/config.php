<?php

declare(strict_types=1);

namespace Shimmie2;

final class HolidayConfig extends ConfigGroup
{
    public const KEY = "holiday";
    public ?string $title = "Holiday Themes";

    #[ConfigMeta("April Fools", ConfigType::BOOL, default: false)]
    public const APRIL_FOOLS = "holiday_aprilfools";
}
