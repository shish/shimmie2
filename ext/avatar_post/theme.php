<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{DIV,IMG,LABEL,INPUT};

class AvatarPostTheme extends Themelet
{
    public function display_avatar_edit_page(Page $page, int $image_id): void
    {
        global $user, $user_config;
        /** @var BuildAvatarEvent $avatar_e */
        $avatar_e = send_event(new BuildAvatarEvent($user));
        $current = $avatar_e->html;
        $page->add_block(new Block("Current Avatar", DIV(["class" => "avatar-editor"], $current)));

        $image = Image::by_id($image_id);
        if (!$image) {
            throw new PostNotFound("Image $image_id not found");
        }
        $html = $this->avatar_editor_html($image);

        $page->add_block(new Block("Avatar Editor", $html));
    }

    public function avatar_editor_html(Image $image): HTMLElement
    {
        $url = $image->get_thumb_link();
        return DIV(
            ["class" => "avatar-editor"],
            LABEL("drag the image to move, slide to zoom"),
            DIV(
                ["class" => "avatar-container", "style" => "--pavatar-width:192px;--pavatar-height:192px;"],
                IMG(["alt" => "avatar", "id" => "avatar-edit", "class" => "avatar gravatar", "src" => $url])
            ),
            DIV(
                ["id" => "avatar-editor-controls"],
                INPUT(["type" => "range", "id" => "zoom-slider", "min" => -10, "max" => 10, "step" => 0.1, "value" => 0])
            ),
            // simulate user config page
            SHM_SIMPLE_FORM(
                "save_avatar",
                INPUT(["type" => "hidden", "name" => "_config_".AvatarPostConfig::AVATAR_ID, "value" => $image->id]),
                INPUT(["type" => "hidden", "name" => "_type_".AvatarPostConfig::AVATAR_ID, "value" => "int"]),
                INPUT(["type" => "hidden", "name" => "_config_".AvatarPostConfig::AVATAR_SCALE, "id" => "avatar-post-scale", "value" => 100]),
                INPUT(["type" => "hidden", "name" => "_type_".AvatarPostConfig::AVATAR_SCALE, "value" => "int"]),
                INPUT(["type" => "hidden", "name" => "_config_".AvatarPostConfig::AVATAR_X, "id" => "avatar-post-x", "value" => 0]),
                INPUT(["type" => "hidden", "name" => "_type_".AvatarPostConfig::AVATAR_X, "value" => "int"]),
                INPUT(["type" => "hidden", "name" => "_config_".AvatarPostConfig::AVATAR_Y, "id" => "avatar-post-y", "value" => 0]),
                INPUT(["type" => "hidden", "name" => "_type_".AvatarPostConfig::AVATAR_Y, "value" => "int"]),
                INPUT(["type" => "submit", "value" => "Set avatar"]),
            )
        );
    }
}
