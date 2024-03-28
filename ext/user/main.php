<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "events.php";

use GQLA\Field;
use GQLA\Type;
use GQLA\Mutation;

use MicroHTML\HTMLElement;
use MicroCRUD\ActionColumn;
use MicroCRUD\EnumColumn;
use MicroCRUD\IntegerColumn;
use MicroCRUD\TextColumn;
use MicroCRUD\DateColumn;
use MicroCRUD\Table;

use function MicroHTML\A;

class UserNameColumn extends TextColumn
{
    public function display(array $row): HTMLElement
    {
        return A(["href" => make_link("user/{$row[$this->name]}")], $row[$this->name]);
    }
}

class UserActionColumn extends ActionColumn
{
    public function __construct()
    {
        parent::__construct("id");
        $this->sortable = false;
    }

    public function display(array $row): HTMLElement
    {
        return A(["href" => search_link(["user={$row['name']}"])], "Posts");
    }
}

class UserTable extends Table
{
    public function __construct(\FFSPHP\PDO $db)
    {
        $classes = [];
        foreach (UserClass::$known_classes as $cls) {
            $classes[$cls->name] = $cls->name;
        }
        ksort($classes);
        parent::__construct($db);
        $this->table = "users";
        $this->base_query = "SELECT * FROM users";
        $this->size = 100;
        $this->limit = 1000000;
        $this->set_columns([
            new IntegerColumn("id", "ID"),
            new UserNameColumn("name", "Name"),
            new EnumColumn("class", "Class", $classes),
            // Added later, for admins only
            // new TextColumn("email", "Email"),
            new DateColumn("joindate", "Join Date"),
            new UserActionColumn(),
        ]);
        $this->order_by = ["id DESC"];
        $this->table_attrs = ["class" => "zebra form"];
    }
}

class UserCreationException extends SCoreException
{
}

#[Type]
class LoginResult
{
    public function __construct(
        #[Field]
        public User $user,
        #[Field]
        public ?string $session = null,
        #[Field]
        public ?string $error = null,
    ) {
    }

    #[Mutation]
    public static function login(string $username, string $password): LoginResult
    {
        global $config;
        $duser = User::by_name_and_pass($username, $password);
        if (!is_null($duser)) {
            return new LoginResult(
                $duser,
                UserPage::get_session_id($duser->name),
                null
            );
        } else {
            $anon = User::by_id($config->get_int("anon_id", 0));
            return new LoginResult(
                $anon,
                null,
                "No user found"
            );
        }
    }

    #[Mutation]
    public static function create_user(string $username, string $password1, string $password2, string $email): LoginResult
    {
        global $config;
        try {
            $uce = send_event(new UserCreationEvent($username, $password1, $password2, $email, true));
            return new LoginResult(
                User::by_name($username),
                UserPage::get_session_id($username),
                null
            );
        } catch (UserCreationException $ex) {
            return new LoginResult(
                User::by_id($config->get_int("anon_id", 0)),
                null,
                $ex->getMessage()
            );
        }
    }
}

class UserPage extends Extension
{
    /** @var UserPageTheme $theme */
    public Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_bool("login_signup_enabled", true);
        $config->set_default_int("login_memory", 365);
        $config->set_default_string("avatar_host", "none");
        $config->set_default_int("avatar_gravatar_size", 80);
        $config->set_default_string("avatar_gravatar_default", "");
        $config->set_default_string("avatar_gravatar_rating", "g");
        $config->set_default_bool("login_tac_bbcode", true);
        $config->set_default_bool("user_email_required", false);
    }

    public function onUserLogin(UserLoginEvent $event): void
    {
        global $user;
        $user = $event->user;
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $database, $page, $user;

        $this->show_user_info();

        if ($user->can(Permissions::VIEW_HELLBANNED)) {
            $page->add_html_header("<style>DIV.hb, TR.hb TD {border: 1px solid red !important;}</style>");
        } elseif (!$user->can(Permissions::HELLBANNED)) {
            $page->add_html_header("<style>.hb {display: none !important;}</style>");
        }

        if ($event->page_matches("user_admin/login", method: "GET")) {
            $this->theme->display_login_page($page);
        }
        if ($event->page_matches("user_admin/login", method: "POST", authed: false)) {
            $this->page_login($event->req_POST('user'), $event->req_POST('pass'));
        }
        if ($event->page_matches("user_admin/recover", method: "POST")) {
            $this->page_recover($event->req_POST('username'));
        }
        if ($event->page_matches("user_admin/create", method: "GET", permission: Permissions::CREATE_USER)) {
            global $config, $page, $user;
            if (!$config->get_bool("login_signup_enabled")) {
                $this->theme->display_signups_disabled($page);
                return;
            }
            $this->theme->display_signup_page($page);
        }
        if ($event->page_matches("user_admin/create", method: "POST", authed: false, permission: Permissions::CREATE_USER)) {
            global $config, $page, $user;
            if (!$config->get_bool("login_signup_enabled")) {
                $this->theme->display_signups_disabled($page);
                return;
            }
            try {
                $uce = send_event(
                    new UserCreationEvent(
                        $event->req_POST('name'),
                        $event->req_POST('pass1'),
                        $event->req_POST('pass2'),
                        $event->req_POST('email'),
                        true
                    )
                );
                $this->set_login_cookie($uce->username);
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("user"));
            } catch (UserCreationException $ex) {
                $this->theme->display_error(400, "User Creation Error", $ex->getMessage());
            }
        }
        if ($event->page_matches("user_admin/create_other", method: "POST", permission: Permissions::CREATE_OTHER_USER)) {
            send_event(
                new UserCreationEvent(
                    $event->req_POST("name"),
                    $event->req_POST("pass1"),
                    $event->req_POST("pass1"),
                    $event->req_POST("email"),
                    false
                )
            );
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("admin"));
            $page->flash("Created new user");
        }
        if ($event->page_matches("user_admin/list", method: "GET")) {
            $t = new UserTable($database->raw_db());
            $t->token = $user->get_auth_token();
            $t->inputs = $event->GET;
            if ($user->can(Permissions::DELETE_USER)) {
                $col = new TextColumn("email", "Email");
                // $t->columns[] = $col;
                array_splice($t->columns, 2, 0, [$col]);
            }
            $this->theme->display_crud("Users", $t->table($t->query()), $t->paginator());
        }
        if ($event->page_matches("user_admin/classes", method: "GET")) {
            $this->theme->display_user_classes(
                $page,
                UserClass::$known_classes,
                (new \ReflectionClass(Permissions::class))->getReflectionConstants()
            );
        }
        if ($event->page_matches("user_admin/logout", method: "GET")) {
            // FIXME: security
            $this->page_logout();
        }

        if ($event->page_matches("user_admin/change_name", method: "POST", permission: Permissions::EDIT_USER_NAME)) {
            $input = validate_input([
                'id' => 'user_id,exists',
                'name' => 'user_name',
            ]);
            $duser = User::by_id($input['id']);
            if ($this->user_can_edit_user($user, $duser)) {
                $duser->set_name($input['name']);
                $page->flash("Username changed");
                // TODO: set login cookie if user changed themselves
                $this->redirect_to_user($duser);
            }
        }
        if ($event->page_matches("user_admin/change_pass", method: "POST")) {
            $input = validate_input([
                'id' => 'user_id,exists',
                'pass1' => 'password',
                'pass2' => 'password',
            ]);
            $duser = User::by_id($input['id']);
            if ($this->user_can_edit_user($user, $duser)) {
                if ($input['pass1'] != $input['pass2']) {
                    throw new InvalidInput("Passwords don't match");
                } else {
                    // FIXME: send_event()
                    $duser->set_password($input['pass1']);
                    if ($duser->id == $user->id) {
                        $this->set_login_cookie($duser->name);
                    }
                    $page->flash("Password changed");
                    $this->redirect_to_user($duser);
                }
            }
        }
        if ($event->page_matches("user_admin/change_email", method: "POST")) {
            $input = validate_input([
                'id' => 'user_id,exists',
                'address' => 'email',
            ]);
            $duser = User::by_id($input['id']);
            if ($this->user_can_edit_user($user, $duser)) {
                $duser->set_email($input['address']);
                $page->flash("Email changed");
                $this->redirect_to_user($duser);
            }
        }
        if ($event->page_matches("user_admin/change_class", method: "POST")) {
            $input = validate_input([
                'id' => 'user_id,exists',
                'class' => 'user_class',
            ]);
            $duser = User::by_id($input['id']);
            // hard-coded that only admins can change people's classes
            if ($user->class->name == "admin") {
                $duser->set_class($input['class']);
                $page->flash("Class changed");
                $this->redirect_to_user($duser);
            }
        }
        if ($event->page_matches("user_admin/delete_user", method: "POST", permission: Permissions::DELETE_USER)) {
            $this->delete_user(
                $page,
                int_escape($event->req_POST('id')),
                $event->get_POST("with_images") == "on",
                $event->get_POST("with_comments") == "on"
            );
        }

        if ($event->page_matches("user/{name}")) {
            $display_user = User::by_name($event->get_arg('name'));
            if (!is_null($display_user) && ($display_user->id != $config->get_int("anon_id"))) {
                $e = send_event(new UserPageBuildingEvent($display_user));
                $this->display_stats($e);
            } else {
                $this->theme->display_error(
                    404,
                    "No Such User",
                    "If you typed the ID by hand, try again; if you came from a link on this " .
                    "site, it might be bug report time..."
                );
            }
        } elseif($event->page_matches("user")) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("user/" . $user->name));
        }
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        global $user, $config;

        $h_join_date = autodate($event->display_user->join_date);
        if ($event->display_user->can(Permissions::HELLBANNED)) {
            $h_class = $event->display_user->class->parent->name;
        } else {
            $h_class = $event->display_user->class->name;
        }

        $event->add_part("Joined: $h_join_date", 10);
        if ($user->name == $event->display_user->name) {
            $event->add_part("Current IP: " . get_real_ip(), 80);
        }
        $event->add_part("Class: $h_class", 90);

        $av = $event->display_user->get_avatar_html();
        if ($av) {
            $event->add_part($av, 0);
        } elseif (
            (
                $config->get_string("avatar_host") == "gravatar"
            ) &&
            ($user->id == $event->display_user->id)
        ) {
            $event->add_part(
                "No avatar? This gallery uses <a href='https://gravatar.com'>Gravatar</a> for avatar hosting, use the" .
                "<br>same email address here and there to have your avatar synced<br>",
                0
            );
        }
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        global $user;
        if ($user->is_anonymous()) {
            $event->add_nav_link("user", new Link('user_admin/login'), "Account", null, 10);
        } else {
            $event->add_nav_link("user", new Link('user'), "Account", null, 10);
        }
    }

    private function display_stats(UserPageBuildingEvent $event): void
    {
        global $user, $page, $config;

        $this->theme->display_user_page($event->display_user, $event->get_parts());

        if (!$user->is_anonymous()) {
            if ($user->id == $event->display_user->id || $user->can("edit_user_info")) {
                $user_config = UserConfig::get_for_user($event->display_user->id);

                $uobe = send_event(new UserOperationsBuildingEvent($event->display_user, $user_config));
                $page->add_block(new Block("Operations", $this->theme->build_operations($event->display_user, $uobe), "main", 60));
            }
        }

        if ($user->id == $event->display_user->id) {
            $ubbe = send_event(new UserBlockBuildingEvent());
            $this->theme->display_user_links($page, $user, $ubbe->get_parts());
        }
        if (
            ($user->can(Permissions::VIEW_IP) || ($user->is_logged_in() && $user->id == $event->display_user->id)) && # admin or self-user
            ($event->display_user->id != $config->get_int('anon_id')) # don't show anon's IP list, it is le huge
        ) {
            $this->theme->display_ip_list(
                $page,
                $this->count_upload_ips($event->display_user),
                $this->count_comment_ips($event->display_user),
                $this->count_log_ips($event->display_user)
            );
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        global $config;

        $hosts = [
            "None" => "none",
            "Gravatar" => "gravatar"
        ];

        $sb = $event->panel->create_new_block("User Options");
        $sb->start_table();
        $sb->add_bool_option(UserConfig::ENABLE_API_KEYS, "Enable user API keys", true);
        $sb->add_bool_option("login_signup_enabled", "Allow new signups", true);
        $sb->add_bool_option("user_email_required", "Require email address", true);
        $sb->add_longtext_option("login_tac", "Terms &amp; Conditions", true);
        $sb->add_choice_option(
            "user_loginshowprofile",
            [
                "Return to previous page" => 0, // 0 is default
                "Send to user profile" => 1,
            ],
            "On log in/out",
            true
        );
        $sb->add_choice_option("avatar_host", $hosts, "Avatars", true);

        if ($config->get_string("avatar_host") == "gravatar") {
            $sb->start_table_row();
            $sb->start_table_cell(2);
            $sb->add_label("<div style='text-align: center'><b>Gravatar Options</b></div>");
            $sb->end_table_cell();
            $sb->end_table_row();

            $sb->add_choice_option(
                "avatar_gravatar_type",
                [
                    'Default' => 'default',
                    'Wavatar' => 'wavatar',
                    'Monster ID' => 'monsterid',
                    'Identicon' => 'identicon'
                ],
                "Type",
                true
            );
            $sb->add_choice_option(
                "avatar_gravatar_rating",
                ['G' => 'g', 'PG' => 'pg', 'R' => 'r', 'X' => 'x'],
                "Rating",
                true
            );
        }
        $sb->end_table();
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "system") {
            if ($user->can(Permissions::EDIT_USER_PASSWORD)) {
                $event->add_nav_link("user_admin", new Link('user_admin/list'), "User List", NavLink::is_active(["user_admin"]));
            }
        }

        if ($event->parent === "user" && !$user->is_anonymous()) {
            $event->add_nav_link("logout", new Link('user_admin/logout'), "Log Out", false, 90);
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        $event->add_link("My Profile", make_link("user"));
        if ($user->can(Permissions::EDIT_USER_PASSWORD)) {
            $event->add_link("User List", make_link("user_admin/list"), 97);
        }
        if ($user->can(Permissions::EDIT_USER_CLASS)) {
            $event->add_link("User Classes", make_link("user_admin/classes"), 98);
        }
        $event->add_link("Log Out", make_link("user_admin/logout"), 99);
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::CREATE_OTHER_USER)) {
            $this->theme->display_user_creator();
        }
    }

    public function onUserCreation(UserCreationEvent $event): void
    {
        global $config, $page, $user;

        $name = $event->username;
        //$pass = $event->password;
        //$email = $event->email;

        if (!$user->can(Permissions::CREATE_USER)) {
            throw new UserCreationException("Account creation is currently disabled");
        }
        if (!$config->get_bool("login_signup_enabled") && !$user->can(Permissions::CREATE_OTHER_USER)) {
            throw new UserCreationException("Account creation is currently disabled");
        }
        if (strlen($name) < 1) {
            throw new UserCreationException("Username must be at least 1 character");
        }
        if (!preg_match('/^[a-zA-Z0-9-_]+$/', $name)) {
            throw new UserCreationException(
                "Username contains invalid characters. Allowed characters are " .
                "letters, numbers, dash, and underscore"
            );
        }
        if (User::by_name($name)) {
            throw new UserCreationException("That username is already taken");
        }
        if (!captcha_check()) {
            throw new UserCreationException("Error in captcha");
        }
        if ($event->password != $event->password2) {
            throw new UserCreationException("Passwords don't match");
        }
        if (
            // Users who can create other users (ie, admins) are exempt
            // from the email requirement
            !$user->can(Permissions::CREATE_OTHER_USER) &&
            ($config->get_bool("user_email_required") && empty($event->email))
        ) {
            throw new UserCreationException("Email address is required");
        }

        $new_user = $this->create_user($event);
        if ($event->login) {
            send_event(new UserLoginEvent($new_user));
        }
    }

    public const USER_SEARCH_REGEX = "/^(?:poster|user)(!?)[=|:](.*)$/i";
    public const USER_ID_SEARCH_REGEX = "/^(?:poster|user)_id(!?)[=|:]([0-9]+)$/i";

    /**
     * @param string[] $context
     */
    public static function has_user_query(array $context): bool
    {
        foreach ($context as $term) {
            if (
                preg_match(self::USER_SEARCH_REGEX, $term) ||
                preg_match(self::USER_ID_SEARCH_REGEX, $term)
            ) {
                return true;
            }
        }
        return false;
    }

    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        global $user;

        if (is_null($event->term)) {
            return;
        }

        $matches = [];
        if (preg_match(self::USER_SEARCH_REGEX, $event->term, $matches)) {
            $duser = User::by_name($matches[2]);
            if (is_null($duser)) {
                throw new SearchTermParseException(
                    "Can't find the user named " . html_escape($matches[2])
                );
            }
            $event->add_querylet(new Querylet("images.owner_id {$matches[1]}= {$duser->id}"));
        } elseif (preg_match(self::USER_ID_SEARCH_REGEX, $event->term, $matches)) {
            $user_id = int_escape($matches[2]);
            $event->add_querylet(new Querylet("images.owner_id {$matches[1]}= $user_id"));
        } elseif ($user->can(Permissions::VIEW_IP) && preg_match("/^(?:poster|user)_ip[=|:]([0-9\.]+)$/i", $event->term, $matches)) {
            $user_ip = $matches[1]; // FIXME: ip_escape?
            $event->add_querylet(new Querylet("images.owner_ip = '$user_ip'"));
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $block = new Block();
            $block->header = "Users";
            $block->body = (string) $this->theme->get_help_html();
            $event->add_block($block);
        }
    }

    private function show_user_info(): void
    {
        global $user, $page;
        // user info is shown on all pages
        if ($user->is_anonymous()) {
            $this->theme->display_login_block($page);
        } else {
            $ubbe = send_event(new UserBlockBuildingEvent());
            $this->theme->display_user_block($page, $user, $ubbe->get_parts());
        }
    }

    private function page_login(string $name, string $pass): void
    {
        global $config, $page;

        $duser = User::by_name_and_pass($name, $pass);
        if (!is_null($duser)) {
            send_event(new UserLoginEvent($duser));
            $this->set_login_cookie($duser->name);
            $page->set_mode(PageMode::REDIRECT);

            // Try returning to previous page
            if ($config->get_int("user_loginshowprofile", 0)) {
                $page->set_redirect(referer_or(make_link(), ["user/"]));
            } else {
                $page->set_redirect(make_link("user"));
            }
        } else {
            $this->theme->display_error(401, "Error", "No user with those details was found");
        }
    }

    private function page_logout(): void
    {
        global $page, $config;
        $page->add_cookie("session", "", time() + 60 * 60 * 24 * $config->get_int('login_memory'), "/");
        if (SPEED_HAX) {
            # to keep as few versions of content as possible,
            # make cookies all-or-nothing
            $page->add_cookie("user", "", time() + 60 * 60 * 24 * $config->get_int('login_memory'), "/");
        }
        log_info("user", "Logged out");
        $page->set_mode(PageMode::REDIRECT);

        // Try forwarding to same page on logout unless user comes from registration page
        if ($config->get_int("user_loginshowprofile", 0)) {
            $page->set_redirect(referer_or(make_link(), ["post/"]));
        } else {
            $page->set_redirect(make_link());
        }
    }

    private function page_recover(string $username): void
    {
        $my_user = User::by_name($username);
        if (is_null($my_user)) {
            $this->theme->display_error(404, "Error", "There's no user with that name");
        } elseif (is_null($my_user->email)) {
            $this->theme->display_error(400, "Error", "That user has no registered email address");
        } else {
            throw new ServerError("Email sending not implemented");
        }
    }

    private function create_user(UserCreationEvent $event): User
    {
        global $database;

        $email = (!empty($event->email)) ? $event->email : null;

        // if there are currently no admins, the new user should be one
        $need_admin = ($database->get_one("SELECT COUNT(*) FROM users WHERE class='admin'") == 0);
        $class = $need_admin ? 'admin' : 'user';

        $database->execute(
            "INSERT INTO users (name, pass, joindate, email, class) VALUES (:username, :hash, now(), :email, :class)",
            ["username" => $event->username, "hash" => '', "email" => $email, "class" => $class]
        );
        $uid = $database->get_last_insert_id('users_id_seq');
        $new_user = User::by_name($event->username);
        $new_user->set_password($event->password);

        log_info("user", "Created User #$uid ({$event->username})");

        return $new_user;
    }

    public static function get_session_id(string $name): string
    {
        global $config;
        $addr = get_session_ip($config);
        $hash = User::by_name($name)->passhash;
        return md5($hash . $addr);
    }

    private function set_login_cookie(string $name): void
    {
        global $config, $page;


        $page->add_cookie(
            "user",
            $name,
            time() + 60 * 60 * 24 * 365,
            '/'
        );
        $page->add_cookie(
            "session",
            $this->get_session_id($name),
            time() + 60 * 60 * 24 * $config->get_int('login_memory'),
            '/'
        );
    }

    private function user_can_edit_user(User $a, User $b): bool
    {
        if ($a->is_anonymous()) {
            $this->theme->display_error(401, "Error", "You aren't logged in");
            return false;
        }

        if (
            ($a->name == $b->name) ||
            ($b->can(Permissions::PROTECTED) && $a->class->name == "admin") ||
            (!$b->can(Permissions::PROTECTED) && $a->can(Permissions::EDIT_USER_INFO))
        ) {
            return true;
        } else {
            $this->theme->display_error(401, "Error", "You need to be an admin to change other people's details");
            return false;
        }
    }

    private function redirect_to_user(User $duser): void
    {
        global $page, $user;

        if ($user->id == $duser->id) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("user"));
        } else {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("user/{$duser->name}"));
        }
    }

    /**
     * @return array<string, int>
     */
    private function count_upload_ips(User $duser): array
    {
        global $database;
        return $database->get_pairs("
				SELECT
					owner_ip,
					COUNT(images.id) AS count
				FROM images
				WHERE owner_id=:id
				GROUP BY owner_ip
				ORDER BY max(posted) DESC", ["id" => $duser->id]);
    }

    /**
     * @return array<string, int>
     */
    private function count_comment_ips(User $duser): array
    {
        global $database;
        return $database->get_pairs("
				SELECT
					owner_ip,
					COUNT(comments.id) AS count
				FROM comments
				WHERE owner_id=:id
				GROUP BY owner_ip
				ORDER BY max(posted) DESC", ["id" => $duser->id]);
    }

    /**
     * @return array<string, int>
     */
    private function count_log_ips(User $duser): array
    {
        if (!Extension::is_enabled(LogDatabaseInfo::KEY)) {
            return [];
        }
        global $database;
        return $database->get_pairs("
				SELECT
					address,
					COUNT(id) AS count
				FROM score_log
				WHERE username=:username
				GROUP BY address
				ORDER BY MAX(date_sent) DESC", ["username" => $duser->name]);
    }

    private function delete_user(Page $page, int $uid, bool $with_images = false, bool $with_comments = false): void
    {
        global $user, $config, $database;

        $page->set_title("Error");
        $page->set_heading("Error");
        $page->add_block(new NavBlock());

        $duser = User::by_id($uid);
        log_warning("user", "Deleting user #{$uid} (@{$duser->name})");

        if ($with_images) {
            log_warning("user", "Deleting user #{$uid} (@{$duser->name})'s uploads");
            $image_ids = $database->get_col("SELECT id FROM images WHERE owner_id = :owner_id", ["owner_id" => $uid]);
            foreach ($image_ids as $image_id) {
                $image = Image::by_id((int) $image_id);
                if ($image) {
                    send_event(new ImageDeletionEvent($image));
                }
            }
        } else {
            $database->execute(
                "UPDATE images SET owner_id = :new_owner_id WHERE owner_id = :old_owner_id",
                ["new_owner_id" => $config->get_int('anon_id'), "old_owner_id" => $uid]
            );
        }

        if ($with_comments) {
            log_warning("user", "Deleting user #{$uid} (@{$duser->name})'s comments");
            $database->execute("DELETE FROM comments WHERE owner_id = :owner_id", ["owner_id" => $uid]);
        } else {
            $database->execute(
                "UPDATE comments SET owner_id = :new_owner_id WHERE owner_id = :old_owner_id",
                ["new_owner_id" => $config->get_int('anon_id'), "old_owner_id" => $uid]
            );
        }

        send_event(new UserDeletionEvent($uid));

        $database->execute(
            "DELETE FROM users WHERE id = :id",
            ["id" => $uid]
        );

        $page->set_mode(PageMode::REDIRECT);
        $page->set_redirect(make_link());
    }
}
