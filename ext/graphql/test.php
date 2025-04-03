<?php

declare(strict_types=1);

namespace Shimmie2;

use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL as GQL;

final class GraphQLTest extends ShimmiePHPUnitTestCase
{
    public function testSchema(): void
    {
        $schema = GraphQL::get_schema();
        $schema->assertValid();
        self::assertNotEmpty($schema->getTypeMap());
    }

    /**
     * @return array<string, mixed>
     */
    protected function graphql(string $query): array
    {
        $schema = GraphQL::get_schema();
        $debug = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::RETHROW_INTERNAL_EXCEPTIONS;
        return GQL::executeQuery($schema, $query, fieldResolver: "\Shimmie2\shmFieldResolver")->toArray($debug);
    }

    public function testQuery(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");
        $image = Image::by_id_ex($image_id);

        $result = $this->graphql('{
            posts(limit: 3, offset: 0) {
                id
                post_id
                tags
                width
                owner {
                    id
                    name
                }
            }
        }');

        self::assertEquals([
            'data' => [
                'posts' => [
                    [
                        'id' => "post:$image_id",
                        'post_id' => $image_id,
                        'tags' => [
                            'test',
                        ],
                        'width' => 640,
                        'owner' => [
                            'id' => 'user:' . $image->get_owner()->id,
                            'name' => self::USER_NAME,
                        ],
                    ],
                ],
            ],
        ], $result, var_export($result, true));
    }

    public function testMutation(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");

        $result = $this->graphql("mutation {
            update_post_metadata(
                post_id: $image_id,
                metadata: [
                    {key: \"tags\", value: \"newtag\"},
                    {key: \"source\", value: \"https://example.com\"}
                ]
            ) {
                id
                tags
                source
            }
        }");

        self::assertEquals([
            'data' => [
                'update_post_metadata' => [
                    'id' => "post:$image_id",
                    'tags' => [
                        'newtag',
                    ],
                    'source' => "https://example.com",
                ],
            ],
        ], $result, var_export($result, true));
    }

    public function testUpload(): void
    {
        global $database;

        self::log_in_as_user();
        $_FILES = [
            'data0' => [
                'name' => 'puppy-hugs.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => 'tests/bedroom_workshop.jpg',
                'error' => 0,
                'size' => 271386,
            ],
        ];
        $page = self::post_page("graphql_upload", ["tags" => "foo", "tags0" => "bar"]);
        self::assertEquals(200, $page->code);
        self::assertEquals(1, $database->get_one("SELECT COUNT(*) FROM images"), $page->data);
        $id = $database->get_one("SELECT id FROM images");
        self::assertEquals("{\"results\":[{\"image_ids\":[$id]}]}", $page->data);
    }
}
