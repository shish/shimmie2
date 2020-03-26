<?php declare(strict_types=1);
class PoolsTest extends ShimmiePHPUnitTestCase
{
    public function testAnon()
    {
        $this->get_page('pool/list');
        $this->assert_title("Pools");

        $this->get_page('pool/new');
        $this->assert_title("Error");
    }

    public function testCreate()
    {
        global $user;

        $this->log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "pbx");
        $this->assertNotNull($image_id_1);
        $this->assertNotNull($image_id_2);

        $this->get_page("pool/new");

        $event = new PoolCreationEvent("foo", $user, true, "My pool description");
        send_event($event);
        $pool_id = $event->new_id;

        send_event(new PoolAddPostsEvent($pool_id, [$image_id_1, $image_id_2]));

        return [$pool_id, [$image_id_1, $image_id_2]];
    }

    /** @depends testCreate */
    public function testOnViewImage($args)
    {
        [$pool_id, $image_ids] = $args;

        global $config;
        $config->set_bool(PoolsConfig::ADDER_ON_VIEW_IMAGE, true);
        $config->set_bool(PoolsConfig::INFO_ON_VIEW_IMAGE, true);

        $this->get_page("post/view/{$image_ids[0]}");
        $this->assert_text("Pool");
    }

    /** @depends testCreate */
    public function testSearch($args)
    {
        [$pool_id, $image_ids] = $args;

        $this->get_page("post/list/pool=$pool_id/1");
        $this->assert_text("Pool");

        $this->get_page("post/list/pool_by_name=demo_pool/1");
        $this->assert_text("Pool");
    }

    /** @depends testCreate */
    public function testList($args)
    {
        [$pool_id, $image_ids] = $args;

        $this->get_page("pool/list");
        $this->assert_text("Pool");
    }

    /** @depends testCreate */
    public function testView($args)
    {
        [$pool_id, $image_ids] = $args;

        $this->get_page("pool/view/$pool_id");
        $this->assert_text("Pool");
    }

    /** @depends testCreate */
    public function testHitory($args)
    {
        [$pool_id, $image_ids] = $args;

        $this->get_page("pool/updated/$pool_id");
        $this->assert_text("Pool");
    }
}
