<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\IMG;

class AvatarGravatar extends AvatarExtension
{
    public function get_priority(): int
    {
        return 50;
    }

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_int(AvatarGravatarConfig::GRAVATAR_SIZE, 128);
        $config->set_default_string(AvatarGravatarConfig::GRAVATAR_DEFAULT, "");
        $config->set_default_string(AvatarGravatarConfig::GRAVATAR_RATING, "g");
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $event->panel->add_config_group(new AvatarGravatarConfig());
    }

    public function avatar_html(User $user): HTMLElement|null
    {
        global $config;

        if (!empty($user->email)) {
            $hash = md5(strtolower($user->email));
            $s = $config->get_string(AvatarGravatarConfig::GRAVATAR_SIZE);
            $d = urlencode($config->get_string(AvatarGravatarConfig::GRAVATAR_DEFAULT));
            $r = $config->get_string(AvatarGravatarConfig::GRAVATAR_RATING);
            $cb = date("Y-m-d");
            $url = "https://www.gravatar.com/avatar/$hash.jpg?s=$s&d=$d&r=$r&cacheBreak=$cb";
            return IMG(["alt" => "avatar", "class" => "avatar gravatar", "src" => $url]);
        }
        return null;
    }
}
