<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "core/extension.php";

abstract class PermissionGroup extends Enablable
{
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
