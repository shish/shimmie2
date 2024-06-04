<?php

declare(strict_types=1);

namespace Shimmie2;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* CAPTCHA abstraction                                                       *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

use ReCaptcha\ReCaptcha;

function captcha_get_html(bool $anon_only): string
{
    global $config, $user;

    if (DEBUG && ip_in_range(get_real_ip(), "127.0.0.0/8")) {
        return "";
    }

    $captcha = "";
    if (!$anon_only || $user->is_anonymous()) {
        $r_publickey = $config->get_string("api_recaptcha_pubkey");
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

function captcha_check(bool $anon_only): bool
{
    global $config, $user;

    if (DEBUG && ip_in_range(get_real_ip(), "127.0.0.0/8")) {
        return true;
    }

    if ($anon_only && !$user->is_anonymous()) {
        return true;
    }

    $r_privatekey = $config->get_string('api_recaptcha_privkey');
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

    return true;
}
