<?php

declare(strict_types=1);

namespace Shimmie2;

#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT)]
readonly class ConfigMeta
{
    /** @var "bool"|"int"|"shorthand_int"|"text"|"longtext"|"multichoice"|"color" */
    public string $input;

    /**
     * @param "shorthand_int"|"longtext"|"color" $input Override the default UI renderer
     * @param array<string, string>|callable-string|null $options A list of key-value pairs, or the name of a function to call to generate pairs
     */
    public function __construct(
        public string $label,
        public ConfigType $type,
        ?string $input = null,
        public mixed $default = null,
        public array|string|null $options = null,
        public ?string $permission = null,
        public ?string $help = null,
        public bool $advanced = false,
    ) {
        $this->input = $input ?? match($type) {
            ConfigType::BOOL => "bool",
            ConfigType::INT => "int",
            ConfigType::STRING => "text",
            ConfigType::ARRAY => "multichoice",
        };
    }
}
