<?php
/**
 * Name: Pools System
 * Author: Sein Kraft <mail@seinkraft.info>, jgen <jgen.tech@gmail.com>, Daku <admin@codeanimu.net>
 * License: GPLv2
 * Description: Allow users to create groups of images and order them.
 * Documentation: This extension allows users to created named groups of
 *   images, and order the images within the group.
 *   Useful for related images like in a comic, etc.
 */

/**
 * This class is just a wrapper around SCoreException.
 */
class PoolCreationException extends SCoreException {
	/** @var string */
	public $error;

	/**
	 * @param string $error
	 */
	public function __construct($error) {
		$this->error = $error;
	}
}

class Pools extends Extension {

	public function onInitExt(InitExtEvent $event) {
		global $config, $database;

		// Set the defaults for the pools extension
		$config->set_default_int("poolsMaxImportResults", 1000);
		$config->set_default_int("poolsImagesPerPage", 20);
		$config->set_default_int("poolsListsPerPage", 20);
		$config->set_default_int("poolsUpdatedPerPage", 20);
		$config->set_default_bool("poolsInfoOnViewImage", false);
		$config->set_default_bool("poolsAdderOnViewImage", false);
		$config->set_default_bool("poolsShowNavLinks", false);
		$config->set_default_bool("poolsAutoIncrementOrder", false);

		// Create the database tables
		if ($config->get_int("ext_pools_version") < 1){
			$database->create_table("pools", "
					id SCORE_AIPK,
					user_id INTEGER NOT NULL,
					public SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
					title VARCHAR(255) NOT NULL,
					description TEXT,
					date SCORE_DATETIME NOT NULL,
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
					date SCORE_DATETIME NOT NULL,
					FOREIGN KEY (pool_id) REFERENCES pools(id) ON UPDATE CASCADE ON DELETE CASCADE,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
					");
			$config->set_int("ext_pools_version", 3);

			log_info("pools", "extension installed");
		}

		if ($config->get_int("ext_pools_version") < 2){
			$database->Execute("ALTER TABLE pools ADD UNIQUE INDEX (title);");
			$database->Execute("ALTER TABLE pools ADD lastupdated TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;");

			$config->set_int("ext_pools_version", 3); // skip 2
		}
	}

	// Add a block to the Board Config / Setup
	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Pools");
		$sb->add_int_option("poolsMaxImportResults", "Max results on import: ");
		$sb->add_int_option("poolsImagesPerPage", "<br>Images per page: ");
		$sb->add_int_option("poolsListsPerPage", "<br>Index list items per page: ");
		$sb->add_int_option("poolsUpdatedPerPage", "<br>Updated list items per page: ");
		$sb->add_bool_option("poolsInfoOnViewImage", "<br>Show pool info on image: ");
		$sb->add_bool_option("poolsShowNavLinks", "<br>Show 'Prev' & 'Next' links when viewing pool images: ");
		$sb->add_bool_option("poolsAutoIncrementOrder", "<br>Autoincrement order when post is added to pool:");
		//$sb->add_bool_option("poolsAdderOnViewImage", "<br>Show pool adder on image: ");

		$event->panel->add_block($sb);
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;
		
		if ($event->page_matches("pool")) {
			$pool_id = 0;
			$pool = array();

			// Check if we have pool id, since this is most often the case.
			if (isset($_POST["pool_id"])) {
				$pool_id = int_escape($_POST["pool_id"]);
				$pool = $this->get_single_pool($pool_id);
			}
			
			// What action are we trying to perform?
			switch($event->get_arg(0)) {
				case "list": //index
					$this->list_pools($page, int_escape($event->get_arg(1)));
					break;

				case "new": // Show form for new pools
					if(!$user->is_anonymous()){
						$this->theme->new_pool_composer($page);
					} else {
						$errMessage = "You must be registered and logged in to create a new pool.";
						$this->theme->display_error(401, "Error", $errMessage);
					}
					break;

				case "create": // ADD _POST
					try {
						$newPoolID = $this->add_pool();
						$page->set_mode("redirect");
						$page->set_redirect(make_link("pool/view/".$newPoolID));
					}
					catch(PoolCreationException $e) {
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
					if(!$user->is_anonymous()) {
						$historyID = int_escape($event->get_arg(1));
						$this->revert_history($historyID);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("pool/updated"));
					}
					break;

				case "edit": // Edit the pool (remove images)
					if ($this->have_permission($user, $pool)) {
						$this->theme->edit_pool($page, $this->get_pool($pool_id), $this->edit_posts($pool_id));
					} else {
						$page->set_mode("redirect");
						$page->set_redirect(make_link("pool/view/".$pool_id));
					}
					break;

				case "order": // Order the pool (view and change the order of images within the pool)
					if (isset($_POST["order_view"])) {
						if ($this->have_permission($user, $pool)) {
							$this->theme->edit_order($page, $this->get_pool($pool_id), $this->edit_order($pool_id));
						} else {
							$page->set_mode("redirect");
							$page->set_redirect(make_link("pool/view/".$pool_id));
						}
					}
					else {
						if ($this->have_permission($user, $pool)) {
							$this->order_posts();
							$page->set_mode("redirect");
							$page->set_redirect(make_link("pool/view/".$pool_id));
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
						$this->add_posts();
						$page->set_mode("redirect");
						$page->set_redirect(make_link("pool/view/".$pool_id));
					} else {
						$this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
					}
					break;

				case "remove_posts":
					if ($this->have_permission($user, $pool)) {
						$this->remove_posts();
						$page->set_mode("redirect");
						$page->set_redirect(make_link("pool/view/".$pool_id));
					} else {
						$this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
					}

					break;

				case "edit_description":
					if ($this->have_permission($user, $pool)) {
						$this->edit_description();
						$page->set_mode("redirect");
						$page->set_redirect(make_link("pool/view/".$pool_id));
					} else {
						$this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
					}

					break;

				case "nuke":
					// Completely remove the given pool.
					//  -> Only admins and owners may do this
					if($user->is_admin() || $user->id == $pool['user_id']) {
						$this->nuke_pool($pool_id);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("pool/list"));
					} else {
						$this->theme->display_error(403, "Permission Denied", "You do not have permission to access this page");
					}
					break;

				default:
					$page->set_mode("redirect");
					$page->set_redirect(make_link("pool/list"));
					break;
			}
		}
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		$event->add_link("Pools", make_link("pool/list"));
	}


	/**
	 * When displaying an image, optionally list all the pools that the
	 * image is currently a member of on a side panel, as well as a link
	 * to the Next image in the pool.
	 *
	 * @var DisplayingImageEvent $event
	 */
	public function onDisplayingImage(DisplayingImageEvent $event) {
		global $config;

		if($config->get_bool("poolsInfoOnViewImage")) {
			$imageID = $event->image->id;
			$poolsIDs = $this->get_pool_ids($imageID);

			$show_nav = $config->get_bool("poolsShowNavLinks", false);

			$navInfo = array();
			foreach($poolsIDs as $poolID) {
				$pool = $this->get_single_pool($poolID);

				$navInfo[$pool['id']] = array();
				$navInfo[$pool['id']]['info'] = $pool;

				// Optionally show a link the Prev/Next image in the Pool.
				if ($show_nav) {
					$navInfo[$pool['id']]['nav'] = $this->get_nav_posts($pool, $imageID);
				}
			}
			$this->theme->pool_info($navInfo);
		}
	}

	public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event) {
		global $config, $database, $user;
		if($config->get_bool("poolsAdderOnViewImage") && !$user->is_anonymous()) {
			if($user->is_admin()) {
				$pools = $database->get_all("SELECT * FROM pools");
			}
			else {
				$pools = $database->get_all("SELECT * FROM pools WHERE user_id=:id", array("id"=>$user->id));
			}
			if(count($pools) > 0) {
				$event->add_part($this->theme->get_adder_html($event->image, $pools));
			}
		}
	}

	public function onSearchTermParse(SearchTermParseEvent $event) {
		$matches = array();
		if(preg_match("/^pool[=|:]([0-9]+|any|none)$/i", $event->term, $matches)) {
			$poolID = $matches[1];

			if(preg_match("/^(any|none)$/", $poolID)){
				$not = ($poolID == "none" ? "NOT" : "");
				$event->add_querylet(new Querylet("images.id $not IN (SELECT DISTINCT image_id FROM pool_images)"));
			}else{
				$event->add_querylet(new Querylet("images.id IN (SELECT DISTINCT image_id FROM pool_images WHERE pool_id = $poolID)"));
			}
		}
		else if(preg_match("/^pool_by_name[=|:](.*)$/i", $event->term, $matches)) {
			$poolTitle = str_replace("_", " ", $matches[1]);

			$pool = $this->get_single_pool_from_title($poolTitle);
			$poolID = 0;
			if ($pool){ $poolID = $pool['id']; }
			$event->add_querylet(new Querylet("images.id IN (SELECT DISTINCT image_id FROM pool_images WHERE pool_id = $poolID)"));
		}
	}

	public function onTagTermParse(TagTermParseEvent $event) {
		$matches = array();

		if(preg_match("/^pool[=|:]([^:]*|lastcreated):?([0-9]*)$/i", $event->term, $matches)) {
			global $user;
			$poolTag = (string) str_replace("_", " ", $matches[1]);

			$pool = null;
			if($poolTag == 'lastcreated'){
				$pool = $this->get_last_userpool($user->id);
			}
			elseif(ctype_digit($poolTag)){ //If only digits, assume PoolID
				$pool = $this->get_single_pool($poolTag);
			}else{ //assume PoolTitle
				$pool = $this->get_single_pool_from_title($poolTag);
			}


			if($pool ? $this->have_permission($user, $pool) : FALSE){
				$image_order = ($matches[2] ?: 0);
				$this->add_post($pool['id'], $event->id, true, $image_order);
			}
		}

		if(!empty($matches)) $event->metatag = true;
	}

	/* ------------------------------------------------- */
	/* --------------  Private Functions  -------------- */
	/* ------------------------------------------------- */

	/**
	 * Check if the given user has permission to edit/change the pool.
	 *
	 * TODO: Should the user variable be global?
	 *
	 * @param \User $user
	 * @param array $pool
	 * @return bool
	 */
	private function have_permission($user, $pool) {
		// If the pool is public and user is logged OR if the user is admin OR if the pool is owned by the user.
		if ( (($pool['public'] == "Y" || $pool['public'] == "y") && !$user->is_anonymous()) || $user->is_admin() || $user->id == $pool['user_id'])
		{
			return true;
		} else {
			return false;
		}
	}

	/**
	 * HERE WE GET THE LIST OF POOLS.
	 *
	 * @param \Page $page
	 * @param int $pageNumber
	 */
	private function list_pools(Page $page, /*int*/ $pageNumber) {
		global $config, $database;

		$pageNumber = clamp($pageNumber, 1, null) - 1;

		$poolsPerPage = $config->get_int("poolsListsPerPage");

		$order_by = "";
		$order = $page->get_cookie("ui-order-pool");
		if($order == "created" || is_null($order)){
			$order_by = "ORDER BY p.date DESC";
		}elseif($order == "updated"){
			$order_by = "ORDER BY p.lastupdated DESC";
		}elseif($order == "name"){
			$order_by = "ORDER BY p.title ASC";
		}elseif($order == "count"){
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
		", array("l"=>$poolsPerPage, "o"=>$pageNumber * $poolsPerPage));

		$totalPages = ceil($database->get_one("SELECT COUNT(*) FROM pools") / $poolsPerPage);

		$this->theme->list_pools($page, $pools, $pageNumber + 1, $totalPages);
	}


	/**
	 * HERE WE CREATE A NEW POOL
	 *
	 * @return int
	 * @throws PoolCreationException
	 */
	private function add_pool() {
		global $user, $database;

		if($user->is_anonymous()) {
			throw new PoolCreationException("You must be registered and logged in to add a image.");
		}
		if(empty($_POST["title"])) {
			throw new PoolCreationException("Pool title is empty.");
		}
		if($this->get_single_pool_from_title($_POST["title"])) {
			throw new PoolCreationException("A pool using this title already exists.");
		}

		$public = $_POST["public"] === "Y" ? "Y" : "N";
		$database->execute("
				INSERT INTO pools (user_id, public, title, description, date)
				VALUES (:uid, :public, :title, :desc, now())",
				array("uid"=>$user->id, "public"=>$public, "title"=>$_POST["title"], "desc"=>$_POST["description"]));

		$poolID = $database->get_last_insert_id('pools_id_seq');
		log_info("pools", "Pool {$poolID} created by {$user->name}");
		return $poolID;
	}

	/**
	 * Retrieve information about pools given multiple pool IDs.
	 *
	 * TODO: What is the difference between this and get_single_pool() other than the db query?
	 *
	 * @param int $poolID Array of integers
	 * @return array
	 */
	private function get_pool(/*int*/ $poolID) {
		global $database;
		return $database->get_all("SELECT * FROM pools WHERE id=:id", array("id"=>$poolID));
	}

	/**
	 * Retrieve information about a pool given a pool ID.
	 * @param int $poolID the pool id
	 * @return array Array with only 1 element in the one dimension
	 */
	private function get_single_pool(/*int*/ $poolID) {
		global $database;
		return $database->get_row("SELECT * FROM pools WHERE id=:id", array("id"=>$poolID));
	}

	/**
	 * Retrieve information about a pool given a pool title.
	 * @param string $poolTitle
	 * @return array Array (with only 1 element in the one dimension)
	 */
	private function get_single_pool_from_title(/*string*/ $poolTitle) {
		global $database;
		return $database->get_row("SELECT * FROM pools WHERE title=:title", array("title"=>$poolTitle));
	}

	/**
	 * Get all of the pool IDs that an image is in, given an image ID.
	 * @param int $imageID Integer ID for the image
	 * @return int[]
	 */
	private function get_pool_ids(/*int*/ $imageID) {
		global $database;
		return $database->get_col("SELECT pool_id FROM pool_images WHERE image_id=:iid", array("iid"=>$imageID));
	}

	/**
	 * Retrieve information about the last pool the given userID created
	 * @param int $userID
	 * @return array
	 */
	private function get_last_userpool(/*int*/ $userID){
		global $database;
		return $database->get_row("SELECT * FROM pools WHERE user_id=:uid ORDER BY id DESC", array("uid"=>$userID));
	}

	/**
	 * HERE WE GET THE IMAGES FROM THE TAG ON IMPORT
	 * @param int $pool_id
	 */
	private function import_posts(/*int*/ $pool_id) {
		global $page, $config;

		$poolsMaxResults = $config->get_int("poolsMaxImportResults", 1000);
		
		$images = $images = Image::find_images(0, $poolsMaxResults, Tag::explode($_POST["pool_tag"]));
		$this->theme->pool_result($page, $images, $this->get_pool($pool_id));
	}


	/**
	 * HERE WE ADD CHECKED IMAGES FROM POOL AND UPDATE THE HISTORY
	 *
	 * TODO: Fix this so that the pool ID and images are passed as Arguments to the function.
	 *
	 * @return int
	 */
	private function add_posts() {
		global $database;

		$poolID = int_escape($_POST['pool_id']);
		$images = "";

		foreach ($_POST['check'] as $imageID){
			if(!$this->check_post($poolID, $imageID)){
				$database->execute("
						INSERT INTO pool_images (pool_id, image_id)
						VALUES (:pid, :iid)",
						array("pid"=>$poolID, "iid"=>$imageID));

				$images .= " ".$imageID;
			}
		}

		if(!strlen($images) == 0) {
			$count = int_escape($database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", array("pid"=>$poolID)));
			$this->add_history($poolID, 1, $images, $count);
		}

		$database->Execute("
			UPDATE pools
			SET posts=(SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid)
			WHERE id=:pid",
			array("pid"=>$poolID)
		);
		return $poolID;
	}

	/**
	 * TODO: Fix this so that the pool ID and images are passed as Arguments to the function.
	 * @return int
	 */
	private function order_posts() {
		global $database;

		$poolID = int_escape($_POST['pool_id']);

		foreach($_POST['imgs'] as $data) {
			list($imageORDER, $imageID) = $data;
			$database->Execute("
				UPDATE pool_images
				SET image_order = :ord
				WHERE pool_id = :pid AND image_id = :iid",
				array("ord"=>$imageORDER, "pid"=>$poolID, "iid"=>$imageID)
			);
		}

		return $poolID;
	}

	/**
	 * HERE WE REMOVE CHECKED IMAGES FROM POOL AND UPDATE THE HISTORY
	 *
	 * TODO: Fix this so that the pool ID and images are passed as Arguments to the function.
	 *
	 * @return int
	 */
	private function remove_posts() {
		global $database;

		$poolID = int_escape($_POST['pool_id']);
		$images = "";

		foreach($_POST['check'] as $imageID) {
			$database->execute("DELETE FROM pool_images WHERE pool_id = :pid AND image_id = :iid", array("pid"=>$poolID, "iid"=>$imageID));
			$images .= " ".$imageID;
		}

		$count = $database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", array("pid"=>$poolID));
		$this->add_history($poolID, 0, $images, $count);
		return $poolID;
	}

	/**
	 * Allows editing of pool description.
	 * @return int
	 */
	private function edit_description() {
		global $database;

		$poolID = int_escape($_POST['pool_id']);
		$database->execute("UPDATE pools SET description=:dsc WHERE id=:pid", array("dsc"=>$_POST['description'], "pid"=>$poolID));

		return $poolID;
	}

	/**
	 * This function checks if a given image is contained within a given pool.
	 * Used by add_posts()
	 *
	 * @see add_posts()
	 * @param int $poolID
	 * @param int $imageID
	 * @return bool
	 */
	private function check_post(/*int*/ $poolID, /*int*/ $imageID) {
		global $database;
		$result = $database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid AND image_id=:iid", array("pid"=>$poolID, "iid"=>$imageID));
		return ($result != 0);
	}

	/**
	 * Gets the previous and next successive images from a pool, given a pool ID and an image ID.
	 *
	 * @param array $pool Array for the given pool
	 * @param int $imageID Integer
	 * @return array Array returning two elements (prev, next) in 1 dimension. Each returns ImageID or NULL if none.
	 */
	private function get_nav_posts(/*array*/ $pool, /*int*/ $imageID) {
		global $database;

		if (empty($pool) || empty($imageID))
			return null;
		
		$result = $database->get_row("
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
					array("pid"=>$pool['id'], "iid"=>$imageID) );

		if (empty($result)) {
			// assume that we are at the end of the pool
			return null;
		} else {
			return $result;
		}
	}

	/**
	 * Retrieve all the images in a pool, given a pool ID.
	 *
	 * @param PageRequestEvent $event
	 * @param int $poolID
	 */
	private function get_posts($event, /*int*/ $poolID) {
		global $config, $user, $database;

		$pageNumber = int_escape($event->get_arg(2));
		if(is_null($pageNumber) || !is_numeric($pageNumber))
			$pageNumber = 0;
		else if ($pageNumber <= 0)
			$pageNumber = 0;
		else
			$pageNumber--;

		$poolID = int_escape($poolID);
		$pool = $this->get_pool($poolID);

		$imagesPerPage = $config->get_int("poolsImagesPerPage");

		// WE CHECK IF THE EXTENSION RATING IS INSTALLED, WHICH VERSION AND IF IT
		// WORKS TO SHOW/HIDE SAFE, QUESTIONABLE, EXPLICIT AND UNRATED IMAGES FROM USER
		if(ext_is_live("Ratings")) {
			$rating = Ratings::privs_to_sql(Ratings::get_user_privs($user));
		}
		if (isset($rating) && !empty($rating)) {

			$result = $database->get_all("
					SELECT p.image_id
					FROM pool_images AS p
					INNER JOIN images AS i ON i.id = p.image_id
					WHERE p.pool_id = :pid AND i.rating IN ($rating)
					ORDER BY p.image_order ASC
					LIMIT :l OFFSET :o",
					array("pid"=>$poolID, "l"=>$imagesPerPage, "o"=>$pageNumber * $imagesPerPage));

			$totalPages = ceil($database->get_one("
					SELECT COUNT(*) 
					FROM pool_images AS p
					INNER JOIN images AS i ON i.id = p.image_id
					WHERE pool_id=:pid AND i.rating IN ($rating)",
					array("pid"=>$poolID)) / $imagesPerPage);
		} else {
		
			$result = $database->get_all("
					SELECT image_id
					FROM pool_images
					WHERE pool_id=:pid
					ORDER BY image_order ASC
					LIMIT :l OFFSET :o",
					array("pid"=>$poolID, "l"=>$imagesPerPage, "o"=>$pageNumber * $imagesPerPage));
					
			$totalPages = ceil($database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", array("pid"=>$poolID)) / $imagesPerPage);
		}

		$images = array();
		foreach($result as $singleResult) {
			$images[] = Image::by_id($singleResult["image_id"]);
		}

		$this->theme->view_pool($pool, $images, $pageNumber + 1, $totalPages);
	}


	/**
	 * This function gets the current order of images from a given pool.
	 * @param int $poolID
	 * @return \Image[] Array of image objects.
	 */
	private function edit_posts(/*int*/ $poolID) {
		global $database;

		$result = $database->Execute("SELECT image_id FROM pool_images WHERE pool_id=:pid ORDER BY image_order ASC", array("pid"=>$poolID));
		$images = array();
		
		while($row = $result->fetch()) {
			$image = Image::by_id($row["image_id"]);
			$images[] = array($image);
		}
		
		return $images;
	}


	/**
	 * WE GET THE ORDER OF THE IMAGES BUT HERE WE SEND KEYS ADDED IN ARRAY TO GET THE ORDER IN THE INPUT VALUE.
	 *
	 * @param int $poolID
	 * @return \Image[]
	 */
	private function edit_order(/*int*/ $poolID) {
		global $database;

		$result = $database->Execute("SELECT image_id FROM pool_images WHERE pool_id=:pid ORDER BY image_order ASC", array("pid"=>$poolID));									
		$images = array();
		
		while($row = $result->fetch())
		{
			$image = $database->get_row("
					SELECT * FROM images AS i
					INNER JOIN pool_images AS p ON i.id = p.image_id
					WHERE pool_id=:pid AND i.id=:iid",
					array("pid"=>$poolID, "iid"=>$row['image_id']));
			$image = ($image ? new Image($image) : null);
			$images[] = array($image);
		}
		
		return $images;
	}


	/**
	 * HERE WE NUKE ENTIRE POOL. WE REMOVE POOLS AND POSTS FROM REMOVED POOL AND HISTORIES ENTRIES FROM REMOVED POOL.
	 *
	 * @param int $poolID
	 */
	private function nuke_pool(/*int*/ $poolID) {
		global $user, $database;

		$p_id = $database->get_one("SELECT user_id FROM pools WHERE id = :pid", array("pid"=>$poolID));
		if($user->is_admin()) {
			$database->execute("DELETE FROM pool_history WHERE pool_id = :pid", array("pid"=>$poolID));
			$database->execute("DELETE FROM pool_images WHERE pool_id = :pid", array("pid"=>$poolID));
			$database->execute("DELETE FROM pools WHERE id = :pid", array("pid"=>$poolID));
		} elseif($user->id == $p_id) {
			$database->execute("DELETE FROM pool_history WHERE pool_id = :pid", array("pid"=>$poolID));
			$database->execute("DELETE FROM pool_images WHERE pool_id = :pid", array("pid"=>$poolID));
			$database->execute("DELETE FROM pools WHERE id = :pid AND user_id = :uid", array("pid"=>$poolID, "uid"=>$user->id));
		}
	}

	/**
	 * HERE WE ADD A HISTORY ENTRY.
	 *
	 * @param int $poolID
	 * @param int $action Action=1 (one) MEANS ADDED, Action=0 (zero) MEANS REMOVED
	 * @param string $images
	 * @param int $count
	 */
	private function add_history(/*int*/ $poolID, $action, $images, $count) {
		global $user, $database;

		$database->execute("
				INSERT INTO pool_history (pool_id, user_id, action, images, count, date)
				VALUES (:pid, :uid, :act, :img, :count, now())",
				array("pid"=>$poolID, "uid"=>$user->id, "act"=>$action, "img"=>$images, "count"=>$count));
	}

	/**
	 * HERE WE GET THE HISTORY LIST.
	 * @param int $pageNumber
	 */
	private function get_history(/*int*/ $pageNumber) {
		global $config, $database;

		if(is_null($pageNumber) || !is_numeric($pageNumber))
			$pageNumber = 0;
		else if ($pageNumber <= 0)
			$pageNumber = 0;
		else
			$pageNumber--;

		$historiesPerPage = $config->get_int("poolsUpdatedPerPage");

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
				", array("l"=>$historiesPerPage, "o"=>$pageNumber * $historiesPerPage));

		$totalPages = ceil($database->get_one("SELECT COUNT(*) FROM pool_history") / $historiesPerPage);

		$this->theme->show_history($history, $pageNumber + 1, $totalPages);
	}

	/**
	 * HERE GO BACK IN HISTORY AND ADD OR REMOVE POSTS TO POOL.
	 * @param int $historyID
	 */
	private function revert_history(/*int*/ $historyID) {
		global $database;
		$status = $database->get_all("SELECT * FROM pool_history WHERE id=:hid", array("hid"=>$historyID));

		foreach($status as $entry) {
			$images = trim($entry['images']);
			$images = explode(" ", $images);
			$poolID = $entry['pool_id'];
			$imageArray = "";
			$newAction = -1;

			if($entry['action'] == 0) {
				// READ ENTRIES
				foreach($images as $image) {
					$imageID = $image;
					$this->add_post($poolID, $imageID);

					$imageArray .= " ".$imageID;
					$newAction = 1;
				}
			}
			else if($entry['action'] == 1) {
				// DELETE ENTRIES
				foreach($images as $image) {
					$imageID = $image;
					$this->delete_post($poolID, $imageID);

					$imageArray .= " ".$imageID;
					$newAction = 0;
				}
			} else {
				// FIXME: should this throw an exception instead?
				log_error("pools", "Invalid history action.");
				continue; // go on to the next one.
			}

			$count = $database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", array("pid"=>$poolID));
			$this->add_history($poolID, $newAction, $imageArray, $count);
		}
	}

	/**
	 * HERE WE ADD A SIMPLE POST FROM POOL.
	 * USED WITH FOREACH IN revert_history() & onTagTermParse().
	 *
	 * @param int $poolID
	 * @param int $imageID
	 * @param bool $history
	 * @param int $imageOrder
	 */
	private function add_post(/*int*/ $poolID, /*int*/ $imageID, $history=false, $imageOrder=0) {
		global $database, $config;

		if(!$this->check_post($poolID, $imageID)) {
			if($config->get_bool("poolsAutoIncrementOrder") && $imageOrder === 0){
				$imageOrder = $database->get_one("
						SELECT CASE WHEN image_order IS NOT NULL THEN MAX(image_order) + 1 ELSE 0 END
						FROM pool_images
						WHERE pool_id = :pid",
						array("pid"=>$poolID));
			}

			$database->execute("
					INSERT INTO pool_images (pool_id, image_id, image_order)
					VALUES (:pid, :iid, :ord)",
					array("pid"=>$poolID, "iid"=>$imageID, "ord"=>$imageOrder));
		}

		$database->execute("UPDATE pools SET posts=(SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid) WHERE id=:pid", array("pid"=>$poolID));

		if($history){
			$count = $database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", array("pid"=>$poolID));
			$this->add_history($poolID, 1, $imageID, $count);
		}
	}

	/**
	 * HERE WE REMOVE A SIMPLE POST FROM POOL.
	 * USED WITH FOREACH IN revert_history() & onTagTermParse().
	 *
	 * @param int $poolID
	 * @param int $imageID
	 * @param bool $history
	 */
	private function delete_post(/*int*/ $poolID, /*int*/ $imageID, $history=false) {
		global $database;

		$database->execute("DELETE FROM pool_images WHERE pool_id = :pid AND image_id = :iid", array("pid"=>$poolID, "iid"=>$imageID));
		$database->execute("UPDATE pools SET posts=(SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid) WHERE id=:pid", array("pid"=>$poolID));

		if($history){
			$count = $database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", array("pid"=>$poolID));
			$this->add_history($poolID, 0, $imageID, $count);
		}
	}

}

