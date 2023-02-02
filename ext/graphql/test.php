<?php

declare(strict_types=1);

namespace Shimmie2;

class GraphQLTest extends ShimmiePHPUnitTestCase
{
    public function testSchema()
    {
        $schema = \GQLA\genSchema();
        $schema->assertValid();
        $this->assertTrue(true);
    }
}
