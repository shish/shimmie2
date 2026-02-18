<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A signal that something needs logging
 */
final class LogEvent extends Event
{
    public function __construct(
        public readonly string $section,
        public readonly int $priority,
        public readonly string $message,
    ) {
        parent::__construct();
    }
}
