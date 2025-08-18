<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{DIV, SCRIPT, emptyHTML};

use MicroHTML\HTMLElement;
use ReCaptcha\ReCaptcha as ReCaptchaLib;

final class ReCaptcha extends CaptchaExtension
{
    public const KEY = "recaptcha";

    public function captcha_html(): HTMLElement|null
    {
        $captcha = null;
        $r_publickey = Ctx::$config->get(ReCaptchaConfig::RECAPTCHA_PUBKEY);
        if (!empty($r_publickey)) {
            $captcha = emptyHTML(
                DIV(["class" => "g-recaptcha", "data-sitekey" => $r_publickey]),
                SCRIPT([
                    "type" => "text/javascript",
                    "src" => "https://www.google.com/recaptcha/api.js"
                ])
            );
        }
        return $captcha;
    }

    public function check_captcha(): bool
    {
        $r_privatekey = Ctx::$config->get(ReCaptchaConfig::RECAPTCHA_PRIVKEY);
        if (!empty($r_privatekey)) {
            $recaptcha = new ReCaptchaLib($r_privatekey);
            $resp = $recaptcha->verify(
                $_POST['g-recaptcha-response'] ?? "",
                (string)Network::get_real_ip()
            );

            if (!$resp->isSuccess()) {
                Log::info("recaptcha", "Captcha failed: " . implode("", $resp->getErrorCodes()));
                return false;
            }
        }

        return true;
    }
}
