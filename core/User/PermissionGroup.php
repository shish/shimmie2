<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class PermissionGroup extends Enablable
{
    public ?string $title = null;
    public ?int $position = null;

    /**
     * @return array<string, PermissionMeta>
     */
    public static function get_all_metas(): array
    {
        $permissions = [];
        foreach (PermissionGroup::get_subclasses() as $class) {
            $group = $class->newInstance();
            if (!$group::is_enabled()) {
                continue;
            }
            foreach ($class->getReflectionConstants() as $const) {
                $attributes = $const->getAttributes(PermissionMeta::class);
                if (count($attributes) === 1) {
                    $meta = $attributes[0]->newInstance();
                    $permissions[$const->getValue()] = $meta;
                }
            }
        }
        return $permissions;
    }
}
