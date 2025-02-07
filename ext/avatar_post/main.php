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

    public function onInitUserConfig(InitUserConfigEvent $event): void
    {
        $event->user_config->set_default_int(AvatarPostConfig::AVATAR_SCALE, 100);
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user, $user_config;
        if ($event->page_matches("set_avatar/{image_id}", method: "POST", permission: Permissions::CHANGE_USER_SETTING)) {
            $image_id = int_escape($event->get_arg('image_id'));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("set_avatar/$image_id"));
        }

        if ($event->page_matches("set_avatar/{image_id}", method: "GET", permission: Permissions::CHANGE_USER_SETTING)) {
            $image_id = int_escape($event->get_arg('image_id'));
            $this->theme->display_avatar_edit_page($page, $image_id);
        }

        if ($event->page_matches("save_avatar", method: "POST", permission: Permissions::CHANGE_USER_SETTING)) {
            $settings = ConfigSaveEvent::postToSettings($event->POST);
            send_event(new ConfigSaveEvent($user_config, $settings));
            $page->flash("Image set as avatar");
            $page->set_mode(PageMode::REDIRECT);
            if (key_exists(AvatarPostConfig::AVATAR_ID, $settings) && is_int($settings[AvatarPostConfig::AVATAR_ID])) {
                $page->set_redirect(make_link("post/view/".$settings[AvatarPostConfig::AVATAR_ID]));
            } else {
                $page->set_redirect(make_link("user_config"));
            }
        }
    }

    public function onUserOptionsBuilding(UserOptionsBuildingEvent $event): void
    {
        global $config, $user_config;
        $sb = $event->panel->create_new_block("Avatar");
        $sb->add_int_option(AvatarPostConfig::AVATAR_ID, 'Avatar post ID: ');
        $image_id = $user_config->get_int(AvatarPostConfig::AVATAR_ID, null);
        if (!is_null($image_id)) {
            $sb->add_label("<br><a href=".make_link("set_avatar/$image_id").">Change cropping</a>");
        }
        $sb->add_label("<br>Manual position and scale:<br>");
        $sb->add_int_option(AvatarPostConfig::AVATAR_SCALE, "scale%: ");
        $sb->add_int_option(AvatarPostConfig::AVATAR_X, "X%: ");
        $sb->add_int_option(AvatarPostConfig::AVATAR_Y, "Y%: ");
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
        if (array_key_exists(AvatarPostConfig::AVATAR_ID, $event->values)) {
            $cache->delete("Pavatar-{$user->id}");
        }
    }

    public function avatar_html(User $user): HTMLElement|false
    {
        return cache_get_or_set("Pavatar-{$user->id}", fn () => $this->get_avatar_post_html($user), 60);
    }

    public function get_avatar_post_html(User $user): HTMLElement|false
    {
        global $database, $config;
        $user_config = new DatabaseConfig($database, "user_config", "user_id", (string)$user->id);
        $id = $user_config->get_int(AvatarPostConfig::AVATAR_ID, 0);
        if ($id === 0) {
            return false;
        }
        $image = Image::by_id($id);
        if ($image) {
            $scale = $user_config->get_int(AvatarPostConfig::AVATAR_SCALE, 100) / 100;
            $x = $user_config->get_int(AvatarPostConfig::AVATAR_X, 0);
            $y = $user_config->get_int(AvatarPostConfig::AVATAR_Y, 0);

            $ar = $image->width / $image->height;

            $thumb_height = $config->get_int(ImageConfig::THUMB_HEIGHT);
            $thumb_width = $config->get_int(ImageConfig::THUMB_WIDTH);
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
        $user_config->delete(AvatarPostConfig::AVATAR_ID);
        return false;
    }

    public function get_avatar_post_url(User $user): ?string
    {
        global $database;
        $user_config = new DatabaseConfig($database, "user_config", "user_id", (string)$user->id);
        $id = $user_config->get_int(AvatarPostConfig::AVATAR_ID, 0);
        if ($id === 0) {
            return null;
        }
        $image = Image::by_id($id);
        if ($image) {
            return $image->get_thumb_link();
        }
        $user_config->delete(AvatarPostConfig::AVATAR_ID);
        return null;
    }
}
