<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{emptyHTML, TITLE, META, rawHTML};

class TermsTheme extends Themelet
{
    public function display_page(Page $page, string $sitename, string $path, string $body): void
    {
        $html =
        "<div id='terms-modal-bg'>
		    <dialog id='terms-modal' class='setupblock' open>
				<h1><span>$sitename</span></h1>
				$body
				<form action='/accept_terms/$path' method='POST'>
					<button class='terms-modal-enter' autofocus>Enter</button>
				</form>
			</dialog>
		</div>";
        $page->add_block(new Block(null, rawHTML($html), "main", 1));
    }
}
