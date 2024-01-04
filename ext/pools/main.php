<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class PoolsConfig
{
    public const MAX_IMPORT_RESULTS = "poolsMaxImportResults";
    public const IMAGES_PER_PAGE = "poolsImagesPerPage";
    public const LISTS_PER_PAGE = "poolsListsPerPage";
    public const UPDATED_PER_PAGE = "poolsUpdatedPerPage";
    public const INFO_ON_VIEW_IMAGE = "poolsInfoOnViewImage";
    public const ADDER_ON_VIEW_IMAGE = "poolsAdderOnViewImage";
    public const SHOW_NAV_LINKS = "poolsShowNavLinks";
    public const AUTO_INCREMENT_ORDER = "poolsAutoIncrementOrder";
}

/**
 * This class is just a wrapper around SCoreException.
 */
class PoolCreationException extends SCoreException
{
}

class PoolAddPostsEvent extends Event
{
    public int $pool_id;
    public array $posts = [];

    public function __construct(int $pool_id, array $posts)
    {
        parent::__construct();
        $this->pool_id = $pool_id;
        $this->posts = $posts;
    }
}

class PoolCreationEvent extends Event
{
    public string $title;
    public User $user;
    public bool $public;
    public string $description;
    public int $new_id = -1;

    public function __construct(
        string $title,
        User $pool_user = null,
        bool $public = false,
        string $description = ""
    ) {
        parent::__construct();
        global $user;

        $this->title = $title;
        $this->user = $pool_user ?? $user;
        $this->public = $public;
        $this->description = $description;
    }
}

class PoolDeletionEvent extends Event
{
    public int $pool_id;

    public function __construct(int $pool_id)
    {
        parent::__construct();
        $this->pool_id = $pool_id;
    }
}

class Pool
{
    public int $id;
    public int $user_id;
    public ?string $user_name;
    public bool $public;
    public string $title;
    public string $description;
    public string $date;
    public int $posts;

    public function __construct(array $row)
    {
        $this->id = (int)$row['id'];
        $this->user_id = (int)$row['user_id'];
        $this->user_name = $row['user_name'] ?? null;
        $this->public = bool_escape($row['public']);
        $this->title = $row['title'];
        $this->description = $row['description'];
        $this->date = $row['date'];
        $this->posts = (int)$row['posts'];
    }

    public static function makePool(array $row): Pool
    {
        return new Pool($row);
    }

    public static function get_pool_id_by_title($poolTitle): ?int
    {
        global $database;
        $row = $database->get_row("SELECT * FROM pools WHERE title=:title", ["title" => $poolTitle]);
        if ($row != null) {
            return $row['id'];
        } else {
            return null;
        }
    }
}

function _image_to_id(Image $image): int
{
    return $image->id;
}

class Pools extends Extension
{
    /** @var PoolsTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event)
    {
        global $config;

        // Set the defaults for the pools extension
        $config->set_default_int(PoolsConfig::MAX_IMPORT_RESULTS, 1000);
        $config->set_default_int(PoolsConfig::IMAGES_PER_PAGE, 20);
        $config->set_default_int(PoolsConfig::LISTS_PER_PAGE, 20);
        $config->set_default_int(PoolsConfig::UPDATED_PER_PAGE, 20);
        $config->set_default_bool(PoolsConfig::INFO_ON_VIEW_IMAGE, false);
        $config->set_default_bool(PoolsConfig::ADDER_ON_VIEW_IMAGE, false);
        $config->set_default_bool(PoolsConfig::SHOW_NAV_LINKS, false);
        $config->set_default_bool(PoolsConfig::AUTO_INCREMENT_ORDER, false);
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $database;

        // Create the database tables
        if ($this->get_version("ext_pools_version") < 1) {
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
            $this->set_version("ext_pools_version", 4);

            log_info("pools", "extension installed");
        }

        if ($this->get_version("ext_pools_version") < 4) {
            $database->standardise_boolean("pools", "public");
            $this->set_version("ext_pools_version", 4);
        }
    }

    // Add a block to the Board Config / Setup
    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Pools");
        $sb->add_int_option(PoolsConfig::MAX_IMPORT_RESULTS, "Max results on import: ");
        $sb->add_int_option(PoolsConfig::IMAGES_PER_PAGE, "<br>Posts per page: ");
        $sb->add_int_option(PoolsConfig::LISTS_PER_PAGE, "<br>Index list items per page: ");
        $sb->add_int_option(PoolsConfig::UPDATED_PER_PAGE, "<br>Updated list items per page: ");
        $sb->add_bool_option(PoolsConfig::INFO_ON_VIEW_IMAGE, "<br>Show pool info on image: ");
        $sb->add_bool_option(PoolsConfig::SHOW_NAV_LINKS, "<br>Show 'Prev' & 'Next' links when viewing pool images: ");
        $sb->add_bool_option(PoolsConfig::AUTO_INCREMENT_ORDER, "<br>Autoincrement order when post is added to pool:");
        //$sb->add_bool_option(PoolsConfig::ADDER_ON_VIEW_IMAGE, "<br>Show pool adder on image: ");
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event)
    {
        $event->add_nav_link("pool", new Link('pool/list'), "Pools");
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        if ($event->parent == "pool") {
            $event->add_nav_link("pool_list", new Link('pool/list'), "List");
            $event->add_nav_link("pool_new", new Link('pool/new'), "Create");
            $event->add_nav_link("pool_updated", new Link('pool/updated'), "Changes");
            $event->add_nav_link("pool_help", new Link('ext_doc/pools'), "Help");
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $config, $database, $page, $user;
        if ($event->page_matches("pool/list")) { //index
            if (isset($_GET['search']) and $_GET['search'] != null) {
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link('pool/list').'/'.$_GET['search'].'/'.strval($event->try_page_num(1)));
                return;
            }
            if (count($event->args) >= 4) { // Assume first 2 args are search and page num
                $search = $event->get_arg(0); // Search is based on name comparison instead of tag search
                $page_num = $event->try_page_num(1);
            } else {
                $search = "";
                $page_num = $event->try_page_num(0);
            }
            $this->list_pools($page, $page_num, $search);
        } elseif ($event->page_matches("pool")) {
            $pool_id = 0;
            $pool = [];

            // Check if we have pool id, since this is most often the case.
            if (isset($_POST["pool_id"])) {
                $pool_id = int_escape($_POST["pool_id"]);
                $pool = $this->get_single_pool($pool_id);
            }

            // What action are we trying to perform?
            switch ($event->get_arg(0)) {
                case "new": // Show form for new pools
                    if (!$user->is_anonymous()) {
                        $this->theme->new_pool_composer($page);
                    } else {
                        $errMessage = "You must be registered and logged in to create a new pool.";
                        $this->theme->display_error(401, "Error", $errMessage);
                    }
                    break;

                case "create": // ADD _POST
                    try {
                        $title = $_POST["title"];
                        $event = send_event(new PoolCreationEvent(
                            $title,
                            $user,
                            bool_escape($_POST["public"]),
                            $_POST["description"]
                        ));
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("pool/view/" . $event->new_id));
                    } catch (PoolCreationException $e) {
                        $this->theme->display_error(400, "Error", $e->error);
                    }
                    break;

                case "view":
                    $poolID = int_escape($event->get_arg(1));
                    $this->get_posts($event, $poolID);
                    break;

                case "updated":
                    $this->get_history($event->try_page_num(1));
                    break;

                case "revert":
                    if (!$user->is_anonymous()) {
                        $historyID = int_escape($event->get_arg(1));
                        $this->revert_history($historyID);
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("pool/updated"));
                    }
                    break;

                case "edit": // Edit the pool (remove images)
                    if ($this->have_permission($user, $pool)) {
                        $result = $database->execute("SELECT image_id FROM pool_images WHERE pool_id=:pid ORDER BY image_order ASC", ["pid" => $pool_id]);
                        $images = [];
                        while ($row = $result->fetch()) {
                            $images[] = Image::by_id((int)$row["image_id"]);
                        }
                        $this->theme->edit_pool($page, $pool, $images);
                    } else {
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("pool/view/" . $pool_id));
                    }
                    break;

                case "order": // Order the pool (view and change the order of images within the pool)
                    if (isset($_POST["order_view"])) {
                        if ($this->have_permission($user, $pool)) {
                            $result = $database->execute(
                                "SELECT image_id FROM pool_images WHERE pool_id=:pid ORDER BY image_order ASC",
                                ["pid" => $pool_id]
                            );
                            $images = [];

                            while ($row = $result->fetch()) {
                                $image = $database->get_row(
                                    "
                                        SELECT * FROM images AS i
                                        INNER JOIN pool_images AS p ON i.id = p.image_id
                                        WHERE pool_id=:pid AND i.id=:iid",
                                    ["pid" => $pool_id, "iid" => (int)$row['image_id']]
                                );
                                $images[] = ($image ? new Image($image) : null);
                            }

                            $this->theme->edit_order($page, $pool, $images);
                        } else {
                            $page->set_mode(PageMode::REDIRECT);
                            $page->set_redirect(make_link("pool/view/" . $pool_id));
                        }
                    } else {
                        if ($this->have_permission($user, $pool)) {
                            foreach ($_POST['imgs'] as $data) {
                                list($imageORDER, $imageID) = $data;
                                $database->execute(
                                    "
                                    UPDATE pool_images
                                    SET image_order = :ord
                                    WHERE pool_id = :pid AND image_id = :iid",
                                    ["ord" => $imageORDER, "pid" => int_escape($_POST['pool_id']), "iid" => $imageID]
                                );
                            }
                            $page->set_mode(PageMode::REDIRECT);
                            $page->set_redirect(make_link("pool/view/" . $pool_id));
                        } else {
                            $this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
                        }
                    }
                    break;
                case "reverse":
                    if ($this->have_permission($user, $pool)) {
                        $result = $database->execute(
                            "SELECT image_id FROM pool_images WHERE pool_id=:pid ORDER BY image_order DESC",
                            ["pid" => $pool_id]
                        );
                        $image_order = 1;
                        try {
                            $database->begin_transaction();
                            while ($row = $result->fetch()) {
                                $database->execute(
                                    "
										UPDATE pool_images 
										SET image_order=:ord 
										WHERE pool_id = :pid AND image_id = :iid",
                                    ["ord" => $image_order, "pid" => $pool_id, "iid" => (int)$row['image_id']]
                                );
                                $image_order = $image_order + 1;
                            }
                            $database->commit();
                        } catch (\Exception $e) {
                            $database->rollback();
                        }
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("pool/view/" . $pool_id));
                    } else {
                        $this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
                    }
                    break;
                case "import":
                    if ($this->have_permission($user, $pool)) {
                        $images = Search::find_images(
                            limit: $config->get_int(PoolsConfig::MAX_IMPORT_RESULTS, 1000),
                            tags: Tag::explode($_POST["pool_tag"])
                        );
                        $this->theme->pool_result($page, $images, $pool);
                    } else {
                        $this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
                    }
                    break;

                case "add_posts":
                    if ($this->have_permission($user, $pool)) {
                        $image_ids = array_map('intval', $_POST['check']);
                        send_event(new PoolAddPostsEvent($pool_id, $image_ids));
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("pool/view/" . $pool_id));
                    } else {
                        $this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
                    }
                    break;

                case "remove_posts":
                    if ($this->have_permission($user, $pool)) {
                        $images = "";
                        foreach ($_POST['check'] as $imageID) {
                            $database->execute(
                                "DELETE FROM pool_images WHERE pool_id = :pid AND image_id = :iid",
                                ["pid" => $pool_id, "iid" => $imageID]
                            );
                            $images .= " " . $imageID;
                        }
                        $count = (int)$database->get_one(
                            "SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid",
                            ["pid" => $pool_id]
                        );
                        $this->add_history($pool_id, 0, $images, $count);
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("pool/view/" . $pool_id));
                    } else {
                        $this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
                    }

                    break;

                case "edit_description":
                    if ($this->have_permission($user, $pool)) {
                        $database->execute(
                            "UPDATE pools SET description=:dsc,lastupdated=CURRENT_TIMESTAMP WHERE id=:pid",
                            ["dsc" => $_POST['description'], "pid" => $pool_id]
                        );
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("pool/view/" . $pool_id));
                    } else {
                        $this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
                    }

                    break;

                case "nuke":
                    // Completely remove the given pool.
                    //  -> Only admins and owners may do this
                    if ($user->can(Permissions::POOLS_ADMIN) || $user->id == $pool->user_id) {
                        send_event(new PoolDeletionEvent($pool_id));
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("pool/list"));
                    } else {
                        $this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
                    }
                    break;
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        $event->add_link("Pools", make_link("pool/list"));
    }

    /**
     * When displaying an image, optionally list all the pools that the
     * image is currently a member of on a side panel, as well as a link
     * to the Next image in the pool.
     */
    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        global $config;

        if ($config->get_bool(PoolsConfig::INFO_ON_VIEW_IMAGE)) {
            $imageID = $event->image->id;
            $poolsIDs = $this->get_pool_ids($imageID);

            $show_nav = $config->get_bool(PoolsConfig::SHOW_NAV_LINKS, false);

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

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        global $config, $database, $user;
        if ($config->get_bool(PoolsConfig::ADDER_ON_VIEW_IMAGE) && !$user->is_anonymous()) {
            $pools = [];
            if ($user->can(Permissions::POOLS_ADMIN)) {
                $pools = $database->get_pairs("SELECT id,title FROM pools ORDER BY title");
            } else {
                $pools = $database->get_pairs("SELECT id,title FROM pools WHERE user_id=:id ORDER BY title", ["id" => $user->id]);
            }
            if (count($pools) > 0) {
                $event->add_part((string)$this->theme->get_adder_html($event->image, $pools));
            }
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event)
    {
        if ($event->key === HelpPages::SEARCH) {
            $event->add_block(new Block("Pools", $this->theme->get_help_html()));
        }
    }

    public function onSearchTermParse(SearchTermParseEvent $event)
    {
        if (is_null($event->term)) {
            return;
        }

        $matches = [];
        if (preg_match("/^pool[=|:]([0-9]+|any|none)$/i", $event->term, $matches)) {
            $poolID = $matches[1];

            if (preg_match("/^(any|none)$/", $poolID)) {
                $not = ($poolID == "none" ? "NOT" : "");
                $event->add_querylet(new Querylet("images.id $not IN (SELECT DISTINCT image_id FROM pool_images)"));
            } else {
                $event->add_querylet(new Querylet("images.id IN (SELECT DISTINCT image_id FROM pool_images WHERE pool_id = $poolID)"));
            }
        } elseif (preg_match("/^pool_by_name[=|:](.*)$/i", $event->term, $matches)) {
            $poolTitle = str_replace("_", " ", $matches[1]);

            $pool = $this->get_single_pool_from_title($poolTitle);
            $poolID = 0;
            if ($pool) {
                $poolID = $pool->id;
            }
            $event->add_querylet(new Querylet("images.id IN (SELECT DISTINCT image_id FROM pool_images WHERE pool_id = $poolID)"));
        } elseif (preg_match("/^pool_id[=|:](.*)$/i", $event->term, $matches)) {
            $poolID = str_replace("_", " ", $matches[1]);
            $event->add_querylet(new Querylet("images.id IN (SELECT DISTINCT image_id FROM pool_images WHERE pool_id = $poolID)"));
        }

    }

    public function onTagTermCheck(TagTermCheckEvent $event)
    {
        if (preg_match("/^pool[=|:]([^:]*|lastcreated):?([0-9]*)$/i", $event->term)) {
            $event->metatag = true;
        }
    }

    public function onTagTermParse(TagTermParseEvent $event)
    {
        $matches = [];
        if (preg_match("/^pool[=|:]([^:]*|lastcreated):?([0-9]*)$/i", $event->term, $matches)) {
            global $user;
            $poolTag = (string)str_replace("_", " ", $matches[1]);

            $pool = null;
            if ($poolTag == 'lastcreated') {
                $pool = $this->get_last_userpool($user->id);
            } elseif (ctype_digit($poolTag)) { //If only digits, assume PoolID
                $pool = $this->get_single_pool((int)$poolTag);
            } else { //assume PoolTitle
                $pool = $this->get_single_pool_from_title($poolTag);
            }

            if ($pool && $this->have_permission($user, $pool)) {
                $image_order = ($matches[2] ?: 0);
                $this->add_post($pool->id, $event->image_id, true, $image_order);
            }
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event)
    {
        global $database;

        $options = $database->get_pairs("SELECT id,title FROM pools ORDER BY title");

        // TODO: Don't cast into strings, make BABBE accept HTMLElement instead.
        $event->add_action("bulk_pool_add_existing", "Add To (P)ool", "p", "", (string)$this->theme->get_bulk_pool_selector($options));
        $event->add_action("bulk_pool_add_new", "Create Pool", "", "", (string)$this->theme->get_bulk_pool_input($event->search_terms));
    }

    public function onBulkAction(BulkActionEvent $event)
    {
        global $user;

        switch ($event->action) {
            case "bulk_pool_add_existing":
                if (!isset($_POST['bulk_pool_select'])) {
                    return;
                }
                $pool_id = intval($_POST['bulk_pool_select']);
                $pool = $this->get_single_pool($pool_id);

                if ($this->have_permission($user, $pool)) {
                    send_event(
                        new PoolAddPostsEvent($pool_id, iterator_map_to_array("Shimmie2\_image_to_id", $event->items))
                    );
                }
                break;
            case "bulk_pool_add_new":
                if (!isset($_POST['bulk_pool_new'])) {
                    return;
                }
                $new_pool_title = $_POST['bulk_pool_new'];
                $pce = send_event(new PoolCreationEvent($new_pool_title));
                send_event(new PoolAddPostsEvent($pce->new_id, iterator_map_to_array("Shimmie2\_image_to_id", $event->items)));
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
            ($pool->public && !$user->is_anonymous()) ||
            $user->can(Permissions::POOLS_ADMIN) ||
            $user->id == $pool->user_id
        );
    }

    private function list_pools(Page $page, int $pageNumber, $search)
    {
        global $config, $database;

        $poolsPerPage = $config->get_int(PoolsConfig::LISTS_PER_PAGE);

        $order_by = "";
        $order = $page->get_cookie("ui-order-pool");
        if ($order == "created" || is_null($order)) {
            $order_by = "ORDER BY p.date DESC";
        } elseif ($order == "updated") {
            $order_by = "ORDER BY p.lastupdated DESC";
        } elseif ($order == "name") {
            $order_by = "ORDER BY p.title ASC";
        } elseif ($order == "count") {
            $order_by = "ORDER BY p.posts DESC";
        }

        $where_clause = "WHERE LOWER(title) like '%%'";
        if ($search != null) {
            $where_clause = "WHERE LOWER(title) like '%".strtolower($search)."%'";
        }

        $pools = array_map([Pool::class, "makePool"], $database->get_all("
			SELECT p.*, u.name as user_name
			FROM pools AS p
			INNER JOIN users AS u
			ON p.user_id = u.id
			$where_clause
			$order_by
			LIMIT :l OFFSET :o
		", ["l" => $poolsPerPage, "o" => $pageNumber * $poolsPerPage]));
        $totalPages = (int)ceil((int)$database->get_one("SELECT COUNT(*) FROM pools ".$where_clause) / $poolsPerPage);

        $this->theme->list_pools($page, $pools, $search, $pageNumber + 1, $totalPages);
    }

    public function onPoolCreation(PoolCreationEvent $event)
    {
        global $user, $database;

        if ($user->is_anonymous()) {
            throw new PoolCreationException("You must be registered and logged in to add a image.");
        }
        if (empty($event->title)) {
            throw new PoolCreationException("Pool title is empty.");
        }
        if ($this->get_single_pool_from_title($event->title)) {
            throw new PoolCreationException("A pool using this title already exists.");
        }

        $poolID = $database->get_one(
            "
				INSERT INTO pools (user_id, public, title, description, date)
				VALUES (:uid, :public, :title, :desc, now())
                RETURNING id
            ",
            ["uid" => $event->user->id, "public" => $event->public, "title" => $event->title, "desc" => $event->description]
        );
        log_info("pools", "Pool {$poolID} created by {$user->name}");

        $event->new_id = $poolID;
    }

    /**
     * Retrieve information about a pool given a pool ID.
     */
    private function get_single_pool(int $poolID): Pool
    {
        global $database;
        return new Pool($database->get_row("SELECT * FROM pools WHERE id=:id", ["id" => $poolID]));
    }

    /**
     * Retrieve information about a pool given a pool title.
     */
    private function get_single_pool_from_title(string $poolTitle): ?Pool
    {
        global $database;
        $row = $database->get_row("SELECT * FROM pools WHERE title=:title", ["title" => $poolTitle]);
        return $row ? new Pool($row) : null;
    }

    /**
     * Get all of the pool IDs that an image is in, given an image ID.
     * #return int[]
     */
    private function get_pool_ids(int $imageID): array
    {
        global $database;
        $col = $database->get_col("SELECT pool_id FROM pool_images WHERE image_id=:iid", ["iid" => $imageID]);
        $col = array_map('intval', $col);
        return $col;
    }

    /**
     * Retrieve information about the last pool the given userID created
     */
    private function get_last_userpool(int $userID): Pool
    {
        global $database;
        return new Pool($database->get_row("SELECT * FROM pools WHERE user_id=:uid ORDER BY id DESC", ["uid" => $userID]));
    }

    /**
     * HERE WE ADD CHECKED IMAGES FROM POOL AND UPDATE THE HISTORY
     */
    public function onPoolAddPosts(PoolAddPostsEvent $event)
    {
        global $database, $user;

        $pool = $this->get_single_pool($event->pool_id);
        if (!$this->have_permission($user, $pool)) {
            return;
        }

        $images = " ";
        foreach ($event->posts as $post_id) {
            if ($this->add_post($event->pool_id, $post_id, false)) {
                $images .= " " . $post_id;
            }
        }

        if (!strlen($images) == 0) {
            $count = (int)$database->get_one(
                "SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid",
                ["pid" => $event->pool_id]
            );
            $this->add_history($event->pool_id, 1, $images, $count);
        }
    }

    /**
     * Gets the previous and next successive images from a pool, given a pool ID and an image ID.
     *
     * #return int[] Array returning two elements (prev, next) in 1 dimension. Each returns ImageID or NULL if none.
     */
    private function get_nav_posts(Pool $pool, int $imageID): ?array
    {
        global $database;

        if (empty($imageID)) {
            return null;
        }

        return $database->get_row(
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

						LIMIT 1",
            ["pid" => $pool->id, "iid" => $imageID]
        );
    }

    /**
     * Retrieve all the images in a pool, given a pool ID.
     */
    private function get_posts(PageRequestEvent $event, int $poolID)
    {
        global $config, $user, $database;

        $pageNumber = $event->try_page_num(2);
        $pool = $this->get_single_pool($poolID);
        $imagesPerPage = $config->get_int(PoolsConfig::IMAGES_PER_PAGE);

        $query = "
            INNER JOIN images AS i ON i.id = p.image_id
            WHERE p.pool_id = :pid
        ";
        $params = [];

        // WE CHECK IF THE EXTENSION RATING IS INSTALLED, WHICH VERSION AND IF IT
        // WORKS TO SHOW/HIDE SAFE, QUESTIONABLE, EXPLICIT AND UNRATED IMAGES FROM USER
        if (Extension::is_enabled(RatingsInfo::KEY)) {
            $query .= "AND i.rating IN (".Ratings::privs_to_sql(Ratings::get_user_class_privs($user)).")";
        }
        if (Extension::is_enabled(TrashInfo::KEY)) {
            $query .= " AND trash != :true";
            $params["true"] = true;
        }

        $result = $database->get_all(
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

        $totalPages = (int)ceil((int)$database->get_one(
            "SELECT COUNT(*) FROM pool_images p $query",
            ["pid" => $poolID] + $params
        ) / $imagesPerPage);

        $images = [];
        foreach ($result as $singleResult) {
            $images[] = Image::by_id((int)$singleResult["image_id"]);
        }

        $this->theme->view_pool($pool, $images, $pageNumber + 1, $totalPages);
    }

    /**
     * HERE WE NUKE ENTIRE POOL. WE REMOVE POOLS AND POSTS FROM REMOVED POOL AND HISTORIES ENTRIES FROM REMOVED POOL.
     */
    public function onPoolDeletion(PoolDeletionEvent $event)
    {
        global $user, $database;
        $poolID = $event->pool_id;

        $owner_id = (int)$database->get_one("SELECT user_id FROM pools WHERE id = :pid", ["pid" => $poolID]);
        if ($owner_id == $user->id || $user->can(Permissions::POOLS_ADMIN)) {
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
    private function add_history(int $poolID, int $action, string $images, int $count)
    {
        global $user, $database;

        $database->execute(
            "
				INSERT INTO pool_history (pool_id, user_id, action, images, count, date)
				VALUES (:pid, :uid, :act, :img, :count, now())",
            ["pid" => $poolID, "uid" => $user->id, "act" => $action, "img" => $images, "count" => $count]
        );
    }

    private function get_history(int $pageNumber)
    {
        global $config, $database;

        $historiesPerPage = $config->get_int(PoolsConfig::UPDATED_PER_PAGE);

        $history = $database->get_all("
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

        $totalPages = (int)ceil((int)$database->get_one("SELECT COUNT(*) FROM pool_history") / $historiesPerPage);

        $this->theme->show_history($history, $pageNumber + 1, $totalPages);
    }

    /**
     * HERE GO BACK IN HISTORY AND ADD OR REMOVE POSTS TO POOL.
     */
    private function revert_history(int $historyID)
    {
        global $database;
        $status = $database->get_all("SELECT * FROM pool_history WHERE id=:hid", ["hid" => $historyID]);

        foreach ($status as $entry) {
            $images = trim($entry['images']);
            $images = explode(" ", $images);
            $poolID = (int)$entry['pool_id'];
            $imageArray = "";
            $newAction = -1;

            if ($entry['action'] == 0) {
                // READ ENTRIES
                foreach ($images as $imageID) {
                    $this->add_post($poolID, int_escape($imageID));

                    $imageArray .= " " . $imageID;
                    $newAction = 1;
                }
            } elseif ($entry['action'] == 1) {
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
                log_error("pools", "Invalid history action.");
                continue; // go on to the next one.
            }

            $count = (int)$database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", ["pid" => $poolID]);
            $this->add_history($poolID, $newAction, $imageArray, $count);
        }
    }

    /**
     * HERE WE ADD A SIMPLE POST FROM POOL.
     * USED WITH FOREACH IN revert_history() & onTagTermParse().
     */
    private function add_post(int $poolID, int $imageID, bool $history = false, int $imageOrder = 0): bool
    {
        global $database, $config;

        $result = (int)$database->get_one(
            "SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid AND image_id=:iid",
            ["pid" => $poolID, "iid" => $imageID]
        );

        if ($result == 0) {
            if ($config->get_bool(PoolsConfig::AUTO_INCREMENT_ORDER) && $imageOrder === 0) {
                $imageOrder = (int)$database->get_one(
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
            $count = (int)$database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", ["pid" => $poolID]);
            $this->add_history($poolID, 1, (string)$imageID, $count);
        }
        return true;
    }

    private function update_count(int $pool_id)
    {
        global $database;
        $database->execute(
            "UPDATE pools SET posts=(SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid),lastupdated=CURRENT_TIMESTAMP WHERE id=:pid",
            ["pid" => $pool_id]
        );
    }
}
