<?php

declare(strict_types=1);

namespace Shimmie2;

final class OAuth2LoginTest extends ShimmiePHPUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Ctx::$config->set(OAuth2LoginConfig::PROVIDER_NAME, "Example SSO");
        Ctx::$config->set(OAuth2LoginConfig::CLIENT_ID, "client-id");
        Ctx::$config->set(OAuth2LoginConfig::CLIENT_SECRET, "client-secret");
        Ctx::$config->set(OAuth2LoginConfig::AUTHORIZATION_URL, "https://idp.example/authorize");
        Ctx::$config->set(OAuth2LoginConfig::TOKEN_URL, "https://idp.example/token");
        Ctx::$config->set(OAuth2LoginConfig::USERINFO_URL, "https://idp.example/userinfo");
        Ctx::$config->set(OAuth2LoginConfig::SCOPES, "openid profile email");
    }

    public function testLoginBlockShowsWhenConfigured(): void
    {
        self::get_page("post/list");
        self::assert_text("Log in with Example SSO", "left");
    }

    public function testStartRedirectsToProvider(): void
    {
        $page = self::get_page("oauth2_login/start");
        self::assertSame(PageMode::REDIRECT, $page->mode);
        self::assertStringStartsWith("https://idp.example/authorize?", (string)$page->redirect);
        self::assertStringContainsString("response_type=code", (string)$page->redirect);
        self::assertStringContainsString("client_id=client-id", (string)$page->redirect);
        self::assertStringContainsString("scope=openid+profile+email", (string)$page->redirect);
        self::assertStringContainsString("state=", (string)$page->redirect);
    }

    public function testCallbackRejectsBadState(): void
    {
        self::assertException(InvalidInput::class, function () {
            self::request(
                "GET",
                "oauth2_login/callback",
                ["state" => "bad", "code" => "abc"],
                [],
                ["shm_oauth2_login_state" => "good"]
            );
        });
    }

    public function testCreatesUserFromVerifiedProfile(): void
    {
        $user = $this->findOrCreateUser([
            "preferred_username" => "OAuth Person",
            "email" => "oauth-person@example.com",
            "email_verified" => true,
        ]);

        self::assertSame("OAuth_Person", $user->name);
        self::assertSame("oauth-person@example.com", $user->email);
    }

    public function testTrustedProxyHeaderCreatesAndLogsInUser(): void
    {
        Ctx::$config->set(OAuth2LoginConfig::TRUST_PROXY_HEADERS, true);
        $_SERVER["HTTP_X_FORWARDED_USER"] = "Proxy Person";
        $_SERVER["HTTP_X_FORWARDED_EMAIL"] = "proxy-person@example.com";

        try {
            self::get_page("post/list");
        } finally {
            unset($_SERVER["HTTP_X_FORWARDED_USER"], $_SERVER["HTTP_X_FORWARDED_EMAIL"]);
        }

        self::assertSame("Proxy_Person", Ctx::$user->name);
        self::assertSame("proxy-person@example.com", Ctx::$user->email);
    }

    public function testRejectsUnverifiedEmail(): void
    {
        self::assertException(InvalidInput::class, function () {
            $this->findOrCreateUser([
                "preferred_username" => "oauth-person",
                "email" => "oauth-person@example.com",
                "email_verified" => false,
            ]);
        });
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function findOrCreateUser(array $profile): User
    {
        $method = new \ReflectionMethod(OAuth2Login::class, "find_or_create_user");
        return $method->invoke(new OAuth2Login(), $profile);
    }
}
