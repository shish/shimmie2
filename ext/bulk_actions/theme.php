<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BR};
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
            INPUT(["type" => "button", "id" => "bulk_selector_activate", "class" => "bulk-select-activate", "value" => "Activate (M)anual Select", "accesskey" => "m"])
        );
        $controls = emptyHTML(
            INPUT(["type" => "button", "class" => "bulk-select-deactivate", "value" => "Deactivate (M)anual Select", "accesskey" => "m"]),
            "Click on posts to mark them.",
            BR(),
            INPUT(["type" => "button", "class" => "bulk-select-all", "value" => "All"]),
            INPUT(["type" => "button", "class" => "bulk-select-invert", "value" => "Invert"]),
            INPUT(["type" => "button", "class" => "bulk-select-none", "value" => "Clear"])
        );

        $action_html = [];
        foreach ($actions as $action) {
            $action_html[] = DIV(
                ["class" => "bulk_action"],
                SHM_FORM(
                    action: make_link("bulk_action"),
                    onsubmit: "return validate_selections(this, " . json_encode($action->confirmation_message, JSON_HEX_TAG | JSON_HEX_AMP) . ");",
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


}
