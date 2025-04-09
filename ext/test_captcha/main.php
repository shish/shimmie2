<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{INPUT, LABEL, emptyHTML};

final class TestCaptcha extends CaptchaExtension
{
    public const KEY = "test_captcha";

    public function captcha_html(): HTMLElement
    {
        return emptyHTML(
            LABEL(
                INPUT(["type" => "checkbox", "name" => "test_captcha"]),
                "I'm a robot"
            )
        );
    }

    public function check_captcha(): bool
    {
        return ($_POST['test_captcha'] ?? "off") !== "on";
    }
}
