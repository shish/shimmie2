<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

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
                    if ($all || self::is_enabled($class::KEY)) {
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
            $extras = explode(",", SysConfig::getExtraExtensions());

            foreach (array_merge(
                ExtensionInfo::get_core_extensions(),
                $extras
            ) as $key) {
                try {
                    $ext = ExtensionInfo::get_by_key($key);
                } catch (\InvalidArgumentException $e) {
                    continue;
                }
                if (!$ext->is_supported()) {
                    continue;
                }
                // FIXME: error if one of our dependencies isn't supported
                self::$enabled_extensions[] = $ext::KEY;
                if (!empty($ext->dependencies)) {
                    foreach ($ext->dependencies as $dep) {
                        self::$enabled_extensions[] = $dep;
                    }
                }
            }
        }

        return self::$enabled_extensions;
    }

    public static function is_enabled(?string $key = null): bool
    {
        return in_array($key ?? static::KEY, self::get_enabled_extensions());
    }

    public static function get_enabled_extensions_as_string(): string
    {
        return implode(",", self::get_enabled_extensions());
    }
}
