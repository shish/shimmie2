<?php

declare(strict_types=1);

namespace Shimmie2;

class GraphQLTest extends ShimmiePHPUnitTestCase
{
    public function testSchema(): void
    {
        $schema = GraphQL::get_schema();
        $schema->assertValid();
        $this->assertTrue(true);
    }
}
