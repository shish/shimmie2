<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\emptyHTML;

final class BlockTest extends ShimmiePHPUnitTestCase
{
    public function test_blockcmp(): void
    {
        // Equal position and heading
        self::assertEquals(
            0,
            Block::cmp(
                new Block("Alice", emptyHTML(), "main", 10),
                new Block("Alice", emptyHTML(), "main", 10),
            )
        );

        // Different positions
        self::assertEquals(
            -1,
            Block::cmp(
                new Block("Alice", emptyHTML(), "main", 10),
                new Block("Alice", emptyHTML(), "main", 20),
            )
        );
        self::assertEquals(
            1,
            Block::cmp(
                new Block("Alice", emptyHTML(), "main", 20),
                new Block("Alice", emptyHTML(), "main", 10),
            )
        );

        // Different headings
        self::assertEquals(
            -1,
            Block::cmp(
                new Block("Alice", emptyHTML(), "main", 10),
                new Block("Bob", emptyHTML(), "main", 10),
            )
        );
        self::assertEquals(
            1,
            Block::cmp(
                new Block("Bob", emptyHTML(), "main", 10),
                new Block("Alice", emptyHTML(), "main", 10),
            )
        );

        // Heading sort is case insensitive
        self::assertEquals(
            0,
            Block::cmp(
                new Block("Alice", emptyHTML(), "main", 10),
                new Block("alice", emptyHTML(), "main", 10),
            )
        );
    }
}
