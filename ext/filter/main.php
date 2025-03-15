<?php

declare(strict_types=1);

namespace Shimmie2;

final class Filter extends Extension
{
    public const KEY = "filter";
    /** @var FilterTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        $this->theme->addFilterBox();
    }
}
