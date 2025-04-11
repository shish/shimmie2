<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{B, DIV, H3, INPUT, LABEL, SECTION, SPAN, TABLE, TD, TH, TR};
use function MicroHTML\{LINK, TITLE, emptyHTML};

class DowntimeTheme extends Themelet
{
    /**
     * Show the admin that downtime mode is enabled
     */
    public function display_notification(): void
    {
        Ctx::$page->add_block(new Block(
            "Downtime",
            SPAN(["style" => "font-size: 1.5rem; text-align: center;"], B("DOWNTIME MODE IS ON!")),
            "left",
            0
        ));
    }

    /**
     * Display $message and exit
     */
    public function display_message(string $message): void
    {
        $theme_name = Ctx::$config->get(SetupConfig::THEME);

        $head = emptyHTML(
            TITLE("Downtime"),
            LINK(["rel" => "stylesheet", "href" => Url::base() . "/ext/static_files/style.css", "type" => "text/css"]),
            LINK(["rel" => "stylesheet", "href" => Url::base() . "/themes/$theme_name/style.css", "type" => "text/css"]),
        );
        $body = DIV(
            ["id" => "downtime"],
            SECTION(
                H3(["style" => "text-align: center;"], "Down for Maintenance"),
                DIV(["id" => "message", "class" => "blockbody"], $message)
            ),
            SECTION(
                H3("Admin Login"),
                DIV(
                    ["id" => "login", "class" => "blockbody"],
                    SHM_SIMPLE_FORM(
                        make_link("user_admin/login"),
                        TABLE(
                            ["class" => "form"],
                            TR(
                                TH(["width" => "70"], LABEL(["for" => "user"], "Name")),
                                TD(["width" => "70"], INPUT(["id" => "user", "type" => "text", "name" => "user"]))
                            ),
                            TR(
                                TH(LABEL(["for" => "pass"], "Password")),
                                TD(INPUT(["id" => "pass", "type" => "password", "name" => "pass"]))
                            ),
                            TR(
                                TD(["colspan" => "2"], SHM_SUBMIT("Log In"))
                            )
                        )
                    ),
                )
            )
        );

        Ctx::$page->set_code(503);
        Ctx::$page->set_data(MimeType::HTML, (string)Ctx::$page->html_html($head, $body));
    }
}
