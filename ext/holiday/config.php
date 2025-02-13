<?php

declare(strict_types=1);

namespace Shimmie2;

class HolidayConfig extends ConfigGroup
{
    public ?string $title = "Holiday Themes";

    #[ConfigMeta("April Fools", ConfigType::BOOL)]
    public const APRIL_FOOLS = "holiday_aprilfools";
}
