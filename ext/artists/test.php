<?php declare(strict_types=1);
class ArtistTest extends ShimmiePHPUnitTestCase
{
    public function testSearch()
    {
        # FIXME: check that the results are there
        $this->get_page("post/list/author=bob/1");
        #$this->assert_response(200);
    }
}
