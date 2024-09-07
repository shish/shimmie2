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

    public function build_thumb_html(Image $image): HTMLElement
    {
        $c = self::get_common();
        assert(is_a($c, CommonElementsTheme::class));
        return $c->build_thumb_html($image);
    }

    public function display_paginator(Page $page, string $base, ?string $query, int $page_number, int $total_pages, bool $show_random = false): void
    {
        $c = self::get_common();
        assert(is_a($c, CommonElementsTheme::class));
        $c->display_paginator($page, $base, $query, $page_number, $total_pages, $show_random);
    }
}
