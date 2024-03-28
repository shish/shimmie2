<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{TR, TH, TD, emptyHTML, rawHTML, joinHTML, DIV, INPUT, A, TEXTAREA};

class PostSourceTheme extends Themelet
{
    public function mss_html(string $terms): string
    {
        $h_terms = html_escape($terms);
        $html = make_form(make_link("tag_edit/mass_source_set")) . "
				<input type='hidden' name='tags' value='$h_terms'>
				<input type='text' name='source' value=''>
				<input type='submit' value='Set Source For All' onclick='return confirm(\"This will mass-edit all sources on the page.\\nAre you sure you want to do this?\")'>
			</form>
		";
        return $html;
    }

    public function get_source_editor_html(Image $image): HTMLElement
    {
        global $user;
        return SHM_POST_INFO(
            "Source Link",
            DIV(
                ["style" => "overflow: hidden; white-space: nowrap; max-width: 350px; text-overflow: ellipsis;"],
                $this->format_source($image->get_source())
            ),
            $user->can(Permissions::EDIT_IMAGE_SOURCE) ? INPUT(["type" => "text", "name" => "source", "value" => $image->get_source()]) : null,
            link: Extension::is_enabled(SourceHistoryInfo::KEY) ? make_link("source_history/{$image->id}") : null,
        );
    }

    protected function format_source(string $source = null): HTMLElement
    {
        if (!empty($source)) {
            if (!str_contains($source, "://")) {
                $source = "https://" . $source;
            }
            $proto_domain = explode("://", $source);
            $h_source = $proto_domain[1];
            if (str_ends_with($h_source, "/")) {
                $h_source = substr($h_source, 0, -1);
            }
            return A(["href" => $source], $h_source);
        }
        return rawHTML("Unknown");
    }

    public function get_upload_common_html(): HTMLElement
    {
        return TR(
            TH(["width" => "20"], "Common Source"),
            TD(["colspan" => "6"], INPUT(["name" => "source", "type" => "text", "placeholder" => "https://..."]))
        );
    }

    public function get_upload_specific_html(string $suffix): HTMLElement
    {
        return TD(
            INPUT([
                "type" => "text",
                "name" => "source{$suffix}",
                "value" => ($suffix == 0) ? @$_GET['source'] : null,
            ])
        );
    }
}
