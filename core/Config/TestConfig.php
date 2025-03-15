<?php

declare(strict_types=1);

namespace Shimmie2;

final class TestConfig extends Config
{
    /**
     * @param array<string, string> $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function save(string $name): void
    {
    }
}
