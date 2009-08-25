<?php
/*
 * Name: SimpleTest integration
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: adds unit testing to SCore
 */

/**
 * \page unittests Unit Tests
 * 
 * Each extension should (although doesn't technically have to) come with a
 * test.php file, for example ext/index/test.php. The SimpleSCoreTest
 * extension will look for these files and load any SCoreWebTestCase classes
 * it finds inside them, then run them and report whether or not the test
 * passes.
 * 
 * For Shimmie2 specific extensions, there is a ShimmieWebTestCase class which
 * includes functions to upload and delete images.
 *
 * For a quick guide on the spcifics of how to write tests, see \ref wut
 *
 *
 * \page wut Writing Unit Tests
 *
 * Note: The unit test framework assumes a fresh, default install with an
 * admin called "demo" and a user called "test". The full test suite takes
 * a few minutes to run on my test VM, which is long enough that my shared
 * hosting provider cuts it off half way through. Running tests for specific
 * extensions (visit "/test/extension_folder_name") is generally OK though.
 *
 * An empty test class starts like so (assuming the extension we're writing
 * tests for is called "Example")
 *
 * \code
 * <?php
 * class ExampleTest extends SCoreWebTestCase {
 *     public function test_blah() {
 *     }
 * }
 * ?>
 * \endcode
 *
 * SCoreWebTestCase is for testing generic extensions, ShimmieWebTestCase is
 * for imageboard-specific extensions. The name of the function doesn't matter
 * as long as it begins with "test". If you want to test several parts of the
 * extension independantly, you can write several functions, just make sure
 * that each begins with "test".
 *
 * Once you're in the function, $this is a reference to a virtual web browser
 * which you can control with code. The functions available are visible in the
 * docs for class SCoreWebTestCase and ShimmieWebTestCase.
 *
 * Basically, just simulate a browsing session, making sure that everything
 * is where it's supposed to be. If you can simulate a browsing session that
 * triggers a bug, then it makes that bug much easier for developers to fix,
 * and will make sure that it doesn't come back.
 *
 * \code
 * <?php
 * class ExampleTest extends SCoreWebTestCase {
 *     public function test_blah() {
 *         $this->get_page("my/page");
 *         $this->assert_title("My Page Title");
 *         $this->assert_text("This is my page");
 *         $this->click("a link to my other page");
 *         $this->assert_title("My Other Page");
 *         $this->back();
 *         $this->assert_title("My Page Title");
 *         $this->set_field("some_text", "Here is some text");
 *         $this->click("Send Some Text");
 *         $this->assert_text("Your input was: Here is some text");
 *     }
 * }
 * ?>
 * \endcode
 *
 */

require_once('simpletest/web_tester.php');
require_once('simpletest/unit_tester.php');
require_once('simpletest/reporter.php');

define('USER_NAME', "test");
define('USER_PASS', "test");
define('ADMIN_NAME', "demo");
define('ADMIN_PASS', "demo");

/**
 * A set of common SCore activities to test
 */
class SCoreWebTestCase extends WebTestCase {
 	/**
	 * Click on a link or a button
	 */
 	public function click($text) {
		return parent::click($text);
	}

	/**
	 * Click the virtual browser's back button
	 */
	public function back() {
		return parent::back();
	}

	/**
	 * Get a page based on the SCore URL, eg get_page("post/list") will do
	 * the right thing; no need for http:// or any such
	 */
	protected function get_page($page) {
		$raw = $this->get(make_http(make_link($page)));
		$this->assertNoText("Exception:");
		$this->assertNoText("Error:");
		$this->assertNoText("Warning:");
		$this->assertNoText("Notice:");
		return $raw;
	}

	protected function log_in_as_user() {
        $this->get_page('user_admin/login');
		$this->assertText("Login");
		$this->setField('user', USER_NAME);
		$this->setField('pass', USER_PASS);
		$this->click("Log In");
	}

	protected function log_in_as_admin() {
        $this->get_page('user_admin/login');
		$this->assertText("Login");
		$this->setField('user', ADMIN_NAME);
		$this->setField('pass', ADMIN_PASS);
		$this->click("Log In");
	}

	protected function log_out() {
        $this->get_page('post/list');
		$this->click('Log Out');
	}

	/**
	 * Look through the HTML for a form element with the name $name,
	 * set its value to $value
	 */
	protected function set_field($name, $value) {
		parent::setField($name, $value);
	}

	protected function assert_text($text) {parent::assertText($text);}
	protected function assert_title($title) {parent::assertTitle($title);}
	protected function assert_no_text($text) {parent::assertNoText($text);}
	protected function assert_mime($type) {parent::assertMime($type);}
	protected function assert_response($code) {parent::assertResponse($code);}
}

/**
 * A set of common Shimmie activities to test
 */
class ShimmieWebTestCase extends SCoreWebTestCase {
	protected function post_image($filename, $tags) {
		$image_id = -1;
		$this->setMaximumRedirects(0);

        $this->get_page('post/list');
		$this->assertText("Upload");
		$this->setField("data0", $filename);
		$this->setField("tags", $tags);
		$this->click("Post");

		$raw_headers = $this->getBrowser()->getHeaders();
		$headers = explode("\n", $raw_headers);
		foreach($headers as $header) {
			$parts = explode(":", $header);
			if(trim($parts[0]) == "X-Shimmie-Image-ID") {
				$image_id = int_escape(trim($parts[1]));
			}
		}

		$this->setMaximumRedirects(5);
		return $image_id;
	}

	protected function delete_image($image_id) {
		if($image_id > 0) {
	        $this->get_page('post/view/'.$image_id);
			$this->click("Delete");
		}
	}
}

/** @private */
class TestFinder extends TestSuite {
	function TestFinder($hint) {
		if(strpos($hint, "..") !== FALSE) return;
		$dir = "*";
		if(file_exists("ext/$hint/test.php")) $dir = $hint;
		$this->TestSuite('All tests');
		foreach(glob("ext/$dir/test.php") as $file) {
			$this->addFile($file);
		}
	}
}

class SimpleSCoreTest extends SimpleExtension {
	public function onPageRequest($event) {
		global $page;
		if($event->page_matches("test")) {
			set_time_limit(0);

			$page->set_title("Test Results");
			$page->set_heading("Test Results");
			$page->add_block(new NavBlock());

			$all = new TestFinder($event->get_arg(0));
			$all->run(new SCoreReporter($page));
		}
	}

	public function onUserBlockBuilding($event) {
		global $user;
		if($user->is_admin()) {
			$event->add_link("Run Tests", make_link("test/all"));
		}
	}
}
?>
