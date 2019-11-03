<?php

abstract class PoolsConfig
{
    const MAX_IMPORT_RESULTS = "poolsMaxImportResults";
    const IMAGES_PER_PAGE = "poolsImagesPerPage";
    const LISTS_PER_PAGE = "poolsListsPerPage";
    const UPDATED_PER_PAGE = "poolsUpdatedPerPage";
    const INFO_ON_VIEW_IMAGE = "poolsInfoOnViewImage";
    const ADDER_ON_VIEW_IMAGE = "poolsAdderOnViewImage";
    const SHOW_NAV_LINKS = "poolsShowNavLinks";
    const AUTO_INCREMENT_ORDER = "poolsAutoIncrementOrder";
}

/**
 * This class is just a wrapper around SCoreException.
 */
class PoolCreationException extends SCoreException
{
    /** @var string */
    public $error;

    public function __construct(string $error)
    {
        $this->error = $error;
    }
}

class PoolAddPostsEvent extends Event
{
    public $pool_id;

    public $posts = [];

    public function __construct(int $pool_id, array $posts)
    {
        $this->pool_id = $pool_id;
        $this->posts = $posts;
    }
}

class PoolCreationEvent extends Event
{
    public $title;
    public $user;
    public $public;
    public $description;

    public $new_id = -1;

    public function __construct(string $title, User $pool_user = null, bool $public = false, string $description = "")
    {
        global $user;

        $this->title = $title;
        $this->user = $pool_user ?? $user;
        $this->public = $public;
        $this->description = $description;
    }
}

class Pools extends Extension
{
    public function onInitExt(InitExtEvent $event) {
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

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event) {
        global $config, $database;

        // Create the database tables
        if ($config->get_int("ext_pools_version") < 1) {
            $database->create_table("pools", "
					id SCORE_AIPK,
					user_id INTEGER NOT NULL,
					public SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
					title VARCHAR(255) NOT NULL,
					description TEXT,
					date TIMESTAMP NOT NULL,
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
					date TIMESTAMP NOT NULL,
					FOREIGN KEY (pool_id) REFERENCES pools(id) ON UPDATE CASCADE ON DELETE CASCADE,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
					");
            $config->set_int("ext_pools_version", 3);

            log_info("pools", "extension installed");
        }

        if ($config->get_int("ext_pools_version") < 2) {
            $database->Execute("ALTER TABLE pools ADD UNIQUE INDEX (title);");
            $database->Execute("ALTER TABLE pools ADD lastupdated TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;");

            $config->set_int("ext_pools_version", 3); // skip 2
        }
    }

    // Add a block to the Board Config / Setup
    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("Pools");
        $sb->add_int_option(PoolsConfig::MAX_IMPORT_RESULTS, "Max results on import: ");
        $sb->add_int_option(PoolsConfig::IMAGES_PER_PAGE, "<br>Images per page: ");
        $sb->add_int_option(PoolsConfig::LISTS_PER_PAGE, "<br>Index list items per page: ");
        $sb->add_int_option(PoolsConfig::UPDATED_PER_PAGE, "<br>Updated list items per page: ");
        $sb->add_bool_option(PoolsConfig::INFO_ON_VIEW_IMAGE, "<br>Show pool info on image: ");
        $sb->add_bool_option(PoolsConfig::SHOW_NAV_LINKS, "<br>Show 'Prev' & 'Next' links when viewing pool images: ");
        $sb->add_bool_option(PoolsConfig::AUTO_INCREMENT_ORDER, "<br>Autoincrement order when post is added to pool:");
        //$sb->add_bool_option(PoolsConfig::ADDER_ON_VIEW_IMAGE, "<br>Show pool adder on image: ");

        $event->panel->add_block($sb);
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event)
    {
        $event->add_nav_link("pool", new Link('pool/list'), "Pools");
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        if ($event->parent=="pool") {
            $event->add_nav_link("pool_list", new Link('pool/list'), "List");
            $event->add_nav_link("pool_new", new Link('pool/new'), "Create");
            $event->add_nav_link("pool_updated", new Link('pool/updated'), "Changes");
            $event->add_nav_link("pool_help", new Link('ext_doc/pools'), "Help");
        }
    }



    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if ($event->page_matches("pool")) {
            $pool_id = 0;
            $pool = [];

            // Check if we have pool id, since this is most often the case.
            if (isset($_POST["pool_id"])) {
                $pool_id = int_escape($_POST["pool_id"]);
                $pool = $this->get_single_pool($pool_id);
            }

            // What action are we trying to perform?
            switch ($event->get_arg(0)) {
                case "list": //index
                    $this->list_pools($page, int_escape($event->get_arg(1)));
                    break;

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
                        $event = new PoolCreationEvent(
                            $title,
                            $user,
                            $_POST["public"] === "Y",
                            $_POST["description"]
                        );

                        send_event($event);
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
                    $this->get_history(int_escape($event->get_arg(1)));
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
                        $this->theme->edit_pool($page, $this->get_pool($pool_id), $this->edit_posts($pool_id));
                    } else {
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("pool/view/" . $pool_id));
                    }
                    break;

                case "order": // Order the pool (view and change the order of images within the pool)
                    if (isset($_POST["order_view"])) {
                        if ($this->have_permission($user, $pool)) {
                            $this->theme->edit_order($page, $this->get_pool($pool_id), $this->edit_order($pool_id));
                        } else {
                            $page->set_mode(PageMode::REDIRECT);
                            $page->set_redirect(make_link("pool/view/" . $pool_id));
                        }
                    } else {
                        if ($this->have_permission($user, $pool)) {
                            $this->order_posts();
                            $page->set_mode(PageMode::REDIRECT);
                            $page->set_redirect(make_link("pool/view/" . $pool_id));
                        } else {
                            $this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
                        }
                    }
                    break;

                case "import":
                    if ($this->have_permission($user, $pool)) {
                        $this->import_posts($pool_id);
                    } else {
                        $this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
                    }
                    break;

                case "add_posts":
                    if ($this->have_permission($user, $pool)) {
                        $images = [];
                        foreach ($_POST['check'] as $imageID) {
                            $images[] = $imageID;
                        }
                        send_event(new PoolAddPostsEvent($pool_id, $images));
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("pool/view/" . $pool_id));
                    } else {
                        $this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
                    }
                    break;

                case "remove_posts":
                    if ($this->have_permission($user, $pool)) {
                        $this->remove_posts();
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("pool/view/" . $pool_id));
                    } else {
                        $this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
                    }

                    break;

                case "edit_description":
                    if ($this->have_permission($user, $pool)) {
                        $this->edit_description();
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("pool/view/" . $pool_id));
                    } else {
                        $this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
                    }

                    break;

                case "nuke":
                    // Completely remove the given pool.
                    //  -> Only admins and owners may do this
                    if ($user->can(Permissions::POOLS_ADMIN) || $user->id == $pool['user_id']) {
                        $this->nuke_pool($pool_id);
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("pool/list"));
                    } else {
                        $this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
                    }
                    break;

                default:
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("pool/list"));
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
     *
     * @var DisplayingImageEvent $event
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

                $navInfo[$pool['id']] = [];
                $navInfo[$pool['id']]['info'] = $pool;

                // Optionally show a link the Prev/Next image in the Pool.
                if ($show_nav) {
                    $navInfo[$pool['id']]['nav'] = $this->get_nav_posts($pool, $imageID);
                }
            }
            $this->theme->pool_info($navInfo);
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        global $config, $database, $user;
        if ($config->get_bool(PoolsConfig::ADDER_ON_VIEW_IMAGE) && !$user->is_anonymous()) {
            if ($user->can(Permissions::POOLS_ADMIN)) {
                $pools = $database->get_all("SELECT * FROM pools");
            } else {
                $pools = $database->get_all("SELECT * FROM pools WHERE user_id=:id", ["id" => $user->id]);
            }
            if (count($pools) > 0) {
                $event->add_part($this->theme->get_adder_html($event->image, $pools));
            }
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event)
    {
        if ($event->key===HelpPages::SEARCH) {
            $block = new Block();
            $block->header = "Pools";
            $block->body = $this->theme->get_help_html();
            $event->add_block($block);
        }
    }


    public function onSearchTermParse(SearchTermParseEvent $event)
    {
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
                $poolID = $pool['id'];
            }
            $event->add_querylet(new Querylet("images.id IN (SELECT DISTINCT image_id FROM pool_images WHERE pool_id = $poolID)"));
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
                $pool = $this->get_single_pool($poolTag);
            } else { //assume PoolTitle
                $pool = $this->get_single_pool_from_title($poolTag);
            }


            if ($pool ? $this->have_permission($user, $pool) : false) {
                $image_order = ($matches[2] ?: 0);
                $this->add_post($pool['id'], $event->id, true, $image_order);
            }
        }

        if (!empty($matches)) {
            $event->metatag = true;
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event)
    {
        global $database;

        $pools = $database->get_all("SELECT * FROM pools ORDER BY title ");


        $event->add_action("bulk_pool_add_existing", "Add To (P)ool", "p", "", $this->theme->get_bulk_pool_selector($pools));
        $event->add_action("bulk_pool_add_new", "Create Pool", "", "", $this->theme->get_bulk_pool_input($event->search_terms));
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
                $pool = $this->get_pool($pool_id);

                if ($this->have_permission($user, $pool)) {
                    send_event(
                        new PoolAddPostsEvent($pool_id, iterator_map_to_array("image_to_id", $event->items))
                    );
                }
                break;
            case "bulk_pool_add_new":
                if (!isset($_POST['bulk_pool_new'])) {
                    return;
                }
                $new_pool_title = $_POST['bulk_pool_new'];
                $pce = new PoolCreationEvent($new_pool_title);
                send_event($pce);
                send_event(new PoolAddPostsEvent($pce->new_id, iterator_map_to_array("image_to_id", $event->items)));
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
    private function have_permission(User $user, array $pool): bool
    {
        // If the pool is public and user is logged OR if the user is admin OR if the pool is owned by the user.
        if ((($pool['public'] == "Y" || $pool['public'] == "y") && !$user->is_anonymous()) || $user->can(Permissions::POOLS_ADMIN) || $user->id == $pool['user_id']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * HERE WE GET THE LIST OF POOLS.
     */
    private function list_pools(Page $page, int $pageNumber)
    {
        global $config, $database;

        $pageNumber = clamp($pageNumber, 1, null) - 1;

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

        $pools = $database->get_all("
			SELECT p.id, p.user_id, p.public, p.title, p.description,
			       p.posts, u.name as user_name
			FROM pools AS p
			INNER JOIN users AS u
			ON p.user_id = u.id
			$order_by
			LIMIT :l OFFSET :o
		", ["l" => $poolsPerPage, "o" => $pageNumber * $poolsPerPage]);

        $totalPages = ceil($database->get_one("SELECT COUNT(*) FROM pools") / $poolsPerPage);

        $this->theme->list_pools($page, $pools, $pageNumber + 1, $totalPages);
    }


    /**
     * HERE WE CREATE A NEW POOL
     */
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


        $database->execute(
            "
				INSERT INTO pools (user_id, public, title, description, date)
				VALUES (:uid, :public, :title, :desc, now())",
            ["uid" => $event->user->id, "public" => $event->public ? "Y" : "N", "title" => $event->title, "desc" => $event->description]
        );

        $poolID = $database->get_last_insert_id('pools_id_seq');
        log_info("pools", "Pool {$poolID} created by {$user->name}");

        $event->new_id = $poolID;
    }

    /**
     * Retrieve information about pools given multiple pool IDs.
     *
     * TODO: What is the difference between this and get_single_pool() other than the db query?
     */
    private function get_pool(int $poolID): array
    {
        global $database;
        return $database->get_all("SELECT * FROM pools WHERE id=:id", ["id" => $poolID]);
    }

    /**
     * Retrieve information about a pool given a pool ID.
     */
    private function get_single_pool(int $poolID): array
    {
        global $database;
        return $database->get_row("SELECT * FROM pools WHERE id=:id", ["id" => $poolID]);
    }

    /**
     * Retrieve information about a pool given a pool title.
     */
    private function get_single_pool_from_title(string $poolTitle): ?array
    {
        global $database;
        return $database->get_row("SELECT * FROM pools WHERE title=:title", ["title" => $poolTitle]);
    }

    /**
     * Get all of the pool IDs that an image is in, given an image ID.
     * #return int[]
     */
    private function get_pool_ids(int $imageID): array
    {
        global $database;
        return $database->get_col("SELECT pool_id FROM pool_images WHERE image_id=:iid", ["iid" => $imageID]);
    }

    /**
     * Retrieve information about the last pool the given userID created
     */
    private function get_last_userpool(int $userID): array
    {
        global $database;
        return $database->get_row("SELECT * FROM pools WHERE user_id=:uid ORDER BY id DESC", ["uid" => $userID]);
    }

    /**
     * HERE WE GET THE IMAGES FROM THE TAG ON IMPORT
     */
    private function import_posts(int $pool_id)
    {
        global $page, $config;

        $poolsMaxResults = $config->get_int(PoolsConfig::MAX_IMPORT_RESULTS, 1000);

        $images = $images = Image::find_images(0, $poolsMaxResults, Tag::explode($_POST["pool_tag"]));
        $this->theme->pool_result($page, $images, $this->get_pool($pool_id));
    }


    /**
     * HERE WE ADD CHECKED IMAGES FROM POOL AND UPDATE THE HISTORY
     *
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
            $count = int_escape($database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", ["pid" => $event->pool_id]));
            $this->add_history($event->pool_id, 1, $images, $count);
        }
    }

    /**
     * TODO: Fix this so that the pool ID and images are passed as Arguments to the function.
     */
    private function order_posts(): int
    {
        global $database;

        $poolID = int_escape($_POST['pool_id']);

        foreach ($_POST['imgs'] as $data) {
            list($imageORDER, $imageID) = $data;
            $database->Execute(
                "
				UPDATE pool_images
				SET image_order = :ord
				WHERE pool_id = :pid AND image_id = :iid",
                ["ord" => $imageORDER, "pid" => $poolID, "iid" => $imageID]
            );
        }

        return $poolID;
    }

    /**
     * HERE WE REMOVE CHECKED IMAGES FROM POOL AND UPDATE THE HISTORY
     *
     * TODO: Fix this so that the pool ID and images are passed as Arguments to the function.
     */
    private function remove_posts(): int
    {
        global $database;

        $poolID = int_escape($_POST['pool_id']);
        $images = "";

        foreach ($_POST['check'] as $imageID) {
            $database->execute("DELETE FROM pool_images WHERE pool_id = :pid AND image_id = :iid", ["pid" => $poolID, "iid" => $imageID]);
            $images .= " " . $imageID;
        }

        $count = $database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", ["pid" => $poolID]);
        $this->add_history($poolID, 0, $images, $count);
        return $poolID;
    }

    /**
     * Allows editing of pool description.
     */
    private function edit_description(): int
    {
        global $database;

        $poolID = int_escape($_POST['pool_id']);
        $database->execute("UPDATE pools SET description=:dsc WHERE id=:pid", ["dsc" => $_POST['description'], "pid" => $poolID]);

        return $poolID;
    }

    /**
     * This function checks if a given image is contained within a given pool.
     * Used by add_posts()
     */
    private function check_post(int $poolID, int $imageID): bool
    {
        global $database;
        $result = $database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid AND image_id=:iid", ["pid" => $poolID, "iid" => $imageID]);
        return ($result != 0);
    }

    /**
     * Gets the previous and next successive images from a pool, given a pool ID and an image ID.
     *
     * #return int[] Array returning two elements (prev, next) in 1 dimension. Each returns ImageID or NULL if none.
     */
    private function get_nav_posts(array $pool, int $imageID): array
    {
        global $database;

        if (empty($pool) || empty($imageID)) {
            return null;
        }

        $result = $database->get_row(
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
            ["pid" => $pool['id'], "iid" => $imageID]
        );

        if (empty($result)) {
            // assume that we are at the end of the pool
            return null;
        } else {
            return $result;
        }
    }

    /**
     * Retrieve all the images in a pool, given a pool ID.
     */
    private function get_posts(PageRequestEvent $event, int $poolID)
    {
        global $config, $user, $database;

        $pageNumber = int_escape($event->get_arg(2));
        if (is_null($pageNumber) || !is_numeric($pageNumber)) {
            $pageNumber = 0;
        } elseif ($pageNumber <= 0) {
            $pageNumber = 0;
        } else {
            $pageNumber--;
        }

        $poolID = int_escape($poolID);
        $pool = $this->get_pool($poolID);

        $imagesPerPage = $config->get_int(PoolsConfig::IMAGES_PER_PAGE);


        $query = "
                INNER JOIN images AS i ON i.id = p.image_id
                WHERE p.pool_id = :pid 
        ";


        // WE CHECK IF THE EXTENSION RATING IS INSTALLED, WHICH VERSION AND IF IT
        // WORKS TO SHOW/HIDE SAFE, QUESTIONABLE, EXPLICIT AND UNRATED IMAGES FROM USER
        if (Extension::is_enabled(RatingsInfo::KEY)) {
            $query .= "AND i.rating IN (".Ratings::privs_to_sql(Ratings::get_user_class_privs($user)).")";
        }
        if (Extension::is_enabled(TrashInfo::KEY)) {
            $query .=  $database->scoreql_to_sql(" AND trash = SCORE_BOOL_N ");
        }

        $result = $database->get_all(
            "
					SELECT p.image_id FROM pool_images p
					$query
					ORDER BY p.image_order ASC
					LIMIT :l OFFSET :o",
            ["pid" => $poolID, "l" => $imagesPerPage, "o" => $pageNumber * $imagesPerPage]
        );

        $totalPages = ceil($database->get_one(
            "
					SELECT COUNT(*) FROM pool_images p
					$query",
            ["pid" => $poolID]
        ) / $imagesPerPage);



        $images = [];
        foreach ($result as $singleResult) {
            $images[] = Image::by_id($singleResult["image_id"]);
        }

        $this->theme->view_pool($pool, $images, $pageNumber + 1, $totalPages);
    }


    /**
     * This function gets the current order of images from a given pool.
     * #return Image[] Array of image objects.
     */
    private function edit_posts(int $poolID): array
    {
        global $database;

        $result = $database->Execute("SELECT image_id FROM pool_images WHERE pool_id=:pid ORDER BY image_order ASC", ["pid" => $poolID]);
        $images = [];

        while ($row = $result->fetch()) {
            $image = Image::by_id($row["image_id"]);
            $images[] = [$image];
        }

        return $images;
    }


    /**
     * WE GET THE ORDER OF THE IMAGES BUT HERE WE SEND KEYS ADDED IN ARRAY TO GET THE ORDER IN THE INPUT VALUE.
     *
     * #return Image[]
     */
    private function edit_order(int $poolID): array
    {
        global $database;

        $result = $database->Execute("SELECT image_id FROM pool_images WHERE pool_id=:pid ORDER BY image_order ASC", ["pid" => $poolID]);
        $images = [];

        while ($row = $result->fetch()) {
            $image = $database->get_row(
                "
					SELECT * FROM images AS i
					INNER JOIN pool_images AS p ON i.id = p.image_id
					WHERE pool_id=:pid AND i.id=:iid",
                ["pid" => $poolID, "iid" => $row['image_id']]
            );
            $image = ($image ? new Image($image) : null);
            $images[] = [$image];
        }

        return $images;
    }


    /**
     * HERE WE NUKE ENTIRE POOL. WE REMOVE POOLS AND POSTS FROM REMOVED POOL AND HISTORIES ENTRIES FROM REMOVED POOL.
     */
    private function nuke_pool(int $poolID)
    {
        global $user, $database;

        $p_id = $database->get_one("SELECT user_id FROM pools WHERE id = :pid", ["pid" => $poolID]);
        if ($user->can(Permissions::POOLS_ADMIN)) {
            $database->execute("DELETE FROM pool_history WHERE pool_id = :pid", ["pid" => $poolID]);
            $database->execute("DELETE FROM pool_images WHERE pool_id = :pid", ["pid" => $poolID]);
            $database->execute("DELETE FROM pools WHERE id = :pid", ["pid" => $poolID]);
        } elseif ($user->id == $p_id) {
            $database->execute("DELETE FROM pool_history WHERE pool_id = :pid", ["pid" => $poolID]);
            $database->execute("DELETE FROM pool_images WHERE pool_id = :pid", ["pid" => $poolID]);
            $database->execute("DELETE FROM pools WHERE id = :pid AND user_id = :uid", ["pid" => $poolID, "uid" => $user->id]);
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

    /**
     * HERE WE GET THE HISTORY LIST.
     */
    private function get_history(int $pageNumber)
    {
        global $config, $database;

        if (is_null($pageNumber) || !is_numeric($pageNumber)) {
            $pageNumber = 0;
        } elseif ($pageNumber <= 0) {
            $pageNumber = 0;
        } else {
            $pageNumber--;
        }

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

        $totalPages = ceil($database->get_one("SELECT COUNT(*) FROM pool_history") / $historiesPerPage);

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
            $poolID = $entry['pool_id'];
            $imageArray = "";
            $newAction = -1;

            if ($entry['action'] == 0) {
                // READ ENTRIES
                foreach ($images as $image) {
                    $imageID = $image;
                    $this->add_post($poolID, $imageID);

                    $imageArray .= " " . $imageID;
                    $newAction = 1;
                }
            } elseif ($entry['action'] == 1) {
                // DELETE ENTRIES
                foreach ($images as $image) {
                    $imageID = $image;
                    $this->delete_post($poolID, $imageID);

                    $imageArray .= " " . $imageID;
                    $newAction = 0;
                }
            } else {
                // FIXME: should this throw an exception instead?
                log_error("pools", "Invalid history action.");
                continue; // go on to the next one.
            }

            $count = $database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", ["pid" => $poolID]);
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

        if (!$this->check_post($poolID, $imageID)) {
            if ($config->get_bool(PoolsConfig::AUTO_INCREMENT_ORDER) && $imageOrder === 0) {
                $imageOrder = $database->get_one(
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
            $count = $database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", ["pid" => $poolID]);
            $this->add_history($poolID, 1, $imageID, $count);
        }
        return true;
    }


    private function update_count($pool_id)
    {
        global $database;
        $database->execute("UPDATE pools SET posts=(SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid) WHERE id=:pid", ["pid" => $pool_id]);
    }

    /**
     * HERE WE REMOVE A SIMPLE POST FROM POOL.
     * USED WITH FOREACH IN revert_history() & onTagTermParse().
     */
    private function delete_post(int $poolID, int $imageID, bool $history = false)
    {
        global $database;

        $database->execute("DELETE FROM pool_images WHERE pool_id = :pid AND image_id = :iid", ["pid" => $poolID, "iid" => $imageID]);

        $this->update_count($poolID);

        if ($history) {
            $count = $database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", ["pid" => $poolID]);
            $this->add_history($poolID, 0, $imageID, $count);
        }
    }
}
