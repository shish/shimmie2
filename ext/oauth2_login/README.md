# OAuth2 Login extension

This extension supports two deployment shapes:

- direct OAuth2 login, where Shimmie talks to the provider itself
- trusted reverse-proxy login, where a fronting service such as
  oauth2-proxy authenticates the request and passes a username/email header to
  Shimmie

For both modes, enable the `oauth2_login` extension first.

## Direct OAuth2 client test with Keycloak

Create a local Keycloak realm and confidential client:

- realm: `shimmie`
- client id: `shimmie-local`
- client authentication: enabled
- client secret: `local-dev-secret`
- valid redirect URI: `http://localhost:4010/oauth2_login/callback`
- web origin: `http://localhost:4010`
- test user: `shimmie-admin`
- test user email: `shimmie-admin@example.test`
- mark the test user's email as verified

Then set these Shimmie board config values:

- Provider name: `Keycloak`
- Client ID: `shimmie-local`
- Client secret: `local-dev-secret`
- Authorization URL:
  `http://keycloak.localhost:8080/realms/shimmie/protocol/openid-connect/auth`
- Token URL:
  `http://keycloak.localhost:8080/realms/shimmie/protocol/openid-connect/token`
- Userinfo URL:
  `http://keycloak.localhost:8080/realms/shimmie/protocol/openid-connect/userinfo`
- Scopes: `openid profile email`
- Username fields: `preferred_username,name,email`
- Email field: `email`
- Email verified field: `email_verified`
- Require verified email: enabled
- Auto-create OAuth2 users: enabled

Visit `http://localhost:4010/`, click "Log in with Keycloak", and sign in as
the Keycloak test user.

## Trusted reverse-proxy testbed

This mode is useful when oauth2-proxy or another fronting service owns all
OAuth2 redirects. Shimmie only trusts the identity headers set by that proxy.

Only enable trusted-header mode when clients cannot reach Shimmie directly.
The reverse proxy must clear user-supplied identity headers before it sets the
trusted values.

Set these Shimmie board config values:

- Trust reverse proxy headers: enabled
- Proxy username header: `X-Forwarded-User`
- Proxy email header: `X-Forwarded-Email`
- Auto-create OAuth2 users: enabled

Some proxies expose the authenticated username as `REMOTE_USER` instead of an
HTTP header. For example, Authelia deployments can set Proxy username header to
`REMOTE_USER`; Shimmie reads that exact server variable instead of converting it
to `HTTP_REMOTE_USER`.

The following files can be dropped into a checkout of
`shish/shimmie2-examples` next to the existing `docker-compose.yaml`. The
values are intentionally local-development only.

### docker-compose override

```yaml
services:
  keycloak:
    image: quay.io/keycloak/keycloak:26.0
    command: start-dev --import-realm --hostname=keycloak.localhost --hostname-strict=false
    ports:
      - "8080:8080"
    environment:
      KC_BOOTSTRAP_ADMIN_USERNAME: admin
      KC_BOOTSTRAP_ADMIN_PASSWORD: admin
    volumes:
      - ./oauth2-keycloak-realm.json:/opt/keycloak/data/import/shimmie-realm.json:ro
    networks:
      default:
        aliases:
          - keycloak.localhost

  oauth2-proxy:
    image: quay.io/oauth2-proxy/oauth2-proxy:v7.7.1
    command:
      - --provider=keycloak-oidc
      - --oidc-issuer-url=http://keycloak.localhost:8080/realms/shimmie
      - --client-id=shimmie-local
      - --client-secret=local-dev-secret
      - --redirect-url=http://localhost:4050/oauth2/callback
      - --cookie-secret=0123456789abcdef0123456789abcdef
      - --cookie-secure=false
      - --email-domain=*
      - --http-address=0.0.0.0:4180
      - --reverse-proxy=true
      - --set-xauthrequest=true
      - --pass-user-headers=true
      - --skip-provider-button=true
    depends_on:
      - keycloak

  oauth2-nginx:
    image: nginx:alpine
    ports:
      - "4050:80"
    volumes:
      - ./oauth2-nginx.conf:/etc/nginx/nginx.conf:ro
      - ../shimmie2:/var/www/html
    depends_on:
      - php-fpm
      - oauth2-proxy
```

### oauth2-nginx.conf

```nginx
worker_processes 1;

events {
    worker_connections 1024;
}

http {
    server {
        listen 80;
        root /var/www/html;
        server_name _;

        client_max_body_size 11M;

        location /oauth2/ {
            proxy_pass http://oauth2-proxy:4180;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Scheme $scheme;
            proxy_set_header X-Auth-Request-Redirect $scheme://$http_host$request_uri;
        }

        location = /oauth2/auth {
            proxy_pass http://oauth2-proxy:4180/oauth2/auth;
            proxy_pass_request_body off;
            proxy_set_header Content-Length "";
            proxy_set_header Host $host;
            proxy_set_header X-Original-URI $request_uri;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Scheme $scheme;
        }

        location / {
            auth_request /oauth2/auth;
            error_page 401 = /oauth2/sign_in;

            auth_request_set $oauth_user $upstream_http_x_auth_request_user;
            auth_request_set $oauth_email $upstream_http_x_auth_request_email;

            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root/index.php;
            fastcgi_param HTTP_X_FORWARDED_USER $oauth_user;
            fastcgi_param HTTP_X_FORWARDED_EMAIL $oauth_email;
            fastcgi_pass php-fpm:9000;
        }
    }
}
```

### oauth2-keycloak-realm.json

```json
{
  "realm": "shimmie",
  "enabled": true,
  "clients": [
    {
      "clientId": "shimmie-local",
      "enabled": true,
      "protocol": "openid-connect",
      "publicClient": false,
      "secret": "local-dev-secret",
      "redirectUris": [
        "http://localhost:4050/oauth2/callback",
        "http://localhost:4010/oauth2_login/callback"
      ],
      "webOrigins": [
        "http://localhost:4050",
        "http://localhost:4010"
      ],
      "standardFlowEnabled": true,
      "directAccessGrantsEnabled": true
    }
  ],
  "users": [
    {
      "username": "shimmie-admin",
      "enabled": true,
      "email": "shimmie-admin@example.test",
      "emailVerified": true,
      "firstName": "Shimmie",
      "lastName": "Admin",
      "credentials": [
        {
          "type": "password",
          "value": "password",
          "temporary": false
        }
      ]
    }
  ]
}
```

Start the examples with:

```sh
docker compose up keycloak oauth2-proxy oauth2-nginx php-fpm
```

Then visit `http://localhost:4050/`. oauth2-proxy should redirect to
Keycloak. Sign in with `shimmie-admin` / `password`. After the callback,
Shimmie should receive:

- `X-Forwarded-User: shimmie-admin`
- `X-Forwarded-Email: shimmie-admin@example.test`

The Shimmie extension should then create or load the matching local user.

For production, replace all secrets and passwords, enable HTTPS, set secure
cookies, and restrict direct access to Shimmie so that only the trusted reverse
proxy can reach PHP-FPM.
