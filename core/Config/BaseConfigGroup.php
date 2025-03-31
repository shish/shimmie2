<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class BaseConfigGroup extends Enablable
{
    protected ?string $title = null;
    public ?int $position = null;

    public function get_title(): string
    {
        return $this->title ?? implode(
            " ",
            array_filter(\Safe\preg_split(
                '/(?=[A-Z])/',
                \Safe\preg_replace("/^Shimmie2.(.*?)(User)?Config/", "\$1", get_class($this))
            ))
        );
    }

    /**
     * Get all config fields from this config group.
     * Can be overridden by extensions who want to have dynamic config fields,
     * eg. Transcode having a map of <input mime type> -> <output mime type>
     * where valid inputs and outputs depend on what encoders are installed.
     *
     * @return array<string, ConfigMeta>
     */
    public function get_config_fields(): array
    {
        $fields = [];
        $refl_config = new \ReflectionClass($this);
        foreach ($refl_config->getReflectionConstants() as $const) {
            $attributes = $const->getAttributes(ConfigMeta::class);
            if (count($attributes) !== 1) {
                continue;
            }
            $meta = $attributes[0]->newInstance();
            $fields[$const->getValue()] = $meta;
        }
        return $fields;
    }

    /**
     * Tweak the HTML generated by the config page, this allows extensions
     * to add extra bits before or after the form, or to totally ignore the
     * generated HTML and come up with their own renderer.
     */
    public function tweak_html(\MicroHTML\HTMLElement $html): \MicroHTML\HTMLElement
    {
        return $html;
    }

    /**
     * @return array<string, ConfigMeta>
     */
    public static function get_all_metas(): array
    {
        $metas = [];
        foreach (self::get_subclasses(true) as $class) {
            foreach ($class->getReflectionConstants() as $const) {
                $attributes = $const->getAttributes(ConfigMeta::class);
                if (count($attributes) !== 1) {
                    continue;
                }
                $meta = $attributes[0]->newInstance();
                $metas[$const->getValue()] = $meta;
            }
        }
        return $metas;
    }
}
