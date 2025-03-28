<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{DIV,IMG};

final class AvatarPost extends AvatarExtension
{
    public const KEY = "avatar_post";
    /** @var AvatarPostTheme */
    protected Themelet $theme;

    public function get_priority(): int
    {
        return 49;
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page;
        if ($event->page_matches("set_avatar/{image_id}", method: "POST", permission: UserAccountsPermission::CHANGE_USER_SETTING)) {
            $image_id = int_escape($event->get_arg('image_id'));
            $page->set_redirect(make_link("set_avatar/$image_id"));
        } elseif ($event->page_matches("set_avatar/{image_id}", method: "GET", permission: UserAccountsPermission::CHANGE_USER_SETTING)) {
            $image_id = int_escape($event->get_arg('image_id'));
            $this->theme->display_avatar_edit_page($image_id);
        } elseif ($event->page_matches("save_avatar", method: "POST", permission: UserAccountsPermission::CHANGE_USER_SETTING)) {
            $c = Ctx::$user->get_config();
            $c->set(AvatarPostUserConfig::AVATAR_ID, (int)$event->req_POST("id"));
            $c->set(AvatarPostUserConfig::AVATAR_SCALE, (int)$event->req_POST("scale"));
            $c->set(AvatarPostUserConfig::AVATAR_X, (int)$event->req_POST("x"));
            $c->set(AvatarPostUserConfig::AVATAR_Y, (int)$event->req_POST("y"));
            $page->flash("Image set as avatar");
            $page->set_redirect(Url::referer_or(make_link("user_config")));
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(UserAccountsPermission::CHANGE_USER_SETTING)) {
            $event->add_button("Set Image As Avatar", "set_avatar/".$event->image->id);
        }
    }

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

        $scale = $user_config->req(AvatarPostUserConfig::AVATAR_SCALE) / 100;
        $x = $user_config->req(AvatarPostUserConfig::AVATAR_X);
        $y = $user_config->req(AvatarPostUserConfig::AVATAR_Y);

        $ar = $image->width / $image->height;

        $thumb_height = Ctx::$config->req(SetupConfig::AVATAR_SIZE);
        $thumb_width = Ctx::$config->req(SetupConfig::AVATAR_SIZE);
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
