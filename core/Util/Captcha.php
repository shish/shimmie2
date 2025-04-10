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

        if (SysConfig::getDebug() && Network::ip_in_range(Network::get_real_ip(), "127.0.0.0/8")) {
            return null;
        }

        return send_event(new BuildCaptchaEvent())->html;
    }

    public static function check(?string $bypass_if = null): bool
    {
        if ($bypass_if !== null && Ctx::$user->can($bypass_if)) {
            return true;
        }

        if (SysConfig::getDebug() && Network::ip_in_range(Network::get_real_ip(), "127.0.0.0/8")) {
            return true;
        }

        $passed = send_event(new CheckCaptchaEvent())->passed;
        if ($passed === null) {
            $passed = true;
        }
        return $passed;
    }
}
