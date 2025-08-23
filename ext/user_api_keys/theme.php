<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{TABLE, TD, TH, TR};

class UserApiKeysTheme extends Themelet
{
    public function get_user_operations(string $key): HTMLElement
    {
        return SHM_SIMPLE_FORM(
            make_link("user_admin/reset_api_key"),
            TABLE(
                ["class" => "form"],
                TR(
                    TH("API Key"),
                    TD($key)
                ),
                TR(
                    TD(["colspan" => 2], SHM_SUBMIT("Reset Key"))
                )
            ),
        );
    }
}
