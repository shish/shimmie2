<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\Constraint\IsEqual;

final class SearchParametersTest extends ShimmiePHPUnitTestCase
{
    /**
     * @param string $tags
     * @param TagCondition[] $expected_tag_conditions
     * @param ImgCondition[] $expected_img_conditions
     * @param string $expected_order
     */
    private function assert_TTC(
        string $tags,
        array $expected_tag_conditions,
        array $expected_img_conditions,
        string $expected_order,
    ): void {
        $params = SearchParameters::from_terms(SearchTerm::explode($tags));

        static::assertThat(
            [
                "tags" => $expected_tag_conditions,
                "imgs" => $expected_img_conditions,
                "order" => $expected_order,
            ],
            new IsEqual([
                "tags" => $params->tag_conditions,
                "imgs" => $params->img_conditions,
                "order" => $params->order,
            ])
        );
    }

    public function testTTC_Empty(): void
    {
        self::assert_TTC(
            "",
            [
            ],
            [
                new ImgCondition(new Querylet("trash != TRUE")),
                new ImgCondition(new Querylet("approved = TRUE")),
                new ImgCondition(new Querylet("private != TRUE OR owner_id = :private_owner_id", [
                    "private_owner_id" => 1])),
                new ImgCondition(new Querylet("rating IN ('?', 's', 'q', 'e')", [])),
            ],
            "images.id DESC"
        );
    }

    public function testTTC_Hash(): void
    {
        self::assert_TTC(
            "hash=1234567890",
            [
            ],
            [
                new ImgCondition(new Querylet("trash != TRUE")),
                new ImgCondition(new Querylet("approved = TRUE")),
                new ImgCondition(new Querylet("private != TRUE OR owner_id = :private_owner_id", [
                    "private_owner_id" => 1])),
                new ImgCondition(new Querylet("rating IN ('?', 's', 'q', 'e')", [])),
                new ImgCondition(new Querylet("images.hash = :hash", ["hash" => "1234567890"])),
            ],
            "images.id DESC"
        );
    }

    public function testTTC_Ratio(): void
    {
        self::assert_TTC(
            "ratio=42:12345",
            [
            ],
            [
                new ImgCondition(new Querylet("trash != TRUE")),
                new ImgCondition(new Querylet("approved = TRUE")),
                new ImgCondition(new Querylet("private != TRUE OR owner_id = :private_owner_id", [
                    "private_owner_id" => 1])),
                new ImgCondition(new Querylet("rating IN ('?', 's', 'q', 'e')", [])),
                new ImgCondition(new Querylet("width / :width1 = height / :height1", ['width1' => 42,
                'height1' => 12345])),
            ],
            "images.id DESC"
        );
    }

    public function testTTC_Order(): void
    {
        self::assert_TTC(
            "order=score",
            [
            ],
            [
                new ImgCondition(new Querylet("trash != TRUE")),
                new ImgCondition(new Querylet("approved = TRUE")),
                new ImgCondition(new Querylet("private != TRUE OR owner_id = :private_owner_id", [
                    "private_owner_id" => 1])),
                new ImgCondition(new Querylet("rating IN ('?', 's', 'q', 'e')", [])),
            ],
            "images.numeric_score DESC"
        );
    }
}
