<?php

declare(strict_types=1);

namespace Shimmie2;

class LinkScanTest extends ShimmiePHPUnitTestCase
{
    public function testScanPostView(): void
    {
        global $page;
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "TeStCase");
        $image_id_2 = $this->post_image("tests/favicon.png", "TeStCase");

        $_POST['text'] = "
        Look at http://example.com/post/view/{$image_id_1} there is an image

        http://example.com/post/view/{$image_id_2} is another one
        ";
        send_event(new AdminActionEvent("link_scan"));
        
        $this->assertEquals(PageMode::REDIRECT, $page->mode);
        $this->assertEquals("/test/post/list/id%3D{$image_id_1}%2C{$image_id_2}/1", $page->redirect);
    }

    public function testScanPostHash(): void
    {
        global $page;
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "TeStCase");
        $image_id_2 = $this->post_image("tests/favicon.png", "TeStCase");

        $_POST['text'] = "
        Look at http://example.com/_images/feb01bab5698a11dd87416724c7a89e3/foobar.jpg
        there is an image or search for e106ea2983e1b77f11e00c0c54e53805";
        send_event(new AdminActionEvent("link_scan"));
        
        $this->assertEquals(PageMode::REDIRECT, $page->mode);
        $this->assertEquals("/test/post/list/id%3D{$image_id_1}%2C{$image_id_2}/1", $page->redirect);
    }
}
