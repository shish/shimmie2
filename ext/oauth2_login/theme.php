<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, P, SMALL, emptyHTML};

class OAuth2LoginTheme extends Themelet
{
    public function display_login_block(string $provider_name): void
    {
        Ctx::$page->add_block(new Block(
            "$provider_name Login",
            emptyHTML(
                P(A(["href" => make_link("oauth2_login/start")], "Log in with $provider_name")),
                SMALL("Uses the OAuth2 or trusted proxy provider configured by the board administrator.")
            ),
            "left",
            91
        ));
    }

    public function display_not_configured(): void
    {
        Ctx::$page->set_title("OAuth2 Login");
        Ctx::$page->add_block(new Block(
            "OAuth2 Login",
            P("OAuth2 login is not fully configured yet.")
        ));
    }
}
