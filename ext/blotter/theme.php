<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * @phpstan-type BlotterEntry array{id:int,entry_date:string,entry_text:string,important:bool}
 */
class BlotterTheme extends Themelet
{
    /**
     * @param BlotterEntry[] $entries
     */
    public function display_editor(array $entries): void
    {
        global $page;
        $html = $this->get_html_for_blotter_editor($entries);
        $page->set_title("Blotter Editor");
        $page->set_heading("Blotter Editor");
        $page->add_block(new Block("Welcome to the Blotter Editor!", $html, "main", 10));
        $page->add_block(new Block("Navigation", "<a href='".make_link()."'>Index</a>", "left", 0));
    }

    /**
     * @param BlotterEntry[] $entries
     */
    public function display_blotter_page(array $entries): void
    {
        global $page;
        $html = $this->get_html_for_blotter_page($entries);
        $page->set_title("Blotter");
        $page->set_heading("Blotter");
        $page->add_block(new Block("Blotter Entries", $html, "main", 10));
    }

    /**
     * @param BlotterEntry[] $entries
     */
    public function display_blotter(array $entries): void
    {
        global $page, $config;
        $html = $this->get_html_for_blotter($entries);
        $position = $config->get_string("blotter_position", "subheading");
        $page->add_block(new Block(null, $html, $position, 20));
    }

    /**
     * @param BlotterEntry[] $entries
     */
    private function get_html_for_blotter_editor(array $entries): string
    {
        global $user;

        /**
         * Long function name, but at least I won't confuse it with something else ^_^
         */

        // Add_new stuff goes here.
        $table_header =  "
			<tr>
			<th>Date</th>
			<th>Message</th>
			<th>Important?</th>
			<th>Action</th>
			</tr>";
        $add_new = "
			<tr class='even'>
			".make_form(make_link("blotter/add"))."
			<td colspan='2'><textarea style='text-align:left;' name='entry_text' rows='2' /></textarea></td>
			<td><input type='checkbox' name='important' /></td>
			<td><input type='submit' value='Add'></td>
			</form>
			</tr>";


        // Now, time for entries list.
        $table_rows = "";
        $num_entries = count($entries);
        for ($i = 0 ; $i < $num_entries ; $i++) {
            /**
             * Add table rows
             */
            $id = $entries[$i]['id'];
            $entry_date = $entries[$i]['entry_date'];
            $entry_text = $entries[$i]['entry_text'];
            if ($entries[$i]['important'] == 'Y') {
                $important = 'Y';
            } else {
                $important = 'N';
            }

            // Add the new table row(s)
            $table_rows .=
                "<tr>
				<td>$entry_date</td>
				<td>$entry_text</td>
				<td>$important</td>
				<td>".make_form(make_link("blotter/remove"), name: "remove$id")."
				<input type='hidden' name='id' value='$id' />
				<input type='submit' style='width: 100%;' value='Remove' />
				</form>
				</td>
				</tr>";
        }

        $html = "
			<table id='blotter_entries' class='zebra'>
			<thead>$table_header</thead>
			<tbody>$add_new</tbody>
			<tfoot>$table_rows</tfoot>
			</table>

			<br />
			<b>Help:</b><br />
			<blockquote>Add entries to the blotter, and they will be displayed.</blockquote>";

        return $html;
    }

    /**
     * @param BlotterEntry[] $entries
     */
    private function get_html_for_blotter_page(array $entries): string
    {
        /**
         * This one displays a list of all blotter entries.
         */
        global $config;
        $i_color = $config->get_string("blotter_color", "#FF0000");
        $html = "<pre>";

        $num_entries = count($entries);
        for ($i = 0 ; $i < $num_entries ; $i++) {
            /**
             * Blotter entries
             */
            // Reset variables:
            $i_open = "";
            $i_close = "";
            //$id = $entries[$i]['id'];
            $messy_date = $entries[$i]['entry_date'];
            $clean_date = date("y/m/d", \Safe\strtotime($messy_date));
            $entry_text = $entries[$i]['entry_text'];
            if ($entries[$i]['important'] == 'Y') {
                $i_open = "<span style='color: #$i_color;'>";
                $i_close = "</span>";
            }
            $html .= "{$i_open}{$clean_date} - {$entry_text}{$i_close}<br /><br />";
        }
        $html .= "</pre>";
        return $html;
    }

    /**
     * @param BlotterEntry[] $entries
     */
    private function get_html_for_blotter(array $entries): string
    {
        global $config;
        $i_color = $config->get_string("blotter_color", "#FF0000");
        $position = $config->get_string("blotter_position", "subheading");
        $entries_list = "";
        foreach($entries as $entry) {
            /**
             * Blotter entries
             */
            // Reset variables:
            $i_open = "";
            $i_close = "";
            //$id = $entry['id'];
            $messy_date = $entry['entry_date'];
            $clean_date = date("m/d/y", \Safe\strtotime($messy_date));
            $entry_text = $entry['entry_text'];
            if ($entry['important'] == 'Y') {
                $i_open = "<span style='color: #$i_color'>";
                $i_close = "</span>";
            }
            $entries_list .= "<li>{$i_open}{$clean_date} - {$entry_text}{$i_close}</li>";
        }

        $pos_break = "";
        $pos_align = "text-align: right; position: absolute; right: 0px;";

        if ($position === "left") {
            $pos_break = "<br />";
            $pos_align = "";
        }

        if (count($entries) === 0) {
            $out_text = "No blotter entries yet.";
            $in_text = "Empty.";
        } else {
            $clean_date = date("m/d/y", \Safe\strtotime($entries[0]['entry_date']));
            $out_text = "Blotter updated: {$clean_date}";
            $in_text = "<ul>$entries_list</ul>";
        }

        $html = "
			<div id='blotter1' class='shm-blotter1'>
				<span>$out_text</span>
				{$pos_break}
				<span style='{$pos_align}'>
					<a href='#' id='blotter2-toggle' class='shm-blotter2-toggle'>Show/Hide</a>
					<a href='".make_link("blotter/list")."'>Show All</a>
				</span>
			</div>
			<div id='blotter2' class='shm-blotter2'>$in_text</div>
		";
        return $html;
    }
}
