<?php

declare(strict_types=1);

namespace Shimmie2;

#[\Attribute(\Attribute::TARGET_METHOD)]
class EventListener
{
    /**
     * @param class-string|null $event
     */
    public function __construct(
        public readonly ?string $event = null,
        public readonly int $priority = 50,
    ) {
    }
}
