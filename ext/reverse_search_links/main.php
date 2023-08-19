<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class ReverseSearchLinksConfig
{
    public const PRIORITY = "ext_reverse_search_links_priority";
    public const POSITION = "ext_reverse_search_links_position";
    public const ENABLED_SERVICES = "ext_reverse_search_links_enabled_services";
}

class ReverseSearchLinks extends Extension
{
    /** @var ReverseSearchLinksTheme */
    protected Themelet $theme;

    /**
     * Show the extension block when viewing an image
     */
    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        global $page;

        // only support image types
        $supported_types = [MimeType::JPEG, MimeType::GIF, MimeType::PNG, MimeType::WEBP];
        if(in_array($event->image->get_mime(), $supported_types)) {
            $this->theme->reverse_search_block($page, $event->image);
        }
    }


    /**
     * Supported reverse search services
     */
    protected array $SERVICES = [
        'SauceNAO',
        'TinEye',
        'trace.moe',
        'ascii2d',
        'Yandex'
    ];

    private function get_options(): array
    {
        global $config;

        $output = [];
        $services = $this->SERVICES;
        foreach ($services as $service) {
            $output[$service] = $service;
        }

        return $output;
    }

    /**
     * Generate the settings block
     */
    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Reverse Search Links");
        $sb->start_table();
        $sb->add_int_option("ext_reverse_search_links_priority", "Priority:");
        $sb->add_choice_option("ext_reverse_search_links_position", ["Main page" => "main", "In navigation bar" => "left"], "<br>Position: ");
        $sb->add_multichoice_option("ext_reverse_search_links_enabled_services", $this->get_options(), "Enabled Services", true);
        $sb->end_table();
    }

    /**
     * Set default config values
     */
    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_int(ReverseSearchLinksConfig::PRIORITY, 20);
        $config->set_default_string(ReverseSearchLinksConfig::POSITION, "main");
        $config->set_default_array(
            ReverseSearchLinksConfig::ENABLED_SERVICES,
            ['SauceNAO', 'TinEye', 'trace.moe', 'ascii2d', 'Yandex']
        );
    }
}
