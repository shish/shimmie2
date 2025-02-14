<?php

declare(strict_types=1);

namespace Shimmie2;

class TermsConfig extends ConfigGroup
{
    public ?string $title = "Terms & Conditions Wall";

    #[ConfigMeta("Message", ConfigType::STRING, ui_type: "longtext")]
    public const MESSAGE = 'terms_message';
}
