<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\IMG;

final class AvatarGravatar extends AvatarExtension
{
    public const KEY = "avatar_gravatar";

    public function get_priority(): int
    {
        return 50;
    }

    public function avatar_html(User $user): HTMLElement|null
    {
        global $config;

        if (!empty($user->email)) {
            $hash = md5(strtolower($user->email));
            $s = $config->req_int(SetupConfig::AVATAR_SIZE);
            $d = urlencode($config->req_string(AvatarGravatarConfig::GRAVATAR_DEFAULT));
            $r = $config->req_string(AvatarGravatarConfig::GRAVATAR_RATING);
            $cb = date("Y-m-d");
            $url = "https://www.gravatar.com/avatar/$hash.jpg?s=$s&d=$d&r=$r&cacheBreak=$cb";
            return IMG(["alt" => "avatar", "class" => "avatar gravatar", "src" => $url]);
        }
        return null;
    }
}
