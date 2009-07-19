<?php
/*
 * A generic extension class, for subclassing
 */
interface Extension {
	public function receive_event(Event $event);
}

/*
 * send_event(BlahEvent()) -> onBlah($event)
 *
 * Also loads the theme object into $this->theme if available
 *
 * index.php will load all SimpleExtension subclasses with default
 * priority, so no need for register_extension(new Foo())
 *
 * Hopefully this removes as much copy & paste code from the extension
 * files as possible \o/
 *
 * The original concept came from Artanis's SimpleExtension extension
 * --> http://github.com/Artanis/simple-extension/tree/master
 * Then re-implemented by Shish after he broke the forum and couldn't
 * find the thread where the original was posted >_<
 */
abstract class SimpleExtension implements Extension {
	var $theme;
	var $_child;

	public function i_am($child) {
		$this->_child = $child;
		if(is_null($this->theme)) $this->theme = get_theme_object($child, false);
	}

	public function receive_event(Event $event) {
		$name = get_class($event);
		$name = "on".str_replace("Event", "", $name);
		if(method_exists($this->_child, $name)) {
			$this->_child->$name($event);
		}
	}
}

/*
 * Several extensions have this in common, make a common API
 */
abstract class FormatterExtension implements Extension {
	public function receive_event(Event $event) {
		if($event instanceof TextFormattingEvent) {
			$event->formatted = $this->format($event->formatted);
			$event->stripped  = $this->strip($event->stripped);
		}
	}

	abstract public function format($text);
	abstract public function strip($text);
}

/*
 * This too is a common class of extension with many methods in common,
 * so we have a base class to extend from
 */
abstract class DataHandlerExtension implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof DataUploadEvent) && $this->supported_ext($event->type) && $this->check_contents($event->tmpname)) {
			$hash = $event->hash;
			$ha = substr($hash, 0, 2);
			if(!move_upload_to_archive($event)) return;
			send_event(new ThumbnailGenerationEvent($event->hash, $event->type));
			$image = $this->create_image_from_data("images/$ha/$hash", $event->metadata);
			if(is_null($image)) {
				throw new UploadException("Data handler failed to create image object from data");
			}
			$iae = new ImageAdditionEvent($event->user, $image);
			send_event($iae);
			$event->image_id = $iae->image->id;
		}

		if(($event instanceof ThumbnailGenerationEvent) && $this->supported_ext($event->type)) {
			$this->create_thumb($event->hash);
		}

		if(($event instanceof DisplayingImageEvent) && $this->supported_ext($event->image->ext)) {
			global $page;
			$this->theme->display_image($page, $event->image);
		}
	}

	abstract protected function supported_ext($ext);
	abstract protected function check_contents($tmpname);
	abstract protected function create_image_from_data($filename, $metadata);
	abstract protected function create_thumb($hash);
}
?>
