<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * When somebody has searched for a tag, "cat", "cute", "-angry", etc
 */
final readonly class TagCondition
{
    /**
     * @param tag-pattern-string $tag
     */
    public function __construct(
        public string $tag,
        public bool $positive = true,
    ) {
    }
}
