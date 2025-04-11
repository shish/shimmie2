<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, INPUT, SMALL, TABLE, TD, TR, joinHTML};

class WarmUserPageTheme extends UserPageTheme
{
    /**
     * @param array<array{link: Url, name: string}> $parts
     */
    public function display_user_block(User $user, array $parts): void
    {
        $parts_html = [];
        foreach ($parts as $part) {
            $parts_html[] = A(["href" => $part["link"]], $part["name"]);
        }
        Ctx::$page->add_block(new Block("Logged in as {$user->name}", joinHTML(" | ", $parts_html), "head", 90));
    }

    public function display_login_block(): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("user_admin/login"),
            TABLE(
                ["summary" => "Login Form", "align" => "center"],
                TR(
                    TD(["width" => "70"], "Name"),
                    TD(["width" => "70"], INPUT(["type" => "text", "name" => "user"]))
                ),
                TR(
                    TD("Password"),
                    TD(INPUT(["type" => "password", "name" => "pass"]))
                ),
                TR(
                    TD(["colspan" => "2"], SHM_SUBMIT("Log In"))
                )
            )
        );
        if (Ctx::$config->get(UserAccountsConfig::SIGNUP_ENABLED)) {
            $html->appendChild(SMALL(A(["href" => make_link("user_admin/create")], "Create Account")));
        }
        Ctx::$page->add_block(new Block("Login", $html, "head", 90));
    }
}
