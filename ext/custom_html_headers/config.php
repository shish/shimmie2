<?php

declare(strict_types=1);

namespace Shimmie2;

final class CustomHtmlHeadersConfig extends ConfigGroup
{
    public const KEY = "custom_html_headers";
    public ?string $title = "Custom HTML Headers";

    #[ConfigMeta("HTML inside <head>", ConfigType::STRING, input: ConfigInput::TEXTAREA)]
    public const CUSTOM_HTML_HEADERS = "custom_html_headers";
}
