<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BUTTON, DIV, INPUT, P, SPAN, TABLE, TD, TR, emptyHTML, joinHTML};

use MicroHTML\HTMLElement;

/**
 * @phpstan-import-type TagCategoryRow from TagCategories
 */
class TagCategoriesTheme extends Themelet
{
    /**
     * @param array<TagCategoryRow> $tc_dict
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
                ["class" => "tagcategoryblock tc-viewing"],
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
                                SPAN(["class" => "tc-view"], $tag_single_name),
                                INPUT(["type" => "text", "name" => "tc_display_singular", "class" => "tc-edit", "value" => $tag_single_name])
                            )
                        ),
                        TR(
                            TD("Name - Multiple"),
                            TD(
                                SPAN(["class" => "tc-view"], $tag_multiple_name),
                                INPUT(["type" => "text", "name" => "tc_display_multiple", "class" => "tc-edit", "value" => $tag_multiple_name])
                            )
                        ),
                        TR(
                            TD("Color"),
                            TD(
                                SPAN(["class" => "tc-view"], $tag_color),
                                DIV(["class" => "tc_colorswatch tc-view", "style" => "background-color:$tag_color"]),
                                INPUT(["type" => "color", "name" => "tc_color", "class" => "tc-edit", "value" => $tag_color])
                            )
                        )
                    ),
                    BUTTON([
                        "class" => "tc-view",
                        "type" => "button",
                        "onclick" => "this.closest('.tagcategoryblock').className = 'tagcategoryblock tc-editing';"
                    ], "Edit"),
                    BUTTON([
                        "class" => "tc-edit",
                        "type" => "submit",
                        "name" => "tc_status",
                        "value" => "edit"
                    ], "Submit"),
                    BUTTON([
                        "class" => "tc-edit",
                        "type" => "button",
                        "onclick" => "this.closest('.tagcategoryblock').className = 'tagcategoryblock tc-deleting';",
                    ], "Delete"),
                    BUTTON([
                        "class" => "tc-delete",
                        "type" => "submit",
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
        $this->display_navigation();
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
