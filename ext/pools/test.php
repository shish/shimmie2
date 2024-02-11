<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\Attributes\Depends;

class PoolsTest extends ShimmiePHPUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Clean up any leftovers to create a fresh test env
        $this->log_in_as_admin();
        global $database;
        foreach ($database->get_col("SELECT id FROM pools") as $pool_id) {
            send_event(new PoolDeletionEvent((int)$pool_id));
        }
    }

    public function testAnon(): void
    {
        $this->log_out();

        $this->get_page('pool/list');
        $this->assert_title("Pools");

        $this->assertException(PermissionDenied::class, function () {
            $this->get_page('pool/new');
        });
    }

    /**
     * @return array{0: int, 1: array{0: int, 1: int}}
     */
    public function testCreate(): array
    {
        $this->log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "pbx");
        $this->assertNotNull($image_id_1);
        $this->assertNotNull($image_id_2);

        $this->get_page("pool/new");

        $page = $this->post_page("pool/create", [
            "title" => "foo",
            "public" => "Y",
            "description" => "My pool description",
        ]);
        $pool_id = (int)(explode("/", $page->redirect)[4]);
        send_event(new PoolAddPostsEvent($pool_id, [$image_id_1, $image_id_2]));

        return [$pool_id, [$image_id_1, $image_id_2]];
    }

    #[Depends('testCreate')]
    public function testOnViewImage(): void
    {
        [$pool_id, $image_ids] = $this->testCreate();

        global $config;
        $config->set_bool(PoolsConfig::ADDER_ON_VIEW_IMAGE, true);
        $config->set_bool(PoolsConfig::INFO_ON_VIEW_IMAGE, true);
        $config->set_bool(PoolsConfig::SHOW_NAV_LINKS, true);

        $this->get_page("post/view/{$image_ids[0]}");
        $this->assert_text("Pool");
    }

    #[Depends('testCreate')]
    public function testSearch(): void
    {
        [$pool_id, $image_ids] = $this->testCreate();

        $this->get_page("post/list/pool=$pool_id/1");
        $this->assert_text("Pool");

        $this->get_page("post/list/pool_by_name=demo_pool/1");
        $this->assert_text("Pool");
    }

    #[Depends('testCreate')]
    public function testList(): void
    {
        $this->testCreate();
        $this->get_page("pool/list");
        $this->assert_text("Pool");
    }

    #[Depends('testCreate')]
    public function testView(): void
    {
        [$pool_id, $image_ids] = $this->testCreate();

        $this->get_page("pool/view/$pool_id");
        $this->assert_text("Pool");
    }

    #[Depends('testCreate')]
    public function testHistory(): void
    {
        [$pool_id, $image_ids] = $this->testCreate();

        $this->get_page("pool/updated/$pool_id");
        $this->assert_text("Pool");
    }

    #[Depends('testCreate')]
    public function testImport(): void
    {
        [$pool_id, $image_ids] = $this->testCreate();

        $this->post_page("pool/import/$pool_id", [
            "pool_tag" => "test"
        ]);
        $this->assert_text("Pool");
    }

    /**
     * @return array{0: int, 1: array{0: int, 1:int}}
     */
    #[Depends('testCreate')]
    public function testRemovePosts(): array
    {
        [$pool_id, $image_ids] = $this->testCreate();

        $page = $this->post_page("pool/remove_posts/$pool_id", [
            "check" => [(string)($image_ids[0]), (string)($image_ids[1])]
        ]);
        $this->assertEquals(PageMode::REDIRECT, $page->mode);

        return [$pool_id, $image_ids];
    }

    #[Depends('testRemovePosts')]
    public function testAddPosts(): void
    {
        [$pool_id, $image_ids] = $this->testRemovePosts();

        $page = $this->post_page("pool/add_posts/$pool_id", [
            "check" => [(string)($image_ids[0]), (string)($image_ids[1])]
        ]);
        $this->assertEquals(PageMode::REDIRECT, $page->mode);
    }

    /**
     * @return array{0: int, 1: array{0: int, 1:int}}
     */
    #[Depends('testCreate')]
    public function testEditDescription(): array
    {
        [$pool_id, $image_ids] = $this->testCreate();

        $page = $this->post_page("pool/edit_description/$pool_id", [
            "description" => "Updated description"
        ]);
        $this->assertEquals(PageMode::REDIRECT, $page->mode);

        return [$pool_id, $image_ids];
    }

    public function testNuke(): void
    {
        $this->log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "pbx");
        $this->assertNotNull($image_id_1);
        $this->assertNotNull($image_id_2);

        $this->get_page("pool/new");

        $page = $this->post_page("pool/create", [
            "title" => "foo2",
            "public" => "Y",
            "description" => "My pool description",
        ]);
        $pool_id = (int)(explode("/", $page->redirect)[4]);
        send_event(new PoolAddPostsEvent($pool_id, [$image_id_1, $image_id_2]));

        $page = $this->post_page("pool/nuke/$pool_id");
        $this->assertEquals(PageMode::REDIRECT, $page->mode);
    }
}
