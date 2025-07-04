<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{INPUT};

final class PoolAddPostsEvent extends Event
{
    /**
     * @param int[] $posts
     */
    public function __construct(
        public int $pool_id,
        public array $posts
    ) {
        parent::__construct();
    }
}

final class PoolCreationEvent extends Event
{
    public User $user;
    public int $new_id = -1;

    public function __construct(
        public string $title,
        ?User $pool_user = null,
        public bool $public = false,
        public string $description = ""
    ) {
        parent::__construct();
        $this->user = $pool_user ?? Ctx::$user;
    }
}

final class PoolDeletionEvent extends Event
{
    public function __construct(
        public int $pool_id
    ) {
        parent::__construct();
    }
}

final class Pool
{
    public int $id;
    public int $user_id;
    public ?string $user_name;
    public bool $public;
    public string $title;
    public string $description;
    public string $date;
    public int $posts;

    /**
     * @param array{
     *     id: string|int,
     *     user_id: string|int,
     *     user_name: ?string,
     *     public: string|bool,
     *     title: string,
     *     description: string,
     *     date: string,
     *     posts: string|int,
     * } $row
     */
    public function __construct(array $row)
    {
        $this->id = (int) $row['id'];
        $this->user_id = (int) $row['user_id'];
        $this->user_name = $row['user_name'] ?? null;
        $this->public = bool_escape($row['public']);
        $this->title = $row['title'];
        $this->description = $row['description'];
        $this->date = $row['date'];
        $this->posts = (int) $row['posts'];
    }

    public static function get_pool_id_by_title(string $poolTitle): ?int
    {
        return Ctx::$database->get_one(
            "SELECT id FROM pools WHERE title=:title",
            ["title" => $poolTitle]
        );
    }
}

function _image_to_id(Image $image): int
{
    return $image->id;
}

/**
 * @phpstan-type PoolHistory array{id:int,pool_id:int,title:string,user_name:string,action:int,images:string,count:int,date:string}
 * @extends Extension<PoolsTheme>
 */
final class Pools extends Extension
{
    public const KEY = "pools";

    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["image_order"] = ImagePropType::INT;
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        $database = Ctx::$database;

        // Create the database tables
        if ($this->get_version() < 1) {
            $database->create_table("pools", "
					id SCORE_AIPK,
					user_id INTEGER NOT NULL,
					public BOOLEAN NOT NULL DEFAULT FALSE,
					title VARCHAR(255) NOT NULL UNIQUE,
					description TEXT,
					date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					lastupdated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					posts INTEGER NOT NULL DEFAULT 0,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
					");
            $database->create_table("pool_images", "
					pool_id INTEGER NOT NULL,
					image_id INTEGER NOT NULL,
					image_order INTEGER NOT NULL DEFAULT 0,
					FOREIGN KEY (pool_id) REFERENCES pools(id) ON UPDATE CASCADE ON DELETE CASCADE,
					FOREIGN KEY (image_id) REFERENCES images(id) ON UPDATE CASCADE ON DELETE CASCADE
					");
            $database->create_table("pool_history", "
					id SCORE_AIPK,
					pool_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					action INTEGER NOT NULL,
					images TEXT,
					count INTEGER NOT NULL DEFAULT 0,
					date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					FOREIGN KEY (pool_id) REFERENCES pools(id) ON UPDATE CASCADE ON DELETE CASCADE,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
					");
            $this->set_version(4);
        }

        if ($this->get_version() < 4) {
            $database->standardise_boolean("pools", "public");
            $this->set_version(4);
        }

        if ($this->get_version() < 5) {
            // earlier versions of the table-creation code added the lastupdated
            // column non-deterministically, so let's check if it is there and
            // add it if needed.
            $cols = $database->raw_db()->describe("pools");
            if (!array_key_exists("lastupdated", $cols)) {
                $database->execute("ALTER TABLE pools ADD COLUMN lastupdated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
            }
            $this->set_version(5);
        }
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link(make_link('pool/list'), "Pools", category: "pool");
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "pool") {
            $event->add_nav_link(make_link('pool/list'), "List");
            $event->add_nav_link(make_link('pool/new'), "Create");
            $event->add_nav_link(make_link('pool/updated'), "Changes");
            $event->add_nav_link(make_link('ext_doc/pools'), "Help");
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database;
        $user = Ctx::$user;
        $page = Ctx::$page;
        if (
            $event->page_matches("pool/list", paged: true)
            || $event->page_matches("pool/list/{search}", paged: true)
        ) { //index
            if ($event->GET->get('search')) {
                $page->set_redirect(make_link('pool/list/'. url_escape($event->GET->get('search')) . "/{$event->page_num}"));
                return;
            }
            $search = $event->get_arg('search', "");
            $page_num = $event->get_iarg('page_num', 1) - 1;
            $this->list_pools($page_num, $search);
        }
        if ($event->page_matches("pool/new", method: "GET", permission: PoolsPermission::CREATE)) {
            $this->theme->new_pool_composer();
        }
        if ($event->page_matches("pool/create", method: "POST", permission: PoolsPermission::CREATE)) {
            $pce = send_event(
                new PoolCreationEvent(
                    $event->POST->req("title"),
                    $user,
                    bool_escape($event->POST->req("public")),
                    $event->POST->req("description")
                )
            );
            $page->set_redirect(make_link("pool/view/" . $pce->new_id));
        }
        if ($event->page_matches("pool/view/{pool_id}", method: "GET", paged: true)) {
            $pool_id = $event->get_iarg('pool_id');
            $this->get_posts($event->get_iarg('page_num', 1) - 1, $pool_id);
        }
        if ($event->page_matches("pool/updated", paged: true)) {
            $this->get_history($event->get_iarg('page_num', 1) - 1);
        }
        if ($event->page_matches("pool/revert/{history_id}", method: "POST", permission: PoolsPermission::UPDATE)) {
            $history_id = $event->get_iarg('history_id');
            $this->revert_history($history_id);
            $page->set_redirect(make_link("pool/updated"));
        }
        if ($event->page_matches("pool/edit/{pool_id}")) {
            $pool_id = $event->get_iarg('pool_id');
            $pool = $this->get_single_pool($pool_id);
            self::assert_permission($user, $pool);

            $image_ids = $database->get_col("SELECT image_id FROM pool_images WHERE pool_id=:pid ORDER BY image_order ASC", ["pid" => $pool_id]);
            $images = array_filter(array_map(Image::by_id(...), $image_ids));
            $this->theme->edit_pool($pool, $images);
        }
        if ($event->page_matches("pool/order/{pool_id}")) {
            $pool_id = $event->get_iarg('pool_id');
            $pool = $this->get_single_pool($pool_id);
            self::assert_permission($user, $pool);

            $image_rows = $database->get_all(
                "SELECT images.*, pool_images.image_order
                FROM images
                JOIN pool_images ON images.id = pool_images.image_id
                WHERE pool_images.pool_id = :pid
                ORDER BY pool_images.image_order ASC",
                ["pid" => $pool_id]
            );
            $images = array_map(fn ($row) => new Image($row), $image_rows);
            $this->theme->edit_order($pool, $images);
        }
        if ($event->page_matches("pool/save_order/{pool_id}", method: "POST")) {
            $pool_id = $event->get_iarg('pool_id');
            $pool = $this->get_single_pool($pool_id);
            self::assert_permission($user, $pool);

            foreach ($event->POST->toArray() as $key => $value) {
                if (str_starts_with($key, "order_")) {
                    $imageID = (int) substr($key, 6);
                    $database->execute(
                        "
                            UPDATE pool_images
                            SET image_order = :ord
                            WHERE pool_id = :pid AND image_id = :iid",
                        ["ord" => $value, "pid" => $pool_id, "iid" => $imageID]
                    );
                }
            }
            $page->set_redirect(make_link("pool/view/" . $pool_id));
        }
        if ($event->page_matches("pool/reverse/{pool_id}", method: "POST")) {
            $pool_id = $event->get_iarg('pool_id');
            $pool = $this->get_single_pool($pool_id);
            self::assert_permission($user, $pool);

            $database->with_savepoint(function () use ($pool_id) {
                global $database;
                $result = $database->execute(
                    "SELECT image_id FROM pool_images WHERE pool_id=:pid ORDER BY image_order DESC",
                    ["pid" => $pool_id]
                );
                $image_order = 1;
                while ($row = $result->fetch()) {
                    $database->execute(
                        "
                                UPDATE pool_images
                                SET image_order=:ord
                                WHERE pool_id = :pid AND image_id = :iid",
                        ["ord" => $image_order, "pid" => $pool_id, "iid" => (int) $row['image_id']]
                    );
                    $image_order = $image_order + 1;
                }
            });
            $page->set_redirect(make_link("pool/view/" . $pool_id));
        }
        if ($event->page_matches("pool/import/{pool_id}")) {
            $pool_id = $event->get_iarg('pool_id');
            $pool = $this->get_single_pool($pool_id);
            self::assert_permission($user, $pool);

            $images = Search::find_images(
                limit: Ctx::$config->get(PoolsConfig::MAX_IMPORT_RESULTS),
                terms: SearchTerm::explode($event->POST->req("pool_tag"))
            );
            $this->theme->pool_result($images, $pool);
        }
        if ($event->page_matches("pool/add_post", method: "POST")) {
            $pool_id = int_escape($event->POST->req("pool_id"));
            $image_id = int_escape($event->POST->req("image_id"));
            $pool = $this->get_single_pool($pool_id);
            self::assert_permission($user, $pool);

            send_event(new PoolAddPostsEvent($pool_id, [$image_id]));
            $page->flash("Post added to {$pool->title}");
            $page->set_redirect(make_link("post/view/$image_id"));
        }
        if ($event->page_matches("pool/add_posts/{pool_id}")) {
            $pool_id = $event->get_iarg('pool_id');
            $pool = $this->get_single_pool($pool_id);
            self::assert_permission($user, $pool);

            $image_ids = array_map(intval(...), $event->POST->getAll('check'));
            send_event(new PoolAddPostsEvent($pool_id, $image_ids));
            $page->set_redirect(make_link("pool/view/" . $pool_id));
        }
        if ($event->page_matches("pool/remove_posts/{pool_id}")) {
            $pool_id = $event->get_iarg('pool_id');
            $pool = $this->get_single_pool($pool_id);
            self::assert_permission($user, $pool);

            $images = "";
            foreach ($event->POST->getAll('check') as $imageID) {
                $database->execute(
                    "DELETE FROM pool_images WHERE pool_id = :pid AND image_id = :iid",
                    ["pid" => $pool_id, "iid" => $imageID]
                );
                $images .= " " . $imageID;
            }
            $count = (int) $database->get_one(
                "SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid",
                ["pid" => $pool_id]
            );
            $this->add_history($pool_id, 0, $images, $count);
            $page->set_redirect(make_link("pool/view/" . $pool_id));
        }
        if ($event->page_matches("pool/edit_description/{pool_id}")) {
            $pool_id = $event->get_iarg('pool_id');
            $pool = $this->get_single_pool($pool_id);
            self::assert_permission($user, $pool);

            $database->execute(
                "UPDATE pools SET description=:dsc,lastupdated=CURRENT_TIMESTAMP WHERE id=:pid",
                ["dsc" => $event->POST->req('description'), "pid" => $pool_id]
            );
            $page->set_redirect(make_link("pool/view/" . $pool_id));
        }
        if ($event->page_matches("pool/nuke/{pool_id}")) {
            // Completely remove the given pool.
            //  -> Only admins and owners may do this
            $pool_id = $event->get_iarg('pool_id');
            $pool = $this->get_single_pool($pool_id);

            if ($user->can(PoolsPermission::ADMIN) || $user->id === $pool->user_id) {
                send_event(new PoolDeletionEvent($pool_id));
                $page->set_redirect(make_link("pool/list"));
            } else {
                throw new PermissionDenied("You do not have permission to access this page");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        $event->add_link("Pools", make_link("pool/list"));
    }

    /**
     * When displaying an image, optionally list all the pools that the
     * image is currently a member of on a side panel, as well as a link
     * to the Next image in the pool.
     */
    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        if (Ctx::$config->get(PoolsConfig::INFO_ON_VIEW_IMAGE)) {
            $imageID = $event->image->id;
            $poolsIDs = $this->get_pool_ids($imageID);

            $show_nav = Ctx::$config->get(PoolsConfig::SHOW_NAV_LINKS);

            $navInfo = [];
            foreach ($poolsIDs as $poolID) {
                $pool = $this->get_single_pool($poolID);

                $navInfo[$pool->id] = [
                    "info" => $pool,
                    "nav" => $show_nav ? $this->get_nav_posts($pool, $imageID) : null,
                ];
            }
            $this->theme->pool_info($navInfo);
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $database;
        if (Ctx::$config->get(PoolsConfig::ADDER_ON_VIEW_IMAGE) && Ctx::$user->can(PoolsPermission::UPDATE)) {
            $pools = [];
            if (Ctx::$user->can(PoolsPermission::ADMIN)) {
                $pools = $database->get_pairs("SELECT id,title FROM pools ORDER BY title");
            } else {
                $pools = $database->get_pairs("SELECT id,title FROM pools WHERE user_id=:id ORDER BY title", ["id" => Ctx::$user->id]);
            }
            if (count($pools) > 0) {
                $html = SHM_SIMPLE_FORM(
                    make_link("pool/add_post"),
                    SHM_SELECT("pool_id", $pools),
                    INPUT(["type" => "hidden", "name" => "image_id", "value" => $event->image->id]),
                    SHM_SUBMIT("Add Post to Pool")
                );
                $event->add_part($html);
            }
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $event->add_section("Pools", $this->theme->get_help_html());
        }
    }

    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if ($matches = $event->matches("/^pool[=|:]([0-9]+|any|none)$/i")) {
            $poolID = $matches[1];

            if (\Safe\preg_match("/^(any|none)$/", $poolID)) {
                $not = ($poolID === "none" ? "NOT" : "");
                $event->add_querylet(new Querylet("images.id $not IN (SELECT DISTINCT image_id FROM pool_images)"));
            } else {
                $event->add_querylet(new Querylet("images.id IN (SELECT DISTINCT image_id FROM pool_images WHERE pool_id = $poolID)"));
            }
        } elseif ($matches = $event->matches("/^pool_by_name[=|:](.*)$/i")) {
            $poolTitle = str_replace("_", " ", $matches[1]);

            $pool = $this->get_single_pool_from_title($poolTitle);
            $poolID = 0;
            if ($pool) {
                $poolID = $pool->id;
            }
            $event->add_querylet(new Querylet("images.id IN (SELECT DISTINCT image_id FROM pool_images WHERE pool_id = $poolID)"));
        } elseif ($matches = $event->matches("/^pool_id[=|:](.*)$/i")) {
            $poolID = str_replace("_", " ", $matches[1]);
            $event->add_querylet(new Querylet("images.id IN (SELECT DISTINCT image_id FROM pool_images WHERE pool_id = $poolID)"));
        }

    }

    public function onTagTermCheck(TagTermCheckEvent $event): void
    {
        if ($event->matches("/^pool[=|:]([^:]*|lastcreated):?([0-9]*)$/i")) {
            $event->metatag = true;
        }
    }

    public function onTagTermParse(TagTermParseEvent $event): void
    {
        if ($matches = $event->matches("/^pool[=|:]([^:]*|lastcreated):?([0-9]*)$/i")) {
            $poolTag = str_replace("_", " ", $matches[1]);

            $pool = null;
            if ($poolTag === 'lastcreated') {
                $pool = $this->get_last_userpool(Ctx::$user->id);
            } elseif (is_numeric($poolTag)) { //If only digits, assume PoolID
                $pool = $this->get_single_pool((int) $poolTag);
            } else { //assume PoolTitle
                $pool = $this->get_single_pool_from_title($poolTag);
            }

            if ($pool && $this->have_permission(Ctx::$user, $pool)) {
                $image_order = (int) ($matches[2] ?: 0);
                $this->add_post($pool->id, $event->image_id, true, $image_order);
            }
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        global $database;

        $options = $database->get_pairs("SELECT id,title FROM pools ORDER BY title");

        // TODO: Don't cast into strings, make BABBE accept HTMLElement instead.
        $event->add_action("pool-extend", "Add To (P)ool", "p", "", $this->theme->get_bulk_pool_selector($options), permission: PoolsPermission::UPDATE);
        $event->add_action("pool-create", "Create Pool", "", "", $this->theme->get_bulk_pool_input($event->search_terms), permission: PoolsPermission::UPDATE);
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        switch ($event->action) {
            case "pool-extend":
                $pool_id = intval($event->params['bulk_pool_select']);
                $pool = $this->get_single_pool($pool_id);

                if ($this->have_permission(Ctx::$user, $pool)) {
                    send_event(
                        new PoolAddPostsEvent($pool_id, iterator_map_to_array(_image_to_id(...), $event->items))
                    );
                }
                break;
            case "pool-create":
                $new_pool_title = $event->params->req('bulk_pool_new');
                $pce = send_event(new PoolCreationEvent($new_pool_title));
                send_event(new PoolAddPostsEvent($pce->new_id, iterator_map_to_array(_image_to_id(...), $event->items)));
                break;
        }
    }

    /* ------------------------------------------------- */
    /* --------------  Private Functions  -------------- */
    /* ------------------------------------------------- */

    /**
     * Check if the given user has permission to edit/change the pool.
     *
     * TODO: Should the user variable be global?
     */
    private function have_permission(User $user, Pool $pool): bool
    {
        // If the pool is public and user is logged
        // OR if the user is admin
        // OR if the pool is owned by the user.
        return (
            ($pool->public && $user->can(PoolsPermission::UPDATE)) ||
            $user->can(PoolsPermission::ADMIN) ||
            $user->id === $pool->user_id
        );
    }

    private function assert_permission(User $user, Pool $pool): void
    {
        if (!$this->have_permission($user, $pool)) {
            throw new PermissionDenied("You do not have permission to access this pool");
        }
    }

    private function list_pools(int $pageNumber, string $search): void
    {
        $poolsPerPage = Ctx::$config->get(PoolsConfig::LISTS_PER_PAGE);

        $order_by = "";
        $order = Ctx::$page->get_cookie("ui-order-pool");
        if ($order === "created" || is_null($order)) {
            $order_by = "ORDER BY p.date DESC";
        } elseif ($order === "updated") {
            $order_by = "ORDER BY p.lastupdated DESC";
        } elseif ($order === "name") {
            $order_by = "ORDER BY p.title ASC";
        } elseif ($order === "count") {
            $order_by = "ORDER BY p.posts DESC";
        }

        $where_clause = "WHERE LOWER(title) like '%" . strtolower($search) . "%'";

        // @phpstan-ignore-next-line
        $pools = array_map(fn ($row) => new Pool($row), Ctx::$database->get_all("
			SELECT p.*, u.name as user_name
			FROM pools AS p
			INNER JOIN users AS u
			ON p.user_id = u.id
			$where_clause
			$order_by
			LIMIT :l OFFSET :o
		", ["l" => $poolsPerPage, "o" => $pageNumber * $poolsPerPage]));
        // @phpstan-ignore-next-line
        $totalPages = (int) ceil((int) Ctx::$database->get_one("SELECT COUNT(*) FROM pools " . $where_clause) / $poolsPerPage);

        $this->theme->list_pools($pools, $search, $pageNumber + 1, $totalPages);
    }

    public function onPoolCreation(PoolCreationEvent $event): void
    {
        if (!Ctx::$user->can(PoolsPermission::UPDATE)) {
            throw new PermissionDenied("You must be registered and logged in to add a image.");
        }
        if (empty($event->title)) {
            throw new InvalidInput("Pool title is empty.");
        }
        if ($this->get_single_pool_from_title($event->title)) {
            throw new InvalidInput("A pool using this title already exists.");
        }

        Ctx::$database->execute(
            "
				INSERT INTO pools (user_id, public, title, description, date)
				VALUES (:uid, :public, :title, :desc, now())",
            ["uid" => $event->user->id, "public" => $event->public, "title" => $event->title, "desc" => $event->description]
        );

        $poolID = Ctx::$database->get_last_insert_id('pools_id_seq');
        Log::info("pools", "Pool {$poolID} created by " . Ctx::$user->name);

        $event->new_id = $poolID;
    }

    /**
     * Retrieve information about a pool given a pool ID.
     */
    private function get_single_pool(int $poolID): Pool
    {
        $row = Ctx::$database->get_row("SELECT * FROM pools WHERE id=:id", ["id" => $poolID]);
        // @phpstan-ignore-next-line
        return new Pool($row);
    }

    /**
     * Retrieve information about a pool given a pool title.
     */
    private function get_single_pool_from_title(string $poolTitle): ?Pool
    {
        $row = Ctx::$database->get_row("SELECT * FROM pools WHERE title=:title", ["title" => $poolTitle]);
        // @phpstan-ignore-next-line
        return $row ? new Pool($row) : null;
    }

    /**
     * Get all of the pool IDs that an image is in, given an image ID.
     * @return int[]
     */
    private function get_pool_ids(int $imageID): array
    {
        $col = Ctx::$database->get_col("SELECT pool_id FROM pool_images WHERE image_id=:iid", ["iid" => $imageID]);
        $col = array_map(intval(...), $col);
        return $col;
    }

    /**
     * Retrieve information about the last pool the given userID created
     */
    private function get_last_userpool(int $userID): Pool
    {
        $row = Ctx::$database->get_row("SELECT * FROM pools WHERE user_id=:uid ORDER BY id DESC", ["uid" => $userID]);
        // @phpstan-ignore-next-line
        return new Pool($row);
    }

    /**
     * HERE WE ADD CHECKED IMAGES FROM POOL AND UPDATE THE HISTORY
     */
    public function onPoolAddPosts(PoolAddPostsEvent $event): void
    {
        global $database;

        $pool = $this->get_single_pool($event->pool_id);
        self::assert_permission(Ctx::$user, $pool);

        $images = [];
        foreach ($event->posts as $post_id) {
            if ($this->add_post($event->pool_id, $post_id, false)) {
                $images[] = $post_id;
            }
        }

        if (count($images) > 0) {
            $count = (int) $database->get_one(
                "SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid",
                ["pid" => $event->pool_id]
            );
            $this->add_history($event->pool_id, 1, implode(" ", $images), $count);
        }
    }

    /**
     * Gets the previous and next successive images from a pool, given a pool ID and an image ID.
     *
     * @return array{prev:?int,next:?int} Array returning two elements (prev, next) in 1 dimension. Each returns ImageID or NULL if none.
     */
    private function get_nav_posts(Pool $pool, int $imageID): array
    {
        /** @var array{prev:?int,next:?int} $nav */
        $nav = Ctx::$database->get_row(
            "
                SELECT (
                    SELECT image_id
                    FROM pool_images
                    WHERE pool_id = :pid
                    AND image_order < (
                        SELECT image_order
                        FROM pool_images
                        WHERE pool_id = :pid
                        AND image_id = :iid
                        LIMIT 1
                    )
                    ORDER BY image_order DESC LIMIT 1
                ) AS prev,
                (
                    SELECT image_id
                    FROM pool_images
                    WHERE pool_id = :pid
                    AND image_order > (
                        SELECT image_order
                        FROM pool_images
                        WHERE pool_id = :pid
                        AND image_id = :iid
                        LIMIT 1
                    )
                    ORDER BY image_order ASC LIMIT 1
                ) AS next

                LIMIT 1
            ",
            ["pid" => $pool->id, "iid" => $imageID]
        );
        return $nav;
    }

    /**
     * Retrieve all the images in a pool, given a pool ID.
     */
    private function get_posts(int $pageNumber, int $poolID): void
    {
        $pool = $this->get_single_pool($poolID);
        $imagesPerPage = Ctx::$config->get(PoolsConfig::IMAGES_PER_PAGE);

        $query = "
            INNER JOIN images AS i ON i.id = p.image_id
            WHERE p.pool_id = :pid
        ";
        $params = [];

        // WE CHECK IF THE EXTENSION RATING IS INSTALLED, WHICH VERSION AND IF IT
        // WORKS TO SHOW/HIDE SAFE, QUESTIONABLE, EXPLICIT AND UNRATED IMAGES FROM USER
        if (RatingsInfo::is_enabled()) {
            $query .= "AND i.rating IN (" . Ratings::privs_to_sql(Ratings::get_user_class_privs(Ctx::$user)) . ")";
        }
        if (TrashInfo::is_enabled()) {
            $query .= " AND trash != :true";
            $params["true"] = true;
        }

        $result = Ctx::$database->get_all(
            // @phpstan-ignore-next-line
            "
					SELECT p.image_id FROM pool_images p
					$query
					ORDER BY p.image_order ASC
					LIMIT :l OFFSET :o",
            [
                "pid" => $poolID,
                "l" => $imagesPerPage,
                "o" => $pageNumber * $imagesPerPage,
            ] + $params
        );

        $totalPages = (int) ceil((int) Ctx::$database->get_one(
            // @phpstan-ignore-next-line
            "SELECT COUNT(*) FROM pool_images p $query",
            ["pid" => $poolID] + $params
        ) / $imagesPerPage);

        $images = [];
        foreach ($result as $singleResult) {
            $images[] = Image::by_id_ex((int) $singleResult["image_id"]);
        }

        $this->theme->view_pool($pool, $images, $pageNumber + 1, $totalPages);
    }

    /**
     * HERE WE NUKE ENTIRE POOL. WE REMOVE POOLS AND POSTS FROM REMOVED POOL AND HISTORIES ENTRIES FROM REMOVED POOL.
     */
    public function onPoolDeletion(PoolDeletionEvent $event): void
    {
        global $database;
        $poolID = $event->pool_id;

        $owner_id = (int) $database->get_one("SELECT user_id FROM pools WHERE id = :pid", ["pid" => $poolID]);
        if ($owner_id === Ctx::$user->id || Ctx::$user->can(PoolsPermission::ADMIN)) {
            $database->execute("DELETE FROM pool_history WHERE pool_id = :pid", ["pid" => $poolID]);
            $database->execute("DELETE FROM pool_images WHERE pool_id = :pid", ["pid" => $poolID]);
            $database->execute("DELETE FROM pools WHERE id = :pid", ["pid" => $poolID]);
        }
    }

    /**
     * HERE WE ADD A HISTORY ENTRY.
     *
     * $action Action=1 (one) MEANS ADDED, Action=0 (zero) MEANS REMOVED
     */
    private function add_history(int $poolID, int $action, string $images, int $count): void
    {
        Ctx::$database->execute(
            "
				INSERT INTO pool_history (pool_id, user_id, action, images, count, date)
				VALUES (:pid, :uid, :act, :img, :count, now())",
            ["pid" => $poolID, "uid" => Ctx::$user->id, "act" => $action, "img" => $images, "count" => $count]
        );
    }

    private function get_history(int $pageNumber): void
    {
        $historiesPerPage = Ctx::$config->get(PoolsConfig::UPDATED_PER_PAGE);

        /** @var PoolHistory[] $history */
        $history = Ctx::$database->get_all("
				SELECT h.id, h.pool_id, h.user_id, h.action, h.images,
				       h.count, h.date, u.name as user_name, p.title as title
				FROM pool_history AS h
				INNER JOIN pools AS p
				ON p.id = h.pool_id
				INNER JOIN users AS u
				ON h.user_id = u.id
				ORDER BY h.date DESC
				LIMIT :l OFFSET :o
				", ["l" => $historiesPerPage, "o" => $pageNumber * $historiesPerPage]);

        $totalPages = (int) ceil((int) Ctx::$database->get_one("SELECT COUNT(*) FROM pool_history") / $historiesPerPage);

        $this->theme->show_history($history, $pageNumber + 1, $totalPages);
    }

    /**
     * HERE GO BACK IN HISTORY AND ADD OR REMOVE POSTS TO POOL.
     */
    private function revert_history(int $historyID): void
    {
        global $database;
        $status = $database->get_all("SELECT * FROM pool_history WHERE id=:hid", ["hid" => $historyID]);

        foreach ($status as $entry) {
            $images = trim($entry['images']);
            $images = explode(" ", $images);
            $poolID = (int) $entry['pool_id'];
            $imageArray = "";
            $newAction = -1;

            if ($entry['action'] === 0) {
                // READ ENTRIES
                foreach ($images as $imageID) {
                    $this->add_post($poolID, int_escape($imageID));

                    $imageArray .= " " . $imageID;
                    $newAction = 1;
                }
            } elseif ($entry['action'] === 1) {
                // DELETE ENTRIES
                foreach ($images as $imageID) {
                    $database->execute(
                        "DELETE FROM pool_images WHERE pool_id = :pid AND image_id = :iid",
                        ["pid" => $poolID, "iid" => $imageID]
                    );
                    $imageArray .= " " . $imageID;
                    $newAction = 0;
                }
                $this->update_count($poolID);
            } else {
                // FIXME: should this throw an exception instead?
                Log::error("pools", "Invalid history action.");
                continue; // go on to the next one.
            }

            $count = (int) $database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", ["pid" => $poolID]);
            $this->add_history($poolID, $newAction, $imageArray, $count);
        }
    }

    /**
     * HERE WE ADD A SIMPLE POST FROM POOL.
     * USED WITH FOREACH IN revert_history() & onTagTermParse().
     */
    private function add_post(int $poolID, int $imageID, bool $history = false, int $imageOrder = 0): bool
    {
        global $database;

        $result = (int) $database->get_one(
            "SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid AND image_id=:iid",
            ["pid" => $poolID, "iid" => $imageID]
        );

        if ($result === 0) {
            if (Ctx::$config->get(PoolsConfig::AUTO_INCREMENT_ORDER) && $imageOrder === 0) {
                $imageOrder = (int) $database->get_one(
                    "
						SELECT COALESCE(MAX(image_order),0) + 1
						FROM pool_images
						WHERE pool_id = :pid AND image_order IS NOT NULL",
                    ["pid" => $poolID]
                );
            }

            $database->execute(
                "
					INSERT INTO pool_images (pool_id, image_id, image_order)
					VALUES (:pid, :iid, :ord)",
                ["pid" => $poolID, "iid" => $imageID, "ord" => $imageOrder]
            );
        } else {
            // If the post is already added, there is nothing else to do
            return false;
        }

        $this->update_count($poolID);

        if ($history) {
            $count = (int) $database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", ["pid" => $poolID]);
            $this->add_history($poolID, 1, (string) $imageID, $count);
        }
        return true;
    }

    private function update_count(int $pool_id): void
    {
        global $database;
        $database->execute(
            "UPDATE pools SET posts=(SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid),lastupdated=CURRENT_TIMESTAMP WHERE id=:pid",
            ["pid" => $pool_id]
        );
    }
}
