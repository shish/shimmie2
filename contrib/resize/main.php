<?php
/*
 * Name: Resize Image
 * Author: jgen <jgen.tech@gmail.com>
 * Description: Allows admins to resize images.
 * License: GPLv2
 * Version: 0.1
 * Notice:
 *  The image resize and resample code is based off of the "smart_resize_image"
 *  function copyright 2008 Maxim Chernyak, released under a MIT-style license.
 * Documentation:
 *  This extension allows admins to resize images.
 */

/**
 * This class is just a wrapper around SCoreException.
 */
class ImageResizeException extends SCoreException {
	var $error;

	public function __construct($error) {
		$this->error = $error;
	}
}

/**
 *	This class handles image resize requests.
 */
class ResizeImage extends SimpleExtension {

	public function onInitExt($event) {
		global $config;
		$config->set_default_bool('resize_enabled', true);
		$config->set_default_bool('resize_upload', false);
		$config->set_default_int('resize_default_width', 0);
		$config->set_default_int('resize_default_height', 0);		
	}

	public function onImageAdminBlockBuilding($event) {
		global $user, $config;
		if($user->is_admin() && $config->get_bool("resize_enabled")) {
			/* Add a link to resize the image */
			$event->add_part($this->theme->get_resize_html($event->image->id));
		}
	}
	
	public function onSetupBuilding($event) {
		$sb = new SetupBlock("Image Resize");
		$sb->add_bool_option("resize_enabled", "Allow resizing images: ");
		$sb->add_bool_option("resize_upload", "<br>Resize on upload: ");
		$sb->add_label("<br>Preset/Default Width: ");
		$sb->add_int_option("resize_default_width");
		$sb->add_label(" px");
		$sb->add_label("<br>Preset/Default Height: ");
		$sb->add_int_option("resize_default_height");
		$sb->add_label(" px");
		$sb->add_label("<br>(enter 0 for no default)");
		$event->panel->add_block($sb);
	}
	
	public function onDataUpload(DataUploadEvent $event) {
		global $config;
		$image_obj = Image::by_id($event->image_id);
		//No auto resizing for gifs due to animated gif causing errors :(
		//Also PNG resizing seems to be completely broken.
		if($config->get_bool("resize_upload") == true && ($image_obj->ext == "jpg")){
			$width = $height = 0;

			if ($config->get_int("resize_default_width") !== 0) {
				$height = $config->get_int("resize_default_width");
			}
			if ($config->get_int("resize_default_height") !== 0) {
				$height = $config->get_int("resize_default_height");
			}

			try {
				$this->resize_image($event->image_id, $width, $height);
			} catch (ImageResizeException $e) {
				$this->theme->display_resize_error($page, "Error Resizing", $e->error);
			}

			//Need to generate thumbnail again...
			//This only seems to be an issue if one of the sizes was set to 0.
			$image_obj = Image::by_id($event->image_id); //Must be a better way to grab the new hash than setting this again..
			send_event(new ThumbnailGenerationEvent($image_obj->hash, $image_obj->ext, true));

			log_info("resize", "Image #{$event->image_id} has been resized to: ".$width."x".$height);
			//TODO: Notify user that image has been resized.
		}
	}

	public function onPageRequest($event) {
		global $page, $user;

		if ( $event->page_matches("resize") && $user->is_admin() ) {
			// Try to get the image ID
			$image_id = int_escape($event->get_arg(0));
			if (empty($image_id)) {
				$image_id = isset($_POST['image_id']) ? $_POST['image_id'] : null;
			}
			if (empty($image_id)) {
				throw new ImageResizeException("Can not resize Image: No valid Image ID given.");
			}
			
			$image = Image::by_id($image_id);
			if(is_null($image)) {
				$this->theme->display_error($page, "Image not found", "No image in the database has the ID #$image_id");
			} else {
			
				/* Check if options were given to resize an image. */
				if (isset($_POST['resize_width']) || isset($_POST['resize_height'])) {
					
					/* get options */
					
					$width = $height = 0;
					
					if (isset($_POST['resize_width'])) {
						$width = int_escape($_POST['resize_width']);
					}
					if (isset($_POST['resize_height'])) {
						$height = int_escape($_POST['resize_height']);
					}
					
					/* Attempt to resize the image */
					try {
						$this->resize_image($image_id, $width, $height);
						
						//$this->theme->display_resize_page($page, $image_id);
						
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/view/".$image_id));
						
					} catch (ImageResizeException $e) {
						$this->theme->display_resize_error($page, "Error Resizing", $e->error);
					}
				} else {
					/* Display options for resizing */
					$this->theme->display_resize_page($page, $image_id);
				}
			}
		}
	}
	
	
	// Private functions
	
	/*
		This function could be made much smaller by using the ImageReplaceEvent
		ie: Pretend that we are replacing the image with a resized copy.
	*/
	private function resize_image($image_id, $width, $height) {
		global $config;
		global $user;
		global $page;
		global $database;
		
		if ( ($height <= 0) && ($width <= 0) ) {
			throw new ImageResizeException("Invalid options for height and width. ($width x $height)");
		}
		
		$image_obj = Image::by_id($image_id);
		$hash = $image_obj->hash;
		if (is_null($hash)) {
			throw new ImageResizeException("Image does not have a hash associated with it.");
		}
		
		$image_filename  = warehouse_path("images", $hash);
		$info = getimagesize($image_filename);
		/* Get the image file type */
		$pathinfo = pathinfo($image_obj->filename);
		$filetype = strtolower($pathinfo['extension']);
		
		if (($image_obj->width != $info[0] ) || ($image_obj->height != $info[1])) {
			throw new ImageResizeException("The image size does not match what is in the database! - Aborting Resize.");
		}
		
		/* Check memory usage limits */
		$memory_use = (filesize($image_filename)*2) + ($width*$height*4) + (4*1024*1024);
		$memory_limit = get_memory_limit();
		
		if ($memory_use > $memory_limit) {
			throw new ImageResizeException("The image is too large to resize given the memory limits. ($memory_use > $memory_limit)");
		}
		
		/* Calculate the new size of the image */
		if ( $height > 0 && $width > 0 ) {
			$new_height = $height;
			$new_width = $width;
		} else {
			// Scale the new image
			if      ($width  == 0)  $factor = $height/$image_obj->height;
			elseif  ($height == 0)  $factor = $width/$image_obj->width;
			else                    $factor = min( $width / $image_obj->width, $height / $image_obj->height );

			$new_width  = round( $image_obj->width * $factor );
			$new_height = round( $image_obj->height * $factor );			
		}
		
		/* Attempt to load the image */
		switch ( $info[2] ) {
		  case IMAGETYPE_GIF:   $image = imagecreatefromgif($image_filename);   break;
		  case IMAGETYPE_JPEG:  $image = imagecreatefromjpeg($image_filename);  break;
		  /* FIXME: PNG support seems to be broken.
		  case IMAGETYPE_PNG:   $image = imagecreatefrompng($image_filename);   break;*/
		  default:
			throw new ImageResizeException("Unsupported image type.");
		}
		
		/* Resize and resample the image */
		$image_resized = imagecreatetruecolor( $new_width, $new_height );
		
		if ( ($info[2] == IMAGETYPE_GIF) || ($info[2] == IMAGETYPE_PNG) ) {
		  $transparency = imagecolortransparent($image);

		  if ($transparency >= 0) {
			$transparent_color  = imagecolorsforindex($image, $trnprt_indx);
			$transparency       = imagecolorallocate($image_resized, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
			imagefill($image_resized, 0, 0, $transparency);
			imagecolortransparent($image_resized, $transparency);
		  }
		  elseif ($info[2] == IMAGETYPE_PNG) {
			imagealphablending($image_resized, false);
			$color = imagecolorallocatealpha($image_resized, 0, 0, 0, 127);
			imagefill($image_resized, 0, 0, $color);
			imagesavealpha($image_resized, true);
		  }
		}
		imagecopyresampled($image_resized, $image, 0, 0, 0, 0, $new_width, $new_height, $image_obj->width, $image_obj->height);
		
		/* Temp storage while we resize */
		$tmp_filename = tempnam("/tmp", 'shimmie_resize');
		if (empty($tmp_filename)) {
			throw new ImageResizeException("Unable to save temporary image file.");
		}
		
		/* Output to the same format as the original image */
		switch ( $info[2] ) {
		  case IMAGETYPE_GIF:   imagegif($image_resized, $tmp_filename);    break;
		  case IMAGETYPE_JPEG:  imagejpeg($image_resized, $tmp_filename);   break;
		  case IMAGETYPE_PNG:   imagepng($image_resized, $tmp_filename);    break;
		  default:
			throw new ImageResizeException("Unsupported image type.");
		}
		
		/* Move the new image into the main storage location */
		$new_hash = md5_file($tmp_filename);
		$new_size = filesize($tmp_filename);
		$target = warehouse_path("images", $new_hash);
		if(!file_exists(dirname($target))) mkdir(dirname($target), 0755, true);
		if(!@copy($tmp_filename, $target)) {
			throw new ImageResizeException("Failed to copy new image file from temporary location ({$tmp_filename}) to archive ($target)");
		}
		$new_filename = 'resized-'.$image_obj->filename;
		
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
		
		log_info("resize", "Resized Image #{$image_id} - New hash: {$new_hash}");
	}
}
?>
