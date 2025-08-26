<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

final class Captcha
{
    public static function get_html(?string $bypass_if = null): ?HTMLElement
    {
        if ($bypass_if !== null && Ctx::$user->can($bypass_if)) {
            return null;
        }

        if (Network::get_real_ip()->is_localhost()) {
            return null;
        }

        return send_event(new BuildCaptchaEvent())->html;
    }

    public static function check(?string $bypass_if = null): bool
    {
        if ($bypass_if !== null && Ctx::$user->can($bypass_if)) {
            return true;
        }

        if (Network::get_real_ip()->is_localhost()) {
            return true;
        }

        $passed = send_event(new CheckCaptchaEvent())->passed;
        if ($passed === null) {
            $passed = true;
        }
        return $passed;
    }
}
