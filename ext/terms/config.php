<?php

declare(strict_types=1);

namespace Shimmie2;

final class TermsConfig extends ConfigGroup
{
    public const KEY = "terms";
    public ?string $title = "Terms & Conditions Wall";

    #[ConfigMeta(
        "Message",
        ConfigType::STRING,
        default: "Cookies may be used. Please read our [url=site://wiki/privacy]privacy policy[/url] for more information.\nBy accepting to enter you agree to our [url=site://wiki/rules]rules[/url] and [url=site://wiki/terms_of_service]terms of service[/url].",
        input: ConfigInput::TEXTAREA
    )]
    public const MESSAGE = 'terms_message';
}
