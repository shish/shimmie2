<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

/**
 * A common base class for avatar extensions
 *
 * avatar_html()
 *   Should return an HTMLElement representing an
 *   avatar, and it should have the CSS property
 *   of "display:inline-block" to emulate an <img>
 *
 * @template TThemelet of Themelet = Themelet
 * @extends Extension<TThemelet>
 */
abstract class AvatarExtension extends Extension
{
    #[EventListener]
    public function onBuildAvatar(BuildAvatarEvent $event): void
    {
        $html = $this->avatar_html($event->user);
        if ($html) {
            $event->setAvatar($html);
            $event->stop_processing = true;
        }
    }

    abstract public function avatar_html(User $user): HTMLElement|null;
}
