<?php

declare(strict_types=1);

namespace Shimmie2;

final class OAuth2LoginInfo extends ExtensionInfo
{
    public const KEY = "oauth2_login";

    public string $name = "OAuth2 Login";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
    public string $description = "Authenticate users through OAuth2 or trusted reverse proxy headers";
    public ?string $documentation =
        "Adds a generic OAuth2 login button using administrator-configured authorization,
token, and userinfo endpoints. The callback URL to register with the provider is
<code>/oauth2_login/callback</code> on this Shimmie instance.

Alternatively, administrators can enable trusted reverse proxy headers for
setups where oauth2-proxy, nginx, or another fronting service has already
authenticated the request. Only enable that mode when Shimmie is not reachable
directly by clients, because Shimmie will trust the configured username and
email headers. A typical oauth2-proxy and nginx setup would protect Shimmie
with <code>auth_request</code>, clear any inbound identity headers, then set the
configured Shimmie headers from oauth2-proxy's authenticated response:

<pre><code>proxy_set_header X-Forwarded-User \"\";
proxy_set_header X-Forwarded-Email \"\";
auth_request /oauth2/auth;
auth_request_set \$user \$upstream_http_x_auth_request_user;
auth_request_set \$email \$upstream_http_x_auth_request_email;
proxy_set_header X-Forwarded-User \$user;
proxy_set_header X-Forwarded-Email \$email;</code></pre>

See <code>ext/oauth2_login/README.md</code> for a local Keycloak and
oauth2-proxy testbed that can be adapted into shimmie2-examples.";
}
