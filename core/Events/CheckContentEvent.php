<?php

declare(strict_types=1);

namespace Shimmie2;

final class CheckContentEvent extends Event
{
    public function __construct(
        public string $content,
        public string $context = "content"
    ) {
        parent::__construct();
    }
}
