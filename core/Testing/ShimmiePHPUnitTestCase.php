<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class ShimmiePHPUnitTestCase extends \PHPUnit\Framework\TestCase
{
    protected const ANON_NAME = "anonymous";
    protected const ADMIN_NAME = "demo";
    protected const USER_NAME = "test";
    /** @var array<string, string> */
    private array $config_snapshot = [];

    /**
     * Start a DB transaction for each test class
     */
    public static function setUpBeforeClass(): void
    {
        global $_tracer, $database;
        $_tracer->begin(get_called_class());
        $database->begin_transaction();
        parent::setUpBeforeClass();
    }

    /**
     * Start a savepoint for each test
     */
    public function setUp(): void
    {
        global $database, $_tracer, $page, $config;
        $_tracer->begin($this->name());
        $_tracer->begin("setUp");
        $class = str_replace("Test", "Info", get_class($this));
        try {
            if (defined("$class::KEY") && !ExtensionInfo::get_all()[$class::KEY]->is_supported()) {
                self::markTestSkipped("$class not supported with this database");
            }
        } catch (ExtensionNotFound $e) {
            // ignore - this is a core test rather than an extension test
        }

        // Set up a clean environment for each test
        $database->execute("SAVEPOINT test_start");
        self::log_out();
        foreach ($database->get_col("SELECT id FROM images") as $image_id) {
            send_event(new ImageDeletionEvent(Image::by_id_ex((int)$image_id), true));
        }
        $page = new Page();
        $this->config_snapshot = $config->values;

        $_tracer->end();  # setUp
        $_tracer->begin("test");
    }

    public function tearDown(): void
    {
        global $_tracer, $config, $database;
        $database->execute("ROLLBACK TO test_start");
        $config->values = $this->config_snapshot;
        $_tracer->end();  # test
        $_tracer->end();  # $this->getName()
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        global $_tracer, $database;
        $database->rollback();
        $_tracer->end();  # get_called_class()
        $_tracer->clear();
        $_tracer->flush("data/test-trace.json");
    }

    /**
     * @param array<string, mixed> $args
     * @return query-array
     */
    private static function check_args(array $args): array
    {
        if (!$args) {
            return [];
        }
        foreach ($args as $k => $v) {
            if (is_array($v)) {
                $args[$k] = $v;
            } else {
                $args[$k] = (string)$v;
            }
        }
        return $args;
    }

    /**
     * @param query-array $get_args
     * @param query-array $post_args
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
        global $page;
        $get_args = self::check_args($get_args);
        $post_args = self::check_args($post_args);

        if (str_contains($page_name, "?")) {
            throw new \RuntimeException("Query string included in page name");
        }
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = (string)make_link($page_name, $get_args);
        $_GET = $get_args;
        $_POST = $post_args;
        $_COOKIE = $cookies;
        $page = new Page();
        send_event(new PageRequestEvent($method, $page_name, $get_args, $post_args));
        if ($page->mode === PageMode::REDIRECT) {
            $page->code = 302;
        }
        return $page;
    }

    /**
     * @param array<string, mixed> $args
     */
    protected static function get_page(string $page_name, array $args = []): Page
    {
        return self::request("GET", $page_name, $args, []);
    }

    /**
     * @param array<string, mixed> $args
     */
    protected static function post_page(string $page_name, array $args = []): Page
    {
        return self::request("POST", $page_name, [], $args);
    }

    // page things
    protected function assert_title(string $title): void
    {
        global $page;
        self::assertStringContainsString($title, $page->title);
    }

    protected function assert_title_matches(string $title): void
    {
        global $page;
        self::assertStringMatchesFormat($title, $page->title);
    }

    protected function assert_no_title(string $title): void
    {
        global $page;
        self::assertStringNotContainsString($title, $page->title);
    }

    protected function assert_response(int $code): void
    {
        global $page;
        self::assertEquals($code, $page->code);
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
        global $page;

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
        global $page;
        self::assertStringContainsString($content, $page->data);
    }

    protected function assert_no_content(string $content): void
    {
        global $page;
        self::assertStringNotContainsString($content, $page->data);
    }

    /**
     * @param list<string> $tags
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

    protected function assertException(string $type, callable $function): \Exception
    {
        try {
            call_user_func($function);
            self::fail("Expected exception of type $type, but none was thrown");
        } catch (\Exception $exception) {
            self::assertThat(
                $exception,
                new \PHPUnit\Framework\Constraint\Exception($type),
                "Expected exception of type $type, but got " . get_class($exception)
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
        global $config;
        send_event(new UserLoginEvent(User::by_id($config->get_int(UserAccountsConfig::ANON_ID, 0))));
    }

    // post things
    protected function post_image(string $filename, string $tags): int
    {
        $file = new Path($filename);
        $dae = send_event(new DataUploadEvent($file, $file->basename()->str(), 0, [
            "filename" => $file->basename()->str(),
            "tags" => $tags,
        ]));
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
