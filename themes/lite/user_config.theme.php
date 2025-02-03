<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\DIV;

class LiteUserConfigTheme extends UserConfigTheme
{
    protected function sb_to_html(SetupBlock $block): HTMLElement
    {
        return DIV(["class" => "tframe"], parent::sb_to_html($block));
    }
}
