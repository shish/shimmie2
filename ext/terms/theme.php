<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{emptyHTML, TITLE, META, rawHTML};

class TermsTheme extends Themelet
{
    public function display_page(Page $page, string $sitename, string $path, string $body): void
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
            <<<EOD
<div id="front-page">
	<h1><span>$sitename</span></h1>
	$body
	<form action="/accept_terms/$path" method="POST">
		<button>Enter</button>
	</form>
</div>
EOD
        ));
    }
}
