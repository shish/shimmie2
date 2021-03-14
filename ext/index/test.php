<?php declare(strict_types=1);
class IndexTest extends ShimmiePHPUnitTestCase
{
    public function testIndexPage()
    {
        $this->get_page('post/list');
        $this->assert_title("Welcome to Shimmie");
        $this->assert_no_text("Prev | Index | Next");

        $this->log_in_as_user();
        $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $this->post_image("tests/bedroom_workshop.jpg", "thing computer computing bedroom workshop");
        $this->log_out();

        $this->get_page('post/list');
        $this->assert_title("Shimmie");
        // FIXME
        //$this->assert_text("Prev | Index | Next");

        $this->get_page('post/list/-1');
        $this->assert_title("Shimmie");

        $this->get_page('post/list/0');
        $this->assert_title("Shimmie");

        $this->get_page('post/list/1');
        $this->assert_title("Shimmie");

        $this->get_page('post/list/99999');
        $this->assert_response(404);

        # No results: 404
        $this->get_page('post/list/maumaumau/1');
        $this->assert_response(404);

        # One result: 302
        $this->get_page("post/list/pbx/1");
        $this->assert_response(302);

        # Multiple results: 200
        $this->get_page('post/list/computer/1');
        $this->assert_response(200);
    }

    public function testWeirdTags()
    {
        $this->log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "question? colon:thing exclamation!");
        $image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "question. colon_thing exclamation%");

        $this->assert_search_results(["question?"], [$image_id_1]);
        $this->assert_search_results(["question."], [$image_id_2]);
        $this->assert_search_results(["colon:thing"], [$image_id_1]);
        $this->assert_search_results(["colon_thing"], [$image_id_2]);
        $this->assert_search_results(["exclamation!"], [$image_id_1]);
        $this->assert_search_results(["exclamation%"], [$image_id_2]);
    }

    // base case
    public function testUpload(): array
    {
        $this->log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "thing computer screenshot pbx phone");
        $image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "thing computer computing bedroom workshop");
        $this->log_out();

        # make sure both uploads were ok
        $this->assertTrue($image_id_1 > 0);
        $this->assertTrue($image_id_2 > 0);

        return [$image_id_1, $image_id_2];
    }

    /* * * * * * * * * * *
    * Tag Search         *
    * * * * * * * * * * */
    /** @depends testUpload */
    public function testTagSearchNoResults($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_search_results(["maumaumau"], []);
    }

    /** @depends testUpload */
    public function testTagSearchOneResult($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_search_results(["pbx"], [$image_ids[0]]);
    }

    /** @depends testUpload */
    public function testTagSearchManyResults($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_search_results(["computer"], [$image_ids[1], $image_ids[0]]);
    }

    /* * * * * * * * * * *
    * Multi-Tag Search   *
    * * * * * * * * * * */
    /** @depends testUpload */
    public function testMultiTagSearchNoResults($image_ids)
    {
        $image_ids = $this->testUpload();
        # multiple tags, one of which doesn't exist
        # (test the "one tag doesn't exist = no hits" path)
        $this->assert_search_results(["computer", "asdfasdfwaffle"], []);
    }

    /** @depends testUpload */
    public function testMultiTagSearchOneResult($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_search_results(["computer", "screenshot"], [$image_ids[0]]);
    }

    /** @depends testUpload */
    public function testMultiTagSearchManyResults($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_search_results(["computer", "thing"], [$image_ids[1], $image_ids[0]]);
    }

    /* * * * * * * * * * *
    * Meta Search        *
    * * * * * * * * * * */
    /** @depends testUpload */
    public function testMetaSearchNoResults($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_search_results(["hash=1234567890"], []);
        $this->assert_search_results(["ratio=42:12345"], []);
    }

    /** @depends testUpload */
    public function testMetaSearchOneResult($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_search_results(["hash=feb01bab5698a11dd87416724c7a89e3"], [$image_ids[0]]);
        $this->assert_search_results(["md5=feb01bab5698a11dd87416724c7a89e3"], [$image_ids[0]]);
        $this->assert_search_results(["id={$image_ids[1]}"], [$image_ids[1]]);
        $this->assert_search_results(["filename=screenshot"], [$image_ids[0]]);
    }

    /** @depends testUpload */
    public function testMetaSearchManyResults($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_search_results(["size=640x480"], [$image_ids[1], $image_ids[0]]);
        $this->assert_search_results(["tags=5"], [$image_ids[1], $image_ids[0]]);
        $this->assert_search_results(["ext=jpg"], [$image_ids[1], $image_ids[0]]);
    }

    /* * * * * * * * * * *
    * Wildcards          *
    * * * * * * * * * * */
    /** @depends testUpload */
    public function testWildSearchNoResults($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_search_results(["asdfasdf*"], []);
    }

    /** @depends testUpload */
    public function testWildSearchOneResult($image_ids)
    {
        $image_ids = $this->testUpload();
        // Only the first image matches both the wildcard and the tag.
        // This checks for https://github.com/shish/shimmie2/issues/547
        $this->assert_search_results(["comp*", "screenshot"], [$image_ids[0]]);
    }

    /** @depends testUpload */
    public function testWildSearchManyResults($image_ids)
    {
        $image_ids = $this->testUpload();
        // two images match comp* - one matches it once,
        // one matches it twice
        $this->assert_search_results(["comp*"], [$image_ids[1], $image_ids[0]]);
    }

    /* * * * * * * * * * *
    * Mixed              *
    * * * * * * * * * * */
    /** @depends testUpload */
    public function testMixedSearchTagMeta($image_ids)
    {
        $image_ids = $this->testUpload();
        // multiple tags, many results
        $this->assert_search_results(["computer", "size=640x480"], [$image_ids[1], $image_ids[0]]);
    }
    // tag + negative
    // wildcards + ???

    /* * * * * * * * * * *
    * Negative           *
    * * * * * * * * * * */
    /** @depends testUpload */
    public function testNegative($image_ids)
    {
        $image_ids = $this->testUpload();

        // negative tag, should have one result
        $this->assert_search_results(["computer", "-pbx"], [$image_ids[1]]);

        // negative tag alone, should work
        $this->assert_search_results(["-pbx"], [$image_ids[1]]);
    }

    // This isn't really an index thing, we just want to test this from
    // SOMEWHERE because the default theme doesn't use them.
    public function test_nav()
    {
        send_event(new UserLoginEvent(User::by_name(self::$user_name)));
        send_event(new PageNavBuildingEvent());
        // just a few common parents
        foreach (["help", "posts", "system", "user"] as $parent) {
            send_event(new PageSubNavBuildingEvent($parent));
        }
        $this->assertTrue(true);
    }
}
