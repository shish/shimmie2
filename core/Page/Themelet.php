<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

/**
 * A collection of common functions for theme parts
 */
class Themelet
{
    private static Themelet $common;

    public static function get_for_extension_class(string $class): Themelet
    {
        $cls = get_theme_class(str_replace("Shimmie2\\", "", $class) . "Theme") ?? new Themelet();
        assert(is_a($cls, Themelet::class));
        return $cls;
    }

    private function get_common(): Themelet
    {
        if (!isset(self::$common)) {
            self::$common = Themelet::get_for_extension_class("CommonElements");
        }
        return self::$common;
    }

    /**
     * @param array<Url|null> $links
     */
    public function display_navigation(array $links = [], ?HTMLElement $extra = null): void
    {
        $c = self::get_common();
        assert(is_a($c, CommonElementsTheme::class));
        $c->display_navigation($links, $extra);
    }

    public function build_tag(
        string $tag,
        bool $show_underscores = true,
        bool $show_category = true,
        ?string $style = null,
    ): HTMLElement {
        $c = self::get_common();
        assert(is_a($c, CommonElementsTheme::class));
        return $c->build_tag($tag, $show_underscores, $show_category, $style);
    }

    public function build_thumb(Image $image): HTMLElement
    {
        $c = self::get_common();
        assert(is_a($c, CommonElementsTheme::class));
        return $c->build_thumb($image);
    }

    /**
     * @param ?query-array $query
     */
    public function display_paginator(Page $page, string $base, ?array $query, int $page_number, int $total_pages, bool $show_random = false): void
    {
        $c = self::get_common();
        assert(is_a($c, CommonElementsTheme::class));
        $c->display_paginator($page, $base, $query, $page_number, $total_pages, $show_random);
    }

    public function config_group_to_block(Config $config, BaseConfigGroup $group): ?Block
    {
        $c = self::get_common();
        assert(is_a($c, CommonElementsTheme::class));
        return $c->config_group_to_block($config, $group);
    }
}
