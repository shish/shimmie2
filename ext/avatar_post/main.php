<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{DIV,IMG};

use MicroHTML\HTMLElement;

/** @extends AvatarExtension<AvatarPostTheme> */
final class AvatarPost extends AvatarExtension
{
    public const KEY = "avatar_post";

    #[EventListener(priority: 49)]
    public function onBuildAvatar(BuildAvatarEvent $event): void
    {
        parent::onBuildAvatar($event);
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("set_avatar/{image_id}", method: "POST", permission: UserAccountsPermission::CHANGE_USER_SETTING)) {
            $image_id = int_escape($event->get_arg('image_id'));
            Ctx::$page->set_redirect(make_link("set_avatar/$image_id"));
        } elseif ($event->page_matches("set_avatar/{image_id}", method: "GET", permission: UserAccountsPermission::CHANGE_USER_SETTING)) {
            $image_id = int_escape($event->get_arg('image_id'));
            $this->theme->display_avatar_edit_page($image_id);
        } elseif ($event->page_matches("save_avatar", method: "POST", permission: UserAccountsPermission::CHANGE_USER_SETTING)) {
            $c = Ctx::$user->get_config();
            $c->set(AvatarPostUserConfig::AVATAR_ID, (int)$event->POST->req("id"));
            $c->set(AvatarPostUserConfig::AVATAR_SCALE, (int)$event->POST->req("scale"));
            $c->set(AvatarPostUserConfig::AVATAR_X, (int)$event->POST->req("x"));
            $c->set(AvatarPostUserConfig::AVATAR_Y, (int)$event->POST->req("y"));
            Ctx::$page->flash("Image set as avatar");
            Ctx::$page->set_redirect(Url::referer_or(make_link("user_config")));
        }
    }

    #[EventListener]
    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(UserAccountsPermission::CHANGE_USER_SETTING)) {
            $event->add_button("Set Image As Avatar", "set_avatar/".$event->image->id);
        }
    }

    #[EventListener]
    public function onConfigSave(ConfigSaveEvent $event): void
    {
        if (array_key_exists(AvatarPostUserConfig::AVATAR_ID, $event->values)) {
            Ctx::$cache->delete("Pavatar-" . Ctx::$user->id);
        }
    }

    public function avatar_html(User $user): HTMLElement|null
    {
        return cache_get_or_set("Pavatar-{$user->id}", fn () => $this->get_avatar_html($user), 60);
    }

    public function get_avatar_html(User $user): HTMLElement|null
    {
        $user_config = $user->get_config();
        $id = $user_config->get(AvatarPostUserConfig::AVATAR_ID);
        if ($id === null) {
            return null;
        }
        $image = Image::by_id($id);
        if (!$image) {
            $user_config->delete(AvatarPostUserConfig::AVATAR_ID);
            return null;
        }

        $scale = $user_config->get(AvatarPostUserConfig::AVATAR_SCALE);
        $x = $user_config->get(AvatarPostUserConfig::AVATAR_X);
        $y = $user_config->get(AvatarPostUserConfig::AVATAR_Y);

        $ar = $image->width / $image->height;

        $scale = $scale / 100;
        $thumb_height = Ctx::$config->get(SetupConfig::AVATAR_SIZE);
        $thumb_width = Ctx::$config->get(SetupConfig::AVATAR_SIZE);
        $h = min(ceil(abs($thumb_height * $scale / $ar)), $thumb_height);
        $w = min(ceil(abs($thumb_width * $scale * $ar)), $thumb_width);

        $style = "--pavatar-height:{$h}px;--pavatar-width:{$w}px;";

        $url = $image->get_thumb_link();
        return DIV(
            ["class" => "avatar-container", "style" => $style],
            IMG([
                "alt" => "avatar",
                "id" => "pavatar",
                "class" => "avatar pavatar",
                "style" => "transform:scale($scale);translate:$x% $y%;",
                "src" => $url
            ])
        );
    }
}
