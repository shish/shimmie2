<?php declare(strict_types=1);
class NotATagTest extends ShimmiePHPUnitTestCase
{
    public function testUntags()
    {
        global $database;
        $database->execute("DELETE FROM untags");
        $database->execute("INSERT INTO untags(tag, redirect) VALUES (:tag, :redirect)", ["tag"=>"face", "redirect"=>"no-body-parts.html"]);

        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id($image_id);

        // Original
        $this->get_page("post/view/$image_id");
        $this->assert_title("Post $image_id: pbx");

        // Modified OK
        send_event(new TagSetEvent($image, ["two"]));
        $this->get_page("post/view/$image_id");
        $this->assert_title("Post $image_id: two");

        // Modified Bad as user - redirect
        try {
            send_event(new TagSetEvent($image, ["three", "face"]));
            $this->assertTrue(false, "Should've had an exception");
        } catch (TagSetException $e) {
            $this->assertTrue(true);
        }
        $this->get_page("post/view/$image_id");
        $this->assert_title("Post $image_id: two");

        // Modified Bad as admin - ignore (should warn?)
        $this->log_in_as_admin();
        send_event(new TagSetEvent($image, ["four", "face"]));
        $this->get_page("post/view/$image_id");
        $this->assert_title("Post $image_id: four");
    }
}
