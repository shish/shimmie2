<?php
/**
 * Name: Pools System
 * Author: Sein Kraft <mail@seinkraft.info>, jgen <jgen.tech@gmail.com>
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
	var $error;

	public function __construct($error) {
		$this->error = $error;
	}
}

class Pools extends Extension {

	public function onInitExt(InitExtEvent $event) {
		global $config, $database;

		// Create the database tables
		if ($config->get_int("ext_pools_version") < 1){
			$database->create_table("pools", "
					id SCORE_AIPK,
					user_id INTEGER NOT NULL,
					public SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
					title VARCHAR(255) NOT NULL,
					description TEXT,
					date DATETIME NOT NULL,
					posts INTEGER NOT NULL DEFAULT 0,
					INDEX (id)
					");
			$database->create_table("pool_images", "
					pool_id INTEGER NOT NULL,
					image_id INTEGER NOT NULL,
					image_order INTEGER NOT NULL DEFAULT 0
					");
			$database->create_table("pool_history", "
					id SCORE_AIPK,
					pool_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					action INTEGER NOT NULL,
					images TEXT,
					count INTEGER NOT NULL DEFAULT 0,
					date DATETIME NOT NULL,
					INDEX (id)
					");

			// Set the defaults for the pools extension
			$config->set_int("ext_pools_version", 1);

			$config->set_int("poolsMaxImportResults", 1000);
			$config->set_int("poolsImagesPerPage", 20);
			$config->set_int("poolsListsPerPage", 20);
			$config->set_int("poolsUpdatedPerPage", 20);
			$config->set_bool("poolsInfoOnViewImage", "N");
			$config->set_bool("poolsAdderOnViewImage", "N");
			$config->set_bool("poolsShowNextLink","N");

			log_info("pools", "extension installed");
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
		$sb->add_bool_option("poolsShowNextLink", "<br>Show 'Next' link when viewing pool images: ");
		//$sb->add_bool_option("poolsAdderOnViewImage", "<br>Show pool adder on image: ");
		$event->panel->add_block($sb);
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $config, $page, $user;
		
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
						$this->theme->display_error($errMessage);
					}
					break;

				case "create": // ADD _POST
					try {
						$newPoolID = $this->add_pool();
						$page->set_mode("redirect");
						$page->set_redirect(make_link("pool/view/".$newPoolID));
					}
					catch(PoolCreationException $e) {
						$this->theme->display_error($e->error);
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
							$this->theme->display_error("Permssion denied.");
						}
					}
					break;

				case "import":
					if ($this->have_permission($user, $pool)) {
						$this->import_posts($pool_id);
					} else {
						$this->theme->display_error("Permssion denied.");
					}
					break;

				case "add_posts":
					if ($this->have_permission($user, $pool)) {
						$this->add_posts();
						$page->set_mode("redirect");
						$page->set_redirect(make_link("pool/view/".$pool_id));
					} else {
						$this->theme->display_error("Permssion denied.");
					}
					break;

				case "remove_posts":
					if ($this->have_permission($user, $pool)) {
						$this->remove_posts();
						$page->set_mode("redirect");
						$page->set_redirect(make_link("pool/view/".$pool_id));
					} else {
						$this->theme->display_error("Permssion denied.");
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
						$this->theme->display_error("Permssion denied.");
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
	 */
	public function onDisplayingImage(DisplayingImageEvent $event) {
		global $config, $database, $page;

		if($config->get_bool("poolsInfoOnViewImage")) {
			$imageID = $event->image->id;
			$poolsIDs = $this->get_pool_id($imageID);
			
			$show_next = $config->get_bool("poolsShowNextLink", false);

			$linksPools = array();
			foreach($poolsIDs as $poolID) {
				$pools = $this->get_pool($poolID['pool_id']);
				foreach ($pools as $pool){
					$linksPools[] = "<a href='".make_link("pool/view/".$pool['id'])."'>".html_escape($pool['title'])."</a>";
					
					// Optionally show a link the Next image in the Pool.
					if ($show_next) {
						$next_image_in_pool = $this->get_next_post($pool, $imageID);
						if (!empty($next_image_in_pool)) {
							$linksPools[] = '<a href="'.make_link('post/view/'.$next_image_in_pool).'" class="pools_next_img">Next</a>';
						}
					}
				}
			}
			$this->theme->pool_info($linksPools);
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

	/* ------------------------------------------------- */
	/* --------------  Private Functions  -------------- */
	/* ------------------------------------------------- */
	
	/**
	 * Check if the given user has permission to edit/change the pool.
	 * TODO: Should the user variable be global?
	 * @retval bool
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
	
	/*
	 * HERE WE GET THE LIST OF POOLS
	 */
	private function list_pools(Page $page, /*int*/ $pageNumber) {
		global $config, $database;

		if(is_null($pageNumber) || !is_numeric($pageNumber))
			$pageNumber = 0;
		else if ($pageNumber <= 0)
			$pageNumber = 0;
		else
			$pageNumber--;

		$poolsPerPage = $config->get_int("poolsListsPerPage");

		$pools = $database->get_all("
				SELECT p.id, p.user_id, p.public, p.title, p.description,
				       p.posts, u.name as user_name
				FROM pools AS p
				INNER JOIN users AS u
				ON p.user_id = u.id
				ORDER BY p.date DESC
				LIMIT :l OFFSET :o
				", array("l"=>$poolsPerPage, "o"=>$pageNumber * $poolsPerPage)
				);

		$totalPages = ceil($database->get_one("SELECT COUNT(*) FROM pools") / $poolsPerPage);

		$this->theme->list_pools($page, $pools, $pageNumber + 1, $totalPages);
	}


	/*
	 * HERE WE CREATE A NEW POOL
	 */
	private function add_pool() {
		global $user, $database;

		if($user->is_anonymous()) {
			throw new PoolCreationException("You must be registered and logged in to add a image.");
		}
		if(empty($_POST["title"])) {
			throw new PoolCreationException("Pool needs a title");
		}

		$public = $_POST["public"] == "Y" ? "Y" : "N";
		$database->execute("
				INSERT INTO pools (user_id, public, title, description, date)
				VALUES (:uid, :public, :title, :desc, now())",
				array("uid"=>$user->id, "public"=>$public, "title"=>$_POST["title"], "desc"=>$_POST["description"]));
		
		//$result = $database->get_row("SELECT LAST_INSERT_ID() AS poolID"); # FIXME database specific?
		$result['poolID'] = $database->get_last_insert_id();

		log_info("pools", "Pool {$result["poolID"]} created by {$user->name}");

		return $result["poolID"];
	}

	/**
	 * Retrieve information about pools given mulitiple pool IDs.
	 * @param $poolID Array of integers
	 * @retval 2D Array
	 */
	private function get_pool(/*int*/ $poolID) {
		global $database;
		return $database->get_all("SELECT * FROM pools WHERE id=:id", array("id"=>$poolID));
	}
	
	/**
	 * Retrieve information about a pool given a pool ID.
	 * @param $poolID Integer
	 * @retval 2D array (with only 1 element in the one dimension)
	 */
	private function get_single_pool(/*int*/ $poolID) {
		global $database;
		return $database->get_row("SELECT * FROM pools WHERE id=:id", array("id"=>$poolID));
	}

	/**
	 * Get all of the pool IDs that an image is in, given an image ID.
	 * @param $imageID Integer
	 * @retval 2D array
	 */
	private function get_pool_id(/*int*/ $imageID) {
		global $database;
		return $database->get_all("SELECT pool_id FROM pool_images WHERE image_id=:iid", array("iid"=>$imageID));
	}


	/*
	 * HERE WE GET THE IMAGES FROM THE TAG ON IMPORT
	 */
	private function import_posts(/*int*/ $pool_id) {
		global $page, $config, $database;

		$poolsMaxResults = $config->get_int("poolsMaxImportResults", 1000);
		
		$images = $images = Image::find_images(0, $poolsMaxResults, Tag::explode($_POST["pool_tag"]));
		$this->theme->pool_result($page, $images, $pool_id);
	}


	/*
	 * HERE WE ADD CHECKED IMAGES FROM POOL AND UPDATE THE HISTORY
	 *
	 * TODO: Fix this so that the pool ID and images are passed as Arguments to the function.
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
			$count = $database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", array("pid"=>$poolID));
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

	/*
	 * TODO: Fix this so that the pool ID and images are passed as Arguments to the function.
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


	/*
	 * HERE WE REMOVE CHECKED IMAGES FROM POOL AND UPDATE THE HISTORY
	 *
	 * TODO: Fix this so that the pool ID and images are passed as Arguments to the function.
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
	 * This function checks if a given image is contained within a given pool.
	 * Used by add_posts()
	 *
	 * @see add_posts()
	 * @param $poolID integer
	 * @param $imageID integer
	 * @retval bool
	 */
	private function check_post(/*int*/ $poolID, /*int*/ $imageID) {
		global $database;
		$result = $database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid AND image_id=:iid", array("pid"=>$poolID, "iid"=>$imageID));
		return ($result != 0);
	}

	/**
	 * Gets the next successive image from a pool, given a pool ID and an image ID.
	 *
	 * @param $pool Array for the given pool
	 * @param $imageID Integer
	 * @retval Integer which is the next Image ID or NULL if none.
	 */
	private function get_next_post(/*array*/ $pool, /*int*/ $imageID) {
		global $database;

		if (empty($pool) || empty($imageID))
			return null;
		
		$result = $database->get_one("
					SELECT image_id
					FROM pool_images
					WHERE pool_id=:pid
					AND image_order > (SELECT image_order FROM pool_images WHERE pool_id=:pid AND image_id =:iid LIMIT 1 )
					ORDER BY image_order ASC LIMIT 1",
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
		if(class_exists("Ratings")) {
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
	 * @param $poolID integer
	 * @retval Array of image objects.
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


	/*
	 * WE GET THE ORDER OF THE IMAGES BUT HERE WE SEND KEYS ADDED IN ARRAY TO GET THE ORDER IN THE INPUT VALUE
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


	/*
	 * HERE WE NUKE ENTIRE POOL. WE REMOVE POOLS AND POSTS FROM REMOVED POOL AND HISTORIES ENTRIES FROM REMOVED POOL
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


	/*
	 * HERE WE ADD A HISTORY ENTRY
	 * FOR $action 1 (one) MEANS ADDED, 0 (zero) MEANS REMOVED
	 */
	private function add_history(/*int*/ $poolID, $action, $images, $count) {
		global $user, $database;
		$database->execute("
				INSERT INTO pool_history (pool_id, user_id, action, images, count, date)
				VALUES (:pid, :uid, :act, :img, :count, now())",
				array("pid"=>$poolID, "uid"=>$user->id, "act"=>$action, "img"=>$images, "count"=>$count));
	}


	/*
	 * HERE WE GET THE HISTORY LIST
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



	/*
	 * HERE GO BACK IN HISTORY AND ADD OR REMOVE POSTS TO POOL
	 */
	private function revert_history(/*int*/ $historyID) {
		global $database;
		$status = $database->get_all("SELECT * FROM pool_history WHERE id=:hid", array("hid"=>$historyID));

		foreach($status as $entry) {
			$images = trim($entry['images']);
			$images = explode(" ", $images);
			$poolID = $entry['pool_id'];
			$imageArray = "";

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
			}

			$count = $database->get_one("SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid", array("pid"=>$poolID));
			$this->add_history($poolID, $newAction, $imageArray, $count);
		}
	}



	/*
	 * HERE WE ADD A SIMPLE POST FROM POOL
	 * USED WITH FOREACH IN revert_history()
	 */
	private function add_post(/*int*/ $poolID, /*int*/ $imageID) {
		global $database;

		if(!$this->check_post($poolID, $imageID)) {
			$database->execute("
					INSERT INTO pool_images (pool_id, image_id)
					VALUES (:pid, :iid)",
					array("pid"=>$poolID, "iid"=>$imageID));
		}

		$database->execute("UPDATE pools SET posts=(SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid) WHERE id=:pid", array("pid"=>$poolID));
	}



	/*
	 * HERE WE REMOVE A SIMPLE POST FROM POOL
	 * USED WITH FOREACH IN revert_history()
	 */
	private function delete_post(/*int*/ $poolID, /*int*/ $imageID) {
		global $database;

		$database->execute("DELETE FROM pool_images WHERE pool_id = :pid AND image_id = :iid", array("pid"=>$poolID, "iid"=>$imageID));
		$database->execute("UPDATE pools SET posts=(SELECT COUNT(*) FROM pool_images WHERE pool_id=:pid) WHERE id=:pid", array("pid"=>$poolID));
	}

}
?>
