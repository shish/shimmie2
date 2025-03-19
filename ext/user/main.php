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

use function MicroHTML\{A, emptyHTML, P};

final class UserNameColumn extends TextColumn
{
    public function display(array $row): HTMLElement
    {
        return A(["href" => make_link("user/{$row[$this->name]}")], $row[$this->name]);
    }
}

final class UserActionColumn extends ActionColumn
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

final class UserTable extends Table
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

final class UserCreationException extends SCoreException
{
}

#[Type]
final class LoginResult
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
        try {
            $duser = User::by_name_and_pass($username, $password);
            return new LoginResult(
                $duser,
                $duser->get_session_id(),
                null
            );
        } catch (UserNotFound $ex) {
            return new LoginResult(
                User::by_id($config->get_int(UserAccountsConfig::ANON_ID, 0)),
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
                $uce->get_user(),
                $uce->get_user()->get_session_id(),
                null
            );
        } catch (UserCreationException $ex) {
            return new LoginResult(
                User::by_id($config->get_int(UserAccountsConfig::ANON_ID, 0)),
                null,
                $ex->getMessage()
            );
        }
    }
}

final class UserPage extends Extension
{
    public const KEY = "user";
    /** @var UserPageTheme $theme */
    public Themelet $theme;

    public function onUserLogin(UserLoginEvent $event): void
    {
        global $user;
        $user = $event->user;
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $database, $page, $user;

        $this->show_user_info();

        if ($event->page_matches("user_admin/login", method: "GET")) {
            $this->theme->display_login_page($page);
        }
        if ($event->page_matches("user_admin/login", method: "POST", authed: false)) {
            $this->page_login($event->req_POST('user'), $event->req_POST('pass'));
        }
        if ($event->page_matches("user_admin/recover", method: "POST")) {
            $this->page_recover($event->req_POST('username'));
        }
        if ($event->page_matches("user_admin/create", method: "GET", permission: UserAccountsPermission::CREATE_USER)) {
            global $config, $page, $user;
            if (!$config->get_bool(UserAccountsConfig::SIGNUP_ENABLED)) {
                $this->theme->display_signups_disabled($page);
                return;
            }
            $this->theme->display_signup_page($page);
        }
        if ($event->page_matches("user_admin/create", method: "POST", authed: false, permission: UserAccountsPermission::CREATE_USER)) {
            global $config, $page, $user;
            if (!$config->get_bool(UserAccountsConfig::SIGNUP_ENABLED)) {
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
                $uce->get_user()->set_login_cookie();
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("user"));
            } catch (UserCreationException $ex) {
                throw new InvalidInput($ex->getMessage());
            }
        }
        if ($event->page_matches("user_admin/create_other", method: "POST", permission: UserAccountsPermission::CREATE_OTHER_USER)) {
            send_event(
                new UserCreationEvent(
                    $event->req_POST("name"),
                    $event->req_POST("pass1"),
                    $event->req_POST("pass2"),
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
            if ($user->can(UserAccountsPermission::DELETE_USER)) {
                $col = new TextColumn("email", "Email");
                // $t->columns[] = $col;
                array_splice($t->columns, 2, 0, [$col]);
            }
            $page->set_title("Users");
            $this->theme->display_navigation();
            $page->add_block(new Block(null, emptyHTML($t->table($t->query()), $t->paginator())));
        }
        if ($event->page_matches("user_admin/logout", method: "GET")) {
            // FIXME: security
            $this->page_logout();
        }

        if ($event->page_matches("user_admin/change_name", method: "POST", permission: UserAccountsPermission::EDIT_USER_NAME)) {
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
                if ($input['pass1'] !== $input['pass2']) {
                    throw new InvalidInput("Passwords don't match");
                } else {
                    // FIXME: send_event()
                    $duser->set_password($input['pass1']);
                    if ($duser->id == $user->id) {
                        $duser->set_login_cookie();
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
        if ($event->page_matches("user_admin/delete_user", method: "POST", permission: UserAccountsPermission::DELETE_USER)) {
            $this->delete_user(
                $page,
                int_escape($event->req_POST('id')),
                $event->get_POST("with_images") == "on",
                $event->get_POST("with_comments") == "on"
            );
        }

        if ($event->page_matches("user/{name}")) {
            $display_user = User::by_name($event->get_arg('name'));
            if ($display_user->id == $config->get_int(UserAccountsConfig::ANON_ID)) {
                throw new UserNotFound("No such user");
            }
            $e = send_event(new UserPageBuildingEvent($display_user));
            $this->display_stats($e);
        } elseif ($event->page_matches("user")) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("user/" . $user->name));
        }
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        global $user, $config;

        $duser = $event->display_user;
        $class = $duser->class;

        $event->add_part(emptyHTML("Joined: ", SHM_DATE($duser->join_date)), 10);
        if ($user->name == $duser->name) {
            $event->add_part(emptyHTML("Current IP: " . Network::get_real_ip()), 80);
        }
        $event->add_part(emptyHTML("Class: {$class->name}"), 90);

        /** @var BuildAvatarEvent $avatar_e */
        $avatar_e = send_event(new BuildAvatarEvent($duser));
        $av = $avatar_e->html;
        if ($av) {
            $event->add_part($av, 0);
        } elseif ($duser->id == $user->id) {
            if (AvatarPostInfo::is_enabled() || AvatarGravatarInfo::is_enabled()) {
                $part = emptyHTML(P("No avatar?"));
                if (AvatarPostInfo::is_enabled()) {
                    $part->appendChild(P(
                        "You can set any post as avatar by clicking \"Set Image As Avatar\" in ",
                        "the Post Controls on any post, or by setting it manually in your ",
                        A(["href" => make_link("user_config")], "user config")
                    ));
                }
                if (AvatarGravatarInfo::is_enabled()) {
                    $part->appendChild(P(
                        "You can set a ",
                        A(["href" => "https://gravatar.com"], "Gravatar"),
                        " avatar by using the same email address here and there"
                    ));
                }
                $event->add_part($part, 0);
            }
        }
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        global $user;
        if ($user->is_anonymous()) {
            $event->add_nav_link(make_link('user_admin/login'), "Account", category: "user", order: 10);
        } else {
            $event->add_nav_link(make_link('user'), "Account", ["user"], "user", 10);
        }
    }

    private function display_stats(UserPageBuildingEvent $event): void
    {
        global $user, $page, $config;

        $this->theme->display_user_page($event->display_user, $event->get_parts());

        if (!$user->is_anonymous()) {
            if ($user->id == $event->display_user->id || $user->can("edit_user_info")) {
                $uobe = send_event(new UserOperationsBuildingEvent($event->display_user, $event->display_user->get_config()));
                $page->add_block(new Block("Operations", $this->theme->build_operations($event->display_user, $uobe), "main", 60));
            }
        }

        if ($user->id === $event->display_user->id) {
            $ubbe = send_event(new UserBlockBuildingEvent());
            $this->theme->display_user_links($page, $user, $ubbe->get_parts());
        }
        if (
            (
                $user->can(IPBanPermission::VIEW_IP) ||  # user can view all IPS
                ($user->id === $event->display_user->id)  # or user is viewing themselves
            ) &&
            ($event->display_user->id !== $config->get_int(UserAccountsConfig::ANON_ID)) # don't show anon's IP list, it is le huge
        ) {
            $this->theme->display_ip_list(
                $page,
                $this->count_upload_ips($event->display_user),
                $this->count_comment_ips($event->display_user),
                $this->count_log_ips($event->display_user)
            );
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "system") {
            if ($user->can(UserAccountsPermission::EDIT_USER_PASSWORD)) {
                $event->add_nav_link(make_link('user_admin/list'), "User List", ["user_admin"]);
            }
        }

        if ($event->parent === "user" && !$user->is_anonymous()) {
            $event->add_nav_link(make_link('user_admin/logout'), "Log Out", order: 90);
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        $event->add_link("My Profile", make_link("user"), 0);
        if ($user->can(UserAccountsPermission::EDIT_USER_PASSWORD)) {
            $event->add_link("User List", make_link("user_admin/list"), 87);
        }
        $event->add_link("Log Out", make_link("user_admin/logout"), 99);
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        global $user;
        if ($user->can(UserAccountsPermission::CREATE_OTHER_USER)) {
            $this->theme->display_user_creator();
        }
    }

    public function onUserCreation(UserCreationEvent $event): void
    {
        global $config, $database, $page, $user;

        $name = $event->username;
        //$pass = $event->password;
        //$email = $event->email;

        if (!$user->can(UserAccountsPermission::CREATE_USER)) {
            throw new UserCreationException("Account creation is currently disabled");
        }
        if (!$config->get_bool(UserAccountsConfig::SIGNUP_ENABLED) && !$user->can(UserAccountsPermission::CREATE_OTHER_USER)) {
            throw new UserCreationException("Account creation is currently disabled");
        }
        if (strlen($name) < 1) {
            throw new UserCreationException("Username must be at least 1 character");
        }
        if (!\Safe\preg_match('/^[a-zA-Z0-9-_]+$/', $name)) {
            throw new UserCreationException(
                "Username contains invalid characters. Allowed characters are " .
                "letters, numbers, dash, and underscore"
            );
        }
        try {
            User::by_name($name);
            throw new UserCreationException("That username is already taken");
        } catch (UserNotFound $ex) {
            // user not found is good
        }
        if (!Captcha::check()) {
            throw new UserCreationException("Error in captcha");
        }
        if ($event->password !== $event->password2) {
            throw new UserCreationException("Passwords don't match");
        }
        if (
            // Users who can create other users (ie, admins) are exempt
            // from the email requirement
            !$user->can(UserAccountsPermission::CREATE_OTHER_USER) &&
            ($config->get_bool(UserAccountsConfig::USER_EMAIL_REQUIRED) && empty($event->email))
        ) {
            throw new UserCreationException("Email address is required");
        }

        $email = (!empty($event->email)) ? $event->email : null;

        // if there are currently no admins, the new user should be one
        $need_admin = ($database->get_one("SELECT COUNT(*) FROM users WHERE class='admin'") == 0);
        $class = $need_admin ? 'admin' : 'user';

        $database->execute(
            "INSERT INTO users (name, pass, joindate, email, class) VALUES (:username, :hash, now(), :email, :class)",
            ["username" => $event->username, "hash" => '', "email" => $email, "class" => $class]
        );
        $new_user = User::by_name($event->username);
        $new_user->set_password($event->password);
        Log::info("user", "Created User @{$event->username}");

        if ($event->login) {
            send_event(new UserLoginEvent($new_user));
        }

        $event->set_user($new_user);
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
                \Safe\preg_match(self::USER_SEARCH_REGEX, $term) ||
                \Safe\preg_match(self::USER_ID_SEARCH_REGEX, $term)
            ) {
                return true;
            }
        }
        return false;
    }

    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        global $user;

        if ($matches = $event->matches(self::USER_SEARCH_REGEX)) {
            $duser = User::by_name($matches[2]);
            $event->add_querylet(new Querylet("images.owner_id {$matches[1]}= {$duser->id}"));
        } elseif ($matches = $event->matches(self::USER_ID_SEARCH_REGEX)) {
            $user_id = int_escape($matches[2]);
            $event->add_querylet(new Querylet("images.owner_id {$matches[1]}= $user_id"));
        } elseif ($user->can(IPBanPermission::VIEW_IP) && $matches = $event->matches("/^(?:poster|user)_ip[=|:]([0-9\.]+)$/i")) {
            $user_ip = $matches[1]; // FIXME: ip_escape?
            $event->add_querylet(new Querylet("images.owner_ip = '$user_ip'"));
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $event->add_section("Users", $this->theme->get_help_html());
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
        send_event(new UserLoginEvent($duser));
        $duser->set_login_cookie();
        $page->set_mode(PageMode::REDIRECT);

        if ($config->get_string(UserAccountsConfig::LOGIN_REDIRECT, "previous") === "previous") {
            $page->set_redirect(Url::referer_or(ignore: ["user/"]));
        } else {
            $page->set_redirect(make_link("user"));
        }
    }

    private function page_logout(): void
    {
        global $page, $config;
        $page->add_cookie("session", "", time() + 60 * 60 * 24 * $config->get_int(UserAccountsConfig::LOGIN_MEMORY), "/");
        if ($config->get_bool(UserAccountsConfig::PURGE_COOKIE)) {
            # to keep as few versions of content as possible,
            # make cookies all-or-nothing
            $page->add_cookie("user", "", time() + 60 * 60 * 24 * $config->get_int(UserAccountsConfig::LOGIN_MEMORY), "/");
        }
        Log::info("user", "Logged out");
        $page->set_mode(PageMode::REDIRECT);
        $page->set_redirect(make_link());
    }

    private function page_recover(string $username): void
    {
        $my_user = User::by_name($username);
        if (is_null($my_user->email)) {
            throw new InvalidInput("That user has no registered email address");
        } else {
            throw new ServerError("Email sending not implemented");
        }
    }

    private function user_can_edit_user(User $a, User $b): bool
    {
        if ($a->is_anonymous()) {
            throw new PermissionDenied("You aren't logged in");
        }

        if (
            ($a->name === $b->name) ||
            ($b->can(UserAccountsPermission::PROTECTED) && $a->class->name === "admin") ||
            (!$b->can(UserAccountsPermission::PROTECTED) && $a->can(UserAccountsPermission::EDIT_USER_INFO))
        ) {
            return true;
        } else {
            throw new PermissionDenied("You need to be an admin to change other people's details");
        }
    }

    private function redirect_to_user(User $duser): void
    {
        global $page, $user;

        if ($user->id === $duser->id) {
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
        if (!LogDatabaseInfo::is_enabled()) {
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

        $duser = User::by_id($uid);
        Log::warning("user", "Deleting user #{$uid} (@{$duser->name})");

        if ($with_images) {
            Log::warning("user", "Deleting user #{$uid} (@{$duser->name})'s uploads");
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
                ["new_owner_id" => $config->get_int(UserAccountsConfig::ANON_ID), "old_owner_id" => $uid]
            );
        }

        if ($with_comments) {
            Log::warning("user", "Deleting user #{$uid} (@{$duser->name})'s comments");
            $database->execute("DELETE FROM comments WHERE owner_id = :owner_id", ["owner_id" => $uid]);
        } else {
            $database->execute(
                "UPDATE comments SET owner_id = :new_owner_id WHERE owner_id = :old_owner_id",
                ["new_owner_id" => $config->get_int(UserAccountsConfig::ANON_ID), "old_owner_id" => $uid]
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
