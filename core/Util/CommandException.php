<?php

declare(strict_types=1);

namespace Shimmie2;

class CommandException extends ServerError
{
    public function __construct(
        public readonly string $command,
        public readonly int $exit_code,
        public readonly string $output,
    ) {
        parent::__construct("Command `$command` failed, returning $exit_code and outputting " . (empty($output) ? "nothing" : $output));
    }
}
