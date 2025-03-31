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
            array_filter(\Safe\preg_split(
                '/(?=[A-Z])/',
                \Safe\preg_replace("/^Shimmie2.(.*?)(User)?Permission/", "\$1", get_class($this))
            ), fn ($x) => strlen($x) > 0)
        );
    }

    /**
     * @return array<string, array<string, PermissionMeta>>
     */
    public static function get_all_metas_grouped(): array
    {
        $permissions = [];
        foreach (PermissionGroup::get_subclasses() as $class) {
            $group_arr = [];
            $group = $class->newInstance();
            if (!$group::is_enabled()) {
                continue;
            }
            foreach ($class->getReflectionConstants() as $const) {
                $attributes = $const->getAttributes(PermissionMeta::class);
                if (count($attributes) === 1) {
                    $group_arr[$const->getValue()] = $attributes[0]->newInstance();
                }
            }
            $permissions[$group->get_title()] = $group_arr;
        }
        return $permissions;
    }
}
