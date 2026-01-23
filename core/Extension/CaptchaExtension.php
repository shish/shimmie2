<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

/**
 * A common base class for captcha extensions
 *
 * captcha_html()
 *   Should return an HTMLElement to be inserted into
 *   the form being processed.
 *
 * check_captcha()
 *   Should return true if the captcha is valid, false otherwise.
 */
abstract class CaptchaExtension extends Extension
{
    #[EventListener]
    public function onBuildCaptcha(BuildCaptchaEvent $event): void
    {
        $html = $this->captcha_html();
        if ($html) {
            $event->setCaptcha($html);
            $event->stop_processing = true;
        }
    }

    abstract public function captcha_html(): HTMLElement|null;

    #[EventListener]
    public function onCheckCaptcha(CheckCaptchaEvent $event): void
    {
        $event->passed = $this->check_captcha();
        $event->stop_processing = true;
    }

    abstract public function check_captcha(): bool;
}
