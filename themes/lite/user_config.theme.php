<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\DIV;

use MicroHTML\HTMLElement;

class LiteUserConfigTheme extends UserConfigTheme
{
    protected function sb_to_html(Block $block): HTMLElement
    {
        return DIV(["class" => "tframe"], parent::sb_to_html($block));
    }
}
