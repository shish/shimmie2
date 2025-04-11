<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{INPUT, TABLE, TD, TEXTAREA, TH, TR, joinHTML};

class PostTagsTheme extends Themelet
{
    public function display_mass_editor(): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("tag_edit/replace"),
            TABLE(
                ["class" => "form"],
                TR(TH("Search"), TD(INPUT(["type" => "text", "name" => "search", "class" => "autocomplete_tags"]))),
                TR(TH("Replace"), TD(INPUT(["type" => "text", "name" => "replace", "class" => "autocomplete_tags"]))),
                TR(TD(["colspan" => "2"], SHM_SUBMIT("Replace")))
            )
        );
        Ctx::$page->add_block(new Block("Mass Tag Edit", $html));
    }

    public function get_tag_editor_html(Image $image): HTMLElement
    {
        $tag_links = [];
        foreach ($image->get_tag_array() as $tag) {
            $tag_links[] = $this->build_tag($tag);
        }

        return SHM_POST_INFO(
            "Tags",
            joinHTML(", ", $tag_links),
            Ctx::$user->can(PostTagsPermission::EDIT_IMAGE_TAG) ? TEXTAREA([
                "class" => "autocomplete_tags",
                "type" => "text",
                "name" => "tags",
                "id" => "tag_editor",
                "spellcheck" => "off",
            ], $image->get_tag_list()) : null,
            link: TagHistoryInfo::is_enabled() ?
                make_link("tag_history/{$image->id}") :
                null,
        );
    }

    public function get_upload_common_html(): HTMLElement
    {
        return TR(
            TH(["width" => "20"], "Common Tags"),
            TD(["colspan" => "6"], INPUT(["name" => "tags", "type" => "text", "placeholder" => "tagme", "class" => "autocomplete_tags"]))
        );
    }

    public function get_upload_specific_html(string $suffix): HTMLElement
    {
        return TD(
            INPUT([
                "type" => "text",
                "name" => "tags{$suffix}",
                "class" => "autocomplete_tags",
                "value" => ($suffix === "0") ? @$_GET['tags'] : null,
            ])
        );
    }
}
