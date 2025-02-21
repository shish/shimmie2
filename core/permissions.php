<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class PermissionGroup
{
    public const KEY = "";
    public ?string $title = null;
    public ?int $position = null;
}

#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT)]
readonly class PermissionMeta
{
    public function __construct(
        public string $label,
        public ?string $help = null,
        public bool $advanced = false,
    ) {
    }
}
