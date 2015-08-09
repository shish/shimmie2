<?php
define("TIMEZONE", 'UTC');
define("EXTRA_EXTS", str_replace("ext/", "", implode(',', glob('ext/*'))));
define("BASE_HREF", "/");
define("CLI_LOG_LEVEL", 50);

$_SERVER['QUERY_STRING'] = '/';

require_once "core/_bootstrap.inc.php";

if(is_null(User::by_name("demo"))) {
	$userPage = new UserPage();
	$userPage->onUserCreation(new UserCreationEvent("demo", "demo", ""));
	$userPage->onUserCreation(new UserCreationEvent("test", "test", ""));
}

abstract class ShimmiePHPUnitTestCase extends PHPUnit_Framework_TestCase {
	protected $backupGlobalsBlacklist = array('database', 'config');
	private $images = array();

	public function setUp() {
		// things to do after bootstrap and before request
		// log in as anon
		$this->log_out();
	}

	public function tearDown() {
		foreach($this->images as $image_id) {
			$this->delete_image($image_id);
		}
	}

	protected function get_page($page_name) {
		// use a fresh page
		global $page;
		$page = class_exists("CustomPage") ? new CustomPage() : new Page();
		send_event(new PageRequestEvent($page_name));
	}

	// page things
	protected function assert_title($title) {
		global $page;
		$this->assertEquals($title, $page->title);
	}

	protected function assert_response($code) {
		global $page;
		$this->assertEquals($code, $page->code);
	}

	protected function has_text($text) {
		global $page;
		foreach($page->blocks as $block) {
			if(strpos($block->header, $text) !== false) return true;
			if(strpos($block->body, $text) !== false) return true;
		}
		return false;
	}

	protected function assert_text($text) {
		$this->assertTrue($this->has_text($text));
	}

	protected function assert_no_text($text) {
		$this->assertFalse($this->has_text($text));
	}

	// user things
	protected function log_in_as_admin() {
		global $user;
		$user = User::by_name('demo');
		$this->assertNotNull($user);
	}

	protected function log_in_as_user() {
		global $user;
		$user = User::by_name('test');
		$this->assertNotNull($user);
	}

	protected function log_out() {
		global $user, $config;
		$user = User::by_id($config->get_int("anon_id", 0));
		$this->assertNotNull($user);
	}

	// post things
	/**
	 * @param string $filename
	 * @param string|string[] $tags
	 * @return int
	 */
	protected function post_image($filename, $tags) {
		$dae = new DataUploadEvent($filename, array(
			"filename"=>$filename,
			"extension"=>'jpg', // fixme
			"tags"=>$tags,
			"source"=>null,
		));
		send_event($dae);
		$this->images[] = $dae->image_id;
		return $dae->image_id;
	}

	/**
	 * @param int $image_id
	 */
	protected function delete_image($image_id) {
		$img = Image::by_id($image_id);
		if($img) {
			$ide = new ImageDeletionEvent($img);
			send_event($ide);
		}
	}
}
