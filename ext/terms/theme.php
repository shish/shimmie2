<?php

declare(strict_types=1);

namespace Shimmie2;

class TermsTheme extends Themelet
{
    public function display_page(Page $page, string $sitename, string $path, string $body): void
    {
        $page->set_mode(PageMode::DATA);
        $page->add_auto_html_headers();
        $hh = $page->get_all_html_headers();
        $page->set_data(
            <<<EOD
<!doctype html>
<html lang="en">
	<head>
		<title>$sitename</title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		$hh
	</head>
	<body>
		<div id="front-page">
			<h1><span>$sitename</span></h1>
			$body
			<form action="/accept_terms/$path" method="POST">
				<button>Enter</button>
			</form>
		</div>
	</body>
</html>
EOD
        );
    }
}
