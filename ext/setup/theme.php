<?php declare(strict_types=1);

use function MicroHTML\rawHTML;
use function MicroHTML\TR;
use function MicroHTML\TH;
use function MicroHTML\TD;

class SetupTheme extends Themelet
{
    /*
     * Display a set of setup option blocks
     *
     * $panel = the container of the blocks
     * $panel->blocks the blocks to be displayed, unsorted
     *
     * It's recommended that the theme sort the blocks before doing anything
     * else, using:  usort($panel->blocks, "blockcmp");
     *
     * The page should wrap all the options in a form which links to setup_save
     */
    public function display_page(Page $page, SetupPanel $panel)
    {
        usort($panel->blocks, "blockcmp");

        /*
         * Try and keep the two columns even; count the line breaks in
         * each an calculate where a block would work best
         */
        $setupblock_html = "";
        foreach ($panel->blocks as $block) {
            $setupblock_html .= $this->sb_to_html($block);
        }

        $table = "
			".make_form(make_link("setup/save"))."
				<div class='setupblocks'>$setupblock_html</div>
				<input type='submit' value='Save Settings'>
			</form>
			";

        $page->set_title("Shimmie Setup");
        $page->set_heading("Shimmie Setup");
        $page->add_block(new Block("Navigation", $this->build_navigation(), "left", 0));
        $page->add_block(new Block("Setup", $table));
    }

    public function display_advanced(Page $page, $options)
    {
        $h_rows = "";
        ksort($options);
        foreach ($options as $name => $value) {
            if (is_null($value)) {
                $value = '';
            }

            $h_name = html_escape($name);
            $h_value = html_escape((string)$value);

            $h_box = "";
            if (is_string($value) && str_contains($value, "\n")) {
                $h_box .= "<textarea cols='50' rows='4' name='_config_$h_name'>$h_value</textarea>";
            } else {
                $h_box .= "<input type='text' name='_config_$h_name' value='$h_value'>";
            }
            $h_box .= "<input type='hidden' name='_type_$h_name' value='string'>";
            $h_rows .= "<tr><td>$h_name</td><td>$h_box</td></tr>";
        }

        $table = "
			".make_form(make_link("setup/save"))."
				<table id='settings' class='zebra'>
					<thead><tr><th width='25%'>Name</th><th>Value</th></tr></thead>
					<tbody>$h_rows</tbody>
					<tfoot><tr><td colspan='2'><input type='submit' value='Save Settings'></td></tr></tfoot>
				</table>
			</form>
			";

        $page->set_title("Shimmie Setup");
        $page->set_heading("Shimmie Setup");
        $page->add_block(new Block("Navigation", $this->build_navigation(), "left", 0));
        $page->add_block(new Block("Setup", $table));
    }

    protected function build_navigation(): string
    {
        return "
			<a href='".make_link()."'>Index</a>
			<br><a href='https://github.com/shish/shimmie2/wiki/Settings'>Help</a>
			<br><a href='".make_link("setup/advanced")."'>Advanced</a>
		";
    }

    protected function sb_to_html(SetupBlock $block): string
    {
        $h = $block->header;
        $b = $this->build_setup_chunk($block->body);
        $i = preg_replace('/[^a-zA-Z0-9]/', '_', $h) . "-setup";
        $html = "
			<section class='setupblock'>
				<b class='shm-toggler' data-toggle-sel='#$i'>$h</b>
				<br><div id='$i'>$b</div>
			</section>
		";
        return $html;
    }

    protected function build_setup_chunk(string $html): string
    {
        return "<table class='form'>$html</table>";
    }

    protected function build_setup_row(string $html): string
    {
        return (string)TR(rawHTML($html));
    }

    protected function build_setup_cell(string $html, bool $is_header=false, bool $full_width=false): string
    {
        $attr = ["colspan"=>($is_header || $full_width ? '2' : '1')];
        $cell = null;
        if ($is_header) {
            $cell = TH($attr, rawHTML($html));
        } else {
            $cell = TD($attr, rawHTML($html));
        }

        if ($is_header || $full_width) {
            return $this->build_setup_row((string)$cell);
        } else {
            return (string)$cell;
        }
    }

    public function format_item(?string $label, ?string $html, ?string $config_name, bool $label_is_header=false, bool $full_width=false): string
    {
        if (empty($label) && empty($html)) {
            return "";
        }

        if (!empty($label)) {
            if (!empty($config_name)) {
                $label = "<label for='{$config_name}'>{$label}</label>";
            }
            $label = $this->build_setup_cell($label, $label_is_header, $full_width);
        } else {
            $label = "";
        }

        if (!empty($html)) {
            $html = $this->build_setup_cell($html, false, $full_width);
        } else {
            $html = "";
        }

        if ($label_is_header || $full_width) {
            return $label . $html;
        }

        return $this->build_setup_row($label . $html);
    }
}
