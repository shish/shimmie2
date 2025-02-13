<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\INPUT;
use function MicroHTML\LABEL;
use function MicroHTML\OPTION;
use function MicroHTML\SELECT;
use function MicroHTML\TABLE;
use function MicroHTML\TD;
use function MicroHTML\TEXTAREA;
use function MicroHTML\TH;
use function MicroHTML\TR;
use function MicroHTML\emptyHTML;
use function MicroHTML\rawHTML;

class SetupPanel
{
    /** @var SetupBlock[]  */
    public array $blocks = [];
    public Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function create_new_block(string $title, int $position = 50): SetupBlock
    {
        $block = new SetupBlock($title, $this->config);
        $block->position = $position;
        $this->blocks[] = $block;
        return $block;
    }

    public function add_config_group(ConfigGroup $group): SetupBlock
    {
        $title = trim($group->title ?? implode(" ", \Safe\preg_split('/(?=[A-Z])/', \Safe\preg_replace("/^Shimmie2.(.*)Config$/", "\$1", get_class($group)))));
        $sb = $this->create_new_block($title, $group->position ?? 50);
        $sb->add_config_group($group);
        return $sb;
    }
}

class SetupBlock extends Block
{
    public Config $config;

    public function __construct(string $title, Config $config)
    {
        parent::__construct($title, emptyHTML(), "main", 50);
        $this->config = $config;
    }

    public function get_html(): HTMLElement
    {
        return $this->body;
    }

    public function add_config_group(ConfigGroup $group): void
    {
        global $user;
        $table = TABLE(["class" => "form"]);
        $refl_config = new \ReflectionClass($group);
        foreach ($refl_config->getConstants() as $const => $key) {
            $refl_const = $refl_config->getReflectionConstant($const);
            if (!$refl_const) {
                continue;
            }
            $attributes = $refl_const->getAttributes();
            if (count($attributes) == 0) {
                continue;
            }
            /** @var ConfigMeta $meta */
            $meta = $attributes[0]->newInstance();

            if ($meta->advanced) {
                continue;
            }
            if ($meta->permission && !$user->can($meta->permission)) {
                continue;
            }

            $row = TR();
            $row->appendChild(TH(LABEL(["for" => $key], $meta->label)));
            switch ($meta->ui_type) {
                case "bool":
                    $val = $this->config->get_bool($key);
                    $input = INPUT(["type" => "checkbox", "id" => $key, "name" => "_config_$key", "checked" => $val]);
                    break;
                case "int":
                    $val = $this->config->get_int($key);
                    $input = INPUT(["type" => "number", "id" => $key, "name" => "_config_$key", "value" => $val, "size" => 4, "step" => 1]);
                    break;
                case "shorthand_int":
                    $val = to_shorthand_int($this->config->get_int($key, 0));
                    $input = INPUT(["type" => "text", "id" => $key, "name" => "_config_$key", "value" => $val, "size" => 6]);
                    break;
                case "text":
                    $val = $this->config->get_string($key);
                    if ($meta->options) {
                        $options = $meta->options;
                        if (is_callable($options)) {
                            $options = call_user_func($options);
                        }
                        $input = SELECT(["id" => $key, "name" => "_config_$key"]);
                        foreach ($options as $optname => $optval) {
                            $input->appendChild(OPTION(["value" => $optval, "selected" => $optval == $val ], $optname));
                        }
                    } else {
                        $input = INPUT(["type" => "text", "id" => $key, "name" => "_config_$key", "value" => $val]);
                    }
                    break;
                case "longtext":
                    $val = $this->config->get_string($key, "");
                    $rows = max(3, min(10, count(explode("\n", $val))));
                    $input = TEXTAREA(["rows" => $rows, "id" => $key, "name" => "_config_$key"], $val);
                    break;
                case "color":
                    $val = $this->config->get_string($key);
                    $input = INPUT(["type" => "color", "id" => $key, "name" => "_config_$key", "value" => $val]);
                    break;
                case "multichoice":
                    $val = $this->config->get_array($key, []);
                    $input = SELECT(["id" => $key, "name" => "_config_{$key}[]", "multiple" => true, "size" => 5]);
                    $options = $meta->options;
                    if (is_callable($options)) {
                        $options = call_user_func($options);
                    }
                    if (is_string($options)) {
                        throw new \Exception("options are invalid: $options");
                    }
                    foreach ($options as $optname => $optval) {
                        $input->appendChild(OPTION(["value" => $optval, "selected" => in_array($optval, $val)], $optname));
                    }
                    break;
                default:
                    throw new \Exception("Unknown ui_type: {$meta->ui_type}");
            }
            $row->appendChild(TD(
                $input,
                INPUT(["type" => "hidden", "name" => "_type_$key", "value" => strtolower($meta->type->name)])
            ));
            $table->appendChild($row);
            if ($meta->help) {
                $table->appendChild(TR(TD(["colspan" => 2, "style" => "text-align: center;"], "(" . $meta->help . ")")));
            }
        }
        $this->body->appendChild($table);
    }

    public function add_label(string $text): void
    {
        $this->body->appendChild(rawHTML($text));
    }

    public function add_text_option(string $name, ?string $label = null): void
    {
        $this->body->appendChild(emptyHTML(
            $label ? LABEL(["for" => $name], rawHTML($label)) : null,
            INPUT(["type" => "text", "id" => $name, "name" => "_config_$name", "value" => $this->config->get_string($name)]),
            INPUT(["type" => "hidden", "name" => "_type_$name", "value" => "string"]),
        ));
    }

    public function add_longtext_option(string $name, ?string $label = null): void
    {
        $val = $this->config->get_string($name, "");
        $rows = max(3, min(10, count(explode("\n", $val))));
        $this->body->appendChild(emptyHTML(
            $label ? LABEL(["for" => $name], rawHTML($label)) : null,
            TEXTAREA(["rows" => $rows, "id" => $name, "name" => "_config_$name"], $val),
            INPUT(["type" => "hidden", "name" => "_type_$name", "value" => "string"]),
        ));
    }

    public function add_bool_option(string $name, ?string $label = null): void
    {
        $this->body->appendChild(emptyHTML(
            $label ? LABEL(["for" => $name], rawHTML($label)) : null,
            INPUT(["type" => "checkbox", "id" => $name, "name" => "_config_$name", "checked" => $this->config->get_bool($name)]),
            INPUT(["type" => "hidden", "name" => "_type_$name", "value" => "bool"]),
        ));
    }

    public function add_int_option(string $name, ?string $label = null): void
    {
        $this->body->appendChild(emptyHTML(
            $label ? LABEL(["for" => $name], rawHTML($label)) : null,
            INPUT(["type" => "number", "id" => $name, "name" => "_config_$name", "value" => $this->config->get_int($name), "size" => 4, "step" => 1]),
            INPUT(["type" => "hidden", "name" => "_type_$name", "value" => "int"]),
        ));
    }

    public function add_shorthand_int_option(string $name, ?string $label = null): void
    {
        $this->body->appendChild(emptyHTML(
            $label ? LABEL(["for" => $name], rawHTML($label)) : null,
            INPUT(["type" => "text", "id" => $name, "name" => "_config_$name", "value" => to_shorthand_int($this->config->get_int($name, 0)), "size" => 6]),
            INPUT(["type" => "hidden", "name" => "_type_$name", "value" => "int"]),
        ));
    }

    /**
     * @param array<string,string> $options
     */
    public function add_choice_option(string $name, array $options, ?string $label = null): void
    {
        $this->body->appendChild(emptyHTML(
            $label ? LABEL(["for" => $name], rawHTML($label)) : null,
            SELECT(
                ["id" => $name, "name" => "_config_$name"],
                ...array_map(
                    fn ($optname, $optval) => OPTION(["value" => $optval, "selected" => $optval == $this->config->get_string($name)], $optname),
                    array_keys($options),
                    array_values($options),
                )
            ),
            INPUT(["type" => "hidden", "name" => "_type_$name", "value" => "string"]),
        ));
    }

    /**
     * @param array<string,string> $options
     */
    public function add_multichoice_option(string $name, array $options, ?string $label = null): void
    {
        $this->body->appendChild(emptyHTML(
            $label ? LABEL(["for" => $name], rawHTML($label)) : null,
            SELECT(
                ["id" => $name, "name" => "_config_{$name}[]", "multiple" => true, "size" => 5],
                ...array_map(
                    fn ($optname, $optval) => OPTION(["value" => $optval, "selected" => in_array($optval, $this->config->get_array($name, []))], $optname),
                    array_keys($options),
                    array_values($options),
                )
            ),
            INPUT(["type" => "hidden", "name" => "_type_$name", "value" => "array"]),
        ));
    }
}
