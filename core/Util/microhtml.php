<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, CODE, DIV, FORM, INPUT, OPTION, P, SELECT, SPAN, TIME};
use function MicroHTML\{emptyHTML};

use MicroHTML\HTMLElement;

use function MicroHTML\{TABLE, TD, TFOOT, TH, THEAD, TR};

/**
 * Turn a date into a time, a date, an "X minutes ago...", etc
 */
function SHM_DATE(string $date, bool $html = true): HTMLElement
{
    $cpu = date('c', \Safe\strtotime($date));
    $hum = date('F j, Y; H:i', \Safe\strtotime($date));
    return ($html ? TIME(["datetime" => $cpu], $hum) : emptyHTML($hum));
}

function SHM_IP(string $ip, string $ban_reason): ?HTMLElement
{
    if (!Ctx::$user->can(IPBanPermission::VIEW_IP)) {
        return null;
    }
    $html = emptyHTML($ip);
    if (Ctx::$user->can(IPBanPermission::BAN_IP)) {
        $html->appendChild(", ");
        $html->appendChild(
            A([
                "href" => make_link("ip_ban/list", ["c_ip" => $ip, "c_reason" => $ban_reason, "c_expires" => "+1 week"], "create"),
            ], "Ban")
        );
    }
    return $html;
}


/**
 * @param "GET"|"POST" $method
 * @param array<string|HTMLElement|null> $children
 */
function SHM_FORM(Url $action, bool $multipart = false, string $id = "", string $onsubmit = "", string $name = "", string $method = "POST", array $children = []): HTMLElement
{
    $attrs = [
        "action" => $action,
        "method" => $method,
    ];

    if ($id) {
        $attrs["id"] = $id;
    }
    if ($multipart) {
        $attrs["enctype"] = 'multipart/form-data';
    }
    if ($onsubmit) {
        $attrs["onsubmit"] = $onsubmit;
    }
    if ($name) {
        $attrs["name"] = $name;
    }

    return FORM(
        $attrs,
        $method === "GET" ? INPUT(["type" => "hidden", "name" => "q", "value" => $action->getPath()]) : null,
        $method !== "GET" ? INPUT(["type" => "hidden", "name" => "auth_token", "value" => Ctx::$user->get_auth_token()]) : null,
        ...$children,
    );
}

/**
 * For when you just want a <form> with default settings and some children
 *
 * @param string|HTMLElement|null $children
 */
function SHM_SIMPLE_FORM(Url $action, ...$children): HTMLElement
{
    $form = SHM_FORM($action);
    $form->appendChild(emptyHTML(...$children));
    return $form;
}

/**
 * @param array<string, mixed> $args
 */
function SHM_SUBMIT(string $text, array $args = []): HTMLElement
{
    $args["type"] = "submit";
    $args["value"] = $text;
    return INPUT($args);
}

function SHM_COMMAND_EXAMPLE(string $ex, string $desc): HTMLElement
{
    return DIV(
        ["class" => "command_example"],
        CODE($ex),
        P($desc)
    );
}

function SHM_USER_FORM(User $duser, Url $target, string $title, HTMLElement $body, HTMLElement|string $foot): HTMLElement
{
    if (is_string($foot)) {
        $foot = TFOOT(TR(TD(["colspan" => "2"], INPUT(["type" => "submit", "value" => $foot]))));
    }
    return SHM_SIMPLE_FORM(
        $target,
        P(
            INPUT(["type" => 'hidden', "name" => 'id', "value" => $duser->id]),
            TABLE(
                ["class" => "form"],
                THEAD(TR(TH(["colspan" => "2"], $title))),
                $body,
                $foot
            )
        )
    );
}

/**
 * Generates a <select> element and sets up the given options.
 *
 * @param string $name The name attribute of <select>.
 * @param array<string|int, string> $options An array of pairs of parameters for <option> tags. First one is value, second one is text. Example: ('optionA', 'Choose Option A').
 * @param array<string> $selected_options The values of options that should be pre-selected.
 * @param bool $required Wether the <select> element is required.
 * @param bool $multiple Wether the <select> element is multiple-choice.
 * @param bool $empty_option Whether the first option should be an empty one.
 * @param array<string, mixed> $attrs Additional attributes dict for <select>. Example: ["id"=>"some_id", "class"=>"some_class"].
 */
function SHM_SELECT(string $name, array $options, array $selected_options = [], bool $required = false, bool $multiple = false, bool $empty_option = false, array $attrs = []): HTMLElement
{
    if ($required) {
        $attrs["required"] = "";
    }
    if ($multiple) {
        if (!str_ends_with($name, "[]")) {
            $name = $name . "[]";
        }
        $attrs["multiple"] = "";
    }

    $attrs["name"] = $name;

    $_options = [];
    if ($empty_option) {
        $_options[] = OPTION();
    }

    foreach ($options as $value => $text) {
        $_options[] = SHM_OPTION((string)$value, $text, in_array($value, $selected_options));
    }

    return SELECT($attrs, ...$_options);
}

function SHM_OPTION(string $value, string $text, bool $selected = false): HTMLElement
{
    if ($selected) {
        return OPTION(["value" => $value, "selected" => ""], $text);
    }

    return OPTION(["value" => $value], $text);
}

function SHM_POST_INFO(
    string $title,
    HTMLElement|string|null $view = null,
    HTMLElement|string|null $edit = null,
    Url|null $link = null,
): HTMLElement {
    if (!is_null($view) && !is_null($edit)) {
        $show = emptyHTML(
            SPAN(["class" => "view"], $view),
            SPAN(["class" => "edit"], $edit),
        );
    } elseif (!is_null($edit)) {
        $show = $edit;
    } elseif (!is_null($view)) {
        $show = $view;
    } else {
        $show = "???";
    }
    return TR(
        ["data-row" => $title],
        TH(["width" => "50px"], $link ? A(["href" => (string)$link], $title) : $title),
        TD($show)
    );
}
