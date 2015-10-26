<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Tagger - Advanced Tagging v2                                              *
 * Author: Artanis (Erik Youngren <artanis.00@gmail.com>)                    *
 * Do not remove this notice.                                                *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class taggerTheme extends Themelet {
	public function build_tagger (Page $page, $event) {
		// Initialization code
		$base_href = get_base_href();
		// TODO: AJAX test and fallback.

		$page->add_html_header("<script src='$base_href/ext/tagger/webtoolkit.drag.js' type='text/javascript'></script>");
		$page->add_block(new Block(null,
			"<script type='text/javascript'>
				$( document ).ready(function() {
					Tagger.initialize(".$event->get_image()->id.");
				});
			</script>","main",1000));

		// Tagger block
		$page->add_block( new Block(
			null,
			$this->html($event->get_image()),
			"main"));
	}
	private function html(Image $image) {
		global $config;
		$i_image_id = int_escape($image->id);
		$h_source = html_escape($image->source);
		$h_query = isset($_GET['search'])? $h_query= "search=".url_escape($_GET['search']) : "";

		$delay = $config->get_string("ext_tagger_search_delay","250");

		$url_form = make_link("tag_edit/set");

		// TODO: option for initial Tagger window placement.
		$html = <<< EOD
<div id="tagger_parent" style="display:none; top:25px; right:25px;">
	<div id="tagger_titlebar">Tagger</div>

	<div id="tagger_toolbar">
		<input type="text" value="" id="tagger_filter" onkeyup="Tagger.tag.search(this.value, $delay);"></input>
		<input type="button" value="Add" onclick="Tagger.tag.create(byId('tagger_filter').value);"></input>
		<form action="$url_form" method="POST" onsubmit="Tagger.tag.submit();">
			<input type='hidden' name='image_id' value='$i_image_id' id="image_id"></input>
			<input type='hidden' name='query' value='$h_query'></input>
			<input type='hidden' name='source' value='$h_source'></input>
			<input type="hidden" name="tags" value="" id="tagger_tags"></input>

			<input type="submit" value="Set"></input>
		</form>
		<!--<ul id="tagger_p-menu"></ul>
		<br style="clear:both;"/>-->
	</div>

	<div id="tagger_body">
		<div id="tagger_p-search" name="Searched Tags"></div>
		<div id="tagger_p-applied" name="Applied Tags"></div>
	</div>
	<div id="tagger_statusbar"></div>
</div>
EOD;
		return $html;
	}
}

