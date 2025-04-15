<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class ShimmiePHPUnitTestCase extends \PHPUnit\Framework\TestCase
{
    protected const ANON_NAME = "anonymous";
    protected const ADMIN_NAME = "demo";
    protected const USER_NAME = "test";
    /** @var array<string, bool|int|string|array<string>> */
    private array $config_snapshot = [];

    /**
     * Start a DB transaction for each test class
     */
    public static function setUpBeforeClass(): void
    {
        Ctx::$tracer->begin(get_called_class());
        Ctx::$database->begin_transaction();
        parent::setUpBeforeClass();
    }

    /**
     * Start a savepoint for each test
     */
    public function setUp(): void
    {
        Ctx::$tracer->begin($this->name());
        Ctx::$tracer->begin("setUp");
        $class = str_replace("Test", "Info", get_class($this));
        try {
            if (defined("$class::KEY") && !ExtensionInfo::get_all()[$class::KEY]->is_supported()) {
                self::markTestSkipped("$class not supported with this database");
            }
        } catch (ExtensionNotFound $e) {
            // ignore - this is a core test rather than an extension test
        }

        // Set up a clean environment for each test
        Ctx::$database->execute("SAVEPOINT test_start");
        self::log_out();
        foreach (Ctx::$database->get_col("SELECT id FROM images") as $image_id) {
            send_event(new ImageDeletionEvent(Image::by_id_ex((int)$image_id), true));
        }
        Ctx::setPage(new Page());
        $this->config_snapshot = Ctx::$config->values;

        Ctx::$tracer->end();  # setUp
        Ctx::$tracer->begin("test");
    }

    public function tearDown(): void
    {
        Ctx::$database->execute("ROLLBACK TO test_start");
        Ctx::$config->values = $this->config_snapshot;
        Ctx::$tracer->end();  # test
        Ctx::$tracer->end();  # $this->getName()
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        Ctx::$database->rollback();
        Ctx::$tracer->end();  # get_called_class()
        Ctx::$tracer->clear();
        Ctx::$tracer->flush("data/test-trace.json");
    }

    /**
     * @param array<string, string|string[]> $get_args
     * @param array<string, string|string[]> $post_args
     * @param array<string, string> $cookies
     */
    protected static function request(
        string $method,
        string $page_name,
        array $get_args = [],
        array $post_args = [],
        array $cookies = ["shm_accepted_terms" => "true"],
    ): Page {
        // use a fresh page
        $get_args = new QueryArray($get_args);
        $post_args = new QueryArray($post_args);

        if (str_contains($page_name, "?")) {
            throw new \RuntimeException("Query string included in page name");
        }
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = (string)make_link($page_name, $get_args);
        $_GET = $get_args;
        $_POST = $post_args;
        $_COOKIE = $cookies;
        Ctx::setPage(new Page());
        send_event(new PageRequestEvent($method, $page_name, $get_args, $post_args));
        if (Ctx::$page->mode === PageMode::REDIRECT) {
            Ctx::$page->code = 302;
        }
        return Ctx::$page;
    }

    /**
     * @param array<string, string|string[]> $args
     */
    protected static function get_page(string $page_name, array $args = []): Page
    {
        return self::request("GET", $page_name, $args, []);
    }

    /**
     * @param array<string, string|string[]> $args
     */
    protected static function post_page(string $page_name, array $args = []): Page
    {
        return self::request("POST", $page_name, [], $args);
    }

    // page things
    protected function assert_title(string $title): void
    {
        self::assertStringContainsString($title, Ctx::$page->title);
    }

    protected function assert_title_matches(string $title): void
    {
        self::assertStringMatchesFormat($title, Ctx::$page->title);
    }

    protected function assert_no_title(string $title): void
    {
        self::assertStringNotContainsString($title, Ctx::$page->title);
    }

    protected function assert_response(int $code): void
    {
        self::assertEquals($code, Ctx::$page->code);
    }

    /**
     * @param array<Block> $blocks
     * @param ?string $section
     * @return string
     */
    private function blocks_to_text(array $blocks, ?string $section): string
    {
        $text = "";
        foreach ($blocks as $block) {
            if (is_null($section) || $section === $block->section) {
                $text .= $block->header . "\n";
                $text .= $block->body . "\n\n";
            }
        }
        return $text;
    }

    protected function page_to_text(?string $section = null): string
    {
        $page = Ctx::$page;

        return match($page->mode) {
            PageMode::PAGE => $page->title . "\n" . $this->blocks_to_text($page->blocks, $section),
            PageMode::DATA => $page->data,
            PageMode::REDIRECT => self::fail("Page mode is REDIRECT ({$page->redirect}) (only PAGE and DATA are supported)"),
            PageMode::FILE => self::fail("Page mode is FILE (only PAGE and DATA are supported)"),
            PageMode::MANUAL => self::fail("Page mode is MANUAL (only PAGE and DATA are supported)"),
            default => self::fail("Unknown page mode {$page->mode->name}"),  // just for phpstan
        };
    }

    /**
     * Assert that the page contains the given text somewhere in the blocks
     */
    protected function assert_text(string $text, ?string $section = null): void
    {
        self::assertStringContainsString($text, $this->page_to_text($section));
    }

    protected function assert_no_text(string $text, ?string $section = null): void
    {
        self::assertStringNotContainsString($text, $this->page_to_text($section));
    }

    /**
     * Assert that the page contains the given text somewhere in the binary data
     */
    protected function assert_content(string $content): void
    {
        self::assertStringContainsString($content, Ctx::$page->data);
    }

    protected function assert_no_content(string $content): void
    {
        self::assertStringNotContainsString($content, Ctx::$page->data);
    }

    /**
     * @param list<tag-string> $tags
     * @param int[] $results
     */
    protected function assert_search_results(array $tags, array $results, string $message = ''): void
    {
        $images = Search::find_images(0, null, $tags);
        $ids = [];
        foreach ($images as $image) {
            $ids[] = $image->id;
        }
        self::assertEquals($results, $ids, $message);
    }

    protected function assertException(string $type, callable $function, string $message = ''): \Exception
    {
        $message = $message ? " ($message)" : '';
        try {
            call_user_func($function);
            self::fail("Expected exception of type $type, but none was thrown$message");
        } catch (\Exception $exception) {
            self::assertThat(
                $exception,
                new \PHPUnit\Framework\Constraint\Exception($type),
                "Expected exception of type $type, but got " . get_class($exception) . $message
            );
            return $exception;
        }
    }

    // user things
    protected static function log_in_as_admin(): void
    {
        send_event(new UserLoginEvent(User::by_name(self::ADMIN_NAME)));
    }

    protected static function log_in_as_user(): void
    {
        send_event(new UserLoginEvent(User::by_name(self::USER_NAME)));
    }

    protected static function log_out(): void
    {
        send_event(new UserLoginEvent(User::get_anonymous()));
    }

    // post things
    protected function post_image(string $filename, string $tags): int
    {
        $file = new Path($filename);
        $dae = send_event(new DataUploadEvent($file, $file->basename()->str(), 0, new QueryArray([
            "filename" => $file->basename()->str(),
            "tags" => $tags,
        ])));
        if (count($dae->images) === 0) {
            throw new \Exception("Upload failed :(");
        }
        return $dae->images[0]->id;
    }

    protected function delete_image(int $image_id): void
    {
        $img = Image::by_id($image_id);
        if ($img) {
            send_event(new ImageDeletionEvent($img, true));
        }
    }
}
