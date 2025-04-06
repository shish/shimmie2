<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BR, DIV, IMG, INPUT, LABEL};

use MicroHTML\HTMLElement;

class AvatarPostTheme extends Themelet
{
    public function display_avatar_edit_page(int $image_id): void
    {
        $avatar_e = send_event(new BuildAvatarEvent(Ctx::$user));
        $current = $avatar_e->html;

        $image = Image::by_id($image_id);
        if (!$image) {
            throw new PostNotFound("Image $image_id not found");
        }

        Ctx::$page->set_title("Edit Avatar");
        Ctx::$page->add_block(new Block("Current Avatar", DIV(["class" => "avatar-editor"], $current)));
        Ctx::$page->add_block(new Block("Avatar Editor", $this->avatar_editor_html($image)));
    }

    public function avatar_editor_html(Image $image): HTMLElement
    {
        $url = $image->get_thumb_link();
        return DIV(
            ["class" => "avatar-editor"],
            LABEL("drag the image to move, slide to zoom"),
            BR(),
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
                make_link("save_avatar"),
                INPUT(["type" => "hidden", "name" => "id", "value" => $image->id]),
                INPUT(["type" => "hidden", "name" => "scale", "id" => "avatar-post-scale", "value" => 100]),
                INPUT(["type" => "hidden", "name" => "x", "id" => "avatar-post-x", "value" => 0]),
                INPUT(["type" => "hidden", "name" => "y", "id" => "avatar-post-y", "value" => 0]),
                INPUT(["type" => "submit", "value" => "Set avatar"]),
            )
        );
    }
}
