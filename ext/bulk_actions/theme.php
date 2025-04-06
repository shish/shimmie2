<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BR, LABEL};
use function MicroHTML\{DIV, INPUT};
use function MicroHTML\{emptyHTML,joinHTML};

use MicroHTML\HTMLElement;

class BulkActionsTheme extends Themelet
{
    /**
     * @param array<BulkAction> $actions
     */
    public function display_selector(array $actions, string $query): void
    {
        $header = emptyHTML(
            INPUT(["type" => "hidden", "name" => "bulk_selected_ids", "id" => "bulk_selected_ids"]),
            INPUT(["type" => "button", "id" => "bulk_selector_activate", "onclick" => 'activate_bulk_selector();' , "value" => "Activate (M)anual Select", "accesskey" => "m"])
        );
        $controls = emptyHTML(
            INPUT(["type" => "button", "onclick" => 'deactivate_bulk_selector();' , "value" => "Deactivate (M)anual Select", "accesskey" => "m"]),
            "Click on posts to mark them.",
            BR(),
            INPUT(["type" => "button", "onclick" => 'select_all();' , "value" => "All"]),
            INPUT(["type" => "button", "onclick" => 'select_invert();' , "value" => "Invert"]),
            INPUT(["type" => "button", "onclick" => 'select_none();' , "value" => "Clear"])
        );

        $action_html = [];
        foreach ($actions as $action) {
            $action_html[] = DIV(
                ["class" => "bulk_action"],
                SHM_FORM(
                    action: make_link("bulk_action"),
                    onsubmit: "return validate_selections(this, " . json_encode($action->confirmation_message) . ");",
                    children: [
                        INPUT(["type" => "hidden", "name" => "bulk_query", "value" => $query]),
                        INPUT(["type" => "hidden", "name" => "bulk_selected_ids"]),
                        INPUT(["type" => "hidden", "name" => "bulk_action", "value" => $action->action]),
                        $action->block,
                        INPUT(["type" => "submit", "name" => "submit_button", "accesskey" => $action->access_key, "value" => $action->button_text])
                    ]
                )
            );
        }
        $actions = joinHTML("", $action_html);

        if (empty($query)) {
            $html = emptyHTML(
                $header,
                DIV(["id" => "bulk_selector_controls", "style" => "display: none;"], $controls, $actions)
            );
        } else {
            $html = emptyHTML(
                $header,
                DIV(["id" => "bulk_selector_controls", "style" => "display: none;"], $controls),
                $actions
            );
        }
        Ctx::$page->add_block(new Block("Bulk Actions", $html, "left", 30));
    }

    public function render_ban_reason_input(): ?HTMLElement
    {
        if (ImageBanInfo::is_enabled()) {
            return INPUT([
                "type" => "text",
                "name" => "bulk_ban_reason",
                "placeholder" => "Ban reason (leave blank to not ban)"
            ]);
        } else {
            return null;
        }
    }

    public function render_tag_input(): HTMLElement
    {
        return emptyHTML(
            LABEL(INPUT(["type" => "checkbox", "style" => 'width:13px;', "name" => "bulk_tags_replace", "value" => true]), "Replace tags"),
            INPUT(["type" => "text", "name" => "bulk_tags", "class" => "autocomplete_tags", "required" => true, "placeholder" => "Enter tags here"]),
        );
    }

    public function render_source_input(): HTMLElement
    {
        return INPUT([
            "type" => "text",
            "name" => "bulk_source",
            "placeholder" => "Enter source here"
        ]);
    }
}
