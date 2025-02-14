<?php

declare(strict_types=1);

namespace Shimmie2;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* CAPTCHA abstraction                                                       *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

use ReCaptcha\ReCaptcha;

function captcha_get_html(): string
{
    global $config, $user;

    if (DEBUG && ip_in_range(get_real_ip(), "127.0.0.0/8")) {
        return "";
    }

    $captcha = "";
    if ($user->is_anonymous() && $config->get_bool(CommentConfig::CAPTCHA)) {
        $r_publickey = $config->get_string(CommentConfig::RECAPTCHA_PUBKEY);
        if (!empty($r_publickey)) {
            $captcha = "
				<div class=\"g-recaptcha\" data-sitekey=\"{$r_publickey}\"></div>
				<script type=\"text/javascript\" src=\"https://www.google.com/recaptcha/api.js\"></script>";
        } /*else {
            session_start();
            $captcha = \Securimage::getCaptchaHtml(['securimage_path' => './vendor/dapphp/securimage/']);
        }*/
    }
    return $captcha;
}

function captcha_check(): bool
{
    global $config, $user;

    if (DEBUG && ip_in_range(get_real_ip(), "127.0.0.0/8")) {
        return true;
    }

    if ($user->is_anonymous() && $config->get_bool(CommentConfig::CAPTCHA)) {
        $r_privatekey = $config->get_string(CommentConfig::RECAPTCHA_PRIVKEY);
        if (!empty($r_privatekey)) {
            $recaptcha = new ReCaptcha($r_privatekey);
            $resp = $recaptcha->verify($_POST['g-recaptcha-response'] ?? "", get_real_ip());

            if (!$resp->isSuccess()) {
                log_info("core", "Captcha failed (ReCaptcha): " . implode("", $resp->getErrorCodes()));
                return false;
            }
        } /*else {
            session_start();
            $securimg = new \Securimage();
            if ($securimg->check($_POST['captcha_code']) === false) {
                log_info("core", "Captcha failed (Securimage)");
                return false;
            }
        }*/
    }

    return true;
}
