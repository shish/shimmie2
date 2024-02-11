<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{emptyHTML};
use function MicroHTML\A;
use function MicroHTML\FORM;
use function MicroHTML\INPUT;
use function MicroHTML\DIV;
use function MicroHTML\OPTION;
use function MicroHTML\PRE;
use function MicroHTML\P;
use function MicroHTML\SELECT;
use function MicroHTML\SPAN;
use function MicroHTML\{TABLE,THEAD,TFOOT,TR,TH,TD};

function SHM_FORM(string $target, bool $multipart = false, string $form_id = "", string $onsubmit = "", string $name = ""): HTMLElement
{
    global $user;

    $attrs = [
        "action" => make_link($target),
        "method" => 'POST'
    ];

    if ($form_id) {
        $attrs["id"] = $form_id;
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
        INPUT(["type" => "hidden", "name" => "auth_token", "value" => $user->get_auth_token()])
    );
}

/**
 * @param array<string|HTMLElement|null> $children
 */
function SHM_SIMPLE_FORM(string $target, ...$children): HTMLElement
{
    $form = SHM_FORM($target);
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

/**
 * @param array<string, mixed> $args
 */
function SHM_A(string $href, string|HTMLElement $text, string $id = "", string $class = "", array $args = []): HTMLElement
{
    $args["href"] = make_link($href);

    if ($id) {
        $args["id"] = $id;
    }
    if ($class) {
        $args["class"] = $class;
    }

    return A($args, $text);
}

function SHM_COMMAND_EXAMPLE(string $ex, string $desc): HTMLElement
{
    return DIV(
        ["class" => "command_example"],
        PRE($ex),
        P($desc)
    );
}

function SHM_USER_FORM(User $duser, string $target, string $title, HTMLElement $body, HTMLElement|string $foot): HTMLElement
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
        $_options[] = SHM_OPTION((string)$value, (string)$text, in_array($value, $selected_options));
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
    string|null $link = null,
): HTMLElement {
    if(!is_null($view) && !is_null($edit)) {
        $show = emptyHTML(
            SPAN(["class" => "view"], $view),
            SPAN(["class" => "edit"], $edit),
        );
    } elseif(!is_null($edit)) {
        $show = $edit;
    } elseif(!is_null($view)) {
        $show = $view;
    } else {
        $show = "???";
    }
    return TR(
        ["data-row" => $title],
        TH(["width" => "50px"], $link ? A(["href" => $link], $title) : $title),
        TD($show)
    );
}
