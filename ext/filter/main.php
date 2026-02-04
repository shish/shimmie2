<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<FilterTheme> */
final class Filter extends Extension
{
    public const KEY = "filter";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        $this->theme->addFilterBox();
    }
}
