<?php

declare(strict_types=1);

namespace Shimmie2;

use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL as GQL;

class GraphQLTest extends ShimmiePHPUnitTestCase
{
    public function testSchema(): void
    {
        $schema = GraphQL::get_schema();
        $schema->assertValid();
        $this->assertTrue(true);
    }

    public function testQuery(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");
        $image = Image::by_id_ex($image_id);

        $query = '{
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
        }';
        $schema = GraphQL::get_schema();
        $debug = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::RETHROW_INTERNAL_EXCEPTIONS;
        $result = GQL::executeQuery($schema, $query)->toArray($debug);

        $this->assertEquals([
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
                            'id' => 'user:'.$image->get_owner()->id,
                            'name' => self::$user_name,
                        ],
                    ],
                ]
                ,
            ],
        ], $result, var_export($result, true));
    }
}
