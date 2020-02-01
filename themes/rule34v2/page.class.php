<?php declare(strict_types=1);
class Page extends BasePage
{
    public function render($nav_links, $subnav_links)
    {
        global $config;

        $theme_name = $config->get_string('theme', 'default');
        $data_href = get_base_href();
        $contact_link = contact_link();
        $header_html = $this->get_all_html_headers();

        $left_block_html = "";
        $right_block_html = "";
        $main_block_html = "";
        $head_block_html = "";
        $sub_block_html = "";

        foreach ($this->blocks as $block) {
            switch ($block->section) {
                case "left":
                    $left_block_html .= $block->get_html(true);
                    break;
                case "right":
                    $right_block_html .= $block->get_html(true);
                    break;
                case "head":
                    $head_block_html .= "<td class='headcol'>".$block->get_html(false)."</td>";
                    break;
                case "main":
                    $main_block_html .= $block->get_html(false);
                    break;
                case "subheading":
                    $sub_block_html .= $block->body; // $block->get_html(true);
                    break;
                default:
                    print "<p>error: {$block->header} using an unknown section ({$block->section})";
                    break;
            }
        }

        $debug = get_debug_info();

        $contact = empty($contact_link) ? "" : "<br><a href='$contact_link'>Contact</a>";
        $subheading = empty($this->subheading) ? "" : "<div id='subtitle'>{$this->subheading}</div>";

        $wrapper = "";
        if (strlen($this->heading) > 100) {
            $wrapper = ' style="height: 3em; overflow: auto;"';
        }

        $flash_html = $this->flash ? "<b id='flash'>".nl2br(html_escape(implode("\n", $this->flash)))."</b>" : "";

        $generated = autodate(date('c'));
        $debug .= "; generated $generated";
        $query = !empty($this->_search_query) ? html_escape(Tag::implode($this->_search_query)) : "";

        $self = _get_query();

        # $header_html_thing = file_get_contents("themes/rule34v2/header.inc");
        $footer_html = $this->footer_html();
        print <<<EOD
<!DOCTYPE html>
<html>
	<head>
		<title>{$this->title}</title>
		<meta name="description" content="Rule 34, if it exists there is porn of it."/>
		<meta name="viewport" content="width=1024">
		<link rel="stylesheet" href="$data_href/themes/$theme_name/menuh.css" type="text/css">
$header_html

		<script defer src="https://unpkg.com/webp-hero@0.0.0-dev.21/dist-cjs/polyfills.js"></script>
		<script defer src="https://unpkg.com/webp-hero@0.0.0-dev.21/dist-cjs/webp-hero.bundle.js"></script>
		<script>
		document.addEventListener('DOMContentLoaded', () => {
			var webpMachine = new webpHero.WebpMachine()
			webpMachine.polyfillDocument()
		});
		</script>
	</head>

	<body>
<table id="header" width="100%">
	<tr>
		<td>
EOD;
        include "themes/rule34v2/header.inc";
        print <<<EOD
		</td>
		$head_block_html
	</tr>
</table>
		$sub_block_html

		<nav>
			$left_block_html
			<p>
				<a href="//whos.amung.us/show/4vcsbthd"><img src="//whos.amung.us/widget/4vcsbthd.png" style="display:none" alt="web counter" /></a>
			</p>
		</nav>

		<article>
			$flash_html
			<!-- <h2>Database reboot will be happening in a bit, expect a few minutes of downtime~</h2>
 -->
			$main_block_html
		</article>

		<footer>
<font size="2px"><a href="http://rule34.paheal.net/wiki/Terms%20of%20use">Terms of use</a> !!! <a href="http://rule34.paheal.net/wiki/Privacy%20policy">Privacy policy</a> !!! <a href="http://rule34.paheal.net/wiki/2257">18 U.S.C. &sect;2257</a><br /></font>
<hr />
<font size="2px">BTC: <b>193gutWtgirF7js14ivcXfnfQgXv9n5BZo</b>
ETH: <b>0x68B88a00e69Bde88E9db1b9fC10b8011226e26aF</b></font>
<hr />
<br>
Thank you!

			$footer_html
		</footer>

		<!-- BEGIN EroAdvertising ADSPACE CODE -->
<!--<script type="text/javascript" language="javascript" charset="utf-8" src="https://adspaces.ero-advertising.com/adspace/158168.js"></script>-->
<!-- END EroAdvertising ADSPACE CODE -->
		<!-- self: $self -->
	</body>
</html>
EOD;
    }
}
