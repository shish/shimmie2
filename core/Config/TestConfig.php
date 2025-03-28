<?php

declare(strict_types=1);

namespace Shimmie2;

final class TestConfig extends Config
{
    /**
    * @param array<string, ConfigMeta> $metas
    * @param array<string, mixed> $values
     */
    public function __construct(array $metas, array $values)
    {
        $this->metas = $metas;
        $this->values = $values;
    }

    public function save(string $name): void
    {
    }
}
