<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\Constraint\IsEqual;

final class SearchParametersTest extends ShimmiePHPUnitTestCase
{
    /**
     * @param string $tags
     * @param TagCondition[] $expected_tag_conditions
     * @param MetadataCondition[] $expected_img_conditions
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
                "imgs" => $params->metadata_conditions,
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
                new MetadataCondition(new Querylet("trash != TRUE")),
                new MetadataCondition(new Querylet("approved = TRUE")),
                new MetadataCondition(new Querylet("private != TRUE OR owner_id = :private_owner_id", [
                    "private_owner_id" => 1])),
                new MetadataCondition(new Querylet("rating IN ('?', 's', 'q', 'e')", [])),
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
                new MetadataCondition(new Querylet("trash != TRUE")),
                new MetadataCondition(new Querylet("approved = TRUE")),
                new MetadataCondition(new Querylet("private != TRUE OR owner_id = :private_owner_id", [
                    "private_owner_id" => 1])),
                new MetadataCondition(new Querylet("rating IN ('?', 's', 'q', 'e')", [])),
                new MetadataCondition(new Querylet("images.hash = :hash", ["hash" => "1234567890"])),
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
                new MetadataCondition(new Querylet("trash != TRUE")),
                new MetadataCondition(new Querylet("approved = TRUE")),
                new MetadataCondition(new Querylet("private != TRUE OR owner_id = :private_owner_id", [
                    "private_owner_id" => 1])),
                new MetadataCondition(new Querylet("rating IN ('?', 's', 'q', 'e')", [])),
                new MetadataCondition(new Querylet("width / :width1 = height / :height1", ['width1' => 42,
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
                new MetadataCondition(new Querylet("trash != TRUE")),
                new MetadataCondition(new Querylet("approved = TRUE")),
                new MetadataCondition(new Querylet("private != TRUE OR owner_id = :private_owner_id", [
                    "private_owner_id" => 1])),
                new MetadataCondition(new Querylet("rating IN ('?', 's', 'q', 'e')", [])),
            ],
            "images.numeric_score DESC"
        );
    }
}
