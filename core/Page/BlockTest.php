<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\emptyHTML;

class BlockTest extends ShimmiePHPUnitTestCase
{
    public function test_blockcmp(): void
    {
        // Equal position and heading
        $this->assertEquals(
            0,
            Block::cmp(
                new Block("Alice", emptyHTML(), "main", 10),
                new Block("Alice", emptyHTML(), "main", 10),
            )
        );

        // Different positions
        $this->assertEquals(
            -1,
            Block::cmp(
                new Block("Alice", emptyHTML(), "main", 10),
                new Block("Alice", emptyHTML(), "main", 20),
            )
        );
        $this->assertEquals(
            1,
            Block::cmp(
                new Block("Alice", emptyHTML(), "main", 20),
                new Block("Alice", emptyHTML(), "main", 10),
            )
        );

        // Different headings
        $this->assertEquals(
            -1,
            Block::cmp(
                new Block("Alice", emptyHTML(), "main", 10),
                new Block("Bob", emptyHTML(), "main", 10),
            )
        );
        $this->assertEquals(
            1,
            Block::cmp(
                new Block("Bob", emptyHTML(), "main", 10),
                new Block("Alice", emptyHTML(), "main", 10),
            )
        );

        // Heading sort is case insensitive
        $this->assertEquals(
            0,
            Block::cmp(
                new Block("Alice", emptyHTML(), "main", 10),
                new Block("alice", emptyHTML(), "main", 10),
            )
        );
    }
}
