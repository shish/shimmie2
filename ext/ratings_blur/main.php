<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class RatingsBlurConfig
{
    public const VERSION = "ext_ratings_blur_version";
    public const GLOBAL_DEFAULTS = "ext_ratings_blur_defaults";
    public const USER_DEFAULTS = "ratings_blur_default";
    public const DEFAULT_OPTIONS = ["e"];
    public const NULL_OPTION = "[none]";
}

class RatingsBlur extends Extension
{
    public function onInitExt(InitExtEvent $event): void
    {
        global $config;

        $config->set_default_array(RatingsBlurConfig::GLOBAL_DEFAULTS, RatingsBlurConfig::DEFAULT_OPTIONS);
    }

    public function onInitUserConfig(InitUserConfigEvent $event): void
    {
        global $config;

        $event->user_config->set_default_array(RatingsBlurConfig::USER_DEFAULTS, $config->get_array(RatingsBlurConfig::GLOBAL_DEFAULTS));
    }

    public function onUserOptionsBuilding(UserOptionsBuildingEvent $event): void
    {
        global $user;

        $levels = Ratings::get_user_class_privs($user);
        $options = [];
        foreach ($levels as $level) {
            $options[ImageRating::$known_ratings[$level]->name] = $level;
        }
        $null_option = RatingsBlurConfig::NULL_OPTION;
        $options[$null_option] = $null_option;

        $sb = $event->panel->create_new_block("Rating Blur Filter");
        $sb->start_table();
        $sb->add_multichoice_option(RatingsBlurConfig::USER_DEFAULTS, $options, "Blurred Ratings: ", true);
        $sb->end_table();
        $sb->add_label("This controls which posts will be blurred. Unselecting all will revert to default settings, so select '$null_option' to blur no images.");
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $ratings = Ratings::get_sorted_ratings();

        $options = [];
        foreach ($ratings as $key => $rating) {
            $options[$rating->name] = $rating->code;
        }
        $null_option = RatingsBlurConfig::NULL_OPTION;
        $options[$null_option] = $null_option;

        $sb = $event->panel->create_new_block("Post Rating Blur Defaults");
        $sb->start_table();
        $sb->add_multichoice_option(RatingsBlurConfig::GLOBAL_DEFAULTS, $options, "Default blurred ratings", true);
        $sb->end_table();
        $sb->add_label("Unselecting all will revert to default settings, so select '$null_option' to blur no images.");
    }

    public function blur(string $rating): bool
    {
        global $user_config;

        $blur_ratings = $user_config->get_array(RatingsBlurConfig::USER_DEFAULTS);
        if (in_array(RatingsBlurConfig::NULL_OPTION, $blur_ratings)) {
            return false;
        }
        return in_array($rating, $blur_ratings);
    }
}
