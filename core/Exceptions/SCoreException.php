<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A base exception to be caught by the upper levels.
 */
class SCoreException extends \RuntimeException
{
    public int $http_code = 500;

    public function __construct(
        public string $error
    ) {
        parent::__construct($error);
    }
}
