<?php
/*
 * Name: Rotate Image
 * Author: jgen <jgen.tech@gmail.com> / Agasa <hiroshiagasa@gmail.com>
 * Description: Allows admins to rotate images.
 * License: GPLv2
 * Version: 0.1
 * Notice:
 *  The image resize and resample code is based off of the "smart_resize_image"
 *  function copyright 2008 Maxim Chernyak, released under a MIT-style license.
 * Documentation:
 *  This extension allows admins to rotate images.
 */

/**
 * This class is just a wrapper around SCoreException.
 */
class ImageRotateException extends SCoreException {
	/** @var string */
	public $error;

	/**
	 * @param string $error
	 */
	public function __construct($error) {
		$this->error = $error;
	}
}

/**
 *	This class handles image rotate requests.
 */
class RotateImage extends Extension {

	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_bool('rotate_enabled', true);
		$config->set_default_int('rotate_default_deg', 180);
	}

	public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event) {
		global $user, $config;
		if($user->is_admin() && $config->get_bool("rotate_enabled")) {
			/* Add a link to rotate the image */
			$event->add_part($this->theme->get_rotate_html($event->image->id));
		}
	}
	
	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Image Rotate");
		$sb->add_bool_option("rotate_enabled", "Allow rotating images: ");
		$sb->add_label("<br>Default Orientation: ");
		$sb->add_int_option("rotate_default_deg");
		$sb->add_label(" deg");
		$event->panel->add_block($sb);
	}
	
	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;

		if ( $event->page_matches("rotate") && $user->is_admin() ) {
			// Try to get the image ID
			$image_id = int_escape($event->get_arg(0));
			if (empty($image_id)) {
				$image_id = isset($_POST['image_id']) ? $_POST['image_id'] : null;
			}
			if (empty($image_id)) {
				throw new ImageRotateException("Can not rotate Image: No valid Image ID given.");
			}
			
			$image = Image::by_id($image_id);
			if(is_null($image)) {
				$this->theme->display_error(404, "Image not found", "No image in the database has the ID #$image_id");
			} else {
			
				/* Check if options were given to rotate an image. */
				if (isset($_POST['rotate_deg'])) {
					
					/* get options */
					
					$deg = 0;
					
					if (isset($_POST['rotate_deg'])) {
						$deg = int_escape($_POST['rotate_deg']);
					}
					
					/* Attempt to rotate the image */
					try {
						$this->rotate_image($image_id, $deg);
						
						//$this->theme->display_rotate_page($page, $image_id);
						
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/view/".$image_id));
						
					} catch (ImageRotateException $e) {
						$this->theme->display_rotate_error($page, "Error Rotating", $e->error);
					}
				}
			}
		}
	}
	
	
	// Private functions
	/* ----------------------------- */

	/**
	 * This function could be made much smaller by using the ImageReplaceEvent
	 * ie: Pretend that we are replacing the image with a rotated copy.
	 *
	 * @param int $image_id
	 * @param int $deg
	 * @throws ImageRotateException
	 */
	private function rotate_image(/*int*/ $image_id, /*int*/ $deg) {
		global $database;
		
		if ( ($deg <= -360) || ($deg >= 360) ) {
			throw new ImageRotateException("Invalid options for rotation angle. ($deg)");
		}
		
		$image_obj = Image::by_id($image_id);
		$hash = $image_obj->hash;
		if (is_null($hash)) {
			throw new ImageRotateException("Image does not have a hash associated with it.");
		}
		
		$image_filename  = warehouse_path("images", $hash);
		if (file_exists ( $image_filename )==false) { throw new ImageRotateException("$image_filename does not exist."); }
		$info = getimagesize($image_filename);
		/* Get the image file type */
		$pathinfo = pathinfo($image_obj->filename);
		$filetype = strtolower($pathinfo['extension']);
		
		/*
			Check Memory usage limits
		
			Old check:   $memory_use = (filesize($image_filename)*2) + ($width*$height*4) + (4*1024*1024);
			New check:    memory_use = width * height * (bits per channel) * channels * 2.5
			
			It didn't make sense to compute the memory usage based on the NEW size for the image. ($width*$height*4)
			We need to consider the size that we are GOING TO instead.
			
			The factor of 2.5 is simply a rough guideline.
			http://stackoverflow.com/questions/527532/reasonable-php-memory-limit-for-image-resize
		*/
		$memory_use = ($info[0] * $info[1] * ($info['bits'] / 8) * $info['channels'] * 2.5) / 1024;
		$memory_limit = get_memory_limit();
		
		if ($memory_use > $memory_limit) {
			throw new ImageRotateException("The image is too large to rotate given the memory limits. ($memory_use > $memory_limit)");
		}
		
		
		/* Attempt to load the image */
		switch ( $info[2] ) {
		  case IMAGETYPE_GIF:   $image = imagecreatefromgif($image_filename);   break;
		  case IMAGETYPE_JPEG:  $image = imagecreatefromjpeg($image_filename);  break;
		  case IMAGETYPE_PNG:   $image = imagecreatefrompng($image_filename);   break;
		  default:
			throw new ImageRotateException("Unsupported image type or ");
		}
		
		/* Rotate and resample the image */
		/*
		$image_rotated = imagecreatetruecolor( $new_width, $new_height );
		
		if ( ($info[2] == IMAGETYPE_GIF) || ($info[2] == IMAGETYPE_PNG) ) {
		  $transparency = imagecolortransparent($image);

		  if ($transparency >= 0) {
			$transparent_color  = imagecolorsforindex($image, $trnprt_indx);
			$transparency       = imagecolorallocate($image_rotated, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
			imagefill($image_rotated, 0, 0, $transparency);
			imagecolortransparent($image_rotated, $transparency);
		  }
		  elseif ($info[2] == IMAGETYPE_PNG) {
			imagealphablending($image_rotated, false);
			$color = imagecolorallocatealpha($image_rotated, 0, 0, 0, 127);
			imagefill($image_rotated, 0, 0, $color);
			imagesavealpha($image_rotated, true);
		  }
		}
		*/
		
		$image_rotated = imagerotate($image, $deg, 0);
		
		/* Temp storage while we rotate */
		$tmp_filename = tempnam(ini_get('upload_tmp_dir'), 'shimmie_rotate');
		if (empty($tmp_filename)) {
			throw new ImageRotateException("Unable to save temporary image file.");
		}
		
		/* Output to the same format as the original image */
		switch ( $info[2] ) {
		  case IMAGETYPE_GIF:   imagegif($image_rotated, $tmp_filename);    break;
		  case IMAGETYPE_JPEG:  imagejpeg($image_rotated, $tmp_filename);   break;
		  case IMAGETYPE_PNG:   imagepng($image_rotated, $tmp_filename);    break;
		  default:
			throw new ImageRotateException("Unsupported image type.");
		}
		
		/* Move the new image into the main storage location */
		$new_hash = md5_file($tmp_filename);
		$new_size = filesize($tmp_filename);
		$target = warehouse_path("images", $new_hash);
		if(!@copy($tmp_filename, $target)) {
			throw new ImageRotateException("Failed to copy new image file from temporary location ({$tmp_filename}) to archive ($target)");
		}
		$new_filename = 'rotated-'.$image_obj->filename;
		
		list($new_width, $new_height) = getimagesize($target);

		
		/* Remove temporary file */
		@unlink($tmp_filename);

		/* Delete original image and thumbnail */
		log_debug("image", "Removing image with hash ".$hash);
		$image_obj->remove_image_only();
		
		/* Generate new thumbnail */
		send_event(new ThumbnailGenerationEvent($new_hash, $filetype));
		
		/* Update the database */
		$database->Execute(
				"UPDATE images SET 
					filename = :filename, filesize = :filesize,	hash = :hash, width = :width, height = :height
				WHERE 
					id = :id
				",
				array(
					"filename"=>$new_filename, "filesize"=>$new_size, "hash"=>$new_hash,
					"width"=>$new_width, "height"=>$new_height,	"id"=>$image_id
				)
		);
		
		log_info("rotate", "Rotated Image #{$image_id} - New hash: {$new_hash}");
	}
}

