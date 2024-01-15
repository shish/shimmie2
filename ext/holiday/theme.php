<?php

declare(strict_types=1);

namespace Shimmie2;

class HolidayTheme extends Themelet
{
    public function display_holiday(?string $holiday): void
    {
        global $page;
        if ($holiday) {
            $page->add_html_header(
                "<link rel='stylesheet' href='".get_base_href()."/contrib/holiday/stylesheets/$holiday.css' type='text/css'>"
            );
        }
    }
}
