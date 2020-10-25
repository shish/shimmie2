<?php declare(strict_types=1);
class Page extends BasePage
{
    public function render()
    {
        global $config, $user;

        $theme_name = $config->get_string('theme', 'default');
        $data_href = get_base_href();
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

        $query = !empty($this->_search_query) ? html_escape(Tag::implode($this->_search_query)) : "";
        assert(!is_null($query));  # used in header.inc, do not remove :P
        $flash_html = $this->flash ? "<b id='flash'>".nl2br(html_escape(implode("\n", $this->flash)))."</b>" : "";
        $generated = autodate(date('c'));
        $footer_html = $this->footer_html();

        print <<<EOD
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>{$this->title}</title>
		<meta name="description" content="Rule 34, if it exists there is porn of it."/>
		<meta name="viewport" content="width=1024">
		<meta name="theme-color" content="#7EB977">
		<link rel="stylesheet" href="$data_href/themes/$theme_name/menuh.css?_=1" type="text/css">
$header_html

		<script defer src="https://unpkg.com/webp-hero@0.0.0-dev.21/dist-cjs/polyfills.js"></script>
		<script defer src="https://unpkg.com/webp-hero@0.0.0-dev.21/dist-cjs/webp-hero.bundle.js"></script>
		<script>
		document.addEventListener('DOMContentLoaded', () => {
			var webpMachine = new webpHero.WebpMachine()
			webpMachine.polyfillDocument()
		});
		</script>
		<script src="/themes/rule34v2/prebid-ads.js"></script>
		<script>
		function makeid(length) {
			var result           = '';
			var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
			var charactersLength = characters.length;
			for ( var i = 0; i < length; i++ ) {
				result += characters.charAt(Math.floor(Math.random() * charactersLength));
			}
			return result;
		}
		function stat(ob) {
			ob._ = makeid(10);
			var xhr = new XMLHttpRequest();
			xhr.open("GET", "/stat.txt?" + new URLSearchParams(ob).toString(), true);
			xhr.send();
		}
		setTimeout(function(){
			var t = window.performance.timing;
			stat({
				"v": 1,
				"class": "{$user->class->name}",
				"block": window.canRunAds === undefined,
				"proto": window.location.protocol,
				"responseStart": t.responseStart - t.fetchStart,
				"responseEnd": t.responseEnd - t.fetchStart,
				"domLoading": t.domLoading - t.fetchStart,
				"domInteractive": t.domInteractive - t.fetchStart,
				"domComplete": t.domComplete - t.fetchStart,
			})
		}, 3000);
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
<span style="font-size: 12px;">
    <a href="http://rule34.paheal.net/wiki/Terms%20of%20use">Terms of use</a>
    !!!
    <a href="http://rule34.paheal.net/wiki/Privacy%20policy">Privacy policy</a>
    !!!
    <a href="http://rule34.paheal.net/wiki/2257">18 U.S.C. &sect;2257</a><br />
</span>
<hr />
<span style="font-size: 12px;">
    BTC: <b>193gutWtgirF7js14ivcXfnfQgXv9n5BZo</b>
    ETH: <b>0x68B88a00e69Bde88E9db1b9fC10b8011226e26aF</b>
</span>
<hr />
<br>
Thank you!

            Page generated $generated.
			$footer_html
		</footer>

		<!-- BEGIN EroAdvertising ADSPACE CODE -->
<!--<script type="text/javascript" language="javascript" charset="utf-8" src="https://adspaces.ero-advertising.com/adspace/158168.js"></script>-->
<!-- END EroAdvertising ADSPACE CODE -->
	</body>
</html>
EOD;
    }
}
