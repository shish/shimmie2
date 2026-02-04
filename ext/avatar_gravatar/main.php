<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\IMG;

final class AvatarGravatar extends AvatarExtension
{
    public const KEY = "avatar_gravatar";

    public function avatar_html(User $user): HTMLElement|null
    {
        if (!empty($user->email)) {
            $hash = md5(strtolower($user->email));
            $s = Ctx::$config->get(SetupConfig::AVATAR_SIZE);
            $d = urlencode(Ctx::$config->get(AvatarGravatarConfig::GRAVATAR_DEFAULT));
            $r = Ctx::$config->get(AvatarGravatarConfig::GRAVATAR_RATING);
            $cb = date("Y-m-d");
            $url = "https://www.gravatar.com/avatar/$hash.jpg?s=$s&d=$d&r=$r&cacheBreak=$cb";
            return IMG(["alt" => "avatar", "class" => "avatar gravatar", "src" => $url]);
        }
        return null;
    }
}
