<?php

declare(strict_types=1);

namespace Shimmie2;

class DatabaseException extends SCoreException
{
    public string $query;
    /** @var array<string, mixed> */
    public array $args;

    /**
     * @param array<string, mixed> $args
     */
    public function __construct(string $msg, string $query, array $args)
    {
        parent::__construct($msg);
        $this->error = $msg;
        $this->query = $query;
        $this->args = $args;
    }
}
