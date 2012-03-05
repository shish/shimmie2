<?php
/**
 * \page eande Events and Extensions
 * 
 * An event is a little blob of data saying "something happened", possibly
 * "something happened, here's the specific data". Events are sent with the
 * send_event() function. Since events can store data, they can be used to
 * return data to the extension which sent them, for example:
 * 
 * \code
 * $tfe = new TextFormattingEvent($original_text);
 * send_event($tfe);
 * $formatted_text = $tfe->formatted;
 * \endcode
 * 
 * An extension is something which is capable of reacting to events.
 *
 *
 * \page hello The Hello World Extension
 *
 * \code
 * // ext/hello/main.php
 * public class HelloEvent extends Event {
 *     public function __construct($username) {
 *         $this->username = $username;
 *     }
 * }
 * 
 * public class Hello extends Extension {
 *     public void onPageRequest(PageRequestEvent $event) {
 *         global $page, $user;
 *         send_event(new HelloEvent($user->name));
 *     }
 *     public void onHello(HelloEvent $event) {
 *         $this->theme->display_hello($event->username);
 *     }
 * }
 *
 * // ext/hello/theme.php
 * public class HelloTheme extends Themelet {
 *     public void display_hello($username) {
 *         global $page;
 *         $page->add_block(new Block("Hello!", "Hello there ".html_escape($username));
 *     }
 * }
 *
 * // ext/hello/test.php
 * public class HelloTest extends SCoreWebTestCase {
 *     public void testHello() {
 *         $this->get_page("post/list");
 *         $this->assert_text("Hello there");
 *     }
 * }
 *
 * // themes/mytheme/hello.theme.php
 * public class CustomHelloTheme extends HelloTheme {
 *     public function display_hello(Page $page, User $user) {
 *         $h_user = html_escape($user->name);
 *         $page->add_block(new Block(
 *             "Hello!",
 *             "Hello there $h_user, look at my snazzy custom theme!"
 *         );
 *     }
 * }
 * \endcode
 *
 */

/**
 * send_event(BlahEvent()) -> onBlah($event)
 *
 * Also loads the theme object into $this->theme if available
 *
 * The original concept came from Artanis's Extension extension
 * --> http://github.com/Artanis/simple-extension/tree/master
 * Then re-implemented by Shish after he broke the forum and couldn't
 * find the thread where the original was posted >_<
 */
abstract class Extension {
	/** this theme's Themelet object */
	var $theme;

	/** @private */
	var $_child;

	// in PHP5.3, late static bindings can take care of this; __CLASS__
	// used here will refer to the subclass
	// http://php.net/manual/en/language.oop5.late-static-bindings.php
	/** @private */
	public function i_am(Extension $child) {
		$this->_child = $child;
		if(is_null($this->theme)) $this->theme = get_theme_object($child, false);
	}

	/**
	 * Override this to change the priority of the extension,
	 * lower numbered ones will recieve events first
	 */
	public function get_priority() {
		return 50;
	}
}

/**
 * Several extensions have this in common, make a common API
 */
abstract class FormatterExtension extends Extension {
	public function onTextFormatting(TextFormattingEvent $event) {
		$event->formatted = $this->format($event->formatted);
		$event->stripped  = $this->strip($event->stripped);
	}

	abstract public function format(/*string*/ $text);
	abstract public function strip(/*string*/ $text);
}

/**
 * This too is a common class of extension with many methods in common,
 * so we have a base class to extend from
 */
abstract class DataHandlerExtension extends Extension {
	public function onDataUpload(DataUploadEvent $event) {
		if($this->supported_ext($event->type) && $this->check_contents($event->tmpname)) {
			if(!move_upload_to_archive($event)) return;
			send_event(new ThumbnailGenerationEvent($event->hash, $event->type));

			/* Check if we are replacing an image */
			if(array_key_exists('replace', $event->metadata) && isset($event->metadata['replace'])) {
				/* hax: This seems like such a dirty way to do this.. */
				
				/* Validate things */
				$image_id = int_escape($event->metadata['replace']);
				
				/* Check to make sure the image exists. */
				$existing = Image::by_id($image_id);
				
				if(is_null($existing)) {
					throw new UploadException("Image to replace does not exist!");
				}
				if ($existing->hash === $event->metadata['hash']) {
					throw new UploadException("The uploaded image is the same as the one to replace.");
				}

				// even more hax..
				$event->metadata['tags'] = $existing->get_tag_list();
				$image = $this->create_image_from_data(warehouse_path("images", $event->metadata['hash']), $event->metadata);
				
				if(is_null($image)) {
					throw new UploadException("Data handler failed to create image object from data");
				}

				$ire = new ImageReplaceEvent($image_id, $image);
				send_event($ire);
				$event->image_id = $image_id;
			}
			else {
				$image = $this->create_image_from_data(warehouse_path("images", $event->hash), $event->metadata);
				if(is_null($image)) {
					throw new UploadException("Data handler failed to create image object from data");
				}
				$iae = new ImageAdditionEvent($event->user, $image);
				send_event($iae);
				$event->image_id = $iae->image->id;
				
				// Rating Stuff.
				if(!empty($event->metadata['rating'])){
					global $user;
					$rating = $event->metadata['rating'];
					send_event(new RatingSetEvent($image, $user, $rating));
				}
				
				// Locked Stuff.
				if(!empty($event->metadata['locked'])){
					$locked = $event->metadata['locked'];
					send_event(new LockSetEvent($image, !empty($locked)));
				}
			}
		}
	}

	public function onThumbnailGeneration(ThumbnailGenerationEvent $event) {
		if($this->supported_ext($event->type)) {
			if (method_exists($this, 'create_thumb_force') && $event->force == true) {
				 $this->create_thumb_force($event->hash);
			}
			else {
				$this->create_thumb($event->hash);
			}
		}
	}

	public function onDisplayingImage(DisplayingImageEvent $event) {
		global $page;
		if($this->supported_ext($event->image->ext)) {
			$this->theme->display_image($page, $event->image);
		}
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = $this->setup();
		if($sb) $event->panel->add_block($sb);
	}

	protected function setup() {}
	abstract protected function supported_ext($ext);
	abstract protected function check_contents($tmpname);
	abstract protected function create_image_from_data($filename, $metadata);
	abstract protected function create_thumb($hash);
}
?>
