<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{emptyHTML, TITLE, LINK};
use function MicroHTML\B;
use function MicroHTML\DIV;
use function MicroHTML\H3;
use function MicroHTML\INPUT;
use function MicroHTML\LABEL;
use function MicroHTML\SECTION;
use function MicroHTML\SPAN;
use function MicroHTML\TABLE;
use function MicroHTML\TD;
use function MicroHTML\TH;
use function MicroHTML\TR;

class DowntimeTheme extends Themelet
{
    /**
     * Show the admin that downtime mode is enabled
     */
    public function display_notification(Page $page): void
    {
        $page->add_block(new Block(
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
        global $config, $user, $page;
        $theme_name = $config->get_string(SetupConfig::THEME);

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

        $page->set_mode(PageMode::DATA);
        $page->set_code(503);
        $page->set_data((string)$page->html_html($head, $body));
    }
}
