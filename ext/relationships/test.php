<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\Attributes\Depends;

class RelationshipsTest extends ShimmiePHPUnitTestCase
{
    //=================================================================
    // Set by box
    //=================================================================

    /**
     * @return array{0: Image, 1: Image, 2: Image}
     */
    public function testNoParent(): array
    {
        $this->log_in_as_user();

        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "pbx");
        $image_id_3 = $this->post_image("tests/favicon.png", "pbx");

        $image_1 = Image::by_id_ex($image_id_1);
        $image_2 = Image::by_id_ex($image_id_2);
        $image_3 = Image::by_id_ex($image_id_3);

        $this->assertNull($image_1['parent_id']);
        $this->assertNull($image_2['parent_id']);
        $this->assertNull($image_3['parent_id']);
        $this->assertFalse($image_1['has_children']);
        $this->assertFalse($image_2['has_children']);
        $this->assertFalse($image_3['has_children']);

        return [$image_1, $image_2, $image_3];
    }

    /**
     * @return array{0:Image, 1:Image, 2:Image}
     */
    #[Depends('testNoParent')]
    public function testSetParent(): array
    {
        [$image_1, $image_2, $image_3] = $this->testNoParent();

        send_event(new ImageRelationshipSetEvent($image_2->id, $image_1->id));

        // refresh data from database
        $image_1 = Image::by_id_ex($image_1->id);
        $image_2 = Image::by_id_ex($image_2->id);
        $image_3 = Image::by_id_ex($image_3->id);

        $this->assertNull($image_1['parent_id']);
        $this->assertEquals($image_1->id, $image_2['parent_id']);
        $this->assertNull($image_3['parent_id']);
        $this->assertTrue($image_1['has_children']);
        $this->assertFalse($image_2['has_children']);
        $this->assertFalse($image_3['has_children']);

        return [$image_1, $image_2, $image_3];
    }

    /**
     * @return array{0:Image, 1:Image, 2:Image}
     */
    #[Depends('testSetParent')]
    public function testChangeParent(): array
    {
        [$image_1, $image_2, $image_3] = $this->testSetParent();
        send_event(new ImageRelationshipSetEvent($image_2->id, $image_3->id));

        // refresh data from database
        $image_1 = Image::by_id_ex($image_1->id);
        $image_2 = Image::by_id_ex($image_2->id);
        $image_3 = Image::by_id_ex($image_3->id);

        $this->assertNull($image_1['parent_id']);
        $this->assertEquals($image_3->id, $image_2['parent_id']);
        $this->assertNull($image_3['parent_id']);
        $this->assertFalse($image_2['has_children']);
        $this->assertFalse($image_2['has_children']);
        $this->assertTrue($image_3['has_children']);

        return [$image_1, $image_2, $image_3];
    }

    #[Depends('testSetParent')]
    public function testSearch(): void
    {
        [$image_1, $image_2, $image_3] = $this->testSetParent();

        $this->assert_search_results(["parent:any"], [$image_2->id]);
        $this->assert_search_results(["parent:none"], [$image_3->id, $image_1->id]);
        $this->assert_search_results(["parent:{$image_1->id}"], [$image_2->id]);

        $this->assert_search_results(["child:any"], [$image_1->id]);
        $this->assert_search_results(["child:none"], [$image_3->id, $image_2->id]);
    }

    #[Depends('testChangeParent')]
    public function testRemoveParent(): void
    {
        [$image_1, $image_2, $image_3] = $this->testChangeParent();

        global $database;
        $database->execute(
            "UPDATE images SET parent_id=NULL, has_children=:false",
            ["false" => false]
        );
        // FIXME: send_event(new ImageRelationshipSetEvent($image_2->id, null));

        // refresh data from database
        $image_1 = Image::by_id_ex($image_1->id);
        $image_2 = Image::by_id_ex($image_2->id);
        $image_3 = Image::by_id_ex($image_3->id);

        $this->assertNull($image_1['parent_id']);
        $this->assertNull($image_2['parent_id']);
        $this->assertNull($image_3['parent_id']);
        $this->assertFalse($image_2['has_children']);
        $this->assertFalse($image_2['has_children']);
        $this->assertFalse($image_3['has_children']);
    }

    //=================================================================
    // Set by tag
    //=================================================================

    /**
     * @return array{0:Image, 1:Image, 2:Image}
     */
    public function testSetParentByTagBase(): array
    {
        $this->log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "pbx");
        $image_id_3 = $this->post_image("tests/favicon.png", "pbx");

        $image_1 = Image::by_id_ex($image_id_1);
        $image_2 = Image::by_id_ex($image_id_2);
        $image_3 = Image::by_id_ex($image_id_3);

        $this->assertNull($image_1['parent_id']);
        $this->assertNull($image_2['parent_id']);
        $this->assertNull($image_3['parent_id']);
        $this->assertFalse($image_1['has_children']);
        $this->assertFalse($image_2['has_children']);
        $this->assertFalse($image_3['has_children']);

        return [$image_1, $image_2, $image_3];
    }

    /**
     * @return array{0:Image, 1:Image, 2:Image}
     */
    #[Depends('testSetParentByTagBase')]
    public function testSetParentByTag(): array
    {
        [$image_1, $image_2, $image_3] = $this->testSetParentByTagBase();

        send_event(new TagSetEvent($image_2, ["pbx", "parent:{$image_1->id}"]));

        // refresh data from database
        $image_1 = Image::by_id_ex($image_1->id);
        $image_2 = Image::by_id_ex($image_2->id);
        $image_3 = Image::by_id_ex($image_3->id);

        $this->assertEquals(["pbx"], $image_2->get_tag_array());
        $this->assertNull($image_1['parent_id']);
        $this->assertEquals($image_1->id, $image_2['parent_id']);
        $this->assertNull($image_3['parent_id']);
        $this->assertTrue($image_1['has_children']);
        $this->assertFalse($image_2['has_children']);
        $this->assertFalse($image_3['has_children']);

        return [$image_1, $image_2, $image_3];
    }

    /**
     * @return array{0:Image, 1:Image, 2:Image}
     */
    #[Depends('testSetParentByTag')]
    public function testSetChildByTag(): array
    {
        [$image_1, $image_2, $image_3] = $this->testSetParentByTag();

        send_event(new TagSetEvent($image_3, ["pbx", "child:{$image_1->id}"]));

        // refresh data from database
        $image_1 = Image::by_id_ex($image_1->id);
        $image_2 = Image::by_id_ex($image_2->id);
        $image_3 = Image::by_id_ex($image_3->id);

        $this->assertEquals(["pbx"], $image_3->get_tag_array());
        $this->assertEquals($image_3->id, $image_1['parent_id']);
        $this->assertEquals($image_1->id, $image_2['parent_id']);
        $this->assertNull($image_3['parent_id']);
        $this->assertTrue($image_1['has_children']);
        $this->assertFalse($image_2['has_children']);
        $this->assertTrue($image_3['has_children']);

        return [$image_1, $image_2, $image_3];
    }

    #[Depends('testSetChildByTag')]
    public function testRemoveParentByTag(): void
    {
        [$image_1, $image_2, $image_3] = $this->testSetChildByTag();

        // check parent is set
        $this->assertEquals($image_2['parent_id'], $image_1->id);

        // un-set it
        send_event(new TagSetEvent($image_2, ["pbx", "parent:none"]));

        // refresh data from database
        $image_2 = Image::by_id_ex($image_2->id);

        // check it was unset
        $this->assertEquals(["pbx"], $image_2->get_tag_array());
        $this->assertNull($image_2['parent_id']);
    }
}
