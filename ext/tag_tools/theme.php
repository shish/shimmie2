<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{INPUT, emptyHTML};

class TagToolsTheme extends Themelet
{
    protected function button(string $name, string $action, bool $protected = false): HTMLElement
    {
        return SHM_FORM(
            action: make_link("admin/$action"),
            children: $protected ? [
                INPUT(["type" => 'submit', "id" => $action, "value" => $name, "disabled" => "disabled"]),
                INPUT(["type" => 'checkbox', "onclick" => "jQuery('#$action').attr('disabled', !jQuery(this).is(':checked'))"])
            ] : [
                INPUT(["type" => 'submit', "id" => $action, "value" => $name])
            ]
        );
    }

    /*
     * Show a form which links to admin_utils with POST[action] set to one of:
     *  'lowercase all tags'
     *  'recount tag use'
     *  etc
     */
    public function display_form(): void
    {
        Ctx::$page->add_block(new Block("Misc Admin Tools", emptyHTML(
            $this->button("All tags to lowercase", "lowercase_all_tags", true),
            $this->button("Recount tag use", "recount_tag_use", false)
        )));

        Ctx::$page->add_block(new Block("Set Tag Case", SHM_SIMPLE_FORM(
            make_link("admin/set_tag_case"),
            INPUT(["type" => 'text', "name" => 'tag', "placeholder" => 'Enter tag with correct case', "autocomplete" => 'off']),
            SHM_SUBMIT('Set Tag Case'),
        )));
    }
}
