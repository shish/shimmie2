<?php
/*
 * Name: Image Manager
 * Author: Shish <webmaster@shishnet.org>
 * Modified by: jgen <jgen.tech@gmail.com>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Handle the image database
 * Visibility: admin
 */

 /**
 * An image is being added to the database.
 */
class ImageAdditionEvent extends Event {
	var $user, $image;
	
	/**
	 * Inserts a new image into the database with its associated
	 * information. Also calls TagSetEvent to set the tags for
	 * this new image.
	 *
	 * @sa TagSetEvent
	 * @param $user	The user adding the image
	 * @param $image	The new image to add.
	 */
	public function ImageAdditionEvent(User $user, Image $image) {
		$this->image = $image;
		$this->user = $user;
	}
}

class ImageAdditionException extends SCoreException {
	var $error;

	public function __construct($error) {
		$this->error = $error;
	}
}

/**
 * An image is being deleted.
 */
class ImageDeletionEvent extends Event {
	var $image;
	
	/**
	 * Deletes an image.
	 * Used by things like tags and comments handlers to
	 * clean out related rows in their tables.
	 *
	 * @param $image 	The image being deleted
	*/
	public function ImageDeletionEvent(Image $image) {
		$this->image = $image;
	}
}

/**
 * An image is being replaced.
 */
class ImageReplaceEvent extends Event {
	var $id, $image;
	
	/**
	 * Replaces an image.
	 * Updates an existing ID in the database to use a new image
	 * file, leaving the tags and such unchanged. Also removes 
	 * the old image file and thumbnail from the disk.
	 *
	 * @param $id
	 *   The ID of the image to replace
	 * @param $image
	 *   The image object of the new image to use
	 */
	public function ImageReplaceEvent($id, Image $image) {
		$this->id = $id;
		$this->image = $image;
	}
}

class ImageReplaceException extends SCoreException {
	var $error;

	public function __construct($error) {
		$this->error = $error;
	}
}

/**
 * Request a thumbnail be made for an image object.
 */
class ThumbnailGenerationEvent extends Event {
	var $hash, $type, $force;

	/**
	 * Request a thumbnail be made for an image object
	 *
	 * @param $hash	The unique hash of the image
	 * @param $type	The type of the image
	 */
	public function ThumbnailGenerationEvent($hash, $type, $force=false) {
		$this->hash = $hash;
		$this->type = $type;
		$this->force = $force;
	}
}


/*
 * ParseLinkTemplateEvent:
 *   $link     -- the formatted link
 *   $original -- the formatting string, for reference
 *   $image    -- the image who's link is being parsed
 */
class ParseLinkTemplateEvent extends Event {
	var $link, $original, $image;

	public function ParseLinkTemplateEvent($link, Image $image) {
		$this->link = $link;
		$this->original = $link;
		$this->image = $image;
	}

	public function replace($needle, $replace) {
		$this->link = str_replace($needle, $replace, $this->link);
	}
}


/**
 * A class to handle adding / getting / removing image files from the disk.
 */
class ImageIO extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_int('thumb_width', 192);
		$config->set_default_int('thumb_height', 192);
		$config->set_default_int('thumb_quality', 75);
		$config->set_default_int('thumb_mem_limit', parse_shorthand_int('8MB'));
		$config->set_default_string('thumb_convert_path', 'convert.exe');

		if(function_exists("exif_read_data")) {
			$config->set_default_bool('image_show_meta', false);
		}
		$config->set_default_string('image_ilink', '');
		$config->set_default_string('image_tlink', '');
		$config->set_default_string('image_tip', '$tags // $size // $filesize');
		$config->set_default_string('upload_collision_handler', 'error');
		$config->set_default_int('image_expires', (60*60*24*31) );	// defaults to one month
	}

	public function onPageRequest(PageRequestEvent $event) {
		if($event->page_matches("image/delete")) {
			global $page, $user;
			if($user->can("delete_image") && isset($_POST['image_id']) && $user->check_auth_token()) {
				$image = Image::by_id($_POST['image_id']);
				if($image) {
					send_event(new ImageDeletionEvent($image));
					$page->set_mode("redirect");
					if(isset($_SERVER['HTTP_REFERER']) && !strstr($_SERVER['HTTP_REFERER'], 'post/view')) {
						$page->set_redirect($_SERVER['HTTP_REFERER']);
					}
					else {
						$page->set_redirect(make_link("post/list"));
					}
				}
			}
		}
		else if($event->page_matches("image/replace")) {
			global $page, $user;
			if($user->can("replace_image") && isset($_POST['image_id']) && $user->check_auth_token()) {
				$image = Image::by_id($_POST['image_id']);
				if($image) {
					$page->set_mode("redirect");
					$page->set_redirect(make_link('upload/replace/'.$image->id));
				} else {
					/* Invalid image ID */
					throw new ImageReplaceException("Image to replace does not exist.");
				}
			}
		}
		else if($event->page_matches("image")) {
			$num = int_escape($event->get_arg(0));
			$this->send_file($num, "image");
		}
		else if($event->page_matches("thumb")) {
			$num = int_escape($event->get_arg(0));
			$this->send_file($num, "thumb");
		}
	}

	public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event) {
		global $user;
		global $config;
		
		if($user->can("delete_image")) {
			$event->add_part($this->theme->get_deleter_html($event->image->id));
		}
		/* In the future, could perhaps allow users to replace images that they own as well... */
		if ($user->can("replace_image")) {
			$event->add_part($this->theme->get_replace_html($event->image->id));
		}
	}

	public function onImageAddition(ImageAdditionEvent $event) {
		try {
			$this->add_image($event->image);
		}
		catch(ImageAdditionException $e) {
			throw new UploadException($e->error);
		}
	}

	public function onImageDeletion(ImageDeletionEvent $event) {
		$event->image->delete();
	}

	public function onImageReplace(ImageReplaceEvent $event) {
		try {
			$this->replace_image($event->id, $event->image);
		}
		catch(ImageReplaceException $e) {
			throw new UploadException($e->error);
		}
	}
	
	public function onUserPageBuilding(UserPageBuildingEvent $event) {
		global $user;
		global $config;
	
		$u_id = url_escape($event->display_user->id);
		$i_image_count = Image::count_images(array("user_id={$event->display_user->id}"));
		$i_days_old = ((time() - strtotime($event->display_user->join_date)) / 86400) + 1;
		$h_image_rate = sprintf("%.1f", ($i_image_count / $i_days_old));
		$images_link = make_link("post/list/user_id=$u_id/1");
		$event->add_stats("<a href='$images_link'>Images uploaded</a>: $i_image_count, $h_image_rate per day");
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		global $config;

		$sb = new SetupBlock("Image Options");
		$sb->position = 30;
		// advanced only
		//$sb->add_text_option("image_ilink", "Image link: ");
		//$sb->add_text_option("image_tlink", "<br>Thumbnail link: ");
		$sb->add_text_option("image_tip", "Image tooltip: ");
		$sb->add_choice_option("upload_collision_handler", array('Error'=>'error', 'Merge'=>'merge'), "<br>Upload collision handler: ");
		if(function_exists("exif_read_data")) {
			$sb->add_bool_option("image_show_meta", "<br>Show metadata: ");
		}

		$event->panel->add_block($sb);

		$thumbers = array();
		$thumbers['Built-in GD'] = "gd";
		$thumbers['ImageMagick'] = "convert";

		$sb = new SetupBlock("Thumbnailing");
		$sb->add_choice_option("thumb_engine", $thumbers, "Engine: ");

		$sb->add_label("<br>Size ");
		$sb->add_int_option("thumb_width");
		$sb->add_label(" x ");
		$sb->add_int_option("thumb_height");
		$sb->add_label(" px at ");
		$sb->add_int_option("thumb_quality");
		$sb->add_label(" % quality ");

		$sb->add_shorthand_int_option("thumb_mem_limit", "<br>Max memory use: ");

		$event->panel->add_block($sb);
	}


// add image {{{
	private function add_image($image) {
		global $page, $user, $database, $config;

		/*
		 * Validate things
		 */
		if(strlen(trim($image->source)) == 0) {
			$image->source = null;
		}

		/*
		 * Check for an existing image
		 */
		$existing = Image::by_hash($image->hash);
		if(!is_null($existing)) {
			$handler = $config->get_string("upload_collision_handler");
			if($handler == "merge" || isset($_GET['update'])) {
				$merged = array_merge($image->get_tag_array(), $existing->get_tag_array());
				send_event(new TagSetEvent($existing, $merged));
				if(isset($_GET['rating']) && isset($_GET['update']) && file_exists("ext/rating")){
					send_event(new RatingSetEvent($existing, $user, $_GET['rating']));
				}
				if(isset($_GET['source']) && isset($_GET['update'])){
					send_event(new SourceSetEvent($existing, $_GET['source']));
				}
				return null;
			}
			else {
				$error = "Image <a href='".make_link("post/view/{$existing->id}")."'>{$existing->id}</a> ".
						"already has hash {$image->hash}:<p>".Themelet::build_thumb_html($existing);
				throw new ImageAdditionException($error);
			}
		}

		// actually insert the info
		$database->Execute(
				"INSERT INTO images(
					owner_id, owner_ip, filename, filesize,
					hash, ext, width, height, posted, source
				)
				VALUES (
					:owner_id, :owner_ip, :filename, :filesize,
					:hash, :ext, :width, :height, now(), :source
				)",
				array(
					"owner_id"=>$user->id, "owner_ip"=>$_SERVER['REMOTE_ADDR'], "filename"=>$image->filename, "filesize"=>$image->filesize,
					"hash"=>$image->hash, "ext"=>$image->ext, "width"=>$image->width, "height"=>$image->height, "source"=>$image->source
				)
		);
		$image->id = $database->get_last_insert_id('images_id_seq');

		log_info("image", "Uploaded Image #{$image->id} ({$image->hash})");

		# at this point in time, the image's tags haven't really been set,
		# and so, having $image->tag_array set to something is a lie (but
		# a useful one, as we want to know what the tags are /supposed/ to
		# be). Here we correct the lie, by first nullifying the wrong tags
		# then using the standard mechanism to set them properly.
		$tags_to_set = $image->get_tag_array();
		$image->tag_array = array();
		send_event(new TagSetEvent($image, $tags_to_set));

		if($image->source) {
			log_info("core-image", "Source for Image #{$image->id} set to: {$image->source}");
		}
	}
// }}}  end add

// fetch image {{{
	private function send_file($image_id, $type) {
		global $config;
		global $database;
		$image = Image::by_id($image_id);

		global $page;
		if(!is_null($image)) {
			$page->set_mode("data");
			if($type == "thumb") {
				$page->set_type("image/jpeg");
				$file = $image->get_thumb_filename();
			}
			else {
				$page->set_type($image->get_mime_type());
				$file = $image->get_image_filename();
			}

			$page->set_data(file_get_contents($file));

			if(isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
				$if_modified_since = preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]);
			}
			else {
				$if_modified_since = "";
			}
			$gmdate_mod = gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT';

			if($if_modified_since == $gmdate_mod) {
				$page->add_http_header("HTTP/1.0 304 Not Modified",3);
			}
			else {
				$page->add_http_header("Last-Modified: $gmdate_mod");
				
				if ( $config->get_int("image_expires") ) {
					$expires = date(DATE_RFC1123, time() + $config->get_int("image_expires"));
				} else {
					$expires = 'Fri, 2 Sep 2101 12:42:42 GMT'; // War was beginning
				}
				$page->add_http_header('Expires: '.$expires);
			}
		}
		else {
			$page->set_title("Not Found");
			$page->set_heading("Not Found");
			$page->add_block(new Block("Navigation", "<a href='".make_link()."'>Index</a>", "left", 0));
			$page->add_block(new Block("Image not in database",
					"The requested image was not found in the database"));
		}
	}
// }}} end fetch

// replace image {{{
	private function replace_image($id, $image) {
		global $page;
		global $user;
		global $database;
		global $config;
		
		/* Check to make sure the image exists. */
		$existing = Image::by_id($id);
		
		if(is_null($existing)) {
			throw new ImageReplaceException("Image to replace does not exist!");
		}
		
		if(strlen(trim($image->source)) == 0) {
			$image->source = $existing->get_source();
		}
		
		/*
			This step could be optional, ie: perhaps move the image somewhere
			and have it stored in a 'replaced images' list that could be 
			inspected later by an admin?
		*/
		log_debug("image", "Removing image with hash ".$existing->hash);
		$existing->remove_image_only();	// Actually delete the old image file from disk
		
		// Update the data in the database.
		$database->Execute(
				"UPDATE images SET 
					filename = :filename, filesize = :filesize,	hash = :hash,
					ext = :ext, width = :width, height = :height, source = :source
				WHERE 
					id = :id
				",
				array(
					"filename"=>$image->filename, "filesize"=>$image->filesize, "hash"=>$image->hash,
					"ext"=>$image->ext, "width"=>$image->width, "height"=>$image->height, "source"=>$image->source,
					"id"=>$id
				)
		);

		log_info("image", "Replaced Image #{$id} with ({$image->hash})");
	}
// }}} end replace


} // end of class ImageIO
?>
