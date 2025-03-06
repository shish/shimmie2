<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class Enablable
{
    public const KEY = "";

    /** @var string[]|null */
    private static ?array $enabled_extensions = null;

    /**
     * @return \ReflectionClass<static>[]
     */
    public static function get_subclasses(bool $all = false): array
    {
        $result = [];
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, get_called_class())) {
                $rclass = new \ReflectionClass($class);
                if (!$rclass->isAbstract()) {
                    if ($all || $class::is_enabled()) {
                        $result[] = $rclass;
                    }
                }
            }
        }
        return $result;
    }
    /**
     * @return string[]
     */
    public static function get_enabled_extensions(): array
    {
        if (is_null(self::$enabled_extensions)) {
            self::$enabled_extensions = [];

            $keys = array_merge(
                array_map(
                    fn ($info) => $info::KEY,
                    array_filter(
                        array_values(ExtensionInfo::get_all()),
                        fn ($info) => $info->core
                    )
                ),
                SysConfig::getExtraExtensions()
            );
            foreach ($keys as $key) {
                $ext = ExtensionInfo::get_all()[$key] ?? null;
                if (!$ext?->is_supported()) {
                    continue;
                }
                // FIXME: error if one of our dependencies isn't supported
                self::$enabled_extensions[] = $ext::KEY;
                foreach ($ext->dependencies as $dep) {
                    self::$enabled_extensions[] = $dep;
                }
            }
        }

        return self::$enabled_extensions;
    }

    public static function is_enabled(): bool
    {
        return in_array(static::KEY, self::get_enabled_extensions());
    }

    public static function get_enabled_extensions_as_string(): string
    {
        return implode(",", self::get_enabled_extensions());
    }
}
