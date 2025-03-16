<?php

declare(strict_types=1);

namespace Shimmie2;

final class AliasEditorTest extends ShimmiePHPUnitTestCase
{
    public function testAliasList(): void
    {
        self::get_page('alias/list');
        self::assert_response(200);
        self::assert_title("Alias List");
    }

    public function testAliasListReadOnly(): void
    {
        self::log_in_as_user();
        self::get_page('alias/list');
        self::assert_title("Alias List");
        self::assert_no_text("Add");

        self::log_out();
        self::get_page('alias/list');
        self::assert_title("Alias List");
        self::assert_no_text("Add");
    }

    public function testAliasOneToOne(): void
    {
        self::log_in_as_admin();

        self::get_page("alias/export/aliases.csv");
        self::assert_no_text("test1");

        self::post_page('alias/add', ['c_oldtag' => 'test1', 'c_newtag' => 'test2']);
        self::get_page('alias/list');
        self::assert_text("test1");
        self::get_page("alias/export/aliases.csv");
        self::assert_text('"test1","test2"');

        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test1");
        self::get_page("post/view/$image_id"); # check that the tag has been replaced
        self::assert_title("Post $image_id: test2");
        self::get_page("post/list/test1/1"); # searching for an alias should find the master tag
        self::assert_response(302);
        self::get_page("post/list/test2/1"); # check that searching for the main tag still works
        self::assert_response(302);
        $this->delete_image($image_id);

        self::post_page('alias/remove', ['d_oldtag' => 'test1']);
        self::get_page('alias/list');
        self::assert_title("Alias List");
        self::assert_no_text("test1");
    }

    public function testAliasOneToMany(): void
    {
        self::log_in_as_admin();

        self::get_page("alias/export/aliases.csv");
        self::assert_no_text("multi");

        send_event(new AddAliasEvent("onetag", "multi tag"));
        self::get_page('alias/list');
        self::assert_text("multi");
        self::assert_text("tag");
        self::get_page("alias/export/aliases.csv");
        self::assert_text('"onetag","multi tag"');

        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "onetag");
        $image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "onetag");
        self::get_page("post/list/onetag/1"); # searching for an aliased tag should find its aliases
        self::assert_title("multi tag");
        self::assert_no_text("No Posts Found");
        self::get_page("post/list/multi/1");
        self::assert_title("multi");
        self::assert_no_text("No Posts Found");
        self::get_page("post/list/multi tag/1");
        self::assert_title("multi tag");
        self::assert_no_text("No Posts Found");
        $this->delete_image($image_id_1);
        $this->delete_image($image_id_2);

        send_event(new DeleteAliasEvent("onetag"));
        self::get_page('alias/list');
        self::assert_title("Alias List");
        self::assert_no_text("test1");
    }
}
