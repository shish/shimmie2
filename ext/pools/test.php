<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\Attributes\Depends;

final class PoolsTest extends ShimmiePHPUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Clean up any leftovers to create a fresh test env
        self::log_in_as_admin();
        global $database;
        foreach ($database->get_col("SELECT id FROM pools") as $pool_id) {
            send_event(new PoolDeletionEvent((int)$pool_id));
        }
    }

    public function testAnon(): void
    {
        self::log_out();

        self::get_page('pool/list');
        self::assert_title("Pools");

        self::assertException(PermissionDenied::class, function () {
            self::get_page('pool/new');
        });
    }

    /**
     * @return array{0: int, 1: array{0: int, 1: int}}
     */
    public function testCreate(): array
    {
        self::log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "pbx");

        self::get_page("pool/new");

        $page = self::post_page("pool/create", [
            "title" => "foo",
            "public" => "Y",
            "description" => "My pool description",
        ]);
        $pool_id = (int)(explode("/", $page->redirect)[4]);
        send_event(new PoolAddPostsEvent($pool_id, [$image_id_1, $image_id_2]));

        self::assertGreaterThan(0, $pool_id);

        return [$pool_id, [$image_id_1, $image_id_2]];
    }

    #[Depends('testCreate')]
    public function testOnViewImage(): void
    {
        [$pool_id, $image_ids] = $this->testCreate();

        Ctx::$config->set(PoolsConfig::ADDER_ON_VIEW_IMAGE, true);
        Ctx::$config->set(PoolsConfig::INFO_ON_VIEW_IMAGE, true);
        Ctx::$config->set(PoolsConfig::SHOW_NAV_LINKS, true);

        self::get_page("post/view/{$image_ids[0]}");
        self::assert_text("Pool");
    }

    #[Depends('testCreate')]
    public function testSearch(): void
    {
        [$pool_id, $image_ids] = $this->testCreate();

        self::get_page("post/list/pool=$pool_id/1");
        self::assert_text("Pool");

        self::get_page("post/list/pool_by_name=foo/1");
        self::assert_text("Pool");
    }

    #[Depends('testCreate')]
    public function testList(): void
    {
        $this->testCreate();
        self::get_page("pool/list");
        self::assert_text("Pool");
    }

    #[Depends('testCreate')]
    public function testView(): void
    {
        [$pool_id, $image_ids] = $this->testCreate();

        self::get_page("pool/view/$pool_id");
        self::assert_text("Pool");
    }

    #[Depends('testCreate')]
    public function testHistory(): void
    {
        [$pool_id, $image_ids] = $this->testCreate();

        self::get_page("pool/updated/$pool_id");
        self::assert_text("Pool");
    }

    #[Depends('testCreate')]
    public function testImport(): void
    {
        [$pool_id, $image_ids] = $this->testCreate();

        self::post_page("pool/import/$pool_id", [
            "pool_tag" => "test"
        ]);
        self::assert_text("Pool");
    }

    /**
     * @return array{0: int, 1: array{0: int, 1:int}}
     */
    #[Depends('testCreate')]
    public function testRemovePosts(): array
    {
        [$pool_id, $image_ids] = $this->testCreate();

        $page = self::post_page("pool/remove_posts/$pool_id", [
            "check" => [(string)($image_ids[0]), (string)($image_ids[1])]
        ]);
        self::assertEquals(PageMode::REDIRECT, $page->mode);

        return [$pool_id, $image_ids];
    }

    #[Depends('testRemovePosts')]
    public function testAddPosts(): void
    {
        [$pool_id, $image_ids] = $this->testRemovePosts();

        $page = self::post_page("pool/add_posts/$pool_id", [
            "check" => [(string)($image_ids[0]), (string)($image_ids[1])]
        ]);
        self::assertEquals(PageMode::REDIRECT, $page->mode);
    }

    /**
     * @return array{0: int, 1: array{0: int, 1:int}}
     */
    #[Depends('testCreate')]
    public function testEditDescription(): array
    {
        [$pool_id, $image_ids] = $this->testCreate();

        $page = self::post_page("pool/edit_description/$pool_id", [
            "description" => "Updated description"
        ]);
        self::assertEquals(PageMode::REDIRECT, $page->mode);

        return [$pool_id, $image_ids];
    }

    public function testNuke(): void
    {
        self::log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "pbx");

        self::get_page("pool/new");

        $page = self::post_page("pool/create", [
            "title" => "foo2",
            "public" => "Y",
            "description" => "My pool description",
        ]);
        $pool_id = (int)(explode("/", $page->redirect)[4]);
        send_event(new PoolAddPostsEvent($pool_id, [$image_id_1, $image_id_2]));

        $page = self::post_page("pool/nuke/$pool_id");
        self::assertEquals(PageMode::REDIRECT, $page->mode);
    }
}
