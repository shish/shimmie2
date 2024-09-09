<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{BODY, emptyHTML, TITLE, META, rawHTML};

class HomeTheme extends Themelet
{
    public function display_page(Page $page, string $sitename, HTMLElement $body): void
    {
        $page->set_mode(PageMode::DATA);
        $page->add_auto_html_headers();

        $page->set_data((string)$page->html_html(
            emptyHTML(
                TITLE($sitename),
                META(["http-equiv" => "Content-Type", "content" => "text/html;charset=utf-8"]),
                META(["name" => "viewport", "content" => "width=device-width, initial-scale=1"]),
                $page->get_all_html_headers(),
            ),
            $body
        ));
    }

    public function build_body(string $sitename, string $main_links, string $main_text, string $contact_link, string $num_comma, string $counter_text): HTMLElement
    {
        global $page;
        $page->set_layout("front-page");

        $main_links_html = empty($main_links) ? "" : "<div class='space' id='links'>$main_links</div>";
        $message_html = empty($main_text) ? "" : "<div class='space' id='message'>$main_text</div>";
        $counter_html = empty($counter_text) ? "" : "<div class='space' id='counter'>$counter_text</div>";
        $contact_link = empty($contact_link) ? "" : "<br><a href='$contact_link'>Contact</a> &ndash;";
        $search_html = "
			<div class='space' id='search'>
				<form action='".search_link()."' method='GET'>
				<input name='search' size='30' type='search' value='' class='autocomplete_tags' autofocus='autofocus' />
				<input type='hidden' name='q' value='post/list'>
				<input type='submit' value='Search'/>
				</form>
			</div>
		";
        return BODY(
            $page->body_attrs(),
            rawHTML("
		<div id='front-page'>
			<h1><a style='text-decoration: none;' href='".make_link()."'><span>$sitename</span></a></h1>
			$main_links_html
			$search_html
			$message_html
			$counter_html
			<div class='space' id='foot'>
				<small><small>
				$contact_link" . (empty($num_comma) ? "" : " Serving $num_comma posts &ndash;") . "
				Running <a href='https://code.shishnet.org/shimmie2/'>Shimmie2</a>
				</small></small>
			</div>
		</div>")
        );
    }
}
