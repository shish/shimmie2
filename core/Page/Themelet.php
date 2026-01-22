<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

/**
 * A collection of common functions for theme parts
 */
class Themelet
{
    private static CommonElementsTheme $common;

    /**
     * @template T of Page|Themelet
     * @param class-string<T> $class
     * @return T|null
     */
    public static function get_theme_class(string $class): ?object
    {
        $class = str_replace("Shimmie2\\", "", $class);
        $theme = ucfirst(get_theme());
        $options = [
            "\\Shimmie2\\$theme$class",
            "\\Shimmie2\\Custom$class",
            "\\Shimmie2\\$class",
        ];
        foreach ($options as $option) {
            if (class_exists($option)) {
                // @phpstan-ignore-next-line
                return new $option();
            }
        }
        return null;
    }

    /**
     * @param class-string<Extension> $class
     */
    public static function get_for_extension_class(string $class): Themelet
    {
        /** @var class-string<Themelet> $theme_class */
        $theme_class = $class . "Theme";
        return static::get_theme_class($theme_class) ?? new Themelet();
    }

    private function get_common(): CommonElementsTheme
    {
        if (!isset(self::$common)) {
            self::$common = static::get_theme_class(CommonElementsTheme::class) ?? new CommonElementsTheme();
        }
        return self::$common;
    }

    /**
     * @param tag-string $tag
     */
    public function build_tag(
        string $tag,
        bool $show_underscores = true,
        bool $show_category = true,
        ?string $style = null,
    ): HTMLElement {
        return self::get_common()->build_tag($tag, $show_underscores, $show_category, $style);
    }

    public function build_thumb(Image $image): HTMLElement
    {
        return self::get_common()->build_thumb($image);
    }

    public function display_paginator(string $base, ?QueryArray $query, int $page_number, int $total_pages, bool $show_random = false): void
    {
        self::get_common()->display_paginator($base, $query, $page_number, $total_pages, $show_random);
    }

    public function config_group_to_block(Config $config, BaseConfigGroup $group): ?Block
    {
        return self::get_common()->config_group_to_block($config, $group);
    }
}
