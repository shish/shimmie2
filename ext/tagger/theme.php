<?php declare(strict_types=1);
use function MicroHTML\DIV;
use function MicroHTML\FORM;
use function MicroHTML\INPUT;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Tagger - Advanced Tagging v2                                              *
 * Author: Artanis (Erik Youngren <artanis.00@gmail.com>)                    *
 * Do not remove this notice.                                                *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class TaggerTheme extends Themelet
{
    public function build_tagger(Page $page, DisplayingImageEvent $event)
    {
        // Initialization code
        $base_href = get_base_href();
        // TODO: AJAX test and fallback.

        $page->add_html_header("<script src='$base_href/ext/tagger/webtoolkit.drag.js' type='text/javascript'></script>");
        $page->add_block(new Block(
            null,
            "<script type='text/javascript'>
				$( document ).ready(function() {
					Tagger.initialize(".$event->get_image()->id.");
				});
			</script>",
            "main",
            1000
        ));

        // Tagger block
        $page->add_block(new Block(
            null,
            (string)$this->html($event->get_image()),
            "main"
        ));
    }
    private function html(Image $image)
    {
        global $config;
        $h_query = isset($_GET['search'])? $h_query= "search=".url_escape($_GET['search']) : "";

        $delay = $config->get_string("ext_tagger_search_delay", "250");

        // TODO: option for initial Tagger window placement.
        return DIV(
            ["id"=>"tagger_parent", "style"=>"display:none; top:25px; right:25px;"],
            DIV(["id"=>"tagger_titlebar"], "Tagger"),
            DIV(
                ["id"=>"tagger_toolbar"],
                INPUT(["type"=>"text", "value"=>"", "id"=>"tagger_filter", "onkeyup"=>"Tagger.tag.search(this.value, $delay);"]),
                INPUT(["type"=>"button", "value">"Add", "onclick"=>"Tagger.tag.create(byId('tagger_filter').value);"]),
                FORM(
                    ["action"=>make_link("tag_edit/set"), "method"=>"POST", "onsubmit"=>"Tagger.tag.submit();"],
                    INPUT(["type"=>"hidden", "name"=>"image_id", "value"=>$image->id, "id"=>"image_id"]),
                    INPUT(["type"=>"hidden", "name"=>"query", "value"=>$h_query, "id"=>""]),
                    INPUT(["type"=>"hidden", "name"=>"source", "value"=>$image->source, "id"=>""]),
                    INPUT(["type"=>"hidden", "name"=>"tags", "value"=>"", "id"=>"tagger_tags"]),
                    INPUT(["type"=>"", "value"=>"Set"]),
                ),
                # UL(["id"=>"tagger_p-menu"]),
                # BR(["style"=>"clear:both;"]),
            ),
            DIV(
                ["id"=>"tagger_body"],
                DIV(["id"=>"tagger_p-search", "name"=>"Searched Tags"]),
                DIV(["id"=>"tagger_p-applied", "name"=>"Applied Tags"]),
            ),
            DIV(
                ["id"=>"tagger_statusbar"],
            ),
        );
    }
}
