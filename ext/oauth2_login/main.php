<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<OAuth2LoginTheme> */
final class OAuth2Login extends Extension
{
    public const KEY = "oauth2_login";
    private const STATE_COOKIE = "oauth2_login_state";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if (Ctx::$user->is_anonymous() && !$event->page_starts_with("oauth2_login")) {
            $this->login_from_proxy_headers();
        }

        if ($event->page_matches("oauth2_login/start", method: "GET")) {
            $this->page_start();
            return;
        }

        if ($event->page_matches("oauth2_login/callback", method: "GET")) {
            $this->page_callback($event->GET->toArray());
            return;
        }

        if (
            Ctx::$user->is_anonymous()
            && $this->is_oauth2_client_configured()
            && !$event->page_starts_with("oauth2_login")
        ) {
            $this->theme->display_login_block($this->provider_name());
        }
    }

    private function provider_name(): string
    {
        $name = trim($this->config_string(OAuth2LoginConfig::PROVIDER_NAME));
        return $name === "" ? "OAuth2" : $name;
    }

    private function is_oauth2_client_configured(): bool
    {
        return
            trim($this->config_string(OAuth2LoginConfig::CLIENT_ID)) !== "" &&
            trim($this->config_string(OAuth2LoginConfig::CLIENT_SECRET)) !== "" &&
            trim($this->config_string(OAuth2LoginConfig::AUTHORIZATION_URL)) !== "" &&
            trim($this->config_string(OAuth2LoginConfig::TOKEN_URL)) !== "" &&
            trim($this->config_string(OAuth2LoginConfig::USERINFO_URL)) !== "";
    }

    private function config_string(string $key): string
    {
        $value = Ctx::$config->get($key);
        return is_string($value) ? $value : "";
    }

    private function config_bool(string $key): bool
    {
        return Ctx::$config->get($key) === true;
    }

    private function login_from_proxy_headers(): void
    {
        if (!$this->config_bool(OAuth2LoginConfig::TRUST_PROXY_HEADERS)) {
            return;
        }

        $username = $this->configured_header_value(OAuth2LoginConfig::PROXY_USERNAME_HEADER);
        if ($username === null) {
            return;
        }

        $email = $this->configured_header_value(OAuth2LoginConfig::PROXY_EMAIL_HEADER) ?? "";
        if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidInput("Trusted proxy returned an invalid email address");
        }

        $user = $this->find_or_create_proxy_user($username, $email);
        send_event(new UserLoginEvent($user));
        $user->set_login_cookie();
        Log::info("oauth2-login", "Logged in @$user->name from trusted reverse proxy headers");
    }

    private function configured_header_value(string $config_key): ?string
    {
        $server_key = $this->header_server_key($this->config_string($config_key));
        if ($server_key === "") {
            return null;
        }

        $value = $_SERVER[$server_key] ?? null;
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string)$value);
        return $value === "" ? null : $value;
    }

    private function header_server_key(string $header): string
    {
        $header = strtoupper(str_replace("-", "_", trim($header)));
        if ($header === "") {
            return "";
        }
        if (str_starts_with($header, "HTTP_") || $header === "REMOTE_USER") {
            return $header;
        }
        return "HTTP_$header";
    }

    private function page_start(): void
    {
        if (!$this->is_oauth2_client_configured()) {
            $this->theme->display_not_configured();
            return;
        }

        $state = bin2hex(random_bytes(24));
        Ctx::$page->add_cookie(self::STATE_COOKIE, $state, time() + 600);
        Ctx::$page->set_redirect(Url::parse($this->with_query(
            $this->config_string(OAuth2LoginConfig::AUTHORIZATION_URL),
            [
                "response_type" => "code",
                "client_id" => $this->config_string(OAuth2LoginConfig::CLIENT_ID),
                "redirect_uri" => $this->callback_url(),
                "scope" => $this->config_string(OAuth2LoginConfig::SCOPES),
                "state" => $state,
            ]
        )));
    }

    /**
     * @param array<string, string|string[]> $query
     */
    private function page_callback(array $query): void
    {
        if (!$this->is_oauth2_client_configured()) {
            $this->theme->display_not_configured();
            return;
        }

        if (isset($query["error"])) {
            $description = $query["error_description"] ?? $query["error"];
            if (is_array($description)) {
                $description = implode(", ", $description);
            }
            throw new InvalidInput("OAuth2 login failed: $description");
        }

        $state = $this->single_query_value($query, "state");
        $code = $this->single_query_value($query, "code");
        $cookie_state = Ctx::$page->get_cookie(self::STATE_COOKIE);
        Ctx::$page->add_cookie(self::STATE_COOKIE, "", time() - 3600);

        if ($state === null || $cookie_state === null || !hash_equals($cookie_state, $state)) {
            throw new InvalidInput("OAuth2 login failed state validation");
        }
        if ($code === null || $code === "") {
            throw new InvalidInput("OAuth2 login callback did not include a code");
        }

        $token = $this->request_json(
            $this->config_string(OAuth2LoginConfig::TOKEN_URL),
            [
                "grant_type" => "authorization_code",
                "code" => $code,
                "redirect_uri" => $this->callback_url(),
                "client_id" => $this->config_string(OAuth2LoginConfig::CLIENT_ID),
                "client_secret" => $this->config_string(OAuth2LoginConfig::CLIENT_SECRET),
            ]
        );
        $access_token = $token["access_token"] ?? null;
        if (!is_string($access_token) || $access_token === "") {
            throw new InvalidInput("OAuth2 token endpoint did not return an access token");
        }

        $profile = $this->request_json(
            $this->config_string(OAuth2LoginConfig::USERINFO_URL),
            null,
            ["Authorization: Bearer $access_token"]
        );
        $user = $this->find_or_create_user($profile);

        send_event(new UserLoginEvent($user));
        $user->set_login_cookie();
        Ctx::$page->flash("Logged in with " . $this->provider_name());
        Ctx::$page->set_redirect(make_link("user"));
    }

    /**
     * @param array<string, string|string[]> $query
     */
    private function single_query_value(array $query, string $key): ?string
    {
        $value = $query[$key] ?? null;
        if (is_array($value)) {
            return $value[0] ?? null;
        }
        return $value;
    }

    /**
     * @param array<string, string> $query
     */
    private function with_query(string $url, array $query): string
    {
        return $url . (str_contains($url, "?") ? "&" : "?") . http_build_query($query);
    }

    private function callback_url(): string
    {
        return (string)make_link("oauth2_login/callback")->asAbsolute();
    }

    /**
     * @param array<string, string>|null $post_fields
     * @param string[] $headers
     * @return array<string, mixed>
     */
    private function request_json(string $url, ?array $post_fields = null, array $headers = []): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new ServerError("Could not initialize OAuth2 HTTP request");
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(["Accept: application/json"], $headers));
        if ($post_fields !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
                ["Accept: application/json", "Content-Type: application/x-www-form-urlencoded"],
                $headers
            ));
        }

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!is_string($response)) {
            throw new InvalidInput("OAuth2 request failed: $error");
        }
        if ($status < 200 || $status >= 300) {
            throw new InvalidInput("OAuth2 endpoint returned HTTP $status");
        }

        $data = \Safe\json_decode($response, true);
        if (!is_array($data)) {
            throw new InvalidInput("OAuth2 endpoint did not return a JSON object");
        }
        return $data;
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function find_or_create_user(array $profile): User
    {
        $email = $this->extract_email($profile);
        if ($email !== "" && $this->email_is_trusted($profile)) {
            $user = $this->find_user_by_email($email);
            if ($user !== null) {
                return $user;
            }
        }

        if (!$this->config_bool(OAuth2LoginConfig::AUTO_CREATE_USERS)) {
            throw new InvalidInput("OAuth2 user does not match an existing account");
        }

        return $this->create_user($this->unique_username($this->extract_username($profile)), $email);
    }

    private function find_or_create_proxy_user(string $username, string $email): User
    {
        $username = $this->normalise_username($username);

        if ($email !== "") {
            $user = $this->find_user_by_email($email);
            if ($user !== null) {
                return $user;
            }
        }

        try {
            return User::by_name($username);
        } catch (UserNotFound) {
            // The trusted proxy identity will be created below if allowed.
        }

        if (!$this->config_bool(OAuth2LoginConfig::AUTO_CREATE_USERS)) {
            throw new InvalidInput("Trusted proxy user does not match an existing account");
        }

        return $this->create_user($this->unique_username($username), $email);
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function extract_email(array $profile): string
    {
        $field = trim($this->config_string(OAuth2LoginConfig::EMAIL_FIELD));
        if ($field === "") {
            return "";
        }

        $email = $this->profile_value($profile, $field);
        if ($email === null || $email === "") {
            return "";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidInput("OAuth2 provider returned an invalid email address");
        }
        return $email;
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function email_is_trusted(array $profile): bool
    {
        if (!$this->config_bool(OAuth2LoginConfig::REQUIRE_VERIFIED_EMAIL)) {
            return true;
        }

        $field = trim($this->config_string(OAuth2LoginConfig::EMAIL_VERIFIED_FIELD));
        if ($field === "") {
            throw new InvalidInput("OAuth2 provider did not prove that the email address is verified");
        }

        $verified = strtolower($this->profile_value($profile, $field) ?? "");
        if (\in_array($verified, ["1", "true", "yes"], true)) {
            return true;
        }
        throw new InvalidInput("OAuth2 provider did not verify the email address");
    }

    private function find_user_by_email(string $email): ?User
    {
        $id = Ctx::$database->get_one(
            "SELECT id FROM users WHERE LOWER(email) = LOWER(:email)",
            ["email" => $email]
        );
        if ($id === null) {
            return null;
        }
        return User::by_id((int)$id);
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function extract_username(array $profile): string
    {
        foreach ($this->configured_fields(OAuth2LoginConfig::USERNAME_FIELDS) as $field) {
            $value = $this->profile_value($profile, $field);
            if ($value !== null && $value !== "") {
                return $this->normalise_username($value);
            }
        }

        return "oauth2_" . substr(hash("sha256", \Safe\json_encode($profile)), 0, 12);
    }

    /**
     * @return list<string>
     */
    private function configured_fields(string $config_key): array
    {
        $fields = array_map("trim", explode(",", $this->config_string($config_key)));
        return array_values(array_filter($fields, fn (string $field): bool => $field !== ""));
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function profile_value(array $profile, string $field): ?string
    {
        $value = $profile;
        foreach (explode(".", $field) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return null;
            }
            $value = $value[$part];
        }
        if (is_scalar($value)) {
            return trim((string)$value);
        }
        return null;
    }

    private function normalise_username(string $name): string
    {
        if (str_contains($name, "@")) {
            $name = explode("@", $name, 2)[0];
        }
        $name = \Safe\preg_replace("/[^a-zA-Z0-9-_]+/", "_", $name);
        $name = trim(\Safe\preg_replace("/_+/", "_", $name), "_-");
        if ($name === "") {
            $name = "oauth2_user";
        }
        return substr($name, 0, 64);
    }

    private function unique_username(string $base): string
    {
        $base = $base === "" ? "oauth2_user" : $base;
        $candidate = $base;
        for ($i = 0; $i < 100; $i++) {
            try {
                User::by_name($candidate);
            } catch (UserNotFound) {
                return $candidate;
            }
            $suffix = $i === 0 ? substr(bin2hex(random_bytes(4)), 0, 8) : (string)$i;
            $candidate = substr($base, 0, 55) . "_" . $suffix;
        }
        throw new InvalidInput("Could not create a unique OAuth2 username");
    }

    private function create_user(string $username, string $email): User
    {
        $need_admin = (Ctx::$database->get_one("SELECT COUNT(*) FROM users WHERE class='admin'") === 0);
        $class = $need_admin ? "admin" : "user";

        Ctx::$database->execute(
            "INSERT INTO users (name, pass, joindate, email, class) VALUES (:username, :hash, now(), :email, :class)",
            ["username" => $username, "hash" => "", "email" => ($email === "" ? null : $email), "class" => $class]
        );

        $user = User::by_name($username);
        $user->set_password(bin2hex(random_bytes(32)));
        Log::info("oauth2-login", "Created OAuth2 user @$username");
        return $user;
    }
}
