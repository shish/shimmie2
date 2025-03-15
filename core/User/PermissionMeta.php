<?php

declare(strict_types=1);

namespace Shimmie2;

#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT)]
final readonly class PermissionMeta
{
    public function __construct(
        public string $label,
        public ?string $help = null,
        public bool $advanced = false,
    ) {
    }
}
