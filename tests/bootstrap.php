<?php
define("UNITTEST", true);
define("TIMEZONE", 'UTC');
define("EXTRA_EXTS", str_replace("ext/", "", implode(',', glob('ext/*'))));
define("BASE_HREF", "/");
define("CLI_LOG_LEVEL", 50);

$_SERVER['QUERY_STRING'] = '/';

chdir(dirname(dirname(__FILE__)));
require_once "core/_bootstrap.php";

if (is_null(User::by_name("demo"))) {
    $userPage = new UserPage();
    $userPage->onUserCreation(new UserCreationEvent("demo", "demo", ""));
    $userPage->onUserCreation(new UserCreationEvent("test", "test", ""));
}

abstract class ShimmiePHPUnitTestCase extends \PHPUnit\Framework\TestCase
{
    private $images = [];

    public function setUp()
    {
        $class = str_replace("Test", "", get_class($this));
        if (!class_exists($class)) {
            $this->markTestSkipped("$class not loaded");
        } elseif (!ExtensionInfo::get_for_extension_class($class)->is_supported()) {
            $this->markTestSkipped("$class not supported with this database");
        }

        // things to do after bootstrap and before request
        // log in as anon
        $this->log_out();
    }

    public function tearDown()
    {
        foreach ($this->images as $image_id) {
            $this->delete_image($image_id);
        }
    }

    protected function get_page($page_name, $args=null)
    {
        // use a fresh page
        global $page;
        if (!$args) {
            $args = [];
        }
        $_GET = $args;
        $_POST = [];
        $page = class_exists("CustomPage") ? new CustomPage() : new Page();
        send_event(new PageRequestEvent($page_name));
        if ($page->mode == PageMode::REDIRECT) {
            $page->code = 302;
        }
    }

    protected function post_page($page_name, $args=null)
    {
        // use a fresh page
        global $page;
        if (!$args) {
            $args = [];
        }
        $_GET = [];
        $_POST = $args;
        $page = class_exists("CustomPage") ? new CustomPage() : new Page();
        send_event(new PageRequestEvent($page_name));
        if ($page->mode == PageMode::REDIRECT) {
            $page->code = 302;
        }
    }

    // page things
    protected function assert_title(string $title)
    {
        global $page;
        $this->assertContains($title, $page->title);
    }

    protected function assert_no_title(string $title)
    {
        global $page;
        $this->assertNotContains($title, $page->title);
    }

    protected function assert_response(int $code)
    {
        global $page;
        $this->assertEquals($code, $page->code);
    }

    protected function page_to_text(string $section=null)
    {
        global $page;
        $text = $page->title . "\n";
        foreach ($page->blocks as $block) {
            if (is_null($section) || $section == $block->section) {
                $text .= $block->header . "\n";
                $text .= $block->body . "\n\n";
            }
        }
        return $text;
    }

    protected function assert_text(string $text, string $section=null)
    {
        $this->assertContains($text, $this->page_to_text($section));
    }

    protected function assert_no_text(string $text, string $section=null)
    {
        $this->assertNotContains($text, $this->page_to_text($section));
    }

    protected function assert_content(string $content)
    {
        global $page;
        $this->assertContains($content, $page->data);
    }

    protected function assert_no_content(string $content)
    {
        global $page;
        $this->assertNotContains($content, $page->data);
    }

    // user things
    protected function log_in_as_admin()
    {
        global $user;
        $user = User::by_name('demo');
        $this->assertNotNull($user);
        send_event(new InitUserConfigEvent($user));
    }

    protected function log_in_as_user()
    {
        global $user;
        $user = User::by_name('test');
        $this->assertNotNull($user);
        send_event(new InitUserConfigEvent($user));
    }

    protected function log_out()
    {
        global $user, $config;
        $user = User::by_id($config->get_int("anon_id", 0));
        $this->assertNotNull($user);
        send_event(new InitUserConfigEvent($user));
    }

    // post things
    protected function post_image(string $filename, string $tags): int
    {
        $dae = new DataUploadEvent($filename, [
            "filename" => $filename,
            "extension" => pathinfo($filename, PATHINFO_EXTENSION),
            "tags" => Tag::explode($tags),
            "source" => null,
        ]);
        send_event($dae);
        $this->images[] = $dae->image_id;
        return $dae->image_id;
    }

    protected function delete_image(int $image_id)
    {
        $img = Image::by_id($image_id);
        if ($img) {
            $ide = new ImageDeletionEvent($img, true);
            send_event($ide);
        }
    }
}
