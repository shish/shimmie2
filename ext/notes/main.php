<?php
/**
 * Name: [Beta] Notes
 * Author: Sein Kraft <mail@seinkraft.info>
 * License: GPLv2
 * Description: Annotate images
 * Documentation:
 */

class Notes extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config, $database;

		// shortcut to latest
		if ($config->get_int("ext_notes_version") < 1) {
			$database->Execute("ALTER TABLE images ADD COLUMN notes INTEGER NOT NULL DEFAULT 0");
			$database->create_table("notes", "
					id SCORE_AIPK,
					enable INTEGER NOT NULL,
					image_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					user_ip CHAR(15) NOT NULL,
					date SCORE_DATETIME NOT NULL,
					x1 INTEGER NOT NULL,
					y1 INTEGER NOT NULL,
					height INTEGER NOT NULL,
					width INTEGER NOT NULL,
					note TEXT NOT NULL,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
					FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE
					");
			$database->execute("CREATE INDEX notes_image_id_idx ON notes(image_id)", array());
			
			$database->create_table("note_request", "
					id SCORE_AIPK,
					image_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					date SCORE_DATETIME NOT NULL,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
					FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE
					");
			$database->execute("CREATE INDEX note_request_image_id_idx ON note_request(image_id)", array());
					
			$database->create_table("note_histories", "
					id SCORE_AIPK,
					note_enable INTEGER NOT NULL,
					note_id INTEGER NOT NULL,
					review_id INTEGER NOT NULL,
					image_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					user_ip CHAR(15) NOT NULL,
					date SCORE_DATETIME NOT NULL,
					x1 INTEGER NOT NULL,
					y1 INTEGER NOT NULL,
					height INTEGER NOT NULL,
					width INTEGER NOT NULL,
					note TEXT NOT NULL,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
					FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE
					");
			$database->execute("CREATE INDEX note_histories_image_id_idx ON note_histories(image_id)", array());

			$config->set_int("notesNotesPerPage", 20);
			$config->set_int("notesRequestsPerPage", 20);
			$config->set_int("notesHistoriesPerPage", 20);

			$config->set_int("ext_notes_version", 1);
			log_info("notes", "extension installed");
		}
	}
	
	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;
		if($event->page_matches("note")) {
			
			switch($event->get_arg(0)) {
				case "list": //index
				{
					$this->get_notes_list($event); // This should show images like post/list but i don't know how do that.
					break;
				}
				case "requests": // The same as post/list but only for note_request table.
				{
					$this->get_notes_requests($event); // This should shouw images like post/list but i don't know how do that.
					break;
				}
				case "search":
				{
					if(!$user->is_anonymous())
						$this->theme->search_notes_page($page);
					break;
				}
				case "updated": //Thinking how biuld this function. 
				{
					$this->get_histories($event);
					break;
				}
				case "history": //Thinking how biuld this function. 
				{
					$this->get_history($event);
					break;
				}
				case "revert":
				{
					$noteID = $event->get_arg(1);
					$reviewID = $event->get_arg(2);
					if(!$user->is_anonymous()){
						$this->revert_history($noteID, $reviewID);
					}
					
					$page->set_mode("redirect");
					$page->set_redirect(make_link("note/updated"));
					break;
				}
				case "add_note":
				{
					if(!$user->is_anonymous())
						$this->add_new_note();
						
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/view/".$_POST["image_id"]));
					break;
				}
				case "add_request":
				{
					if(!$user->is_anonymous())
						$this->add_note_request();
						
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/view/".$_POST["image_id"]));
					break;
				}
				case "nuke_notes":
				{
					if($user->is_admin())
						$this->nuke_notes();
						
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/view/".$_POST["image_id"]));
					break;
				}
				case "nuke_requests":
				{
					if($user->is_admin())
						$this->nuke_requests();
						
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/view/".$_POST["image_id"]));
					break;
				}
				case "edit_note":
				{
					if (!$user->is_anonymous()) {
						$this->update_note();
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/view/".$_POST["image_id"]));
					}
				break;
				}
				case "delete_note":
				{
					if ($user->is_admin()) {
						$this->delete_note();
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/view/".$_POST["image_id"]));
					}
				break;
				}
				default:
				{
					$page->set_mode("redirect");
					$page->set_redirect(make_link("note/list"));
					break;
				}
			}
		}
	}


	/*
	 * HERE WE LOAD THE NOTES IN THE IMAGE
	 */
	public function onDisplayingImage(DisplayingImageEvent $event) {
		global $page, $user;

		//display form on image event
        $notes = $this->get_notes($event->image->id);
		$this->theme->display_note_system($page, $event->image->id, $notes, $user->is_admin());
	}


	/*
	 * HERE WE ADD THE BUTTONS ON SIDEBAR
	 */
	public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event) {
		global $user;
		if(!$user->is_anonymous()) {
			$event->add_part($this->theme->note_button($event->image->id));
			$event->add_part($this->theme->request_button($event->image->id));
			if($user->is_admin()) {
				$event->add_part($this->theme->nuke_notes_button($event->image->id));
				$event->add_part($this->theme->nuke_requests_button($event->image->id));
			}
		}
	}


	/*
	 * HERE WE ADD QUERYLETS TO ADD SEARCH SYSTEM
	 */
	public function onSearchTermParse(SearchTermParseEvent $event) {
		$matches = array();
		if(preg_match("/^note[=|:](.*)$/i", $event->term, $matches)) {
			$notes = int_escape($matches[1]);
			$event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM notes WHERE note = $notes)"));
		}
		else if(preg_match("/^notes([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)%/i", $event->term, $matches)) {
			$cmp = ltrim($matches[1], ":") ?: "=";
			$notes = $matches[2];
			$event->add_querylet(new Querylet("images.id IN (SELECT id FROM images WHERE notes $cmp $notes)"));
		}
		else if(preg_match("/^notes_by[=|:](.*)$/i", $event->term, $matches)) {
			$user = User::by_name($matches[1]);
			if(!is_null($user)) {
				$user_id = $user->id;
			}
			else {
				$user_id = -1;
			}

			$event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM notes WHERE user_id = $user_id)"));
		}
		else if(preg_match("/^notes_by_userno[=|:](\d+)$/i", $event->term, $matches)) {
			$user_id = int_escape($matches[1]);
			$event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM notes WHERE user_id = $user_id)"));
		}
	}


	/**
	 * HERE WE GET ALL NOTES FOR DISPLAYED IMAGE.
	 *
	 * @param int $imageID
	 * @return array
	 */
	private function get_notes($imageID) {
		global $database;

		return $database->get_all(
			"SELECT * ".
			"FROM notes ".
			"WHERE enable = ? AND image_id = ? ".
			"ORDER BY date ASC"
			, array('1', $imageID));
	}


	/*
	 * HERE WE ADD A NOTE TO DATABASE
	 */
	private function add_new_note() {
		global $database, $user;

		$imageID    = int_escape($_POST["image_id"]);
		$user_id    = $user->id;
		$noteX1     = int_escape($_POST["note_x1"]);
		$noteY1     = int_escape($_POST["note_y1"]);
		$noteHeight = int_escape($_POST["note_height"]);
		$noteWidth  = int_escape($_POST["note_width"]);
		$noteText   = html_escape($_POST["note_text"]);

		$database->execute("
				INSERT INTO notes
				(enable, image_id, user_id, user_ip, date, x1, y1, height, width, note)
				VALUES
				(?, ?, ?, ?, now(), ?, ?, ?, ?, ?)",
				array(1, $imageID, $user_id, $_SERVER['REMOTE_ADDR'], $noteX1, $noteY1, $noteHeight, $noteWidth, $noteText));

		$noteID = $database->get_last_insert_id('notes_id_seq');

		log_info("notes", "Note added {$noteID} by {$user->name}");

		$database->Execute("UPDATE images SET notes=(SELECT COUNT(*) FROM notes WHERE image_id=?) WHERE id=?", array($imageID, $imageID));

		$this->add_history(1, $noteID, $imageID, $noteX1, $noteY1, $noteHeight, $noteWidth, $noteText);
	}
	
	
	
	/*
	 * HERE WE ADD A REQUEST TO DATABASE
	 */
	private function add_note_request() {
		global $database, $user;

		$image_id = int_escape($_POST["image_id"]);
		$user_id = $user->id;

		$database->execute("
				INSERT INTO note_request
				(image_id, user_id, date)
				VALUES
				(?, ?, now())",
				array($image_id, $user_id));

		$resultID = $database->get_last_insert_id('note_request_id_seq');

		log_info("notes", "Note requested {$requestID} by {$user->name}");
	}
	
	
	
	/*
	* HERE WE EDIT THE NOTE
	*/
	private function update_note()
	{
		$imageID = int_escape($_POST["image_id"]);
		$noteID  = int_escape($_POST["note_id"]);
		$noteX1 = int_escape($_POST["note_x1"]);
		$noteY1 = int_escape($_POST["note_y1"]);
		$noteHeight = int_escape($_POST["note_height"]);
		$noteWidth = int_escape($_POST["note_width"]);
		$noteText = sql_escape(html_escape($_POST["note_text"]));

		// validate parameters
		if (is_null($imageID)    || !is_numeric($imageID)        ||
			is_null($noteID)     || !is_numeric($noteID)         ||
			is_null($noteX1)     || !is_numeric($noteX1)         ||
			is_null($noteY1)     || !is_numeric($noteY1)         ||
			is_null($noteHeight) || !is_numeric($noteHeight)     ||
			is_null($noteWidth)  || !is_numeric($noteWidth)      ||
			is_null($noteText)   || strlen($noteText) == 0)
		{
			return;
		}

		global $database;
		$database->execute("UPDATE notes ".
			"SET x1 = ?, ".
			"y1 = ?, ".
			"height = ?, ".
			"width = ?,".
			"note = ? ".
			"WHERE image_id = ? AND id = ?", array($noteX1, $noteY1, $noteHeight, $noteWidth, $noteText, $imageID, $noteID));
			
		$this->add_history(1, $noteID, $imageID, $noteX1, $noteY1, $noteHeight, $noteWidth, $noteText);
	}


	/*
	* HERE WE DELETE THE NOTE
	*/
	private function delete_note()
	{
		global $user;

		$imageID = int_escape($_POST["image_id"]);
		$noteID = int_escape($_POST["note_id"]);

		// validate parameters
		if( is_null($imageID) || !is_numeric($imageID) ||
			is_null($noteID)  || !is_numeric($noteID))
		{
			return;
		}

		global $database;
		
		$database->execute("UPDATE notes ".
			"SET enable = ? ".
			"WHERE image_id = ? AND id = ?", array(0, $imageID, $noteID));
		
		log_info("notes", "Note deleted {$noteID} by {$user->name}");
	}



	/*
	* HERE WE DELETE ALL NOTES FROM IMAGE
	*/
	private function nuke_notes() {
		global $database, $user;
		$image_id = int_escape($_POST["image_id"]);
		$database->execute("DELETE FROM notes WHERE image_id = ?", array($image_id));
		log_info("notes", "Notes deleted from {$image_id} by {$user->name}");
	}
	
	
	
	/*
	* HERE WE DELETE ALL REQUESTS FOR IMAGE
	*/
	private function nuke_requests() {
		global $database, $user;
		$image_id = int_escape($_POST["image_id"]);

		$database->execute("DELETE FROM note_request WHERE image_id = ?", array($image_id));

		log_info("notes", "Requests deleted from {$image_id} by {$user->name}");
	}

	/**
	 * HERE WE ALL IMAGES THAT HAVE NOTES
	 * @param PageRequestEvent $event
	 */
	private function get_notes_list(PageRequestEvent $event) {
		global $database, $config;

		$pageNumber = $event->get_arg(1);

		if(is_null($pageNumber) || !is_numeric($pageNumber))
		$pageNumber = 0;
		else if ($pageNumber <= 0)
		$pageNumber = 0;
		else
		$pageNumber--;

		$notesPerPage = $config->get_int('notesNotesPerPage');
	
		//$result = $database->get_all("SELECT * FROM pool_images WHERE pool_id=?", array($poolID));

		$get_notes = "
			SELECT DISTINCT image_id ".
			"FROM notes ".
			"WHERE enable = ? ".
			"ORDER BY date DESC LIMIT ?, ?";
			
		$result = $database->Execute($get_notes, array(1, $pageNumber * $notesPerPage, $notesPerPage));
		
		$totalPages = ceil($database->get_one("SELECT COUNT(DISTINCT image_id) FROM notes") / $notesPerPage);
		
		$images = array();
		while(!$result->EOF) {
			$image = Image::by_id($result->fields["image_id"]);
			$images[] = array($image);
			$result->MoveNext();
		}
		
		$this->theme->display_note_list($images, $pageNumber + 1, $totalPages);
	}

	/**
	 * HERE WE GET ALL NOTE REQUESTS
	 * @param PageRequestEvent $event
	 */
	private function get_notes_requests(PageRequestEvent $event) {
		global $config;

		$pageNumber = $event->get_arg(1);

		if(is_null($pageNumber) || !is_numeric($pageNumber))
			$pageNumber = 0;
		else if ($pageNumber <= 0)
			$pageNumber = 0;
		else
			$pageNumber--;

		$requestsPerPage = $config->get_int('notesRequestsPerPage');


		//$result = $database->get_all("SELECT * FROM pool_images WHERE pool_id=?", array($poolID));
		global $database;
		$get_requests = "
			SELECT DISTINCT image_id ".
			"FROM note_request ".
			"ORDER BY date DESC LIMIT ?, ?";
			
		$result = $database->Execute($get_requests, array($pageNumber * $requestsPerPage, $requestsPerPage));
		
		$totalPages = ceil($database->get_one("SELECT COUNT(*) FROM note_request") / $requestsPerPage);
		
		$images = array();
		while(!$result->EOF) {
			$image = Image::by_id($result->fields["image_id"]);
			$images[] = array($image);
			$result->MoveNext();
		}
		
		$this->theme->display_note_requests($images, $pageNumber + 1, $totalPages);
	}
	
	
	
	/*
	* HERE WE ADD HISTORY TO TRACK THE CHANGES OF THE NOTES FOR THE IMAGES.
	*/
	private function add_history($noteEnable, $noteID, $imageID, $noteX1, $noteY1, $noteHeight, $noteWidth, $noteText){
		global $user, $database;
		
		$userID = $user->id;
		
		$reviewID = $database->get_one("SELECT COUNT(*) FROM note_histories WHERE note_id = ?", array($noteID));
		$reviewID = $reviewID + 1;
	
		$database->execute("
							INSERT INTO note_histories
								(note_enable, note_id, review_id, image_id, user_id, user_ip, date, x1, y1, height, width, note)
							VALUES
								(?, ?, ?, ?, ?, ?, now(), ?, ?, ?, ?, ?)",
							array($noteEnable, $noteID, $reviewID, $imageID, $userID, $_SERVER['REMOTE_ADDR'], $noteX1, $noteY1, $noteHeight, $noteWidth, $noteText));
	}


	/**
	 * HERE WE GET ALL HISTORIES.
	 * @param PageRequestEvent $event
	 */
	private function get_histories(PageRequestEvent $event){
		$pageNumber = $event->get_arg(1);
            if(is_null($pageNumber) || !is_numeric($pageNumber))
                $pageNumber = 0;
            else if ($pageNumber <= 0)
                $pageNumber = 0;
            else
                $pageNumber--;

            global $config;
            
		$histiriesPerPage = $config->get_int('notesHistoriesPerPage');
		
		//ORDER BY IMAGE & DATE
		global $database;
		$histories = $database->get_all("SELECT h.note_id, h.review_id, h.image_id, h.date, h.note, u.name AS user_name ".
										"FROM note_histories AS h ".
										"INNER JOIN users AS u ".
										"ON u.id = h.user_id ".
										"ORDER BY date DESC LIMIT ?, ?",
										array($pageNumber * $histiriesPerPage, $histiriesPerPage));
		
		$totalPages = ceil($database->get_one("SELECT COUNT(*) FROM note_histories") / $histiriesPerPage);
		
		$this->theme->display_histories($histories, $pageNumber + 1, $totalPages);
	}


	/**
	 * HERE WE THE HISTORY FOR A SPECIFIC NOTE.
	 * @param PageRequestEvent $event
	 */
	private function get_history(PageRequestEvent $event){
		$noteID = $event->get_arg(1);
		$pageNumber = $event->get_arg(2);
            if(is_null($pageNumber) || !is_numeric($pageNumber))
                $pageNumber = 0;
            else if ($pageNumber <= 0)
                $pageNumber = 0;
            else
                $pageNumber--;

            global $config;
            
		$histiriesPerPage = $config->get_int('notesHistoriesPerPage');
		
		global $database;
		$histories = $database->get_all("SELECT h.note_id, h.review_id, h.image_id, h.date, h.note, u.name AS user_name ".
										"FROM note_histories AS h ".
										"INNER JOIN users AS u ".
										"ON u.id = h.user_id ".
										"WHERE note_id = ? ".
										"ORDER BY date DESC LIMIT ?, ?",
										array($noteID, $pageNumber * $histiriesPerPage, $histiriesPerPage));
					
		$totalPages = ceil($database->get_one("SELECT COUNT(*) FROM note_histories WHERE note_id = ?", array($noteID)) / $histiriesPerPage);
		
		$this->theme->display_history($histories, $pageNumber + 1, $totalPages);
	}

	/**
	 * HERE GO BACK IN HISTORY AND SET THE OLD NOTE. IF WAS REMOVED WE RE-ADD IT.
	 * @param int $noteID
	 * @param int $reviewID
	 */
	private function revert_history($noteID, $reviewID){
		global $database;
		
		$history = $database->get_row("SELECT * FROM note_histories WHERE note_id = ? AND review_id = ?",array($noteID, $reviewID));
		
		$noteEnable = $history['note_enable'];
		$noteID = $history['note_id'];
		$imageID = $history['image_id'];
		$noteX1 = $history['x1'];
		$noteY1 = $history['y1'];
		$noteHeight = $history['height'];
		$noteWidth = $history['width'];
		$noteText = $history['note'];
		
		$database->execute("UPDATE notes ".
						   "SET enable = ?, ".
						   "x1 = ?, ".
						   "y1 = ?, ".
						   "height = ?, ".
						   "width = ?,".
						   "note = ? ".
						   "WHERE image_id = ? AND id = ?", array(1, $noteX1, $noteY1, $noteHeight, $noteWidth, $noteText, $imageID, $noteID));
								  
		$this->add_history($noteEnable, $noteID, $imageID, $noteX1, $noteY1, $noteHeight, $noteWidth, $noteText);
	}
}

