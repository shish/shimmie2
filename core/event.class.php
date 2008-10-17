<?php
/*
 * Event:
 * generic parent class
 */
abstract class Event {
	var $context;

	public function __construct(RequestContext $context) {
		$this->context = $context;
	}
}


/*
 * ConfigSaveEvent:
 * Sent when the setup screen's 'set' button has been
 * activated; new config options are in $_POST
 */
class ConfigSaveEvent extends Event {
	var $config;
	
	public function ConfigSaveEvent($config) {
		$this->config = $config;
	}
}


/*
 * DataUploadEvent:
 *   $user     -- the user uploading the data
 *   $tmpname  -- the temporary file used for upload
 *   $metadata -- info about the file, should contain at least "filename", "extension", "tags" and "source"
 *
 * Some data is being uploaded. Should be caught by a file handler.
 */
class DataUploadEvent extends Event {
	var $user, $tmpname, $metadata, $hash, $type;

	public function DataUploadEvent($user, $tmpname, $metadata) {
		$this->user = $user;
		$this->tmpname = $tmpname;
		
		$this->metadata = $metadata;
		$this->metadata['hash'] = md5_file($tmpname);
		$this->metadata['size'] = filesize($tmpname);
		
		// useful for most file handlers, so pull directly into fields
		$this->hash = $this->metadata['hash'];
		$this->type = strtolower($metadata['extension']);
	}
}


/*
 * DisplayingImageEvent:
 *   $image -- the image being displayed
 *   $page  -- the page to display on
 *
 * Sent when an image is ready to display. Extensions who
 * wish to appear on the "view" page should listen for this,
 * which only appears when an image actually exists.
 */
class DisplayingImageEvent extends Event {
	var $image, $page;

	public function DisplayingImageEvent($image, $page) {
		$this->image = $image;
		$this->page = $page;
	}

	public function get_image() {
		return $this->image;
	}
}


/*
 * ImageAdditionEvent:
 *   $user  -- the user adding the image
 *   $image -- the image being added
 *
 * An image is being added to the database
 */
class ImageAdditionEvent extends Event {
	var $user, $image;

	public function ImageAdditionEvent($user, $image) {
		$this->image = $image;
		$this->user = $user;
	}
}


/*
 * ImageDeletionEvent:
 *   $image -- the image being deleted
 *
 * An image is being deleted. Used by things like tags
 * and comments handlers to clean out related rows in
 * their tables
 */
class ImageDeletionEvent extends Event {
	var $image;

	public function ImageDeletionEvent($image) {
		$this->image = $image;
	}
}


/*
 * InitExtEvent:
 * A wake-up call for extensions
 */
class InitExtEvent extends Event {}


/*
 * PageRequestEvent:
 *	
 * TODO: up to date docs
 *
 * Used for initial page generation triggers
 */
class PageRequestEvent extends Event {
	var $args;
	var $arg_count;

	var $part_count;

	public function __construct(RequestContext $context, $args) {
		parent::__construct($context);
		$this->args = $args;
		$this->arg_count = count($args);
		$this->page = $context->page;
		$this->user = $context->user;
	}

	public function page_matches($name) {
		$parts = explode("/", $name);
		$this->part_count = count($parts);
		
		if($this->part_count > $this->arg_count) {
			return false;
		}

		for($i=0; $i<$this->part_count; $i++) {
			if($parts[$i] != $this->args[$i]) {
				return false;
			}
		}

		return true;
	}

	public function get_arg($n) {
		$offset = $this->part_count + $n;
		if($offset >= 0 && $offset < $this->arg_count) {
			return $this->args[$offset];
		}
		else {
			return null;
		}
	}

	public function count_args() {
		return $this->arg_count - $this->part_count;
	}
}


/*
 * ParseLinkTemplateEvent:
 *   $link     -- the formatted link
 *   $original -- the formatting string, for reference
 *   $image    -- the image who's link is being parsed
 */
class ParseLinkTemplateEvent extends Event {
	var $link, $original;
	var $image;

	public function ParseLinkTemplateEvent($link, $image) {
		$this->link = $link;
		$this->original = $link;
		$this->image = $image;
	}

	public function replace($needle, $replace) {
		$this->link = str_replace($needle, $replace, $this->link);
	}
}


/*
 * SourceSetEvent:
 *   $image_id
 *   $source
 *
 */
class SourceSetEvent extends Event {
	var $image_id;
	var $source;

	public function SourceSetEvent($image_id, $source) {
		$this->image_id = $image_id;
		$this->source = $source;
	}
}


/*
 * TagSetEvent:
 *   $image_id
 *   $tags
 *
 */
class TagSetEvent extends Event {
	var $image_id;
	var $tags;

	public function TagSetEvent($image_id, $tags) {
		$this->image_id = $image_id;
		$this->tags = tag_explode($tags);
	}
}


/*
 * TextFormattingEvent:
 *   $original  - for reference
 *   $formatted - with formatting applied
 *   $stripped  - with formatting removed
 *
 */
class TextFormattingEvent extends Event {
	var $original;
	var $formatted;
	var $stripped;

	public function TextFormattingEvent($text) {
		$h_text = html_escape(trim($text));
		$this->original  = $h_text;
		$this->formatted = $h_text;
		$this->stripped  = $h_text;
	}
}


/*
 * ThumbnailGenerationEvent:
 * Request a thumb be made for an image
 */
class ThumbnailGenerationEvent extends Event {
	var $hash;
	var $type;

	public function ThumbnailGenerationEvent($hash, $type) {
		$this->hash = $hash;
		$this->type = $type;
	}
}


/*
 * SearchTermParseEvent:
 * Signal that a search term needs parsing
 */
class SearchTermParseEvent extends Event {
	var $term = null;
	var $context = null;
	var $querylets = array();

	public function SearchTermParseEvent($term, $context) {
		$this->term = $term;
		$this->context = $context;
	}

	public function is_querylet_set() {
		return (count($this->querylets) > 0);
	}

	public function get_querylets() {
		return $this->querylets;
	}

	public function add_querylet($q) {
		$this->querylets[] = $q;
	}
}
?>
