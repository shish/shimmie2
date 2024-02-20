<?php

declare(strict_types=1);

namespace Shimmie2;

if(class_exists("\\PHPUnit\\Framework\\TestCase")) {
    abstract class ShimmiePHPUnitTestCase extends \PHPUnit\Framework\TestCase
    {
        protected static string $anon_name = "anonymous";
        protected static string $admin_name = "demo";
        protected static string $user_name = "test";
        protected string $wipe_time = "test";

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
            global $database, $_tracer;
            $_tracer->begin($this->name());
            $_tracer->begin("setUp");
            $class = str_replace("Test", "", get_class($this));
            try {
                if (!ExtensionInfo::get_for_extension_class($class)->is_supported()) {
                    $this->markTestSkipped("$class not supported with this database");
                }
            } catch (ExtensionNotFound $e) {
                // ignore - this is a core test rather than an extension test
            }

            // Set up a clean environment for each test
            $database->execute("SAVEPOINT test_start");
            self::log_out();
            foreach ($database->get_col("SELECT id FROM images") as $image_id) {
                send_event(new ImageDeletionEvent(Image::by_id((int)$image_id), true));
            }

            $_tracer->end();  # setUp
            $_tracer->begin("test");
        }

        public function tearDown(): void
        {
            global $_tracer, $database;
            $database->execute("ROLLBACK TO test_start");
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
         * @return array<string, string|mixed[]>
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
         * @param array<string, mixed> $get_args
         * @param array<string, mixed> $post_args
         */
        protected static function request(
            string $method,
            string $page_name,
            array $get_args = [],
            array $post_args = []
        ): Page {
            // use a fresh page
            global $page;
            $get_args = self::check_args($get_args);
            $post_args = self::check_args($post_args);

            if (str_contains($page_name, "?")) {
                throw new \RuntimeException("Query string included in page name");
            }
            $_SERVER['REQUEST_METHOD'] = $method;
            $_SERVER['REQUEST_URI'] = make_link($page_name, http_build_query($get_args));
            $_GET = $get_args;
            $_POST = $post_args;
            $page = new Page();
            send_event(new PageRequestEvent($method, $page_name, $get_args, $post_args));
            if ($page->mode == PageMode::REDIRECT) {
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
            $this->assertStringContainsString($title, $page->title);
        }

        protected function assert_title_matches(string $title): void
        {
            global $page;
            $this->assertStringMatchesFormat($title, $page->title);
        }

        protected function assert_no_title(string $title): void
        {
            global $page;
            $this->assertStringNotContainsString($title, $page->title);
        }

        protected function assert_response(int $code): void
        {
            global $page;
            $this->assertEquals($code, $page->code);
        }

        protected function page_to_text(string $section = null): string
        {
            global $page;
            if ($page->mode == PageMode::PAGE) {
                $text = $page->title . "\n";
                foreach ($page->blocks as $block) {
                    if (is_null($section) || $section == $block->section) {
                        $text .= $block->header . "\n";
                        $text .= $block->body . "\n\n";
                    }
                }
                return $text;
            } elseif ($page->mode == PageMode::DATA) {
                return $page->data;
            } else {
                $this->fail("Page mode is {$page->mode->name} (only PAGE and DATA are supported)");
            }
        }

        /**
         * Assert that the page contains the given text somewhere in the blocks
         */
        protected function assert_text(string $text, string $section = null): void
        {
            $this->assertStringContainsString($text, $this->page_to_text($section));
        }

        protected function assert_no_text(string $text, string $section = null): void
        {
            $this->assertStringNotContainsString($text, $this->page_to_text($section));
        }

        /**
         * Assert that the page contains the given text somewhere in the binary data
         */
        protected function assert_content(string $content): void
        {
            global $page;
            $this->assertStringContainsString($content, $page->data);
        }

        protected function assert_no_content(string $content): void
        {
            global $page;
            $this->assertStringNotContainsString($content, $page->data);
        }

        /**
         * @param string[] $tags
         * @param int[] $results
         */
        protected function assert_search_results(array $tags, array $results): void
        {
            $images = Search::find_images(0, null, $tags);
            $ids = [];
            foreach ($images as $image) {
                $ids[] = $image->id;
            }
            $this->assertEquals($results, $ids);
        }

        protected function assertException(string $type, callable $function): \Exception|null
        {
            $exception = null;
            try {
                call_user_func($function);
            } catch (\Exception $e) {
                $exception = $e;
            }

            self::assertThat(
                $exception,
                new \PHPUnit\Framework\Constraint\Exception($type),
                "Expected exception of type $type, but got " . ($exception ? get_class($exception) : "none")
            );
            return $exception;
        }

        // user things
        protected static function log_in_as_admin(): void
        {
            send_event(new UserLoginEvent(User::by_name(self::$admin_name)));
        }

        protected static function log_in_as_user(): void
        {
            send_event(new UserLoginEvent(User::by_name(self::$user_name)));
        }

        protected static function log_out(): void
        {
            global $config;
            send_event(new UserLoginEvent(User::by_id($config->get_int("anon_id", 0))));
        }

        // post things
        protected function post_image(string $filename, string $tags): int
        {
            $dae = send_event(new DataUploadEvent($filename, basename($filename), 0, [
                "filename" => $filename,
                "tags" => $tags,
            ]));
            if(count($dae->images) == 0) {
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
} else {
    abstract class ShimmiePHPUnitTestCase
    {
    }
}
