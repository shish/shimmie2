<?php

declare(strict_types=1);

namespace Shimmie2;

class TagCategoriesTheme extends Themelet
{
    /**
     * @param array<array{category: string, display_singular: string, display_multiple: string, color: string}> $tc_dict
     */
    public function show_tag_categories(Page $page, array $tc_dict): void
    {
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
            '.make_form(make_link("tags/categories")).'
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
                        <span>'.$tag_color.'</span><div class="tc_colorswatch" style="background-color:'.$tag_color.'"></div>
                        <input type="color" name="tc_color" style="display:none" value="'.$tag_color.'">
                    </td>
                </tr>
                </table>
                <button class="tc_edit" type="button" onclick="$(\'.tagcategoryblock:nth-of-type('.$tc_block_index.') tr + tr td span\').hide(); $(\'.tagcategoryblock:nth-of-type('.$tc_block_index.') td input\').show(); $(\'.tagcategoryblock:nth-of-type('.$tc_block_index.') .tc_edit\').hide(); $(\'.tagcategoryblock:nth-of-type('.$tc_block_index.') .tc_colorswatch\').hide(); $(\'.tagcategoryblock:nth-of-type('.$tc_block_index.') .tc_submit\').show();">Edit</button>
                <button class="tc_submit" type="submit" style="display:none;" name="tc_status" value="edit">Submit</button>
                <button class="tc_submit" type="button" style="display:none;" onclick="$(\'.tagcategoryblock:nth-of-type('.$tc_block_index.') .tc_delete\').show(); $(this).hide();">Delete</button>
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
        '.make_form(make_link("tags/categories")).'
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
        $page->set_title("Tag Categories");
        $page->set_heading("Tag Categories");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Editing", $html, "main", 10));
    }

    public function get_help_html(): string
    {
        return '<p>Search for posts containing a certain number of tags with the specified tag category.</p>
        <div class="command_example">
        <pre>persontags=1</pre>
        <p>Returns posts with exactly 1 tag with the tag category "person".</p>
        </div>
        <div class="command_example">
        <pre>cattags>0</pre>
        <p>Returns posts with 1 or more tags with the tag category "cat". </p>
        </div>
        <p>Can use &lt;, &lt;=, &gt;, &gt;=, or =.</p>
        <p>Category name is not case sensitive, category must exist for search to work.</p>
        ';
    }
}
