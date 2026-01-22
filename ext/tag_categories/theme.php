<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BUTTON, DIV, INPUT, P, SPAN, TABLE, TD, TR, emptyHTML, joinHTML};

use MicroHTML\HTMLElement;

class TagCategoriesTheme extends Themelet
{
    /**
     * @param array<array{category: string, display_singular: string, display_multiple: string, color: string}> $tc_dict
     */
    public function show_tag_categories(array $tc_dict): void
    {
        $tc_block_index = 0;
        $html = [];

        foreach ($tc_dict as $row) {
            $tc_block_index += 1;
            $tag_category = $row['category'];
            $tag_single_name = $row['display_singular'];
            $tag_multiple_name = $row['display_multiple'];
            $tag_color = $row['color'];
            $html[] = DIV(
                ["class" => "tagcategoryblock"],
                SHM_SIMPLE_FORM(
                    make_link("tags/categories"),
                    TABLE(
                        TR(
                            TD("Category"),
                            TD(
                                SPAN($tag_category),
                                INPUT(["type" => "hidden", "name" => "tc_category", "value" => $tag_category])
                            )
                        ),
                        TR(
                            TD("Name - Single"),
                            TD(
                                SPAN($tag_single_name),
                                INPUT(["type" => "text", "name" => "tc_display_singular", "style" => "display:none", "value" => $tag_single_name])
                            )
                        ),
                        TR(
                            TD("Name - Multiple"),
                            TD(
                                SPAN($tag_multiple_name),
                                INPUT(["type" => "text", "name" => "tc_display_multiple", "style" => "display:none", "value" => $tag_multiple_name])
                            )
                        ),
                        TR(
                            TD("Color"),
                            TD(
                                SPAN($tag_color),
                                DIV(["class" => "tc_colorswatch", "style" => "background-color:$tag_color"]),
                                INPUT(["type" => "color", "name" => "tc_color", "style" => "display:none", "value" => $tag_color])
                            )
                        )
                    ),
                    BUTTON([
                        "class" => "tc_edit",
                        "type" => "button",
                        "onclick" => "$('.tagcategoryblock:nth-of-type($tc_block_index) tr + tr td span').hide(); $('.tagcategoryblock:nth-of-type($tc_block_index) td input').show(); $('.tagcategoryblock:nth-of-type($tc_block_index) .tc_edit').hide(); $('.tagcategoryblock:nth-of-type($tc_block_index) .tc_colorswatch').hide(); $('.tagcategoryblock:nth-of-type($tc_block_index) .tc_submit').show();"
                    ], "Edit"),
                    BUTTON([
                        "class" => "tc_submit",
                        "type" => "submit",
                        "style" => "display:none;",
                        "name" => "tc_status",
                        "value" => "edit"
                    ], "Submit"),
                    BUTTON([
                        "class" => "tc_submit",
                        "type" => "button",
                        "style" => "display:none;",
                        "onclick" => "$('.tagcategoryblock:nth-of-type($tc_block_index) .tc_delete').show(); $(this).hide();",
                    ], "Delete"),
                    BUTTON([
                        "class" => "tc_delete",
                        "type" => "submit",
                        "style" => "display:none;",
                        "name" => "tc_status",
                        "value" => "delete",
                    ], "Really, really delete"),
                )
            );
        }

        // new
        $tag_category = 'example';
        $tag_single_name = 'Example';
        $tag_multiple_name = 'Examples';
        $tag_color = '#EE5542';
        $html[] = DIV(
            ["class" => "tagcategoryblock"],
            SHM_SIMPLE_FORM(
                make_link("tags/categories"),
                TABLE(
                    TR(
                        TD("Category"),
                        TD(
                            INPUT(["type" => "text", "name" => "tc_category", "value" => $tag_category])
                        )
                    ),
                    TR(
                        TD("Name - Single"),
                        TD(
                            INPUT(["type" => "text", "name" => "tc_display_singular", "value" => $tag_single_name])
                        )
                    ),
                    TR(
                        TD("Name - Multiple"),
                        TD(
                            INPUT(["type" => "text", "name" => "tc_display_multiple", "value" => $tag_multiple_name])
                        )
                    ),
                    TR(
                        TD("Color"),
                        TD(
                            INPUT(["type" => "color", "name" => "tc_color", "value" => $tag_color])
                        )
                    ),
                ),
                BUTTON(["class" => "tc_submit", "type" => "submit", "name" => "tc_status", "value" => "new"], "Submit"),
            ),
        );

        // add html to stuffs
        Ctx::$page->set_title("Tag Categories");
        Ctx::$page->add_block(new Block("Editing", joinHTML("\n", $html), "main", 10));
    }

    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P("Search for posts containing a certain number of tags with the specified tag category."),
            SHM_COMMAND_EXAMPLE("person_tags=1", "Returns posts with exactly 1 tag with the tag category 'person'."),
            SHM_COMMAND_EXAMPLE("cat_tags>0", "Returns posts with 1 or more tags with the tag category 'cat'."),
            P("Can use <, <=, >, >=, or =."),
            P("Category name is not case sensitive, category must exist for search to work.")
        );
    }
}
