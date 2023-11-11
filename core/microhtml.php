<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\emptyHTML;
use function MicroHTML\A;
use function MicroHTML\FORM;
use function MicroHTML\INPUT;
use function MicroHTML\DIV;
use function MicroHTML\OPTION;
use function MicroHTML\PRE;
use function MicroHTML\P;
use function MicroHTML\SELECT;
use function MicroHTML\TABLE;
use function MicroHTML\THEAD;
use function MicroHTML\TFOOT;
use function MicroHTML\TR;
use function MicroHTML\TH;
use function MicroHTML\TD;

function SHM_FORM(string $target, string $method = "POST", bool $multipart = false, string $form_id = "", string $onsubmit = "", string $name = ""): HTMLElement
{
    global $user;

    $attrs = [
        "action" => make_link($target),
        "method" => $method
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
        INPUT(["type" => "hidden", "name" => "q", "value" => $target]),
        $method == "GET" ? "" : $user->get_auth_microhtml()
    );
}

function SHM_SIMPLE_FORM($target, ...$children): HTMLElement
{
    $form = SHM_FORM($target);
    $form->appendChild(emptyHTML(...$children));
    return $form;
}

function SHM_SUBMIT(string $text, array $args = []): HTMLElement
{
    $args["type"] = "submit";
    $args["value"] = $text;
    return INPUT($args);
}

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

function SHM_USER_FORM(User $duser, string $target, string $title, $body, $foot): HTMLElement
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
 * @param array $options An array of pairs of parameters for <option> tags. First one is value, second one is text. Example: ('optionA', 'Choose Option A').
 * @param array $selected_options The values of options that should be pre-selected.
 * @param bool $required Wether the <select> element is required.
 * @param bool $multiple Wether the <select> element is multiple-choice.
 * @param bool $empty_option Whether the first option should be an empty one.
 * @param array $attrs Additional attributes dict for <select>. Example: ["id"=>"some_id", "class"=>"some_class"].
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
    HTMLElement|string $title,
    bool $can_edit,
    HTMLElement|string $view,
    HTMLElement|string $edit = "",
): HTMLElement {
    return TR(
        TH(["width" => "50px"], $title),
        $can_edit ?
            emptyHTML(
                TD(["class" => "view"], $view),
                TD(["class" => "edit"], $edit),
            ) :
            TD($view)
    );
}
