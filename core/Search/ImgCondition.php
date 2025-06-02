<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * When somebody has searched for a specific image property, like "rating:safe",
 * "id:123", "width:100", etc - an extension will spot those meta-tags and turn
 * them into a little chunk of SQL
 */
final readonly class ImgCondition
{
    public function __construct(
        public Querylet $qlet,
        public bool $positive = true,
    ) {
    }
}
