<?php

declare(strict_types=1);

namespace Shimmie2;

final class RatingsBlur extends Extension
{
    public const KEY = "ratings_blur";
    public const NULL_OPTION = "[none]";

    // Called from CommonElements::build_thumb()
    public function blur(string $rating): bool
    {
        global $config, $user;

        $blur_ratings = $user->get_config()->get_array(
            RatingsBlurUserConfig::USER_DEFAULTS,
            $config->get_array(RatingsBlurConfig::GLOBAL_DEFAULTS)
        );
        if (in_array(RatingsBlur::NULL_OPTION, $blur_ratings)) {
            return false;
        }
        return in_array($rating, $blur_ratings);
    }
}
