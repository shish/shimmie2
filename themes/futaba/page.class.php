<?php declare(strict_types=1);

class Page extends BasePage
{
    public bool $left_enabled = true;
    public function disable_left()
    {
        $this->left_enabled = false;
    }

    public function render()
    {
        $left_block_html = "";
        $main_block_html = "";
        $sub_block_html = "";

        foreach ($this->blocks as $block) {
            switch ($block->section) {
                case "left":
                    $left_block_html .= $block->get_html(true);
                    break;
                case "main":
                    $main_block_html .= $block->get_html(false);
                    break;
                case "subheading":
                    $sub_block_html .= $block->body; // $this->block_to_html($block, true);
                    break;
                default:
                    print "<p>error: {$block->header} using an unknown section ({$block->section})";
                    break;
            }
        }

        if (empty($this->subheading)) {
            $subheading = "";
        } else {
            $subheading = "<div id='subtitle'>{$this->subheading}</div>";
        }

        if ($this->left_enabled) {
            $left = "<nav>$left_block_html</nav>";
            $withleft = "withleft";
        } else {
            $left = "";
            $withleft = "";
        }

        $flash_html = $this->flash ? "<b id='flash'>".nl2br(html_escape(implode("\n", $this->flash)))."</b>" : "";
        $head_html = $this->head_html();
        $footer_html = $this->footer_html();

        print <<<EOD
<!doctype html>
<html class="no-js" lang="en">
    $head_html
	<body>
		<header>
			<h1>{$this->heading}</h1>
			$subheading
			$sub_block_html
		</header>
		$left
		<article class="$withleft">
			$flash_html
			$main_block_html
		</article>
		<footer>
			<hr>
			$footer_html
		</footer>
	</body>
</html>
EOD;
    }
}
