<?php

declare(strict_types=1);

namespace Shimmie2;

final class OAuth2LoginInfo extends ExtensionInfo
{
    public const KEY = "oauth2_login";

    public string $name = "OAuth2 Login";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
    public string $description = "Authenticate users through a configurable OAuth2 provider";
    public ?string $documentation =
        "Adds a generic OAuth2 login button using administrator-configured authorization,
token, and userinfo endpoints. The callback URL to register with the provider is
<code>/oauth2_login/callback</code> on this Shimmie instance.";
}
