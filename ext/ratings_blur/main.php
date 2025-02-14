<?php

declare(strict_types=1);

namespace Shimmie2;

class RatingsBlur extends Extension
{
    public const NULL_OPTION = "[none]";

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_array(RatingsBlurConfig::GLOBAL_DEFAULTS, ["e"]);
    }

    public function onInitUserConfig(InitUserConfigEvent $event): void
    {
        global $config;
        $event->user_config->set_default_array(RatingsBlurUserConfig::USER_DEFAULTS, $config->get_array(RatingsBlurConfig::GLOBAL_DEFAULTS));
    }

    public function blur(string $rating): bool
    {
        global $user;

        $blur_ratings = $user->get_config()->get_array(RatingsBlurUserConfig::USER_DEFAULTS);
        if (in_array(RatingsBlur::NULL_OPTION, $blur_ratings)) {
            return false;
        }
        return in_array($rating, $blur_ratings);
    }
}
