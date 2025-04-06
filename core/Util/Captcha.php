<?php

declare(strict_types=1);

namespace Shimmie2;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* CAPTCHA abstraction                                                       *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

use function MicroHTML\{DIV, SCRIPT, emptyHTML};

use MicroHTML\HTMLElement;
use ReCaptcha\ReCaptcha;

final readonly class Captcha
{
    public static function get_html(): ?HTMLElement
    {
        if (SysConfig::getDebug() && Network::ip_in_range(Network::get_real_ip(), "127.0.0.0/8")) {
            return null;
        }

        $captcha = null;
        if (Ctx::$user->is_anonymous() && Ctx::$config->req(CommentConfig::CAPTCHA)) {
            $r_publickey = Ctx::$config->get(CommentConfig::RECAPTCHA_PUBKEY);
            if (!empty($r_publickey)) {
                $captcha = emptyHTML(
                    DIV(["class" => "g-recaptcha", "data-sitekey" => $r_publickey]),
                    SCRIPT([
                        "type" => "text/javascript",
                        "src" => "https://www.google.com/recaptcha/api.js"
                    ])
                );
            }
        }
        return $captcha;
    }

    public static function check(): bool
    {
        if (SysConfig::getDebug() && Network::ip_in_range(Network::get_real_ip(), "127.0.0.0/8")) {
            return true;
        }

        if (Ctx::$user->is_anonymous() && Ctx::$config->req(CommentConfig::CAPTCHA)) {
            $r_privatekey = Ctx::$config->get(CommentConfig::RECAPTCHA_PRIVKEY);
            if (!empty($r_privatekey)) {
                $recaptcha = new ReCaptcha($r_privatekey);
                $resp = $recaptcha->verify($_POST['g-recaptcha-response'] ?? "", Network::get_real_ip());

                if (!$resp->isSuccess()) {
                    Log::info("core", "Captcha failed (ReCaptcha): " . implode("", $resp->getErrorCodes()));
                    return false;
                }
            } /*else {
                session_start();
                $securimg = new \Securimage();
                if ($securimg->check($_POST['captcha_code']) === false) {
                    Log::info("core", "Captcha failed (Securimage)");
                    return false;
                }
            }*/
        }

        return true;
    }
}
