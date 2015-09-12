<?php
/*
 * Name: Source History
 * Author: Shish, copied from Source History
 * Description: Keep a record of source changes, and allows you to revert changes.
 */

class Source_History extends Extension {
	// in before source are actually set, so that "get current source" works
	public function get_priority() {return 40;}

	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_int("history_limit", -1);

		// shimmie is being installed so call install to create the table.
		if($config->get_int("ext_source_history_version") < 3) {
			$this->install();
		}
	}

	public function onAdminBuilding(AdminBuildingEvent $event) {
		$this->theme->display_admin_block();
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;

		if($event->page_matches("source_history/revert")) {
			// this is a request to revert to a previous version of the source
			if($user->can("edit_image_tag")) {
				if(isset($_POST['revert'])) {
					$this->process_revert_request($_POST['revert']);
				}
			}
		}
		else if($event->page_matches("source_history/bulk_revert")) {
			if($user->can("bulk_edit_image_tag") && $user->check_auth_token()) {
				$this->process_bulk_revert_request();
			}
		}
		else if($event->page_matches("source_history/all")) {
			$page_id = int_escape($event->get_arg(0));
			$this->theme->display_global_page($page, $this->get_global_source_history($page_id), $page_id);
		}
		else if($event->page_matches("source_history") && $event->count_args() == 1) {
			// must be an attempt to view a source history
			$image_id = int_escape($event->get_arg(0));
			$this->theme->display_history_page($page, $image_id, $this->get_source_history_from_id($image_id));
		}
	}
	
	public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event) {
		$event->add_part("
			<form action='".make_link("source_history/{$event->image->id}")."' method='GET'>
				<input type='submit' value='View Source History'>
			</form>
		", 20);
	}

	/*
	// disk space is cheaper than manually rebuilding history,
	// so let's default to -1 and the user can go advanced if
	// they /really/ want to
	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Source History");
		$sb->add_label("Limit to ");
		$sb->add_int_option("history_limit");
		$sb->add_label(" entires per image");
		$sb->add_label("<br>(-1 for unlimited)");
		$event->panel->add_block($sb);
	}
	*/

	public function onSourceSet(SourceSetEvent $event) {
		$this->add_source_history($event->image, $event->source);
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		global $user;
		if($user->can("bulk_edit_image_tag")) {
			$event->add_link("Source Changes", make_link("source_history/all/1"));
		}
	}
	
	protected function install() {
		global $database, $config;

		if($config->get_int("ext_source_history_version") < 1) {
			$database->create_table("source_histories", "
	    		id SCORE_AIPK,
	    		image_id INTEGER NOT NULL,
				user_id INTEGER NOT NULL,
				user_ip SCORE_INET NOT NULL,
	    		source TEXT NOT NULL,
				date_set SCORE_DATETIME NOT NULL,
				FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
				FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
			");
			$database->execute("CREATE INDEX source_histories_image_id_idx ON source_histories(image_id)", array());
			$config->set_int("ext_source_history_version", 3);
		}
		
		if($config->get_int("ext_source_history_version") == 1) {
			$database->Execute("ALTER TABLE source_histories ADD COLUMN user_id INTEGER NOT NULL");
			$database->Execute("ALTER TABLE source_histories ADD COLUMN date_set DATETIME NOT NULL");
			$config->set_int("ext_source_history_version", 2);
		}

		if($config->get_int("ext_source_history_version") == 2) {
			$database->Execute("ALTER TABLE source_histories ADD COLUMN user_ip CHAR(15) NOT NULL");
			$config->set_int("ext_source_history_version", 3);
		}
	}

	/**
	 * This function is called when a revert request is received.
	 * @param int $revert_id
	 */
	private function process_revert_request($revert_id) {
		global $page;

		$revert_id = int_escape($revert_id);

		// check for the nothing case
		if($revert_id < 1) {
			$page->set_mode("redirect");
			$page->set_redirect(make_link());
			return;
		}
		
		// lets get this revert id assuming it exists
		$result = $this->get_source_history_from_revert($revert_id);
		
		if(empty($result)) {
			// there is no history entry with that id so either the image was deleted
			// while the user was viewing the history, someone is playing with form
			// variables or we have messed up in code somewhere.
			/* calling die() is probably not a good idea, we should throw an Exception */
			die("Error: No source history with specified id was found.");
		}
		
		// lets get the values out of the result
		//$stored_result_id = $result['id'];
		$stored_image_id = $result['image_id'];
		$stored_source = $result['source'];
		
		log_debug("source_history", 'Reverting source of Image #'.$stored_image_id.' to ['.$stored_source.']');

		$image = Image::by_id($stored_image_id);
		
		if (is_null($image)) {
			die('Error: No image with the id ('.$stored_image_id.') was found. Perhaps the image was deleted while processing this request.');
		}

		// all should be ok so we can revert by firing the SetUserSources event.
		send_event(new SourceSetEvent($image, $stored_source));
		
		// all should be done now so redirect the user back to the image
		$page->set_mode("redirect");
		$page->set_redirect(make_link('post/view/'.$stored_image_id));
	}

	protected function process_bulk_revert_request() {
		if (isset($_POST['revert_name']) && !empty($_POST['revert_name'])) {
			$revert_name = $_POST['revert_name'];
		}
		else {
			$revert_name = null;
		}

		if (isset($_POST['revert_ip']) && !empty($_POST['revert_ip'])) {
			$revert_ip = filter_var($_POST['revert_ip'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE);
			
			if ($revert_ip === false) {
				// invalid ip given.
				$this->theme->display_admin_block('Invalid IP');
				return;
			}
		}
		else {
			$revert_ip = null;
		}
		
		if (isset($_POST['revert_date']) && !empty($_POST['revert_date'])) {
			if (isValidDate($_POST['revert_date']) ){
				$revert_date = addslashes($_POST['revert_date']); // addslashes is really unnecessary since we just checked if valid, but better safe.
			}
			else {
				$this->theme->display_admin_block('Invalid Date');
				return;
			}
		}
		else {
			$revert_date = null;
		}
		
		set_time_limit(0); // reverting changes can take a long time, disable php's timelimit if possible.
		
		// Call the revert function.
		$this->process_revert_all_changes($revert_name, $revert_ip, $revert_date);
		// output results
		$this->theme->display_revert_ip_results();
	}

	/**
	 * @param int $revert_id
	 * @return mixed|null
	 */
	public function get_source_history_from_revert(/*int*/ $revert_id) {
		global $database;
		$row = $database->get_row("
				SELECT source_histories.*, users.name
				FROM source_histories
				JOIN users ON source_histories.user_id = users.id
				WHERE source_histories.id = ?", array($revert_id));
		return ($row ? $row : null);
	}

	/**
	 * @param int $image_id
	 * @return array
	 */
	public function get_source_history_from_id(/*int*/ $image_id) {
		global $database;
		$row = $database->get_all("
				SELECT source_histories.*, users.name
				FROM source_histories
				JOIN users ON source_histories.user_id = users.id
				WHERE image_id = ?
				ORDER BY source_histories.id DESC",
				array($image_id));
		return ($row ? $row : array());
	}

	/**
	 * @param int $page_id
	 * @return array
	 */
	public function get_global_source_history($page_id) {
		global $database;
		$row = $database->get_all("
				SELECT source_histories.*, users.name
				FROM source_histories
				JOIN users ON source_histories.user_id = users.id
				ORDER BY source_histories.id DESC
				LIMIT 100 OFFSET :offset
		", array("offset" => ($page_id-1)*100));
		return ($row ? $row : array());
	}

	/**
	 * This function attempts to revert all changes by a given IP within an (optional) timeframe.
	 *
	 * @param string $name
	 * @param string $ip
	 * @param string $date
	 */
	public function process_revert_all_changes($name, $ip, $date) {
		global $database;
		
		$select_code = array();
		$select_args = array();

		if(!is_null($name)) {
			$duser = User::by_name($name);
			if(is_null($duser)) {
				$this->theme->add_status($name, "user not found");
				return;
			}
			else {
				$select_code[] = 'user_id = ?';
				$select_args[] = $duser->id;
			}
		}

		if(!is_null($date)) {
			$select_code[] = 'date_set >= ?';
			$select_args[] = $date;
		}

		if(!is_null($ip)) {
			$select_code[] = 'user_ip = ?';
			$select_args[] = $ip;
		}

		if(count($select_code) == 0) {
			log_error("source_history", "Tried to mass revert without any conditions");
			return;
		}

		log_info("source_history", 'Attempting to revert edits where '.implode(" and ", $select_code)." (".implode(" / ", $select_args).")");
		
		// Get all the images that the given IP has changed source on (within the timeframe) that were last editied by the given IP
		$result = $database->get_col('
				SELECT t1.image_id
				FROM source_histories t1
				LEFT JOIN source_histories t2 ON (t1.image_id = t2.image_id AND t1.date_set < t2.date_set)
				WHERE t2.image_id IS NULL
				AND t1.image_id IN ( select image_id from source_histories where '.implode(" AND ", $select_code).') 
				ORDER BY t1.image_id
		', $select_args);
	
		foreach($result as $image_id) {
			// Get the first source history that was done before the given IP edit
			$row = $database->get_row('
				SELECT id, source
				FROM source_histories
				WHERE image_id='.$image_id.'
				AND NOT ('.implode(" AND ", $select_code).')
				ORDER BY date_set DESC LIMIT 1
			', $select_args);
			
			if (empty($row)) {
				// we can not revert this image based on the date restriction.
				// Output a message perhaps?
			}
			else {
				$revert_id = $row['id'];
				$result = $this->get_source_history_from_revert($revert_id);
				
				if(empty($result)) {
					// there is no history entry with that id so either the image was deleted
					// while the user was viewing the history,  or something messed up
					/* calling die() is probably not a good idea, we should throw an Exception */
					die('Error: No source history with specified id ('.$revert_id.') was found in the database.'."\n\n".
						'Perhaps the image was deleted while processing this request.');
				}
				
				// lets get the values out of the result
				$stored_result_id = $result['id'];
				$stored_image_id = $result['image_id'];
				$stored_source = $result['source'];
				
				log_debug("source_history", 'Reverting source of Image #'.$stored_image_id.' to ['.$stored_source.']');

				$image = Image::by_id($stored_image_id);

				if (is_null($image)) {
					die('Error: No image with the id ('.$stored_image_id.') was found. Perhaps the image was deleted while processing this request.');
				}

				// all should be ok so we can revert by firing the SetSources event.
				send_event(new SourceSetEvent($image, $stored_source));
				$this->theme->add_status('Reverted Change','Reverted Image #'.$image_id.' to Source History #'.$stored_result_id.' ('.$row['source'].')');
			}
		}

		log_info("source_history", 'Reverted '.count($result).' edits.');
	}

	/**
	 * This function is called just before an images source is changed.
	 * @param Image $image
	 * @param string $source
	 */
	private function add_source_history($image, $source) {
		global $database, $config, $user;

		$new_source = $source;
		$old_source = $image->source;
		
		if($new_source == $old_source) return;
		
		if(empty($old_source)) {
			/* no old source, so we are probably adding the image for the first time */
			log_debug("source_history", "adding new source history: [$new_source]");
		}
		else {
			log_debug("source_history", "adding source history: [$old_source] -> [$new_source]");
		}
		
		$allowed = $config->get_int("history_limit");
		if($allowed == 0) return;
		
		// if the image has no history, make one with the old source
		$entries = $database->get_one("SELECT COUNT(*) FROM source_histories WHERE image_id = ?", array($image->id));
		if($entries == 0 && !empty($old_source)) {
			$database->execute("
				INSERT INTO source_histories(image_id, source, user_id, user_ip, date_set)
				VALUES (?, ?, ?, ?, now())",
				array($image->id, $old_source, $config->get_int('anon_id'), '127.0.0.1'));
			$entries++;
		}

		// add a history entry	
		$database->execute("
				INSERT INTO source_histories(image_id, source, user_id, user_ip, date_set)
				VALUES (?, ?, ?, ?, now())",
				array($image->id, $new_source, $user->id, $_SERVER['REMOTE_ADDR']));
		$entries++;
		
		// if needed remove oldest one
		if($allowed == -1) return;
		if($entries > $allowed) {
			// TODO: Make these queries better
			/*
				MySQL does NOT allow you to modify the same table which you use in the SELECT part.
				Which means that these will probably have to stay as TWO separate queries...
				
				http://dev.mysql.com/doc/refman/5.1/en/subquery-restrictions.html
				http://stackoverflow.com/questions/45494/mysql-error-1093-cant-specify-target-table-for-update-in-from-clause
			*/
			$min_id = $database->get_one("SELECT MIN(id) FROM source_histories WHERE image_id = ?", array($image->id));
			$database->execute("DELETE FROM source_histories WHERE id = ?", array($min_id));
		}
	}
}

