<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Constraint\IsEqual;

require_once "core/imageboard/search.php";

class SearchTest extends ShimmiePHPUnitTestCase
{
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


    /********************************************************
     * Test turning a string into an abstract query
     */
    private function assert_TTC(string $tags, array $expected_tag_conditions, array $expected_img_conditions, string $expected_order)
    {
        $class = new \ReflectionClass('\Shimmie2\Search');
        $terms_to_conditions = $class->getMethod("terms_to_conditions");
        $terms_to_conditions->setAccessible(true); // Use this if you are running PHP older than 8.1.0

        $obj = new Search();
        [$tag_conditions, $img_conditions, $order] = $terms_to_conditions->invokeArgs($obj, [Tag::explode($tags, false)]);

        static::assertThat(
            [
                "tags" => $expected_tag_conditions,
                "imgs" => $expected_img_conditions,
                "order" => $expected_order,
            ],
            new IsEqual([
                "tags" => $tag_conditions,
                "imgs" => $img_conditions,
                "order" => $order,
            ])
        );
    }

    public function testTTC_Empty()
    {
        $this->assert_TTC(
            "",
            [
            ],
            [
                new ImgCondition(new Querylet("trash != :true", ["true" => true])),
                new ImgCondition(new Querylet("private != :true OR owner_id = :private_owner_id", [
                    "private_owner_id" => 1,
                    "true" => true])),
                new ImgCondition(new Querylet("rating IN ('?', 's', 'q', 'e')", [])),
            ],
            "images.id DESC"
        );
    }

    public function testTTC_Hash()
    {
        $this->assert_TTC(
            "hash=1234567890",
            [
            ],
            [
                new ImgCondition(new Querylet("trash != :true", ["true" => true])),
                new ImgCondition(new Querylet("private != :true OR owner_id = :private_owner_id", [
                    "private_owner_id" => 1,
                    "true" => true])),
                new ImgCondition(new Querylet("rating IN ('?', 's', 'q', 'e')", [])),
                new ImgCondition(new Querylet("images.hash = :hash", ["hash" => "1234567890"])),
            ],
            "images.id DESC"
        );
    }

    public function testTTC_Ratio()
    {
        $this->assert_TTC(
            "ratio=42:12345",
            [
            ],
            [
                new ImgCondition(new Querylet("trash != :true", ["true" => true])),
                new ImgCondition(new Querylet("private != :true OR owner_id = :private_owner_id", [
                    "private_owner_id" => 1,
                    "true" => true])),
                new ImgCondition(new Querylet("rating IN ('?', 's', 'q', 'e')", [])),
                new ImgCondition(new Querylet("width / :width1 = height / :height1", ['width1' => 42,
                'height1' => 12345])),
            ],
            "images.id DESC"
        );
    }

    public function testTTC_Order()
    {
        $this->assert_TTC(
            "order=score",
            [
            ],
            [
                new ImgCondition(new Querylet("trash != :true", ["true" => true])),
                new ImgCondition(new Querylet("private != :true OR owner_id = :private_owner_id", [
                    "private_owner_id" => 1,
                    "true" => true])),
                new ImgCondition(new Querylet("rating IN ('?', 's', 'q', 'e')", [])),
            ],
            "images.numeric_score DESC"
        );
    }

    /********************************************************
     * Test turning an abstract query into SQL + fetching the results
     */
    private function assert_BSQ(
        array $tcs = [],
        array $ics = [],
        string $order = "id DESC",
        int $limit = 9999,
        int $start = 0,
        array $res = [],
        array $path = null,
    ) {
        global $database;

        $tcs = array_map(
            fn ($tag) => ($tag[0] == "-") ?
                new TagCondition(substr($tag, 1), false) :
                new TagCondition($tag),
            $tcs
        );

        $ics = array_map(
            fn ($ic) => send_event(new SearchTermParseEvent(0, $ic, []))->img_conditions,
            $ics
        );
        $ics = array_merge(...$ics);

        Search::$_search_path = [];

        $class = new \ReflectionClass('\Shimmie2\Search');
        $build_search_querylet = $class->getMethod("build_search_querylet");
        $build_search_querylet->setAccessible(true); // Use this if you are running PHP older than 8.1.0

        $obj = new Search();
        $querylet = $build_search_querylet->invokeArgs($obj, [$tcs, $ics, $order, $limit, $start]);

        $results = $database->get_all($querylet->sql, $querylet->variables);

        static::assertThat(
            [
                "res" => array_map(fn ($row) => $row['id'], $results),
                "path" => Search::$_search_path,
            ],
            new IsEqual([
                "res" => $res,
                "path" => $path ?? Search::$_search_path,
            ])
        );
    }

    /* * * * * * * * * * *
    * No-tag search      *
    * * * * * * * * * * */
    #[Depends('testUpload')]
    public function testBSQ_NoTags($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_BSQ(
            tcs: [],
            res: [$image_ids[1], $image_ids[0]],
            path: ["no_tags"],
        );
    }

    /* * * * * * * * * * *
    * Fast-path search   *
    * * * * * * * * * * */
    #[Depends('testUpload')]
    public function testBSQ_FastPath_NoResults($image_ids)
    {
        $this->testUpload();
        $this->assert_BSQ(
            tcs: ["maumaumau"],
            res: [],
            path: ["fast", "invalid_tag"],
        );
    }

    #[Depends('testUpload')]
    public function testBSQ_FastPath_OneResult($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_BSQ(
            tcs: ["pbx"],
            res: [$image_ids[0]],
            path: ["fast"],
        );
    }

    #[Depends('testUpload')]
    public function testBSQ_FastPath_ManyResults($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_BSQ(
            tcs: ["computer"],
            res: [$image_ids[1], $image_ids[0]],
            path: ["fast"],
        );
    }

    #[Depends('testUpload')]
    public function testBSQ_FastPath_WildNoResults($image_ids)
    {
        $this->testUpload();
        $this->assert_BSQ(
            tcs: ["asdfasdf*"],
            res: [],
            path: ["fast", "invalid_tag"],
        );
    }

    /**
     * Only the first image matches both the wildcard and the tag.
     * This checks for a bug where searching for "a* b" would return
     * an image tagged "a1 a2" because the number of matched tags
     * was equal to the number of searched tags.
     *
     * https://github.com/shish/shimmie2/issues/547
     */
    #[Depends('testUpload')]
    public function testBSQ_FastPath_WildOneResult($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_BSQ(
            tcs: ["screen*"],
            res: [$image_ids[0]],
            path: ["fast"],
        );
    }

    /**
     * Test that the fast path doesn't return duplicate results
     * when a wildcard matches one image multiple times.
     */
    #[Depends('testUpload')]
    public function testBSQ_FastPath_WildManyResults($image_ids)
    {
        $image_ids = $this->testUpload();
        // two images match comp* - one matches it once, one matches it twice
        $this->assert_BSQ(
            tcs: ["comp*"],
            res: [$image_ids[1], $image_ids[0]],
            path: ["fast"],
        );
    }

    /* * * * * * * * * * *
    * General search     *
    * * * * * * * * * * */
    #[Depends('testUpload')]
    public function testBSQ_GeneralPath_NoResults($image_ids)
    {
        $this->testUpload();
        # multiple tags, one of which doesn't exist
        # (test the "one tag doesn't exist = no hits" path)
        $this->assert_BSQ(
            tcs: ["computer", "not_a_tag"],
            res: [],
            path: ["general", "invalid_tag"],
        );
    }

    #[Depends('testUpload')]
    public function testBSQ_GeneralPath_OneResult($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_BSQ(
            tcs: ["computer", "screenshot"],
            res: [$image_ids[0]],
            path: ["general", "some_positives"],
        );
    }

    /**
     * Only the first image matches both the wildcard and the tag.
     * This checks for a bug where searching for "a* b" would return
     * an image tagged "a1 a2" because the number of matched tags
     * was equal to the number of searched tags.
     *
     * https://github.com/shish/shimmie2/issues/547
     */
    #[Depends('testUpload')]
    public function testBSQ_GeneralPath_WildOneResult($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_BSQ(
            tcs: ["comp*", "screenshot"],
            res: [$image_ids[0]],
            path: ["general", "some_positives"],
        );
    }

    #[Depends('testUpload')]
    public function testBSQ_GeneralPath_ManyResults($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_BSQ(
            tcs: ["computer", "thing"],
            res: [$image_ids[1], $image_ids[0]],
            path: ["general", "some_positives"],
        );
    }

    #[Depends('testUpload')]
    public function testBSQ_GeneralPath_WildManyResults($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_BSQ(
            tcs: ["comp*", "-asdf"],
            res: [$image_ids[1], $image_ids[0]],
            path: ["general", "some_positives"],
        );
    }

    #[Depends('testUpload')]
    public function testBSQ_GeneralPath_SubtractValidFromResults($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_BSQ(
            tcs: ["computer", "-pbx"],
            res: [$image_ids[1]],
            path: ["general", "some_positives"],
        );
    }

    #[Depends('testUpload')]
    public function testBSQ_GeneralPath_SubtractNotValidFromResults($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_BSQ(
            tcs: ["computer", "-not_a_tag"],
            res: [$image_ids[1], $image_ids[0]],
            path: ["general", "some_positives"],
        );
    }

    #[Depends('testUpload')]
    public function testBSQ_GeneralPath_SubtractValidFromDefault($image_ids)
    {
        $image_ids = $this->testUpload();
        // negative tag alone, should remove the image with that tag
        $this->assert_BSQ(
            tcs: ["-pbx"],
            res: [$image_ids[1]],
            path: ["general", "only_negative_tags"],
        );
    }

    #[Depends('testUpload')]
    public function testBSQ_GeneralPath_SubtractNotValidFromDefault($image_ids)
    {
        $image_ids = $this->testUpload();
        // negative that doesn't exist, should return all results
        $this->assert_BSQ(
            tcs: ["-not_a_tag"],
            res: [$image_ids[1], $image_ids[0]],
            path: ["general", "all_nonexistent_negatives"],
        );
    }

    #[Depends('testUpload')]
    public function testBSQ_GeneralPath_SubtractMultipleNotValidFromDefault($image_ids)
    {
        $image_ids = $this->testUpload();
        // multiple negative tags that don't exist, should return all results
        $this->assert_BSQ(
            tcs: ["-not_a_tag", "-also_not_a_tag"],
            res: [$image_ids[1], $image_ids[0]],
            path: ["general", "all_nonexistent_negatives"],
        );
    }

    /* * * * * * * * * * *
    * Meta Search        *
    * * * * * * * * * * */
    #[Depends('testUpload')]
    public function testBSQ_ImgCond_NoResults($image_ids)
    {
        $this->testUpload();
        $this->assert_BSQ(
            ics: ["hash=1234567890"],
            res: [],
            path: ["no_tags"],
        );
        $this->assert_BSQ(
            ics: ["ratio=42:12345"],
            res: [],
            path: ["no_tags"],
        );
    }

    #[Depends('testUpload')]
    public function testBSQ_ImgCond_OneResult($image_ids)
    {
        $image_ids = $this->testUpload();
        $this->assert_BSQ(
            ics: ["hash=feb01bab5698a11dd87416724c7a89e3"],
            res: [$image_ids[0]],
            path: ["no_tags"],
        );
        $this->assert_BSQ(
            ics: ["id={$image_ids[1]}"],
            res: [$image_ids[1]],
            path: ["no_tags"],
        );
        $this->assert_BSQ(
            ics: ["filename=screenshot"],
            res: [$image_ids[0]],
            path: ["no_tags"],
        );
    }

    #[Depends('testUpload')]
    public function testBSQ_ImgCond_ManyResults($image_ids)
    {
        $image_ids = $this->testUpload();

        $this->assert_BSQ(
            ics: ["size=640x480"],
            res: [$image_ids[1], $image_ids[0]],
            path: ["no_tags"],
        );
        $this->assert_BSQ(
            ics: ["tags=5"],
            res: [$image_ids[1], $image_ids[0]],
            path: ["no_tags"],
        );
        $this->assert_BSQ(
            ics: ["ext=jpg"],
            res: [$image_ids[1], $image_ids[0]],
            path: ["no_tags"],
        );
    }

    /* * * * * * * * * * *
    * Mixed              *
    * * * * * * * * * * */
    #[Depends('testUpload')]
    public function testBSQ_TagCondWithImgCond($image_ids)
    {
        $image_ids = $this->testUpload();
        // multiple tags, many results
        $this->assert_BSQ(
            tcs: ["computer"],
            ics: ["size=640x480"],
            res: [$image_ids[1], $image_ids[0]],
            path: ["general", "some_positives"],
        );
    }
}
