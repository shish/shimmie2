<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{DIV,IMG};

class AvatarPost extends AvatarExtension
{
    /** @var AvatarPostTheme */
    protected Themelet $theme;

    public function get_priority(): int
    {
        return 49;
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;
        if ($event->page_matches("set_avatar/{image_id}", method: "POST", permission: Permissions::CHANGE_USER_SETTING)) {
            $image_id = int_escape($event->get_arg('image_id'));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("set_avatar/$image_id"));
        } elseif ($event->page_matches("set_avatar/{image_id}", method: "GET", permission: Permissions::CHANGE_USER_SETTING)) {
            $image_id = int_escape($event->get_arg('image_id'));
            $this->theme->display_avatar_edit_page($page, $image_id);
        } elseif ($event->page_matches("save_avatar", method: "POST", permission: Permissions::CHANGE_USER_SETTING)) {
            $settings = ConfigSaveEvent::postToSettings($event->POST);
            send_event(new ConfigSaveEvent($user->get_config(), $settings));
            $page->flash("Image set as avatar");
            $page->set_mode(PageMode::REDIRECT);
            if (key_exists(AvatarPostUserConfig::AVATAR_ID, $settings) && is_int($settings[AvatarPostUserConfig::AVATAR_ID])) {
                $page->set_redirect(make_link("post/view/".$settings[AvatarPostUserConfig::AVATAR_ID]));
            } else {
                $page->set_redirect(make_link("user_config"));
            }
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user, $config;
        if ($user->can(Permissions::CHANGE_USER_SETTING)) {
            $event->add_button("Set Image As Avatar", "set_avatar/".$event->image->id);
        }
    }

    public function onConfigSave(ConfigSaveEvent $event): void
    {
        global $cache, $user;
        if (array_key_exists(AvatarPostUserConfig::AVATAR_ID, $event->values)) {
            $cache->delete("Pavatar-{$user->id}");
        }
    }

    public function avatar_html(User $user): HTMLElement|null
    {
        return cache_get_or_set("Pavatar-{$user->id}", fn () => $this->get_avatar_html($user), 60);
    }

    public function get_avatar_html(User $user): HTMLElement|null
    {
        global $database, $config;

        $user_config = $user->get_config();
        $id = $user_config->get_int(AvatarPostUserConfig::AVATAR_ID);
        if ($id === null) {
            return null;
        }
        $image = Image::by_id($id);
        if (!$image) {
            $user_config->delete(AvatarPostUserConfig::AVATAR_ID);
            return null;
        }

        $scale = $user_config->get_int(AvatarPostUserConfig::AVATAR_SCALE, 100) / 100;
        $x = $user_config->get_int(AvatarPostUserConfig::AVATAR_X, 0);
        $y = $user_config->get_int(AvatarPostUserConfig::AVATAR_Y, 0);

        $ar = $image->width / $image->height;

        $thumb_height = $config->get_int(SetupConfig::AVATAR_SIZE);
        $thumb_width = $config->get_int(SetupConfig::AVATAR_SIZE);
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
