<?php

declare(strict_types=1);

namespace Shimmie2;

class DatabaseException extends ServerError
{
    /**
     * @param array<string, mixed> $args
     */
    public function __construct(
        public string $msg,
        public string $query,
        public array $args
    ) {
        parent::__construct($msg);
    }
}
