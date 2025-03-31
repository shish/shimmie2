<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class PermissionGroup extends Enablable
{
    protected ?string $title = null;
    public ?int $position = null;

    public function get_title(): string
    {
        return $this->title ?? implode(
            " ",
            \Safe\preg_split(
                '/(?=[A-Z])/',
                \Safe\preg_replace("/^Shimmie2.(.*?)(User)?Config$/", "\$1", get_class($this))
            )
        );
    }
}
