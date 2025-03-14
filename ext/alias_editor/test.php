<?php

declare(strict_types=1);

namespace Shimmie2;

class AliasEditorTest extends ShimmiePHPUnitTestCase
{
    public function testAliasList(): void
    {
        $this->get_page('alias/list');
        self::assert_response(200);
        self::assert_title("Alias List");
    }

    public function testAliasListReadOnly(): void
    {
        $this->log_in_as_user();
        $this->get_page('alias/list');
        self::assert_title("Alias List");
        self::assert_no_text("Add");

        $this->log_out();
        $this->get_page('alias/list');
        self::assert_title("Alias List");
        self::assert_no_text("Add");
    }

    public function testAliasOneToOne(): void
    {
        $this->log_in_as_admin();

        $this->get_page("alias/export/aliases.csv");
        self::assert_no_text("test1");

        $this->post_page('alias/add', ['c_oldtag' => 'test1', 'c_newtag' => 'test2']);
        $this->get_page('alias/list');
        self::assert_text("test1");
        $this->get_page("alias/export/aliases.csv");
        self::assert_text('"test1","test2"');

        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test1");
        $this->get_page("post/view/$image_id"); # check that the tag has been replaced
        self::assert_title("Post $image_id: test2");
        $this->get_page("post/list/test1/1"); # searching for an alias should find the master tag
        self::assert_response(302);
        $this->get_page("post/list/test2/1"); # check that searching for the main tag still works
        self::assert_response(302);
        $this->delete_image($image_id);

        $this->post_page('alias/remove', ['d_oldtag' => 'test1']);
        $this->get_page('alias/list');
        self::assert_title("Alias List");
        self::assert_no_text("test1");
    }

    public function testAliasOneToMany(): void
    {
        $this->log_in_as_admin();

        $this->get_page("alias/export/aliases.csv");
        self::assert_no_text("multi");

        send_event(new AddAliasEvent("onetag", "multi tag"));
        $this->get_page('alias/list');
        self::assert_text("multi");
        self::assert_text("tag");
        $this->get_page("alias/export/aliases.csv");
        self::assert_text('"onetag","multi tag"');

        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "onetag");
        $image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "onetag");
        $this->get_page("post/list/onetag/1"); # searching for an aliased tag should find its aliases
        self::assert_title("multi tag");
        self::assert_no_text("No Posts Found");
        $this->get_page("post/list/multi/1");
        self::assert_title("multi");
        self::assert_no_text("No Posts Found");
        $this->get_page("post/list/multi tag/1");
        self::assert_title("multi tag");
        self::assert_no_text("No Posts Found");
        $this->delete_image($image_id_1);
        $this->delete_image($image_id_2);

        send_event(new DeleteAliasEvent("onetag"));
        $this->get_page('alias/list');
        self::assert_title("Alias List");
        self::assert_no_text("test1");
    }
}
