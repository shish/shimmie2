<?php

declare(strict_types=1);

namespace Shimmie2;

/*
 * Sent when the setup screen's 'set' button has been activated
 */
class ConfigSaveEvent extends Event
{
    public Config $config;
    /** @var array<string, null|string|int|boolean|array<string>> $values */
    public array $values;

    /**
     * @param array<string, null|string|int|boolean|array<string>> $values
     */
    public function __construct(Config $config, array $values)
    {
        parent::__construct();
        $this->config = $config;
        $this->values = $values;
    }

    /**
     * Convert POST data to settings data, eg
     *
     *     $_POST = [
     *         "_type_mynull" => "string",
     *         "_type_mystring" => "string",
     *         "_config_mystring" => "hello world!",
     *         "_type_myint" => "int",
     *         "_config_myint" => "42KB",
     *     ]
     *
     * becomes
     *
     *     $config = [
     *         "mynull" => null,
     *         "mystring" => "hello world!",
     *         "myint" => 43008,
     *     ]
     *
     * @param array<string, string|string[]> $post
     * @return array<string, null|string|int|boolean|array<string>>
     */
    public static function postToSettings(array $post): array
    {
        $settings = [];
        foreach ($post as $key => $type) {
            if (str_starts_with($key, "_type_")) {
                $key = str_replace("_type_", "", $key);
                $value = $post["_config_$key"] ?? null;
                if ($type === "string") {
                    $settings[$key] = $value;
                } elseif ($type === "int") {
                    assert(is_string($value));
                    $settings[$key] = $value ? parse_shorthand_int($value) : null;
                } elseif ($type === "bool") {
                    $settings[$key] = $value === "on";
                } elseif ($type === "array") {
                    $settings[$key] = $value;
                } else {
                    if (is_array($value)) {
                        $value = implode(", ", $value);
                    }
                    throw new InvalidInput("Invalid type '$value' for key '$key'");
                }
            }
        }
        return $settings;
    }
}

/*
 * Sent when the setup page is ready to be added to
 */
class SetupBuildingEvent extends Event
{
    protected SetupTheme $theme;
    public SetupPanel $panel;

    public function __construct(SetupPanel $panel)
    {
        parent::__construct();
        $this->panel = $panel;
    }
}
