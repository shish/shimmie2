<?php declare(strict_types=1);
class RelationshipTest extends ShimmiePHPUnitTestCase
{
    public function testSetParent()
    {
        $this->log_in_as_user();

        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "pbx");
        $image_id_3 = $this->post_image("tests/favicon.png", "pbx");

        $image_1 = Image::by_id($image_id_1);
        $image_2 = Image::by_id($image_id_2);
        $image_3 = Image::by_id($image_id_3);

        $this->assertNull($image_1->parent_id);
        $this->assertNull($image_2->parent_id);
        $this->assertNull($image_3->parent_id);
        $this->assertFalse($image_1->has_children);
        $this->assertFalse($image_2->has_children);
        $this->assertFalse($image_3->has_children);

        $this->get_page("post/view/$image_id_2");
        $this->assert_title("Image $image_id_2: pbx");

        $this->markTestIncomplete();

        $this->set_field("tag_edit__parent", $image_id_1);
        $this->click("Set");


        $image_1 = Image::by_id($image_id_1);
        $image_2 = Image::by_id($image_id_2);
        $image_3 = Image::by_id($image_id_3);

        $this->assertNull($image_1->parent_id);
        $this->assertEquals($image_id_1, $image_2->parent_id);
        $this->assertNull($image_3->parent_id);
        $this->assertTrue($image_1->has_children);
        $this->assertFalse($image_2->has_children);
        $this->assertFalse($image_3->has_children);


        // Test changing to a different parent

        $this->get_page("post/view/$image_id_2");
        $this->assert_title("Image $image_id_2: pbx");

        $this->markTestIncomplete();

        $this->set_field("tag_edit__parent", $image_id_3);
        $this->click("Set");

        $image_1 = Image::by_id($image_id_1);
        $image_2 = Image::by_id($image_id_2);
        $image_3 = Image::by_id($image_id_3);

        $this->assertNull($image_1->parent_id);
        $this->assertEquals($image_id_3, $image_2->parent_id);
        $this->assertNull($image_3->parent_id);
        $this->assertFalse($image_2->has_children);
        $this->assertFalse($image_2->has_children);
        $this->assertTrue($image_3->has_children);


        // Test setting parent to none

        $this->get_page("post/view/$image_id_2");
        $this->assert_title("Image $image_id_2: pbx");

        $this->markTestIncomplete();

        $this->set_field("tag_edit__parent", "");
        $this->click("Set");

        $image_1 = Image::by_id($image_id_1);
        $image_2 = Image::by_id($image_id_2);
        $image_3 = Image::by_id($image_id_3);

        $this->assertNull($image_1->parent_id);
        $this->assertNull($image_2->parent_id);
        $this->assertNull($image_3->parent_id);
        $this->assertFalse($image_2->has_children);
        $this->assertFalse($image_2->has_children);
        $this->assertFalse($image_3->has_children);


        $this->log_out();

        $this->log_in_as_admin();
        $this->delete_image($image_id_1);
        $this->delete_image($image_id_2);
        $this->delete_image($image_id_3);
        $this->log_out();
    }

    public function testSetParentByTag()
    {
        $this->log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "pbx");
        $image_id_3 = $this->post_image("tests/favicon.png", "pbx");

        $image_1 = Image::by_id($image_id_1);
        $image_2 = Image::by_id($image_id_2);
        $image_3 = Image::by_id($image_id_3);

        $this->assertNull($image_1->parent_id);
        $this->assertNull($image_2->parent_id);
        $this->assertNull($image_3->parent_id);
        $this->assertFalse($image_1->has_children);
        $this->assertFalse($image_2->has_children);
        $this->assertFalse($image_3->has_children);

        // Test settings parent:#

        $this->get_page("post/view/$image_id_2");
        $this->assert_title("Image $image_id_2: pbx");

        $this->markTestIncomplete();

        $this->set_field("tag_edit__tags", "pbx parent:$image_id_1");
        $this->click("Set");

        $this->assert_title("Image $image_id_2: pbx");

        $image_1 = Image::by_id($image_id_1);
        $image_2 = Image::by_id($image_id_2);
        $image_3 = Image::by_id($image_id_3);

        $this->assertNull($image_1->parent_id);
        $this->assertEquals($image_id_1, $image_2->parent_id);
        $this->assertNull($image_3->parent_id);
        $this->assertTrue($image_1->has_children);
        $this->assertFalse($image_2->has_children);
        $this->assertFalse($image_3->has_children);

        // Test settings child:#

        $this->get_page("post/view/$image_id_3");
        $this->assert_title("Image $image_id_3: pbx");

        $this->markTestIncomplete();

        $this->set_field("tag_edit__tags", "pbx child:$image_id_1");
        $this->click("Set");

        $this->assert_title("Image $image_id_3: pbx");

        $image_1 = Image::by_id($image_id_1);
        $image_2 = Image::by_id($image_id_2);
        $image_3 = Image::by_id($image_id_3);

        $this->assertEquals($image_id_3, $image_1->parent_id);
        $this->assertEquals($image_id_1, $image_2->parent_id);
        $this->assertNull($image_3->parent_id);
        $this->assertTrue($image_1->has_children);
        $this->assertFalse($image_2->has_children);
        $this->assertTrue($image_3->has_children);

        // Test settings parent:none

        $this->get_page("post/view/$image_id_1");
        $this->assert_title("Image $image_id_1: pbx");

        $this->markTestIncomplete();

        $this->set_field("tag_edit__tags", "pbx parent:none");
        $this->click("Set");

        $this->assert_title("Image $image_id_1: pbx");

        $image_1 = Image::by_id($image_id_1);
        $image_2 = Image::by_id($image_id_2);
        $image_3 = Image::by_id($image_id_3);

        $this->assertNull($image_1->parent_id);
        $this->assertEquals($image_id_1, $image_2->parent_id);
        $this->assertNull($image_3->parent_id);
        $this->assertTrue($image_1->has_children);
        $this->assertFalse($image_2->has_children);
        $this->assertFalse($image_3->has_children);

        $this->log_out();

        $this->log_in_as_admin();
        $this->delete_image($image_id_1);
        $this->delete_image($image_id_2);
        $this->delete_image($image_id_3);
        $this->log_out();
    }
}
