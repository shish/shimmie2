<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{DIV, TABLE, TD, TH, TR};

use MicroHTML\HTMLElement;

class UserApiKeysTheme extends Themelet
{
    public function get_user_operations(string $key): HTMLElement
    {
        if ($key) {
            $key_row = TR(
                TH("API Key"),
                TD($key)
            );
        } else {
            $key_row = TR(
                TH("API Key"),
                TD("Not yet created")
            );
        }

        $submit_row = TR(
            TD(["colspan" => 2], SHM_SUBMIT("Generate/Reset Key"))
        );

        $form = SHM_SIMPLE_FORM(
            make_link("user_admin/reset_api_key"),
            TABLE(
                ["class" => "form"],
                $key_row,
                $submit_row
            ),
        );

        if ($key) {
            // Add delete form
            $delete_form = SHM_SIMPLE_FORM(
                make_link("user_admin/delete_api_key"),
                TABLE(
                    ["class" => "form"],
                    TR(
                        TD(["colspan" => 2], SHM_SUBMIT("Delete Key"))
                    )
                ),
            );
            return DIV($form, $delete_form);
        }

        return $form;
    }
}
