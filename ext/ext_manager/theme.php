<?php

class ExtManagerTheme extends Themelet
{
    /**
     * #param ExtensionInfo[] $extensions
     */
    public function display_table(Page $page, array $extensions, bool $editable)
    {
        $h_en = $editable ? "<th>Enabled</th>" : "";
        $html = "
			" . make_form(make_link("ext_manager/set")) . "
				<table id='extensions' class='zebra sortable'>
					<thead>
						<tr>
							$h_en
							<th>Name</th>
							<th>Docs</th>
							<th>Description</th>
						</tr>
					</thead>
					<tbody>
		";
        foreach ($extensions as $extension) {
            if ((!$editable && $extension->visibility === ExtensionInfo::VISIBLE_ADMIN)
                    || $extension->visibility === ExtensionInfo::VISIBLE_HIDDEN) {
                continue;
            }

            $h_name = html_escape(($extension->beta===true ? "[BETA] ":"").(empty($extension->name) ? $extension->key : $extension->name));
            $h_description = html_escape($extension->description);
            $h_link = make_link("ext_doc/" . url_escape($extension->key));

            $h_enabled = ($extension->is_enabled() === true ? " checked='checked'" : "");
            $h_disabled = ($extension->is_supported()===false || $extension->core===true? " disabled ": " ");

            //baseline_open_in_new_black_18dp.png

            $h_enabled_box = $editable ? "<td><input type='checkbox' name='ext_" . html_escape($extension->key) . "' id='ext_" . html_escape($extension->key) . "'$h_disabled $h_enabled></td>" : "";
            $h_docs = ($extension->documentation ? "<a href='$h_link'><img src='ext/ext_manager/baseline_open_in_new_black_18dp.png'/></a>" : ""); //TODO: A proper "docs" symbol would be preferred here.

            $html .= "
				<tr data-ext='{$extension->name}'>
					{$h_enabled_box}
					<td><label for='ext_" . html_escape($extension->key) . "'>{$h_name}</label></td>
					<td>{$h_docs}</td>
					<td style='text-align: left;'>{$h_description} <b style='color:red'>".$extension->get_support_info()."</b></td>
				</tr>";
        }
        $h_set = $editable ? "<tfoot><tr><td colspan='5'><input type='submit' value='Set Extensions'></td></tr></tfoot>" : "";
        $html .= "
					</tbody>
					$h_set
				</table>
			</form>
		";

        $page->set_title("Extensions");
        $page->set_heading("Extensions");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Extension Manager", $html));
    }

    /*
    public function display_blocks(Page $page, $extensions) {
        global $user;
        $col_1 = "";
        $col_2 = "";
        foreach($extensions as $extension) {
            $ext_name = $extension->name;
            $h_name = empty($extension->name) ? $ext_name : html_escape($extension->name);
            $h_email = html_escape($extension->email);
            $h_link = isset($extension->link) ?
                    "<a href=\"".html_escape($extension->link)."\">Original Site</a>" : "";
            $h_doc = isset($extension->documentation) ?
                    "<a href=\"".make_link("ext_doc/".html_escape($extension->name))."\">Documentation</a>" : "";
            $h_author = html_escape($extension->author);
            $h_description = html_escape($extension->description);
            $h_enabled = $extension->is_enabled() ? " checked='checked'" : "";
            $h_author_link = empty($h_email) ?
                    "$h_author" :
                    "<a href='mailto:$h_email'>$h_author</a>";

            $html = "
                <p><table border='1'>
                    <tr>
                        <th colspan='2'>$h_name</th>
                    </tr>
                    <tr>
                        <td>By $h_author_link</td>
                        <td width='25%'>Enabled:&nbsp;<input type='checkbox' name='ext_$ext_name'$h_enabled></td>
                    </tr>
                    <tr>
                        <td style='text-align: left' colspan='2'>$h_description<p>$h_link $h_doc</td>
                    </tr>
                </table>
            ";
            if($n++ % 2 == 0) {
                $col_1 .= $html;
            }
            else {
                $col_2 .= $html;
            }
        }
        $html = "
            ".make_form(make_link("ext_manager/set"))."
                ".$user->get_auth_html()."
                <table border='0'>
                    <tr><td width='50%'>$col_1</td><td>$col_2</td></tr>
                    <tr><td colspan='2'><input type='submit' value='Set Extensions'></td></tr>
                </table>
            </form>
        ";

        $page->set_title("Extensions");
        $page->set_heading("Extensions");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Extension Manager", $html));
    }
    */

    public function display_doc(Page $page, ExtensionInfo $info)
    {
        $author = "";
        if (count($info->authors) > 0) {
            $author = "<br /><b>Author";
            if (count($info->authors) > 1) {
                $author .= "s";
            }
            $author .= ":</b>";
            foreach ($info->authors as $auth=>$email) {
                if (!empty($email)) {
                    $author .= "<a href=\"mailto:" . html_escape($email) . "\">" . html_escape($auth) . "</a>";
                } else {
                    $author .= html_escape($auth);
                }
                $author .= "<br/>";
            }
        }

        $version = ($info->version) ? "<br><b>Version:</b> " . html_escape($info->version) : "";
        $link = ($info->link) ? "<br><b>Home Page:</b> <a href=\"" . html_escape($info->link) . "\">Link</a>" : "";
        $doc = $info->documentation;
        $html = "
			<div style='margin: auto; text-align: left; width: 512px;'>
				$author
				$version
				$link
				<p>$doc
				<hr>
				<p><a href='" . make_link("ext_manager") . "'>Back to the list</a>
			</div>";

        $page->set_title("Documentation for " . html_escape($info->name));
        $page->set_heading(html_escape($info->name));
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Documentation", $html));
    }
}
