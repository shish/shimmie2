<?php

class TagCategoriesTheme extends Themelet {
    var $heading = "";
    var $list = "";

    public function show_tag_categories($page, $tc_dict) {
        $tc_block_index = 0;
        $html = '';

        foreach ($tc_dict as $row) {
            $tc_block_index += 1;
            $tag_category = $row['category'];
            $tag_single_name = $row['display_singular'];
            $tag_multiple_name = $row['display_multiple'];
            $tag_color = $row['color'];
            $html .= '
            <div class="tagcategoryblock">
            <form name="input" action="'.make_link("tags/categories").'" method="post">
                <table>
                <tr>
                    <td>Category</td>
                    <td>
                        <span>'.$tag_category.'</span>
                        <!--<input type="text" name="tc_category" style="display:none" value="'.$tag_category.'">-->
                        <input type="hidden" name="tc_category" value="'.$tag_category.'">
                    </td>
                </tr>
                <tr>
                    <td>Name &ndash; Single</td>
                    <td>
                        <span>'.$tag_single_name.'</span>
                        <input type="text" name="tc_display_singular" style="display:none" value="'.$tag_single_name.'">
                    </td>
                </tr>
                <tr>
                    <td>Name &ndash; Multiple</td>
                    <td>
                        <span>'.$tag_multiple_name.'</span>
                        <input type="text" name="tc_display_multiple" style="display:none" value="'.$tag_multiple_name.'">
                    </td>
                </tr>
                <tr>
                    <td>Color</td>
                    <td>
                        <span>'.$tag_color.'</span>
                        <input type="text" name="tc_color" style="display:none" value="'.$tag_color.'">
                    </td>
                </tr>
                </table>
                <button class="tc_edit" type="button" onclick="$(\'.tagcategoryblock:nth-of-type('.$tc_block_index.') tr + tr td span\').hide(); $(\'.tagcategoryblock:nth-of-type('.$tc_block_index.') td input\').show(); $(\'.tagcategoryblock:nth-of-type('.$tc_block_index.') .tc_edit\').hide(); $(\'.tagcategoryblock:nth-of-type('.$tc_block_index.') .tc_submit\').show();">Edit</button>
                <button class="tc_submit" type="submit" style="display:none;" name="tc_status" value="edit">Submit</button>
                <button class="tc_submit" type="button" style="display:none.tagcategoryblock:nth-of-type('.$tc_block_index.');" onclick="$(\'.tagcategoryblock:nth-of-type('.$tc_block_index.') .tc_delete\').show(); $(this).hide();">Delete</button>
                <button class="tc_delete" type="submit" style="display:none;" name="tc_status" value="delete">Really, really delete</button>
            </form>
            </div>
            ';
        }

        // new
        $tag_category = 'example';
        $tag_single_name = 'Example';
        $tag_multiple_name = 'Examples';
        $tag_color = '#EE5542';
        $html .= '
        <div class="tagcategoryblock">
        <form name="input" action="'.make_link("tags/categories").'" method="post">
            <table>
            <tr>
                <td>Category</td>
                <td>
                    <input type="text" name="tc_category" value="'.$tag_category.'">
                </td>
            </tr>
            <tr>
                <td>Name &ndash; Single</td>
                <td>
                    <input type="text" name="tc_display_singular" value="'.$tag_single_name.'">
                </td>
            </tr>
            <tr>
                <td>Name &ndash; Multiple</td>
                <td>
                    <input type="text" name="tc_display_multiple" value="'.$tag_multiple_name.'">
                </td>
            </tr>
            <tr>
                <td>Color</td>
                <td>
                    <input type="color" name="tc_color" value="'.$tag_color.'">
                </td>
            </tr>
            </table>
            <button class="tc_submit" type="submit" name="tc_status" value="new">Submit</button>
        </form>
        </div>
        ';

        // add html to stuffs
        $page->add_block(new Block("Editing", $html, "main", 10));
    }
}
