<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Misc functions                                                            *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Move a file from PHP's temporary area into shimmie's image storage
 * hierarchy, or throw an exception trying.
 *
 * @param DataUploadEvent $event
 * @throws UploadException
 */
function move_upload_to_archive(DataUploadEvent $event) {
	$target = warehouse_path("images", $event->hash);
	if(!@copy($event->tmpname, $target)) {
		$errors = error_get_last();
		throw new UploadException(
			"Failed to copy file from uploads ({$event->tmpname}) to archive ($target): ".
			"{$errors['type']} / {$errors['message']}"
		);
	}
}

/**
 * Add a directory full of images
 *
 * @param $base string
 * @return array|string[]
 */
function add_dir($base) {
	$results = array();

	foreach(list_files($base) as $full_path) {
		$short_path = str_replace($base, "", $full_path);
		$filename = basename($full_path);

		$tags = path_to_tags($short_path);
		$result = "$short_path (".str_replace(" ", ", ", $tags).")... ";
		try {
			add_image($full_path, $filename, $tags);
			$result .= "ok";
		}
		catch(UploadException $ex) {
			$result .= "failed: ".$ex->getMessage();
		}
		$results[] = $result;
	}

	return $results;
}

/**
 * @param string $tmpname
 * @param string $filename
 * @param string $tags
 * @throws UploadException
 */
function add_image($tmpname, $filename, $tags) {
	assert(file_exists($tmpname));

	$pathinfo = pathinfo($filename);
	if(!array_key_exists('extension', $pathinfo)) {
		throw new UploadException("File has no extension");
	}
	$metadata = array();
	$metadata['filename'] = $pathinfo['basename'];
	$metadata['extension'] = $pathinfo['extension'];
	$metadata['tags'] = Tag::explode($tags);
	$metadata['source'] = null;
	$event = new DataUploadEvent($tmpname, $metadata);
	send_event($event);
	if($event->image_id == -1) {
		throw new UploadException("File type not recognised");
	}
}

/**
 * Given a full size pair of dimensions, return a pair scaled down to fit
 * into the configured thumbnail square, with ratio intact
 *
 * @param int $orig_width
 * @param int $orig_height
 * @return integer[]
 */
function get_thumbnail_size(int $orig_width, int $orig_height) {
	global $config;

	if($orig_width === 0) $orig_width = 192;
	if($orig_height === 0) $orig_height = 192;

	if($orig_width > $orig_height * 5) $orig_width = $orig_height * 5;
	if($orig_height > $orig_width * 5) $orig_height = $orig_width * 5;

	$max_width  = $config->get_int('thumb_width');
	$max_height = $config->get_int('thumb_height');

	$xscale = ($max_height / $orig_height);
	$yscale = ($max_width / $orig_width);
	$scale = ($xscale < $yscale) ? $xscale : $yscale;

	if($scale > 1 && $config->get_bool('thumb_upscale')) {
		return array((int)$orig_width, (int)$orig_height);
	}
	else {
		return array((int)($orig_width*$scale), (int)($orig_height*$scale));
	}
}
