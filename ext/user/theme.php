<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\emptyHTML;
use function MicroHTML\joinHTML;
use function MicroHTML\TABLE;
use function MicroHTML\TBODY;
use function MicroHTML\TFOOT;
use function MicroHTML\TR;
use function MicroHTML\TH;
use function MicroHTML\TD;
use function MicroHTML\LABEL;
use function MicroHTML\INPUT;
use function MicroHTML\SMALL;
use function MicroHTML\A;
use function MicroHTML\BR;
use function MicroHTML\P;
use function MicroHTML\SELECT;
use function MicroHTML\OPTION;

class UserPageTheme extends Themelet
{
    public function display_login_page(Page $page): void
    {
        $page->set_title("Login");
        $this->display_navigation();
        $page->add_block(new Block(
            "Login There",
            emptyHTML("There should be a login box to the left")
        ));
    }

    /**
     * @param array<int, array{name: string|HTMLElement, link: Url}> $parts
     */
    public function display_user_links(Page $page, User $user, array $parts): void
    {
        # $page->add_block(new Block("User Links", join(", ", $parts), "main", 10));
    }

    /**
     * @param array<array{link: Url, name: string|HTMLElement}> $parts
     */
    public function display_user_block(Page $page, User $user, array $parts): void
    {
        $html = emptyHTML('Logged in as ', $user->name);
        foreach ($parts as $part) {
            $html->appendChild(BR());
            $html->appendChild(A(["href" => (string)$part["link"]], $part["name"]));
        }
        $b = new Block("User Links", $html, "left", 90);
        $b->is_content = false;
        $page->add_block($b);
    }

    public function display_signup_page(Page $page): void
    {
        global $config, $user;
        $tac = $config->get_string(UserAccountsConfig::LOGIN_TAC, "");

        if ($config->get_bool(UserAccountsConfig::LOGIN_TAC_BBCODE)) {
            $tac = format_text($tac);
        }

        $email_required = (
            $config->get_bool(UserAccountsConfig::USER_EMAIL_REQUIRED) &&
            !$user->can(UserAccountsPermission::CREATE_OTHER_USER)
        );

        $form = SHM_SIMPLE_FORM(
            make_link("user_admin/create"),
            TABLE(
                ["class" => "form"],
                TBODY(
                    TR(
                        TH("Name"),
                        TD(INPUT(["type" => 'text', "name" => 'name', "required" => true]))
                    ),
                    TR(
                        TH("Password"),
                        TD(INPUT(["type" => 'password', "name" => 'pass1', "required" => true]))
                    ),
                    TR(
                        TH(\MicroHTML\rawHTML("Repeat&nbsp;Password")),
                        TD(INPUT(["type" => 'password', "name" => 'pass2', "required" => true]))
                    ),
                    TR(
                        TH($email_required ? "Email" : \MicroHTML\rawHTML("Email&nbsp;(Optional)")),
                        TD(INPUT(["type" => 'email', "name" => 'email', "required" => $email_required]))
                    ),
                    TR(
                        TD(["colspan" => "2"], Captcha::get_html())
                    ),
                ),
                TFOOT(
                    TR(TD(["colspan" => "2"], INPUT(["type" => "submit", "value" => "Create Account"])))
                )
            )
        );

        $html = emptyHTML(
            $tac ? P($tac) : null,
            $form
        );

        $page->set_title("Create Account");
        $this->display_navigation();
        $page->add_block(new Block("Signup", $html));
    }

    public function display_user_creator(): void
    {
        global $page;

        $form = SHM_SIMPLE_FORM(
            make_link("user_admin/create_other"),
            TABLE(
                ["class" => "form"],
                TBODY(
                    TR(
                        TH("Name"),
                        TD(INPUT(["type" => 'text', "name" => 'name', "required" => true]))
                    ),
                    TR(
                        TH("Password"),
                        TD(INPUT(["type" => 'password', "name" => 'pass1', "required" => true]))
                    ),
                    TR(
                        TH(\MicroHTML\rawHTML("Repeat&nbsp;Password")),
                        TD(INPUT(["type" => 'password', "name" => 'pass2', "required" => true]))
                    ),
                    TR(
                        TH("Email"),
                        TD(INPUT(["type" => 'email', "name" => 'email']))
                    ),
                    TR(
                        TD(["colspan" => 2], "(Email is optional for admin-created accounts)"),
                    ),
                ),
                TFOOT(
                    TR(TD(["colspan" => "2"], INPUT(["type" => "submit", "value" => "Create Account"])))
                )
            )
        );
        $page->add_block(new Block("Create User", $form, "main", 75));
    }

    public function display_signups_disabled(Page $page): void
    {
        global $config;
        $page->set_title("Signups Disabled");
        $this->display_navigation();
        $page->add_block(new Block(
            "Signups Disabled",
            format_text($config->get_string(UserAccountsConfig::SIGNUP_DISABLED_MESSAGE)),
        ));
    }

    public function display_login_block(Page $page): void
    {
        $page->add_block(new Block("Login", $this->create_login_block(), "left", 90));
    }

    public function create_login_block(): HTMLElement
    {
        global $config, $user;
        $form = SHM_SIMPLE_FORM(
            make_link("user_admin/login"),
            TABLE(
                ["style" => "width: 100%", "class" => "form"],
                TBODY(
                    TR(
                        TH(LABEL(["for" => "user"], "Name")),
                        TD(INPUT(["id" => "user", "type" => "text", "name" => "user", "autocomplete" => "username", "required" => true]))
                    ),
                    TR(
                        TH(LABEL(["for" => "pass"], "Password")),
                        TD(INPUT(["id" => "pass", "type" => "password", "name" => "pass", "autocomplete" => "current-password", "required" => true]))
                    )
                ),
                TFOOT(
                    TR(TD(["colspan" => "2"], INPUT(["type" => "submit", "value" => "Log In"])))
                )
            )
        );

        $html = emptyHTML();
        $html->appendChild($form);
        if ($config->get_bool(UserAccountsConfig::SIGNUP_ENABLED) && $user->can(UserAccountsPermission::CREATE_USER)) {
            $html->appendChild(SMALL(A(["href" => make_link("user_admin/create")], "Create Account")));
        }

        return $html;
    }

    /**
     * @param array<string, int> $ips
     */
    protected function _ip_list(string $name, array $ips): HTMLElement
    {
        $td = TD("$name: ");
        $n = 0;
        foreach ($ips as $ip => $count) {
            $td->appendChild(BR());
            $td->appendChild("$ip ($count)");
            if (++$n >= 20) {
                $td->appendChild(BR());
                $td->appendChild("...");
                break;
            }
        }
        return $td;
    }

    /**
     * @param array<string, int> $uploads
     * @param array<string, int> $comments
     * @param array<string, int> $events
     */
    public function display_ip_list(Page $page, array $uploads, array $comments, array $events): void
    {
        $html = TABLE(
            ["id" => "ip-history"],
            TR(
                $this->_ip_list("Uploaded from", $uploads),
                $this->_ip_list("Commented from", $comments),
                $this->_ip_list("Logged Events", $events)
            ),
            TR(
                TD(["colspan" => "3"], "(Most recent at top)")
            )
        );

        $page->add_block(new Block("IPs", $html, "main", 70));
    }

    /**
     * @param array<HTMLElement|string> $stats
     */
    public function display_user_page(User $duser, array $stats): void
    {
        global $page;
        $stats[] = emptyHTML('User ID: '.$duser->id);

        $page->set_title("{$duser->name}'s Page");
        $this->display_navigation();
        $page->add_block(new Block("Stats", joinHTML(BR(), $stats), "main", 10));
    }


    public function build_operations(User $duser, UserOperationsBuildingEvent $event): HTMLElement
    {
        global $config, $user;
        $html = emptyHTML();

        // just a fool-admin protection so they dont mess around with anon users.
        if ($duser->id !== $config->get_int(UserAccountsConfig::ANON_ID)) {
            if ($user->can(UserAccountsPermission::EDIT_USER_NAME)) {
                $html->appendChild(SHM_USER_FORM(
                    $duser,
                    make_link("user_admin/change_name"),
                    "Change Name",
                    TBODY(TR(
                        TH("New name"),
                        TD(INPUT(["type" => 'text', "name" => 'name', "value" => $duser->name]))
                    )),
                    "Set"
                ));
            }

            $html->appendChild(SHM_USER_FORM(
                $duser,
                make_link("user_admin/change_pass"),
                "Change Password",
                TBODY(
                    TR(
                        TH("Password"),
                        TD(INPUT(["type" => 'password', "name" => 'pass1', "autocomplete" => 'new-password']))
                    ),
                    TR(
                        TH("Repeat password"),
                        TD(INPUT(["type" => 'password', "name" => 'pass2', "autocomplete" => 'new-password']))
                    ),
                ),
                "Set"
            ));

            $html->appendChild(SHM_USER_FORM(
                $duser,
                make_link("user_admin/change_email"),
                "Change Email",
                TBODY(TR(
                    TH("Address"),
                    TD(INPUT(["type" => 'text', "name" => 'address', "value" => $duser->email, "autocomplete" => 'email', "inputmode" => 'email']))
                )),
                "Set"
            ));

            if ($user->can(UserAccountsPermission::EDIT_USER_CLASS)) {
                $select = SELECT(["name" => "class"]);
                foreach (UserClass::$known_classes as $name => $values) {
                    $select->appendChild(
                        OPTION(["value" => $name, "selected" => $name == $duser->class->name], ucwords($name))
                    );
                }
                $html->appendChild(SHM_USER_FORM(
                    $duser,
                    make_link("user_admin/change_class"),
                    "Change Class",
                    TBODY(TR(TD($select))),
                    "Set"
                ));
            }

            if ($user->can(UserAccountsPermission::DELETE_USER)) {
                $html->appendChild(SHM_USER_FORM(
                    $duser,
                    make_link("user_admin/delete_user"),
                    "Delete User",
                    TBODY(
                        TR(TD(LABEL(INPUT(["type" => 'checkbox', "name" => 'with_images']), "Delete images"))),
                        TR(TD(LABEL(INPUT(["type" => 'checkbox', "name" => 'with_comments']), "Delete comments"))),
                    ),
                    TFOOT(
                        TR(TD(INPUT(["type" => 'button', "class" => 'shm-unlocker', "data-unlock-sel" => '.deluser', "value" => 'Unlock']))),
                        TR(TD(INPUT(["type" => 'submit', "class" => 'deluser', "value" => 'Delete User', "disabled" => 'true']))),
                    )
                ));
            }

            foreach ($event->get_parts() as $part) {
                $html->appendChild($part);
            }
        }
        return $html;
    }

    public function get_help_html(): HTMLElement
    {
        global $user;
        return emptyHTML(
            P("Search for posts posted by particular individuals."),
            SHM_COMMAND_EXAMPLE("poster=username", 'Returns posts posted by "username"'),
            // SHM_COMMAND_EXAMPLE("poster_id=123", 'Returns posts posted by user 123'),
            $user->can(IPBanPermission::VIEW_IP)
                ? SHM_COMMAND_EXAMPLE("poster_ip=127.0.0.1", "Returns posts posted from IP 127.0.0.1.")
                : null
        );
    }
}
