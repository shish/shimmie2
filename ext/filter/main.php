<?php

declare(strict_types=1);

namespace Shimmie2;

class Filter extends Extension
{
    /** @var FilterTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        $this->theme->addFilterBox();
    }
}
