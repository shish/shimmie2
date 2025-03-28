<?php

declare(strict_types=1);

namespace Shimmie2;

#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT)]
final readonly class ConfigMeta
{
    public ConfigInput $input;

    /**
     * @param ConfigInput $input Override the default UI renderer
     * @param array<string, string>|callable-string|null $options A list of key-value pairs, or the name of a function to call to generate pairs
     * @param (
     *     $type is ConfigType::BOOL ? bool|null :
     *     $type is ConfigType::INT ? int|null :
     *     $type is ConfigType::STRING ? string|null :
     *     $type is ConfigType::ARRAY ? array<string>|null :
     *     null) $default
     */
    public function __construct(
        public string $label,
        public ConfigType $type,
        ?ConfigInput $input = null,
        public mixed $default = null,
        public array|string|null $options = null,
        public ?string $permission = null,
        public ?string $help = null,
        public bool $advanced = false,
    ) {
        $this->input = $input ?? match($type) {
            ConfigType::BOOL => ConfigInput::CHECKBOX,
            ConfigType::INT => ConfigInput::NUMBER,
            ConfigType::STRING => ConfigInput::TEXT,
            ConfigType::ARRAY => ConfigInput::MULTICHOICE,
        };
    }
}
